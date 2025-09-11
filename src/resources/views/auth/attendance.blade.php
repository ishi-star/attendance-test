@extends('layout.app')

@section('title','勤怠打刻')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance-container">
    <h2 class="attendance-title">勤怠登録画面</h2>

    @if(session('message'))
    <p class="message">{{ session('message') }}</p>
    @endif

    <div class="attendance-card">
        <p><strong>日付：</strong>{{ \Carbon\Carbon::now()->format('Y年n月j日(D)') }}</p>

        @if(!$attendance)
        <form action="/attendance/clock-in" method="POST">
            @csrf
            <button type="submit" class="clockin-button">出勤</button>
        </form>
        @elseif($attendance && !$attendance->clock_out && !$isBreaking)
        <p><strong>出勤時間：</strong>{{ $attendance->clock_in->format('H:i') }}</p>
        <form action="/attendance/break-start" method="POST">
            @csrf
            <button type="submit" class="break-button">休憩入</button>
        </form>
        <form action="/attendance/clock-out" method="POST">
            @csrf
            <button type="submit" class="clockout-button">退勤</button>
        </form>
        @elseif($isBreaking)
        <p><strong>休憩中</strong></p>
        <form action="/attendance/break-end" method="POST">
            @csrf
            <button type="submit" class="breakend-button">休憩戻</button>
        </form>
        @else
        <p><strong>退勤済み</strong></p>
        @endif
    </div>
</div>
@endsection
