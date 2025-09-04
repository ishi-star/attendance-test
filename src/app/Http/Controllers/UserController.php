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
    return view('auth.register');
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

  public function showLoginForm()
  {
    return view('auth.login');
  }
// 　ユーザーログイン画面表示
  public function login(Request $request)
  {
        // バリデーション
    $request->validate([
        'name' => 'required|string',
        'password' => 'required|string',
    ]);

    $credentials = $request->only('name', 'password');

    if (Auth::attempt($credentials)) {
        // 認証成功 → 勤怠一覧画面へリダイレクト
      return redirect('/attendance/list');
    }

    // 認証失敗 → 元のログイン画面に戻す
    return back()->withErrors([
        'name' => 'ログイン情報が正しくありません。',
    ])->withInput();
  }
  // 勤怠登録画面表示
  public function showUserAttendance()
  {
    return view('auth.attendance');
  }
}