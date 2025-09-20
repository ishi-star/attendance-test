@extends('layout.app')

@section('title','勤怠打刻画面')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance-container">
    @if(session('message'))
    <p class="message">{{ session('message') }}</p>
    @endif
    <div class="attendance-card">
        @if(!$attendance)
            <p class="attendance-status">勤務外</p>
            <p class="attendance-date">
                {{ \Carbon\Carbon::now()->locale('ja')->translatedFormat('Y年n月j日')}} ({{ $dayOfWeek }})
            </p>
            <p class="attendance-time">{{ \Carbon\Carbon::now()->format('H:i') }}</p>
            <form action="/attendance/clock-in" method="POST">
                @csrf
                <button type="submit" class="attendance-button">出勤</button>
            </form>
        @elseif($attendance && !$attendance->clock_out && !$isBreaking)
            <p class="attendance-status">勤務中</p>
            <p class="attendance-date">
                {{ $attendance->clock_in->locale('ja')->translatedFormat('Y年n月j日')}} ({{ $dayOfWeek }})
            </p>
            <p class="attendance-time">{{ $attendance->clock_in->format('H:i') }}</p>
            <div class=attendance__button-side>
                <form action="/attendance/clock-out" method="POST">
                    @csrf
                    <button type="submit" class="attendance-button">退勤</button>
                </form>
                <form action="/attendance/break-start" method="POST">
                    @csrf
                    <button type="submit" class="attendance-button break-start-button">休憩入</button>
                </form
            ></div>
        @elseif($isBreaking)
            <p class="attendance-status">休憩中</p>
            <p class="attendance-date">
                {{ $attendance->clock_in->locale('ja')->translatedFormat('Y年n月j日')}} ({{ $dayOfWeek }})
            </p>
            <p class="attendance-time">{{ \Carbon\Carbon::now()->format('H:i') }}</p>
            <form action="/attendance/break-end" method="POST">
                @csrf
                <button type="submit" class="attendance-button break-start-button">休憩戻</button>
            </form>
        @else
            <p class="attendance-status">退勤済</p>
            <p class="attendance-date">
                {{ $attendance->clock_in->locale('ja')->translatedFormat('Y年n月j日')}} ({{ $dayOfWeek }})
            </p>
            <p class="attendance-time">{{ $attendance->clock_out->format('H:i') }}</p>
        @endif
    </div>
</div>
@endsection