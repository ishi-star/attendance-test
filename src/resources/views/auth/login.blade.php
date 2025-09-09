@extends('layout.app')

<!-- タイトル ユーザー登録画面です -->
@section('title','ログイン')

@section('header-nav')
<!-- ログイン画面ではナビゲーションは表示しない -->
@endsection

<!-- css読み込み -->
@section('css')
<link rel="stylesheet" href="{{ asset('/css/auth.css')  }}">
@endsection

<!-- 本体 -->
@section('content')

<form action="/login" method="post" class="form">
    @csrf
    <h2 class="page__title">ログイン</h2>
    <label for="name" class="entry__name">名前</label>
    <input name="name" id="name" type="text" class="input" value="{{ old('name') }}">
    <div class="form__error">
        @error('name')
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
    <button class="btn btn--big">ログインする</button>
    <a href="/register" class="link">会員登録はこちら</a>
</form>
@endsection