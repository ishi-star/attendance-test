@extends('admin.layouts.admin_app')

@section('title', '勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/list-attendance.css') }}">
@endsection

@section('content')
<h2 class="list-heading">{{ $date->format('Y年n月j日') }}の勤怠</h2>

<div class="month-navigation">
    <a href="{{ route('admin.attendances', ['date' => $date->copy()->subDay()->format('Y-m-d')]) }}" class="list__nav-button">
        &larr; 前日
    </a>
    <div class="list__date">{{ $date->format('Y/m/d') }}</div>
    <a href="{{ route('admin.attendances', ['date' => $date->copy()->addDay()->format('Y-m-d')]) }}" class="list__nav-button">
    翌日 &rarr;
    </a>
</div>
<div class="attendance-list-container">
    <table class="attendance-table">
        <thead class="table-header">
            <tr>
                <th class="table-cell">名前</th>
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
                <td class="table-cell">{{ $attendance->user->name }}</td>
                <td class="table-cell">{{ $attendance->clock_in }}</td>
                <td class="table-cell">{{ $attendance->clock_out }}</td>
                <td class="table-cell">{{ $attendance->total_break_time }}</td>
                <td class="table-cell">{{ $attendance->work_time }}</td>
                <td>詳細</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection