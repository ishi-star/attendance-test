@extends('admin.layouts.admin_app')

@section('title', $user->name . 'の個別の勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/list-attendance.css') }}">
@endsection

@section('content')
{{-- スタッフの名前と表示月 --}}
<h2 class="list-heading">{{ $user->name }}の{{ $targetMonth->format('Y年n月') }}の勤怠</h2>

{{-- 月ナビゲーション (前月/翌月) --}}
<div class="month-navigation">
    {{-- 前月へのリンク --}}
    <a href="{{ route('admin.user.attendances', ['id' => $user->id, 'month' => $targetMonth->copy()->subMonth()->format('Y-m')]) }}" class="list__nav-button">
        &larr; 前月
    </a>
    <div class="list__date">{{ $targetMonth->format('Y/m') }}</div>
    {{-- 翌月へのリンク --}}
    <a href="{{ route('admin.user.attendances', ['id' => $user->id, 'month' => $targetMonth->copy()->addMonth()->format('Y-m')]) }}" class="list__nav-button">
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
            @forelse($attendances as $attendance)
            <tr class="table-row">
                {{-- 該当日の日付を表示 --}}
                <td class="table-cell">{{ $attendance->clock_in->format('j日') }}</td>
                <td class="table-cell">{{ $attendance->clock_in->format('H:i') }}</td>
                <td class="table-cell">
                    {{ $attendance->clock_out ? $attendance->clock_out->format('H:i') : '' }}
                </td>
                <td class="table-cell">
                    {{-- 休憩時間表示 (計算ロジックは既存のものを流用) --}}
                    {{ floor($attendance->total_break_time / 60) }}:{{ sprintf('%02d', $attendance->total_break_time % 60) }}
                </td>
                <td class="table-cell">
                    {{-- 実労働時間表示 (計算ロジックは既存のものを流用) --}}
                    {{ floor($attendance->work_time / 60) }}:{{ sprintf('%02d', $attendance->work_time % 60) }}
                </td>
                <td class="table-cell">
                    {{-- 勤怠詳細画面へ遷移 --}}
                    <a href="{{ route('admin.attendance.detail', ['id' => $attendance->id]) }}">修正</a>
                </td>
            </tr>
            @empty
            <tr class="table-row">
                <td class="table-cell" colspan="6">この月には勤怠記録がありません。</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection