@extends('layout.app')

@section('title', '勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/list-attendance.css') }}">
@endsection

@section('content')
<div class="attendance-list-container">
    <h2 class="list-heading">勤怠一覧</h2>
    <table class="attendance-table">
        <thead class="table-header">
            <tr>
                <th class="table-cell">日付</th>
                <th class="table-cell">出勤</th>
                <th class="table-cell">退勤</th>
                <th class="table-cell">休憩</th>
                <th class="table-cell">合計</th>
                <th class="table-cell">詳細</th>
            </tr>
        </thead>
        <tbody class="table-body">
            @foreach($attendances as $attendance)
            <tr class="table-row">
                <td class="table-cell">{{ $attendance->clock_in->locale('ja')->translatedFormat('m/d(D)') }}</td>
                <td class="table-cell">{{ $attendance->clock_in->format('H:i') }}</td>
                <td class="table-cell">{{ $attendance->clock_out ? $attendance->clock_out->format('H:i') : '--:--' }}</td>
                <td class="table-cell">{{ $attendance->total_break_time }}分</td>
                <td class="table-cell">{{ $attendance->work_time }}分</td>
                <td class="table-cell"><a href="/attendance/detail/{{ $attendance->id }}" class="detail-link">詳細</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection