<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
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
    Route::get('/attendance', [AttendanceController::class, 'showUserAttendance']);

    // 出勤ボタン押印
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn']);
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut']);
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart']);
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd']);

    // 勤怠一覧画面
    Route::get('/attendance/list', [AttendanceController::class, 'showAttendanceList']);

    // 勤怠詳細画面
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'showAttendanceDetail']);

    //ログアウトする処理
    Route::post('/logout', function () {
        Auth::logout();
        return redirect('/login');
    })->name('logout');

});