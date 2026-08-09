<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekapitulasi Kehadiran Siswa</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }
        body {
            background-color: #ffffff;
            color: #000000;
            margin: 0;
            padding: 30px;
            font-size: 13px;
        }
        .header {
            border-bottom: 2px solid #000000;
            padding-bottom: 12px;
            margin-bottom: 20px;
            text-align: center;
        }
        .header h1 {
            font-size: 20px;
            margin: 0 0 5px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header p {
            font-size: 14px;
            margin: 0;
            font-weight: bold;
        }
        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #000000;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background-color: #e5e7eb;
            text-align: center;
            font-weight: bold;
        }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            page-break-inside: avoid;
        }
        .signatures > div {
            text-align: center;
            width: 250px;
        }
        .signature-space {
            height: 60px;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 30px; margin-top: 10px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; background-color: #16a34a; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            🖨️ Cetak / Simpan PDF
        </button>
    </div>

    <div class="header">
        <h1>SCHOOL CLASS PROGRESS (SCP)</h1>
        <p>LAPORAN REKAPITULASI KEHADIRAN SISWA</p>
        <div class="meta-info">
            <span>KELAS: {{ strtoupper($classroom?->name ?? '-') }}</span>
            <span>PERIODE: {{ $start_date }} s/d {{ $end_date }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th>NIS / Username</th>
                <th>Nama Siswa</th>
                <th style="width: 60px;">Hadir</th>
                <th style="width: 60px;">Sakit</th>
                <th style="width: 60px;">Izin</th>
                <th style="width: 60px;">Alpa</th>
                <th style="width: 60px;">Total</th>
                <th style="width: 90px;">% Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($students as $index => $student)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $student['username'] }}</td>
                    <td>{{ $student['full_name'] }}</td>
                    <td class="text-center">{{ $student['hadir'] }}</td>
                    <td class="text-center">{{ $student['sakit'] }}</td>
                    <td class="text-center">{{ $student['izin'] }}</td>
                    <td class="text-center">{{ $student['alpa'] }}</td>
                    <td class="text-center font-bold">{{ $student['total'] }}</td>
                    <td class="text-center font-bold">{{ $student['percentage'] }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data siswa ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signatures">
        <div>
            <p>Mengetahui,</p>
            <p class="font-bold">Waka Kurikulum</p>
            <div class="signature-space"></div>
            <p class="font-bold">(..........................................)</p>
        </div>
        <div>
            <p>Dicetak Pada: {{ now()->isoFormat('D MMMM Y, HH:mm') }}</p>
            <p class="font-bold">Wali Kelas / Guru BK</p>
            <div class="signature-space"></div>
            <p class="font-bold">(..........................................)</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
