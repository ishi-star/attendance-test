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
    // 該当日のスタッフ勤務の勤怠状況の一覧
    Route::get('/attendances', [AdminController::class, 'showAttendances'])->name('attendances');
    // スタッフの名前とメルアドの一覧画面のルート(GET)
    Route::get('/staff/list', [AdminController::class, 'showUsers'])->name('users');
     // 勤怠詳細画面のルート (ID指定)
    Route::get('/attendances/{id}', [AdminController::class, 'showAttendanceDetail'])->name('attendance.detail');
    // 管理者側の勤怠修正処理ルート (POST)
    Route::post('/attendances/correct/{id}', [AdminController::class, 'correctAttendance'])->name('attendance.correct');
    // 個別スタッフの勤怠一覧画面
    Route::get('/attendance/staff/{id}/{month?}', [AdminController::class, 'showUserAttendances'])->name('user.attendances');
    // 申請一覧画面
    Route::get('/stamp_correction_request/list', [AdminController::class, 'showRequests'])->name('requests');
    // 申請処理 (承認/却下)
    Route::post('/request/{id}/handle', [AdminController::class, 'handleRequest'])->name('request.handle');
     // ★ 勤怠修正申請の詳細画面 (GET) ★
    // {id} は申請レコード（stamp_correction_requests）のIDです。
    Route::get('/stamp_correction_request/approve/{id}', [AdminController::class, 'showRequestDetail'])->name('request.detail');

        // ログアウトルートを追加
    Route::post('/logout', function () {
        Auth::logout();
        return redirect()->route('admin.login');
    })->name('logout');
});


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
    Route::post('/attendance/request/{id}', [AttendanceController::class, 'requestCorrection'])->name('attendance.request');
    // ログアウトルート
    Route::post('/logout', function () {
        Auth::logout();
        return redirect('login');
    })->name('logout');
});
