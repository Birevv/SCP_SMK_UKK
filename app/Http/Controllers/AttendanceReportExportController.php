<?php

namespace App\Http\Controllers;

use App\Filament\Pages\AttendanceReport;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportExportController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        if (! AttendanceReport::canAccess()) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengekspor laporan presensi.');
        }

        $classroomId = $request->query('classroom_id');
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->toDateString());

        /** @var User|null $user */
        $user = Auth::user();
        $isSuperAdminOrWaka = $user?->hasRole('Super Admin')
            || $user?->role?->name === 'Super Admin'
            || $user?->hasRole('Waka Kurikulum')
            || $user?->role?->name === 'Waka Kurikulum';

        if (! $isSuperAdminOrWaka) {
            $userClassroomIds = $user?->classrooms()->pluck('classrooms.id')->toArray() ?? [];
            if (empty($userClassroomIds) && $user?->classroom_id) {
                $userClassroomIds = [$user->classroom_id];
            }

            if (empty($userClassroomIds) && ($user?->hasRole('Guru') || $user?->role?->name === 'Guru')) {
                $userClassroomIds = Schedule::where('teacher_id', $user->id)
                    ->pluck('classroom_id')
                    ->unique()
                    ->toArray();
            }

            if (! empty($userClassroomIds)) {
                if (! $classroomId) {
                    $classroomId = $userClassroomIds[0];
                } elseif (! in_array((int) $classroomId, array_map('intval', $userClassroomIds), true)) {
                    abort(403, 'Akses ditolak. Anda tidak memiliki akses ke kelas ini.');
                }
            } else {
                if (! $classroomId) {
                    $classroomId = Classroom::first()?->id;
                }
            }
        } else {
            if (! $classroomId) {
                $classroomId = Classroom::first()?->id;
            }
        }

        $selectedClassroom = Classroom::find($classroomId);
        if (! $selectedClassroom) {
            abort(404, 'Data kelas tidak ditemukan.');
        }

        $students = User::where('status', 'Aktif')
            ->whereHas('role', fn ($q) => $q->where('name', 'Siswa'))
            ->whereHas('classrooms', function ($q) use ($classroomId) {
                $q->where('classrooms.id', $classroomId)
                    ->where('user_class.status', 'Aktif');
            })
            ->orderBy('full_name')
            ->get();

        // Eager load attendances within range
        $attendancesGrouped = Attendance::whereIn('student_id', $students->pluck('id'))
            ->whereHas('journal', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            })
            ->get()
            ->groupBy('student_id');

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Presensi');

        // Header Informasi
        $sheet->setCellValue('A1', 'SCHOOL CLASS PROGRESS - LAPORAN REKAPITULASI PRESENSI SISWA');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Kelas: '.strtoupper($selectedClassroom->name));
        $sheet->mergeCells('A2:C2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);

        $periodFormatted = Carbon::parse($startDate)->isoFormat('D MMMM Y').' s/d '.Carbon::parse($endDate)->isoFormat('D MMMM Y');
        $sheet->setCellValue('D2', 'Periode: '.$periodFormatted);
        $sheet->mergeCells('D2:I2');
        $sheet->getStyle('D2')->getFont()->setBold(true)->setSize(11);

        // Header Tabel Kolom
        $headers = [
            'A4' => 'NO',
            'B4' => 'NISN / IDENTITAS',
            'C4' => 'NAMA LENGKAP SISWA',
            'D4' => 'HADIR (H)',
            'E4' => 'SAKIT (S)',
            'F4' => 'IZIN (I)',
            'G4' => 'ALPA (A)',
            'H4' => 'TOTAL KBM',
            'I4' => 'PERSENTASE (%)',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A8A'], // Indigo / Navy
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']],
            ],
        ];
        $sheet->getStyle('A4:I4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(26);

        $row = 5;
        $no = 1;
        $totalHadir = 0;
        $totalSakit = 0;
        $totalIzin = 0;
        $totalAlpa = 0;
        $totalKbm = 0;

        foreach ($students as $student) {
            $attendances = $attendancesGrouped->get($student->id, collect());
            $h = $attendances->where('status', 'Hadir')->count();
            $s = $attendances->where('status', 'Sakit')->count();
            $i = $attendances->where('status', 'Izin')->count();
            $a = $attendances->where('status', 'Alpa')->count();
            $tot = $attendances->count();
            $pct = $tot > 0 ? round(($h / $tot) * 100, 1) : 100.0;

            $totalHadir += $h;
            $totalSakit += $s;
            $totalIzin += $i;
            $totalAlpa += $a;
            $totalKbm += $tot;

            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValueExplicit("B{$row}", $student->identity_number ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValue("C{$row}", $student->full_name);
            $sheet->setCellValue("D{$row}", $h);
            $sheet->setCellValue("E{$row}", $s);
            $sheet->setCellValue("F{$row}", $i);
            $sheet->setCellValue("G{$row}", $a);
            $sheet->setCellValue("H{$row}", $tot);
            $sheet->setCellValue("I{$row}", $pct.'%');

            // Alignment
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}:I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Row striping
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:I{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }

            $sheet->getStyle("A{$row}:I{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');

            $row++;
        }

        // Baris Total / Rata-rata
        $summaryRow = $row;
        $sheet->setCellValue("A{$summaryRow}", 'TOTAL AKUMULASI');
        $sheet->mergeCells("A{$summaryRow}:C{$summaryRow}");
        $sheet->setCellValue("D{$summaryRow}", $totalHadir);
        $sheet->setCellValue("E{$summaryRow}", $totalSakit);
        $sheet->setCellValue("F{$summaryRow}", $totalIzin);
        $sheet->setCellValue("G{$summaryRow}", $totalAlpa);
        $sheet->setCellValue("H{$summaryRow}", $totalKbm);

        $avgPct = $totalKbm > 0 ? round(($totalHadir / $totalKbm) * 100, 1) : 100.0;
        $sheet->setCellValue("I{$summaryRow}", $avgPct.'%');

        $summaryStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '94A3B8']],
            ],
        ];
        $sheet->getStyle("A{$summaryRow}:I{$summaryRow}")->applyFromArray($summaryStyle);

        // Auto width untuk tiap kolom
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Rekap_Presensi_'.str_replace(' ', '_', $selectedClassroom->name).'_'.Carbon::parse($startDate)->format('Ymd').'_'.Carbon::parse($endDate)->format('Ymd').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
