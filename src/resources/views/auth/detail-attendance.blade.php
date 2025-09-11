@extends('layout.app')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail-attendance.css') }}">
@endsection

@section('content')
<div class="detail-container">
    <h2 class="detail-heading">勤怠詳細</h2>

    <div class="info-item">
      <span class="info-label">名前</span>
      <span class="info-value">{{ $attendance->user->name }}</span>
    </div>

    <div class="info-item">
        <span class="info-label">日付</span>
        <span class="info-value">{{ $attendance->clock_in->format('Y年m月d日') }}</span>
    </div>

    <div class="info-item">
        <span class="info-label">勤務開始</span>
        <span class="info-value">{{ $attendance->clock_in->format('H時i分s秒') }}</span>
    </div>

    <div class="info-item">
        <span class="info-label">勤務終了</span>
        <span class="info-value">
            @if ($attendance->clock_out)
                {{ $attendance->clock_out->format('H時i分s秒') }}
            @else
                --:--:--
            @endif
        </span>
    </div>

    <div class="info-item">
        <span class="info-label">総休憩時間</span>
        <span class="info-value">{{ $attendance->total_break_time }}分</span>
    </div>

    <div class="info-item">
        <span class="info-label">総勤務時間</span>
        <span class="info-value">{{ $attendance->work_time }}分</span>
    </div>

    <h3>休憩記録</h3>
    <ul class="break-list">
        @foreach ($attendance->breaks as $break)
        <li class="break-item">
            {{ $break->start_time->format('H:i') }} - 
            @if ($break->end_time)
                {{ $break->end_time->format('H:i') }} ({{ $break->duration_minutes }}分)
            @else
                休憩中
            @endif
        </li>
        @endforeach
    </ul>
</div>
@endsection