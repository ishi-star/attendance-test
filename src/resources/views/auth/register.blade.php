@extends('layout.app')

<!-- タイトル ユーザー登録画面です -->
@section('title','会員登録')

@section('header-nav')
<!-- 会員登録画面ではナビゲーションは表示しない -->
@endsection

<!-- css読み込み -->
@section('css')
<link rel="stylesheet" href="{{ asset('/css/auth.css')  }}">
@endsection

<!-- 本体 -->
@section('content')

<form action="/register" method="post" class="form">
    @csrf
    <h2 class="page__title">会員登録</h2>
    <label for="name" class="entry__name">名前</label>
    <input name="name" id="name" type="text" class="input" value="{{ old('name') }}">
    <div class="form__error">
        @error('name')
        {{ $message }}
        @enderror
    </div>
    <label for="mail" class="entry__name">メールアドレス</label>
    <input name="email" id="mail" type="email" class="input" value="{{ old('email') }}">
    <div class="form__error">
        @error('email')
        {{ $message }}
        @enderror
    </div>
    <label for="password" class="entry__name">パスワード</label>
    <input name="password" id="password" type="password" class="input">
    <div class="form__error">
        @error('password')
        {{ $message }}
        @enderror
    </div>
    <label for="password_confirm" class="entry__name">パスワード確認</label>
    <input name="password_confirmation" id="password_confirm" type="password" class="input">
    <button class="btn btn--big">登録する</button>
    <a href="/login" class="link">ログインはこちら</a>
</form>
@endsection