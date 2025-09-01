<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;



Route::get('/register', [UserController::class, 'showRegisterForm']);
