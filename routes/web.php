<?php

use App\Http\Controllers\AttendanceReportPrintController;
use App\Http\Controllers\JournalPhotoUploadController;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;

// Sesuaikan routing dengan konfigurasi AdminPanelProvider secara dinamis
$adminPanel = Filament::getPanel('admin');
$adminPath = trim($adminPanel->getPath(), '/');

if (filled($adminPath)) {
    // 1. Jika panel admin memiliki prefix (misal: 'admin'), arahkan root ('/') ke panel admin
    Route::get('/', fn () => redirect()->to($adminPanel->getUrl()));

    // Route utama mengikuti path AdminPanelProvider
    $printUri = "{$adminPath}/attendance-report/print";
    $uploadUri = "{$adminPath}/journals/upload-realtime-photo";

    // Fallback kompatibilitas tanpa prefix
    Route::get('/attendance-report/print', fn () => redirect()->to(route('attendance-report.print')))
        ->middleware(['auth']);

    Route::post('/journals/upload-realtime-photo', [JournalPhotoUploadController::class, 'upload'])
        ->middleware(['auth'])
        ->name('journals.upload-realtime-photo');
} else {
    // 2. Jika panel admin berada di root (''), arahkan request lama '/admin' ke root
    Route::get('/admin', fn () => redirect()->to($adminPanel->getUrl()));
    Route::get('/admin/{any}', function (string $any) {
        return redirect("/{$any}");
    })->where('any', '.*');

    // Route utama berada di root
    $printUri = 'attendance-report/print';
    $uploadUri = 'journals/upload-realtime-photo';

    // Fallback kompatibilitas dengan prefix '/admin'
    Route::get('/admin/attendance-report/print', [AttendanceReportPrintController::class, 'print'])
        ->middleware(['auth']);

    Route::post('/admin/journals/upload-realtime-photo', [JournalPhotoUploadController::class, 'upload'])
        ->middleware(['auth'])
        ->name('journals.upload-realtime-photo');
}

// Route Cetak Laporan Presensi
Route::get($printUri, [AttendanceReportPrintController::class, 'print'])
    ->middleware(['auth'])
    ->name('attendance-report.print');

// Route Upload Foto Realtime
Route::post($uploadUri, [JournalPhotoUploadController::class, 'upload'])
    ->middleware(['auth'])
    ->name('admin.journals.upload-realtime-photo');
