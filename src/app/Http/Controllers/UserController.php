<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
  // ユーザー登録画面表示
  public function showRegisterForm()
  {
    return view('user.register');
  }

  // ユーザー登録処理
  public function register(Request $request)
  {
  // バリデーション
  $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

   // ユーザー作成
  $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
         // 自動ログイン
  Auth::login($user);

    // 認証済み状態で勤怠登録画面へ遷移
    return redirect('/attendance');
  }

  // 勤怠登録画面表示
  public function showUserAttendance()
  {
    return view('user.attendance');
  }
}