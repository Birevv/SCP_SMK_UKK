<?php

use App\Http\Controllers\AttendanceReportPrintController;
use App\Http\Controllers\JournalPhotoUploadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Filament Admin Panel is mounted at root path ('').
| Below are standalone application web routes.
|
*/

// Cetak Laporan Rekap Presensi
Route::get('/attendance-report/print', [AttendanceReportPrintController::class, 'print'])
    ->middleware(['auth'])
    ->name('attendance-report.print');

// Upload Foto Realtime Jurnal Mengajar
Route::post('/journals/upload-realtime-photo', [JournalPhotoUploadController::class, 'upload'])
    ->middleware(['auth'])
    ->name('admin.journals.upload-realtime-photo');
