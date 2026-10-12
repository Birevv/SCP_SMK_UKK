<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Homeroom\DashboardController as HomeroomDashboardController;
use App\Http\Controllers\Api\Homeroom\LeaveRequestController as HomeroomLeaveRequestController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Api\StudentApiController;
use App\Http\Controllers\Api\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Api\Teacher\JournalController as TeacherJournalController;
use App\Http\Controllers\Api\Teacher\ScheduleController as TeacherScheduleController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('/login', [AuthController::class, 'login']);

// Authenticated Routes (Memerlukan Token Sanctum)
Route::middleware('auth:sanctum')->group(function () {

    // Auth & Profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/profile/password', [AuthController::class, 'changePassword']);

    // Reference Data (Untuk Dropdown Form)
    Route::prefix('references')->group(function () {
        Route::get('/classrooms', [ReferenceController::class, 'classrooms']);
        Route::get('/rooms', [ReferenceController::class, 'rooms']);
        Route::get('/subjects', [ReferenceController::class, 'subjects']);
    });

    // Student API
    Route::prefix('student')->group(function () {
        Route::get('/dashboard', [StudentApiController::class, 'dashboard']);
        Route::get('/schedules/today', [StudentApiController::class, 'todaySchedule']);
        Route::get('/attendance-summary', [StudentApiController::class, 'attendanceSummary']);
        Route::get('/attendance-history', [StudentApiController::class, 'attendanceHistory']);
        Route::get('/leave-requests', [StudentApiController::class, 'leaveRequests']);
        Route::post('/leave-requests', [StudentApiController::class, 'storeLeaveRequest']);
    });

    // Teacher API
    Route::prefix('teacher')->group(function () {
        Route::get('/dashboard', [TeacherDashboardController::class, 'index']);
        Route::get('/schedules', [TeacherScheduleController::class, 'index']);
        Route::post('/schedules', [TeacherScheduleController::class, 'store']);
        Route::get('/schedules/{schedule}/students', [TeacherScheduleController::class, 'students']);
        Route::post('/journals', [TeacherJournalController::class, 'store']);
        Route::get('/journals', [TeacherJournalController::class, 'index']);
    });

    // Homeroom (Wali Kelas) API
    Route::prefix('homeroom')->group(function () {
        Route::get('/dashboard', [HomeroomDashboardController::class, 'index']);
        Route::get('/leave-requests', [HomeroomLeaveRequestController::class, 'index']);
        Route::get('/leave-requests/{leaveRequest}', [HomeroomLeaveRequestController::class, 'show']);
        Route::put('/leave-requests/{leaveRequest}/verify', [HomeroomLeaveRequestController::class, 'verify']);
    });

});
