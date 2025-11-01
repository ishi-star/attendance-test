@extends('admin.layouts.admin_app')

@section('title', $user->name . 'の個別の勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/list-attendance.css') }}">
@endsection

@section('content')
{{-- スタッフの名前と表示月 --}}
<h2 class="list-heading">{{ $user->name }}さんの勤怠</h2>

{{-- 月ナビゲーション (前月/翌月) --}}
<div class="month-navigation">
    {{-- 前月へのリンク --}}
    <a href="{{ route('admin.user.attendances', ['user' => $user->id, 'month' => $targetMonth->copy()->subMonth()->format('Y-m')]) }}" class="list__nav-button">
        &larr; 前月
    </a>
    <div class="current-month">{{ $targetMonth->format('Y/m') }}</div>
    {{-- 翌月へのリンク --}}
    <a href="{{ route('admin.user.attendances', ['user' => $user->id, 'month' => $targetMonth->copy()->addMonth()->format('Y-m')]) }}" class="list__nav-button">
        翌月 &rarr;
    </a>
</div>

{{-- 勤怠テーブル --}}
<div class="attendance-list-container">
    <table class="attendance-table">
        <thead class="table-header">
            <tr>
                <th class="table-cell">日付</th> {{-- 名前ではなく日付を表示 --}}
                <th class="table-cell">出勤</th>
                <th class="table-cell">退勤</th>
                <th class="table-cell">休憩</th>
                <th class="table-cell">合計</th>
                <th class="table-cell">詳細</th>
            </tr>
        </thead>
        <tbody class="table-body">
            @php
                // 月初〜月末をループ
                $startDate = $targetMonth->copy()->startOfMonth();
                $endDate = $targetMonth->copy()->endOfMonth();

                // 日付キーで勤怠データを整理
                $attendances = $attendances->keyBy(fn($a) => $a->clock_in->format('Y-m-d'));
            @endphp

            @for ($date = $startDate->copy(); $date <= $endDate; $date->addDay())
                @php
                    $attendance = $attendances->get($date->format('Y-m-d'));
                @endphp

                <tr class="table-row">
                    <td class="table-cell">
                            {{ $date->format('m/d') }}
                            ({{ ['日','月','火','水','木','金','土'][$date->dayOfWeek] }})
                    </td>

                    <td class="table-cell">
                        {{ $attendance?->clock_in?->format('H:i') ?? '' }}
                    </td>

                    <td class="table-cell">
                        {{ $attendance?->clock_out?->format('H:i') ?? '' }}
                    </td>

                    <td class="table-cell">
                        @if ($attendance)
                            {{ floor($attendance->total_break_time / 60) }}:{{ sprintf('%02d', $attendance->total_break_time % 60) }}
                        @endif
                    </td>

                    <td class="table-cell">
                        @if ($attendance)
                            {{ floor($attendance->work_time / 60) }}:{{ sprintf('%02d', $attendance->work_time % 60) }}
                        @endif
                    </td>

                    <td class="table-cell">
                        @if ($attendance)
                            <a href="{{ route('admin.attendance.detail', ['id' => $attendance->id]) }}">詳細</a>
                        @endif
                    </td>
                </tr>
            @endfor
        </tbody>
    </table>
    {{-- =================================== --}}
    {{-- ★ CSVダウンロードボタン ★ --}}
    {{-- =================================== --}}
    <div class="csv-actions">
        {{-- ダウンロードルート (admin.attendance.export.csv) に必要なパラメータを渡す --}}
        <a
            href="{{ route('admin.attendance.export.csv', [
                'userId' => $user->id,
                'year' => $targetMonth->year,
                'month' => $targetMonth->month
            ]) }}"
            class="csv-download-button"
            target="_blank" {{-- 新しいタブで開くことで、現在の画面を維持 --}}
        >
            CSV出力
        </a>
    </div>
</div>
@endsection