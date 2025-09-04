@extends('layout.app') {{-- 共通レイアウトを継承 --}}
<!--　勤怠一覧画面です -->
@section('title','会員登録')

<!-- css読み込み -->
@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<table class="attendance-table">
  <thead>
    <tr>
      <th>日付</th>
      <th>出勤</th>
      <th>退勤</th>
      <th>休憩</th>
      <th>勤務時間</th>
      <th>詳細</th>
    </tr>
  </thead>
  <tbody>
    @foreach($attendances as $attendance)
      <tr>
        <td>{{ $attendance->date }}</td>
        <td>{{ $attendance->start_time }}</td>
        <td>{{ $attendance->end_time }}</td>
        <td>{{ $attendance->break_time }}</td>
        <td>{{ $attendance->work_hours }}</td>
        {{-- <td><a href="{{ route('attendance.show', $attendance->id) }}">詳細</a></td> --}}
      </tr>
    @endforeach
  </tbody>
</table>
@endsection
