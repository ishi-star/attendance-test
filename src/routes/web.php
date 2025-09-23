<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminController;


Route::prefix('admin')->name('admin.')->group(function () {
    // 管理者ログイン画面のルート
    Route::get('/login', [AdminController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminController::class, 'login']);
    // 管理者勤怠一覧画面のルート
    Route::get('/attendance/list/{date?}', [AdminController::class, 'showAttendances'])->name('attendances');
});

// ユーザー関連のルートを`/user`プレフィックスでグループ化
Route::prefix('user')->group(function () {
    // ユーザー登録画面
    Route::get('/register', [UserController::class, 'showRegisterForm']);
    Route::post('/register', [UserController::class, 'register']);
    // ユーザーログイン画面
    Route::get('/login', [UserController::class, 'showLoginForm']);
    Route::post('/login', [UserController::class, 'login']);

    Route::middleware('auth')->group(function () {
        // 勤怠登録画面（ログイン必須）
        Route::get('/attendance', [AttendanceController::class, 'showUserAttendance']);
        // 勤怠ボタンのルート
        Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn']);
        Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut']);
        Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart']);
        Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd']);
        // 勤怠一覧画面
        Route::get('/attendance/list/{year?}/{month?}', [AttendanceController::class, 'showAttendanceList'])->name('attendance.list');
        // 勤怠詳細画面
        Route::get('/attendance/detail/{id}', [AttendanceController::class, 'showAttendanceDetail']);
        // 勤怠修正のルート
        Route::post('/attendance/correct/{id}', [AttendanceController::class, 'correctAttendance']);
        // ログアウトルート
        Route::post('/logout', function () {
            Auth::logout();
            return redirect('/user/login');
        })->name('logout');
    });
});