<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AcademicEventCategory: string implements HasLabel, HasColor, HasIcon
{
    case LIBUR_NASIONAL = 'libur_nasional';
    case LIBUR_SEKOLAH  = 'libur_sekolah';
    case UPACARA        = 'upacara';       // Upacara Bendera / Peringatan Hari Besar
    case UJIAN          = 'ujian';
    case PKL            = 'pkl';           // Praktik Kerja Lapangan (Khusus SMK)
    case UKK            = 'ukk';           // Uji Kompetensi Keahlian (Khusus SMK)
    case KEGIATAN_SISWA = 'kegiatan_siswa';
    case RAPAT          = 'rapat';
    case LAINNYA        = 'lainnya';

    public function getLabel(): string
    {
        return match ($this) {
            self::LIBUR_NASIONAL => 'Libur Nasional',
            self::LIBUR_SEKOLAH  => 'Libur Sekolah / Semester',
            self::UPACARA        => 'Upacara Bendera / Peringatan',
            self::UJIAN          => 'Ujian / Asesmen',
            self::PKL            => 'Praktik Kerja Lapangan (PKL)',
            self::UKK            => 'Uji Kompetensi Keahlian (UKK)',
            self::KEGIATAN_SISWA => 'Kegiatan Siswa / OSIS',
            self::RAPAT          => 'Rapat Guru / Dinas',
            self::LAINNYA        => 'Agenda Khusus Lainnya',
        };
    }

    public function getShortLabel(): string
    {
        return match ($this) {
            self::LIBUR_NASIONAL => 'Libur Nasional',
            self::LIBUR_SEKOLAH  => 'Libur Sekolah',
            self::UPACARA        => 'Upacara',
            self::UJIAN          => 'Ujian',
            self::PKL            => 'PKL',
            self::UKK            => 'UKK',
            self::KEGIATAN_SISWA => 'Kegiatan Siswa',
            self::RAPAT          => 'Rapat Dinas',
            self::LAINNYA        => 'Lainnya',
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::LIBUR_NASIONAL => 'danger',   // Merah
            self::LIBUR_SEKOLAH  => 'warning',  // Kuning / Oranye
            self::UPACARA        => 'rose',     // Rose / Merah Bendera
            self::UJIAN          => 'info',     // Biru
            self::PKL            => 'teal',     // Cyan / Teal khas vokasi industri
            self::UKK            => 'rose',     // Rose / Merah bata khas uji kompetensi
            self::KEGIATAN_SISWA => 'success',  // Hijau
            self::RAPAT          => 'primary',  // Ungu / Brand
            self::LAINNYA        => 'gray',     // Abu-abu
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::LIBUR_NASIONAL => 'heroicon-o-flag',
            self::LIBUR_SEKOLAH  => 'heroicon-o-sun',
            self::UPACARA        => 'heroicon-o-megaphone',
            self::UJIAN          => 'heroicon-o-academic-cap',
            self::PKL            => 'heroicon-o-briefcase',
            self::UKK            => 'heroicon-o-wrench-screwdriver',
            self::KEGIATAN_SISWA => 'heroicon-o-sparkles',
            self::RAPAT          => 'heroicon-o-user-group',
            self::LAINNYA        => 'heroicon-o-calendar',
        };
    }

    // Default status libur resmi (tanggal merah)
    public function defaultIsHoliday(): bool
    {
        return in_array($this, [self::LIBUR_NASIONAL, self::LIBUR_SEKOLAH, self::UPACARA]);
    }

    // Default penonaktifan jadwal KBM kelas
    public function defaultDisableKbm(): bool
    {
        return in_array($this, [
            self::LIBUR_NASIONAL,
            self::LIBUR_SEKOLAH,
            self::UPACARA,
            self::UJIAN,
            self::PKL,
            self::UKK,
        ]);
    }

    // Default kewajiban presensi / kehadiran siswa
    public function defaultRequiresAttendance(): bool
    {
        return match ($this) {
            self::LIBUR_NASIONAL, self::LIBUR_SEKOLAH => false, // Libur murni bebas absen
            self::UPACARA => true, // Upacara WAJIB hadir absen gerbang!
            default => true,
        };
    }

    // Rekomendasi otomatis target tingkat kelas saat Waka memilih kategori
    public function defaultTargetGrades(): ?array
    {
        return match ($this) {
            self::PKL => ['XII'],
            self::UKK => ['XII'],
            default   => null, // Berlaku seluruh sekolah
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::LIBUR_NASIONAL => 'bg-red-100 text-red-800 dark:bg-red-950/70 dark:text-red-300 border-l-4 border-red-500',
            self::LIBUR_SEKOLAH  => 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border-l-4 border-amber-500',
            self::UPACARA        => 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 border-l-4 border-rose-500',
            self::UJIAN          => 'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-300 border-l-4 border-sky-500',
            self::PKL            => 'bg-teal-100 text-teal-800 dark:bg-teal-950/70 dark:text-teal-300 border-l-4 border-teal-500',
            self::UKK            => 'bg-purple-100 text-purple-800 dark:bg-purple-950/70 dark:text-purple-300 border-l-4 border-purple-500',
            self::KEGIATAN_SISWA => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border-l-4 border-emerald-500',
            self::RAPAT          => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/70 dark:text-indigo-300 border-l-4 border-indigo-500',
            self::LAINNYA        => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 border-l-4 border-gray-400',
        };
    }

    public function getLegendDotClass(): string
    {
        return match ($this) {
            self::LIBUR_NASIONAL => 'bg-red-500',
            self::LIBUR_SEKOLAH  => 'bg-amber-500',
            self::UPACARA        => 'bg-rose-500',
            self::UJIAN          => 'bg-sky-500',
            self::PKL            => 'bg-teal-500',
            self::UKK            => 'bg-purple-500',
            self::KEGIATAN_SISWA => 'bg-emerald-500',
            self::RAPAT          => 'bg-indigo-500',
            self::LAINNYA        => 'bg-gray-400',
        };
    }

    public function getLegendBadgeClass(): string
    {
        return match ($this) {
            self::LIBUR_NASIONAL => 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300 border border-red-200 dark:border-red-900',
            self::LIBUR_SEKOLAH  => 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-200 dark:border-amber-900',
            self::UPACARA        => 'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 border border-rose-200 dark:border-rose-900',
            self::UJIAN          => 'bg-sky-50 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300 border border-sky-200 dark:border-sky-900',
            self::PKL            => 'bg-teal-50 text-teal-700 dark:bg-teal-950/50 dark:text-teal-300 border border-teal-200 dark:border-teal-900',
            self::UKK            => 'bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300 border border-purple-200 dark:border-purple-900',
            self::KEGIATAN_SISWA => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900',
            self::RAPAT          => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-900',
            self::LAINNYA        => 'bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700',
        };
    }
}

