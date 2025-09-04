@extends('layout.app') {{-- 共通レイアウトを継承 --}}
<!-- タイトル ユーザー登録画面・ログイン画面から遷移先です -->
@section('title','会員登録')

<!-- css読み込み -->

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance-container">
  <h2 class="attendance-title">勤怠登録画面：出勤前（一般ユーザー）</h2>

  <div class="attendance-card">
    <p><strong>日付：</strong>{{ \Carbon\Carbon::now()->format('Y年n月j日(D)') }}</p>
    <p><strong>出勤時間：</strong>{{ \Carbon\Carbon::now()->format('H:i') }}</p>

    <form action="/attendance/clock-in" method="POST">
      @csrf
      <button type="submit" class="clockin-button">出勤</button>
    </form>
  </div>
</div>
@endsection

