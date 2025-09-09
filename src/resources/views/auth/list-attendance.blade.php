@extends('layout.app') {{-- 共通レイアウトを継承 --}}
<!--　勤怠一覧画面です -->
@section('title','勤怠一覧')

<!-- css読み込み -->
@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<h2>勤怠一覧</h2>
<table class="attendance-table">
  <thead>
    <tr>
      <th>日付</th>
      <th>出勤</th>
      <th>退勤</th>
      <th>休憩</th>
      <th>合計</th>
      <th>詳細</th>
    </tr>
  </thead>
  <tbody>
    @foreach($attendances as $attendance)
      <tr>
        <td>{{ $attendance->clock_in->format('Y-m-d') }}</td> <!-- 日付 -->
        <td>{{ $attendance->clock_in->format('H:i') }}</td>  <!-- 出勤 -->
        <td>{{ $attendance->clock_out ? $attendance->clock_out->format('H:i') : '' }}</td> <!-- 退勤 -->
        <td>{{ $attendance->total_break_time }}分</td> <!-- 休憩 -->
        <td>{{ $attendance->work_time }}分</td> <!-- 合計 -->
        {{-- <td><a href="{{ route('attendance.show', $attendance->id) }}">詳細</a></td> --}}
      </tr>
    @endforeach
  </tbody>
</table>
@endsection
