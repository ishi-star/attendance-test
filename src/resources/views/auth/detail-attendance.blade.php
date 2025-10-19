@extends('layout.app')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail-attendance.css') }}">
{{-- 必要に応じて、is-invalidクラスのエラー時のスタイルをここに追記してください --}}
<style>
.error-message {
    color: red;
    font-size: 0.85em;
    margin-top: 5px;
}
.is-invalid {
    border: 1px solid red; /* エラー発生時に入力欄の枠を赤くする例 */
}
</style>
@endsection

@section('content')
<div class="detail-page-container">
    <h2 class="page-heading">勤怠詳細</h2>

    <form action="{{ route('attendance.request', ['id' => $attendance->id]) }}" method="POST">
        @csrf

        {{-- 💡【修正点 1: 全体エラー表示】フォームの上部に全てのエラーメッセージを一覧で表示 --}}
        @if ($errors->any())
            <div class="alert alert-danger" style="color: red; margin-bottom: 20px; padding: 10px; border: 1px solid red; background-color: #fdd;">
                <p><strong>入力内容にエラーがあります。ご確認ください。</strong></p>
                <ul>
                    {{-- 備考欄のエラーは個別表示に任せる場合は $errors->all() の代わりに $errors->default->all() を使う場合もありますが、ここでは全て表示します --}}
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

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
                
                {{-- 💡【修正点 2: 出勤・退勤】個別エラー表示と old() 関数 --}}
                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        <input type="time" name="clock_in"
                        value="{{ old('clock_in', $attendance->clock_in->format('H:i')) }}"
                        class="time-input @error('clock_in') is-invalid @enderror">
                        〜
                        <input type="time" name="clock_out"
                        value="{{ old('clock_out', $attendance->clock_out ? $attendance->clock_out->format('H:i') : '') }}"
                        class="time-input @error('clock_out') is-invalid @enderror">

                        {{-- エラーメッセージを個別に表示 --}}
                        @error('clock_in')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        @error('clock_out')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                
                {{-- 💡【修正点 3: 既存の休憩時間】配列のバリデーションにはドット記法を使用 --}}
                @foreach($attendance->breaks as $index => $break)
                <tr>
                    <th>休憩{{ $index + 1 }}</th>
                    <td>
                        {{-- フィールド名: breaks.ID.start_time --}}
                        <input type="time" name="breaks[{{ $break->id }}][start_time]"
                            value="{{ old("breaks.{$break->id}.start_time", optional($break->start_time)->format('H:i')) }}"
                            class="time-input @error("breaks.{$break->id}.start_time") is-invalid @enderror">
                        〜
                        {{-- フィールド名: breaks.ID.end_time --}}
                        <input type="time" name="breaks[{{ $break->id }}][end_time]"
                            value="{{ old("breaks.{$break->id}.end_time", optional($break->end_time)->format('H:i')) }}"
                            class="time-input @error("breaks.{$break->id}.end_time") is-invalid @enderror">

                        {{-- エラー表示 (フィールド名をドット記法で指定) --}}
                        @error("breaks.{$break->id}.start_time")
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        @error("breaks.{$break->id}.end_time")
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                @endforeach

                {{-- 💡【修正点 4: 新規追加の休憩時間】個別エラー表示と old() 関数 --}}
                <tr>
                    <th>休憩{{ $attendance->breaks->count() + 1 }}</th>
                    <td>
                        {{-- フィールド名: new_break.start_time --}}
                        <input type="time" name="new_break[start_time]"
                            value="{{ old('new_break.start_time') }}"
                            class="time-input @error('new_break.start_time') is-invalid @enderror">
                        〜
                        {{-- フィールド名: new_break.end_time --}}
                        <input type="time" name="new_break[end_time]"
                            value="{{ old('new_break.end_time') }}"
                            class="time-input @error('new_break.end_time') is-invalid @enderror">

                        {{-- エラー表示 --}}
                        @error('new_break.start_time')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        @error('new_break.end_time')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                
                {{-- 💡【修正点 5: 備考】個別エラー表示と old() 関数 --}}
                <tr>
                    <th>備考</th>
                    <td>
                        <textarea name="remarks" class="remarks-input @error('remarks') is-invalid @enderror" placeholder="修正理由を記入してください">{{ old('remarks') }}</textarea>

                        {{-- エラー表示 --}}
                        @error('remarks')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
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