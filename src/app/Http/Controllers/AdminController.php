<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AdminLoginRequest;

class AdminController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.admin-login');
    }

    public function login(AdminLoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            // 認証されたユーザーを取得
            $user = Auth::user();

            // is_adminカラムがtrueかチェック
            if ($user->is_admin) {
                // 管理者であれば、管理者用のトップページにリダイレクト
                return redirect()->intended('/admin/home');
            }

            // 管理者でなければ、ログイン失敗として扱う
            Auth::logout(); // ログイン状態をリセット
            return back()->withErrors([
                'email' => 'ログイン情報が登録されていません',
            ]);
        }

        // 認証に失敗したら、ログイン画面に戻る
        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}