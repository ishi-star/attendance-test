@extends('admin.layouts.admin_app')

@section('title','管理者ログイン')

@section('header')
{{-- ヘッダーの内容を空にする --}}
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('/css/admin-login.css') }}">
@endsection

@section('content')
<form method="POST" action="{{ route('admin.login') }}" class="login-form">
    @csrf
    <h2 class="page__title">管理者ログイン</h2>
    <label for="email" class="entry__name">メールアドレス</label>
    <input id="email" type="email" name="email" class="input">
    <div class="form__error">
        @error('email')
        {{ $message }}
        @enderror
    </div>
    <label for="password" class="entry__name">パスワード</label>
    <input id="password" type="password" name="password" class="input">
    <div class="form__error">
        @error('password')
        {{ $message }}
        @enderror
    </div>
    <button class="btn btn--big">ログインする</button>
</form>
@endsection