@extends('admin.layouts.admin_app')

@section('title', '勤怠詳細画面（管理者修正）')

@section('css')
{{-- CSSファイル名がdetail-attendance.cssであればそのまま --}}
<link rel="stylesheet" href="{{ asset('css/admin-detail.css') }}">
@endsection

@section('content')
<div class="detail-page-container">
    <h2 class="page-heading">勤怠詳細</h2>

    {{-- ★ 管理者画面での全体エラー表示 ★ --}}
    @if ($errors->any())
        <div class="alert alert-danger" >
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.attendance.correct', ['id' => $attendance->id]) }}" method="POST"onsubmit="return validateForm(event)">
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
                        {{-- 修正可能にするため input type="time" を使用 --}}
                        <input type="time" name="clock_in" value="{{ $attendance->clock_in->format('H:i') }}" class="time-input">
                        〜
                        <input type="time" name="clock_out" value="{{ $attendance->clock_out ? $attendance->clock_out->format('H:i') : '' }}" class="time-input">
                    </td>
                </tr>
                
                {{-- 登録済みの休憩を全て表示（修正可能） --}}
                @foreach($attendance->breaks as $index => $break)
                <tr>
                    <th>休憩{{ $index + 1 }}</th>
                    <td>
                        {{-- 既存の休憩IDを配列キーとしてPOSTする --}}
                        <input type="time" name="breaks[{{ $break->id }}][start_time]" value="{{ $break->start_time->format('H:i') }}" class="time-input">
                        〜
                        <input type="time" name="breaks[{{ $break->id }}][end_time]" value="{{ $break->end_time ? $break->end_time->format('H:i') : '' }}" class="time-input">
                    </td>
                </tr>
                @endforeach

                {{-- 新規追加用の休憩入力枠 --}}
                <tr>
                    <th>休憩{{ $attendance->breaks->count() + 1 }}</th>
                    <td>
                        {{-- 新規休憩として配列キー 'new_break' を使用 --}}
                        <input type="time" name="new_break[start_time]" class="time-input">
                        〜
                        <input type="time" name="new_break[end_time]" class="time-input">
                    </td>
                </tr>
                <tr>
                    <th>備考</th>
                    <td>
                        <textarea name="remarks" class="remarks-input" placeholder="修正理由を記入してください">{{ $attendance->remarks ?? '' }}</textarea>
                    </td>
                </tr>
            </table>
        </div>

        <div class="form-actions">
            <button type="submit" class="correction-button">修正</button>
        </div>
    </form>
</div>
@endsection