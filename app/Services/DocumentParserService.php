<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use SimpleXMLElement;
use ZipArchive;

class DocumentParserService
{
    /**
     * Parse uploaded file (CSV, XLSX, or PDF) into an array of associative rows.
     *
     * @return array<int, array<string, string>>
     */
    public function parse(string $filePath, ?string $originalExtension = null): array
    {
        $extension = strtolower($originalExtension ?: pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv', 'txt' => $this->parseCsv($filePath),
            'xlsx', 'xlsm' => $this->parseXlsx($filePath),
            'pdf' => $this->parsePdf($filePath),
            default => $this->parseFallback($filePath),
        };
    }

    /**
     * Parse CSV file with auto-detected delimiter (comma, semicolon, tab).
     */
    public function parseCsv(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return [];
        }

        $content = file_get_contents($filePath);
        if ($content === false || trim($content) === '') {
            return [];
        }

        // Clean UTF-8 BOM if present
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^{$bom}/", '', $content);

        // Detect delimiter: comma, semicolon, tab, pipe
        $firstLine = strtok($content, "\r\n") ?: '';
        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $maxCount = 0;

        foreach ($delimiters as $delim) {
            $count = substr_count($firstLine, $delim);
            if ($count > $maxCount) {
                $maxCount = $count;
                $bestDelimiter = $delim;
            }
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if (empty($lines)) {
            return [];
        }

        $headers = null;
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $fields = str_getcsv($line, $bestDelimiter);
            if ($headers === null) {
                $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), $fields);

                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $row[$header] = trim((string) ($fields[$index] ?? ''));
                }
            }

            if (! empty(array_filter($row))) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Native XLSX parser using ZipArchive and SimpleXMLElement.
     */
    public function parseXlsx(string $filePath): array
    {
        if (! file_exists($filePath) || ! class_exists(ZipArchive::class)) {
            return [];
        }

        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            return [];
        }

        // 1. Extract shared strings if available
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            try {
                $xml = new SimpleXMLElement($sharedStringsXml);
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $run) {
                            $text .= (string) $run->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            } catch (Exception) {
                // Ignore XML parse errors in shared strings
            }
        }

        // 2. Extract worksheet data (sheet1.xml)
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $rowsData = [];
        try {
            $xml = new SimpleXMLElement($sheetXml);
            if (! isset($xml->sheetData->row)) {
                return [];
            }

            foreach ($xml->sheetData->row as $row) {
                $rowCells = [];
                foreach ($row->c as $cell) {
                    $cellRef = (string) $cell['r'];
                    $colLetters = preg_replace('/\d+/', '', $cellRef) ?: 'A';
                    $colIndex = $this->colLetterToIndex($colLetters);

                    $type = (string) ($cell['t'] ?? '');
                    $val = (string) ($cell->v ?? '');

                    if ($type === 's') {
                        $index = (int) $val;
                        $value = $sharedStrings[$index] ?? '';
                    } elseif ($type === 'inlineStr' && isset($cell->is->t)) {
                        $value = (string) $cell->is->t;
                    } else {
                        $value = $val;
                    }

                    $rowCells[$colIndex] = trim($value);
                }

                if (! empty($rowCells)) {
                    ksort($rowCells);
                    $maxIndex = max(array_keys($rowCells));
                    $filledRow = [];
                    for ($i = 0; $i <= $maxIndex; $i++) {
                        $filledRow[] = $rowCells[$i] ?? '';
                    }
                    $rowsData[] = $filledRow;
                }
            }
        } catch (Exception) {
            return [];
        }

        if (empty($rowsData)) {
            return [];
        }

        $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), array_shift($rowsData));
        $rows = [];

        foreach ($rowsData as $data) {
            $row = [];
            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $row[$header] = trim((string) ($data[$index] ?? ''));
                }
            }
            if (! empty(array_filter($row))) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Parse text-based PDF by extracting streams and lines.
     */
    public function parsePdf(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return [];
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        $extractedText = '';

        // Extract streams
        if (preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $matches)) {
            foreach ($matches[1] as $stream) {
                // Try decompressing FlateDecode
                $uncompressed = @gzuncompress($stream);
                $streamText = $uncompressed !== false ? $uncompressed : $stream;

                // Extract text inside parentheses: (Text) Tj or [(Text1) ... (Text2)] TJ
                if (preg_match_all('/\((.*?)\)\s*T[jJ]/s', $streamText, $textMatches)) {
                    $extractedText .= implode(' ', $textMatches[1])."\n";
                } elseif (preg_match_all('/\[(.*?)\]\s*TJ/s', $streamText, $arrayMatches)) {
                    foreach ($arrayMatches[1] as $arr) {
                        if (preg_match_all('/\((.*?)\)/s', $arr, $innerMatches)) {
                            $extractedText .= implode('', $innerMatches[1]).' ';
                        }
                    }
                    $extractedText .= "\n";
                }
            }
        }

        // Clean extracted text unescaped PDF chars
        $extractedText = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $extractedText);

        $lines = preg_split('/\r\n|\r|\n/', trim($extractedText));
        if (empty($lines) || count($lines) < 2) {
            return [];
        }

        // Try parsing lines as tabular data separated by tab, comma, semicolon, or 2+ spaces
        $rows = [];
        $headers = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // Split by tab, pipe, semicolon, comma or 2+ consecutive spaces
            $columns = preg_split('/\t+|\|+|;+|,+|\s{2,}/', $line);
            if (! $columns || count($columns) < 2) {
                continue;
            }

            if ($headers === null) {
                $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), $columns);

                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $row[$header] = trim((string) ($columns[$index] ?? ''));
                }
            }
            if (! empty(array_filter($row))) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Fallback parser for plain text / CSV.
     */
    protected function parseFallback(string $filePath): array
    {
        return $this->parseCsv($filePath);
    }

    /**
     * Normalize header to lowercase snake_case.
     */
    public function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/i', '_', $header);

        return trim((string) $header, '_');
    }

    /**
     * Convert Excel column letters (A, B, C, ..., AA, AB) to 0-based index.
     */
    protected function colLetterToIndex(string $col): int
    {
        $col = strtoupper($col);
        $index = 0;
        $len = strlen($col);

        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
        }

        return $index - 1;
    }

    /**
     * Generate sample CSV for Schedule Import.
     */
    public static function getScheduleCsvTemplate(): string
    {
        return "guru,nip,mata_pelajaran,kelas,ruangan,hari,jam_mulai,jam_selesai,putaran\n"
            ."Budi Santoso,198002022005011002,Pemrograman Berorientasi Objek,XII RPL 1,Lab Komputer 1,Senin,07:30,09:30,Putaran 1\n"
            ."Siti Aminah,198503032010012003,Basis Data,XI RPL 2,Lab Komputer 2,Selasa,08:00,10:00,Putaran 1\n"
            ."Hendra Gunawan,197804042003121004,Bimbingan Konseling Karir,X RPL 1,Ruang Teori 1,Rabu,09:00,10:30,Putaran 1\n";
    }

    /**
     * Generate sample CSV for User Import.
     */
    public static function getUserCsvTemplate(): string
    {
        return "nama_lengkap,username,role,nisn_nip,email,telepon,kelas,status,password\n"
            ."Ahmad Siswa Baru,ahmadsiswa,Siswa,0061234568,ahmad@scp.sch.id,081234567890,X RPL 1,Aktif,password\n"
            ."Ratna Guru Baru,ratnaguru,Guru,198905052015012005,ratna@scp.sch.id,081298765432,,Aktif,password\n"
            ."Dewi Walikelas,dewiwali,Wali Kelas,198706062012012006,dewi@scp.sch.id,081345678901,XI RPL 2,Aktif,password\n";
    }
}
