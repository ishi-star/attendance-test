<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

/*
　　パーソナルアクセストークンメモにある
　　⭐git pushで必要です。
*/

Route::get('/register', [UserController::class, 'showRegisterForm']);
