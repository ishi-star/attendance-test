<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;


// ユーザー登録画面表示
Route::get('/register', [UserController::class, 'showRegisterForm']);
// ユーザー登録処理
Route::post('/register', [UserController::class, 'register']);

// 勤怠登録画面表示
Route::get('/attendance', [UserController::class, 'showUserAttendance']);

Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])
    ->name('attendance.clockIn')
    ->middleware('auth');