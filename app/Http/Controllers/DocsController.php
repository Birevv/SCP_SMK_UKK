<?php

namespace App\Http\Controllers;

use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DocsController extends Controller
{
    /**
     * Tampilkan Halaman Dokumentasi Resmi SCP SMK bergaya Laravel Docs.
     */
    public function index(Request $request, ?string $section = null): Response
    {
        $panel = Filament::getPanel('admin');
        $loginUrl = $panel->getLoginUrl();

        $defaultSection = $section ?? 'prologue';

        $sections = [
            'prologue' => [
                'title' => 'Prologue & Latar Belakang',
                'category' => 'Prologue',
                'description' => 'Gambaran umum School Class Progress (SCP), latar belakang UKK 2026/2027 Paket 3 RPL, arsitektur sistem, dan akun akses default.',
            ],
            'getting-started' => [
                'title' => 'Getting Started & Panduan Instalasi',
                'category' => 'Getting Started',
                'description' => 'Persyaratan sistem, langkah instalasi lokal cepat, migrasi & seeding data, konfigurasi environment, serta panduan deployment.',
            ],
            'business-logic' => [
                'title' => '5 Logika Bisnis Utama (Kunci Kelulusan UKK)',
                'category' => 'Core Business Logic',
                'description' => 'Spesifikasi algoritma anti-tabrakan jadwal, saklar putaran blok 2-mingguan, proteksi lock schedule, sinkronisasi presensi, dan dashboard alert.',
            ],
            'operational-sop' => [
                'title' => 'Dokumentasi Operasional (SOP per Role)',
                'category' => 'Operational Guide',
                'description' => 'Standar Operasional Prosedur (SOP) harian bagi Waka Kurikulum, Guru Mata Pelajaran, Wali Kelas & BK, Siswa/Orang Tua, serta prosedur pemeliharaan & troubleshooting.',
            ],
            'system-modules' => [
                'title' => 'Modul & Fitur Teknis Sistem',
                'category' => 'System Features',
                'description' => 'Dokumentasi teknis kamera realtime webcam, kalender akademik, cetak & ekspor rekap presensi (PDF & Excel), kenaikan kelas, dan import FET.',
            ],
            'rest-api' => [
                'title' => 'REST API & Integrasi Mobile Android',
                'category' => 'REST API',
                'description' => 'Katalog endpoint REST API autentikasi Sanctum, data referensi, endpoint siswa, guru, dan tim kesiswaan untuk aplikasi mobile.',
            ],
            'project-evaluation' => [
                'title' => 'Evaluasi Proyek & Rubrik UKK Paket 3',
                'category' => 'Project Evaluation',
                'description' => 'Analisis komprehensif ketercapaian rubrik penilaian UKK (100% skor sempurna), evaluasi arsitektur, hasil unit testing, kelebihan & limitasi, dan rencana roadmap.',
            ],
        ];

        return response()->view('docs.index', [
            'activeSection' => $defaultSection,
            'sections' => $sections,
            'loginUrl' => $loginUrl,
            'appVersion' => '1.0.0 (UKK 2026 Edition)',
        ]);
    }
}

