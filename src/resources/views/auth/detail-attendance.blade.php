@extends('layout.app')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail-attendance.css') }}">
@endsection

@section('content')
<div class="detail-page-container">
    <h2 class="page-heading">勤怠詳細</h2>

    <form action="{{ route('attendance.request', ['id' => $attendance->id]) }}" method="POST">
        @csrf

        <div class="card-container">
            <table class="detail-table">
                <tr>
                    <th>名前</th>
                    <td>{{ $attendance->user->name }}</td>
                </tr>
                <tr>
                    <th>日付</th>
                    <td>
                        <span class="detail-time">{{ $attendance->clock_in->format('Y年') }}</span>
                        <span class="detail-time">{{ $attendance->clock_in->format('m月d日') }}</span>
                    </td>
                </tr>
                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        <input type="time" name="clock_in"
                        value="{{ old('clock_in', $attendance->clock_in->format('H:i')) }}"
                        class="time-input">
                        〜
                        <input type="time" name="clock_out"
                        value="{{ old('clock_out', $attendance->clock_out ? $attendance->clock_out->format('H:i') : '') }}"
                        class="time-input">
                    </td>
                </tr>
                @foreach($attendance->breaks as $index => $break)
                <tr>
                    <th>休憩{{ $index + 1 }}</th>
                    <td>
                        <input type="time" name="breaks[{{ $break->id }}][start_time]"
                            value="{{ old("breaks.{$break->id}.start_time", $break->start_time->format('H:i')) }}"
                            class="time-input">
                        〜
                        <input type="time" name="breaks[{{ $break->id }}][end_time]"
                            value="{{ old("breaks.{$break->id}.end_time", $break->end_time ? $break->end_time->format('H:i') : '') }}"
                            class="time-input">
                    </td>
                </tr>
                @endforeach

                <tr>
                    <th>休憩{{ $attendance->breaks->count() + 1 }}</th>
                    <td>
                        <input type="time" name="new_break[start_time]"
                            value="{{ old('new_break.start_time') }}"
                            class="time-input">
                        〜
                        <input type="time" name="new_break[end_time]"
                            value="{{ old('new_break.end_time') }}"
                            class="time-input">
                    </td>
                </tr>
                <tr>
                    <th>備考</th>
                    <td>
                        <textarea name="remarks" class="remarks-input" placeholder="修正理由を記入してください">{{ old('remarks') }}</textarea>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ボタンをカードの外に出す -->
        <div class="form-actions">
            <button type="submit" class="correction-button">修正</button>
        </div>
    </form>
</div>
@endsection
