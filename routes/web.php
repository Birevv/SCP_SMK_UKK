<?php

use App\Http\Controllers\AttendanceReportPrintController;
use App\Http\Controllers\DownloadPageController;
use App\Http\Controllers\JournalPhotoUploadController;
use App\Http\Controllers\LandingController;
use App\Http\Middleware\LandingSecurityHeaders;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Halaman landing publik berada di root ('/').
| Panel Filament dipasang pada path '/app'.
|
*/

// Halaman Landing Publik (Stateless - Tanpa Session, Cookie, & DB Query)
Route::get('/', LandingController::class)
    ->withoutMiddleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        AuthenticateSession::class,
        ShareErrorsFromSession::class,
        PreventRequestForgery::class,
        VerifyCsrfToken::class,
    ])
    ->middleware(LandingSecurityHeaders::class)
    ->name('landing');

// Halaman Unduh Aplikasi Mobile SCP (Stateless)
Route::get('/download', DownloadPageController::class)
    ->withoutMiddleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        AuthenticateSession::class,
        ShareErrorsFromSession::class,
        PreventRequestForgery::class,
        VerifyCsrfToken::class,
    ])
    ->middleware(LandingSecurityHeaders::class)
    ->name('download');

// Redirect Permanen URL Auth Lama ke Panel (/app)
Route::get('/login', [LandingController::class, 'redirectToLogin'])
    ->name('login');

Route::get('/password-reset/{any}', [LandingController::class, 'redirectToPasswordReset'])
    ->where('any', '.*')
    ->name('legacy.password-reset');

// Cetak Laporan Rekap Presensi
Route::get('/attendance-report/print', [AttendanceReportPrintController::class, 'print'])
    ->middleware(['auth'])
    ->name('attendance-report.print');

// Upload Foto Realtime Jurnal Mengajar
Route::post('/journals/upload-realtime-photo', [JournalPhotoUploadController::class, 'upload'])
    ->middleware(['auth'])
    ->name('admin.journals.upload-realtime-photo');
