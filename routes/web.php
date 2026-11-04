<?php

use App\Http\Controllers\AttendanceReportPrintController;
use App\Http\Controllers\JournalPhotoUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'));

Route::get('/admin/attendance-report/print', [AttendanceReportPrintController::class, 'print'])
    ->middleware(['auth'])
    ->name('attendance-report.print');

Route::post('/admin/journals/upload-realtime-photo', [JournalPhotoUploadController::class, 'upload'])
    ->middleware(['auth'])
    ->name('admin.journals.upload-realtime-photo');
