<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;

// ユーザー登録画面表示
Route::get('/register', [UserController::class, 'showRegisterForm']);
// ユーザー登録処理
Route::post('/register', [UserController::class, 'register']);
// ユーザーログイン画面表示
Route::get('/login', [UserController::class, 'showLoginForm']);
// ユーザーログイン処理
Route::post('/login', [UserController::class, 'login']);

Route::middleware('auth')->group(function () {
    // 勤怠登録画面（ログイン必須）
    Route::get('/attendance', [UserController::class, 'showUserAttendance']);

    // 出勤ボタン押印
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn']);

    Route::get('/attendance/list', [AttendanceController::class, 'attendanceList']);
});