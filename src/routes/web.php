<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;



Route::get('/register', [UserController::class, 'showRegisterForm']);

Route::get('/attendance', [UserController::class, 'showRegisterAttendance']);

