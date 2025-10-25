@extends('layout.app')

@section('title', '勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/list-attendance.css') }}">
@endsection

@section('content')
<h2 class="list-heading">勤怠一覧</h2>

<div class="month-navigation">
    <a href="{{ route('attendance.list', ['year' => $previousMonth->year, 'month' => $previousMonth->month]) }}" class="month-link">
        &larr; 前月
    </a>
    <div class="current-month">{{ $currentMonth->format('Y年m月') }}</div>
    <a href="{{ route('attendance.list', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}" class="month-link">
        翌月 &rarr;
    </a>
</div>
<div class="attendance-list-container">
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
            @foreach($dates as $date)
                @php
                    $dateKey = $date->format('Y-m-d');
                    $attendance = $attendances->get($dateKey);

                    // 💡 詳細リンクのURLを定義 (勤怠の有無でURLを切り替え)
                    if ($attendance) {
                        $detailUrl = "/attendance/detail/{$attendance->id}"; 
                    } else {
                        // 勤怠がない場合: 新規申請フォームへ誘導
                        $detailUrl = "/attendance/request/new?date={$dateKey}";
                    }
                @endphp
                <tr class="table-row">
                    <td class="table-cell">{{ $date->locale('ja')->translatedFormat('m/d(D)') }}</td>

                    {{-- 勤怠データ表示部分 (4列) --}}
                    @if($attendance)
                        <td class="table-cell">{{ $attendance->clock_in->format('H:i') }}</td>
                        <td class="table-cell">{{ $attendance->clock_out ? $attendance->clock_out->format('H:i') : '' }}</td>
                        <td class="table-cell">
                            {{ floor($attendance->total_break_time / 60) }}:{{ sprintf('%02d', $attendance->total_break_time % 60) }}
                        </td>
                        <td class="table-cell">
                            {{ floor($attendance->work_time / 60) }}:{{ sprintf('%02d', $attendance->work_time % 60) }}
                        </td>
                    @else
                        {{-- 勤怠がない場合は空欄のセルを4つ表示 --}}
                        <td class="table-cell"></td>
                        <td class="table-cell"></td>
                        <td class="table-cell"></td>
                        <td class="table-cell"></td>
                    @endif

                    {{-- 💡 詳細リンクセル (常に表示) --}}
                    <td class="table-cell">
                        <a href="{{ $detailUrl }}" class="detail-link">詳細</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection