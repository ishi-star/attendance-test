@extends('layout.app')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail-user.css') }}">

@endsection

@section('content')

@php
// 1. まず $stampCorrectionRequest が存在しない場合に備えて初期化する
    if (!isset($stampCorrectionRequest)) {
        $stampCorrectionRequest = null;
    }
    
    // 2. $isReadOnly を定義（申請データがあればtrue）
    $isReadOnly = ($stampCorrectionRequest !== null);

    // ★★★ 追加: 承認待ちフラグ ★★★
    // statusカラムに 'pending' が設定されていると想定
    $isPending = $isReadOnly && optional($stampCorrectionRequest)->status === 'pending';

    // 3. $remarksValue を定義
    // 申請詳細モード($isReadOnly = true)なら申請理由、そうでなければ old または元の勤怠備考
    $remarksValue = $isReadOnly
        ? optional($stampCorrectionRequest)->reason
        : old('remarks', optional($attendance)->remarks);

    $newAttendanceData = [];
    $isNewAttendance = $isReadOnly && optional($stampCorrectionRequest)->type === 'new_attendance';

    // 新規勤怠申請の場合、JSONから時刻データをデコードする
    if ($isNewAttendance) {
        $requestedDataJson = optional($stampCorrectionRequest)->requested_data;
        if (is_string($requestedDataJson)) {
            $newAttendanceData = json_decode($requestedDataJson, true);
        }
    }
@endphp

{{-- 💡全体エラー表示 (修正申請画面でのみ表示) --}}
@if (!$isReadOnly && $errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="detail-page-container">
    <h2 class="page-heading">勤怠詳細6</h2>

    {{-- 💡 フォームの action とクラスを isReadOnly に応じて制御 --}}
    <form action="{{ $isReadOnly ? '#' : route('attendance.request', ['id' => $attendance->id]) }}"
        method="POST"
        class="{{ $isReadOnly ? 'is-readonly-mode' : '' }} {{ $isPending ? 'is-pending-request' : '' }}">
        @csrf
        @if ($attendance->id === 0)
            {{-- 💡 修正: 新規登録時は URL の date クエリから日付を target_date に含める --}}
            <input type="hidden" name="target_date" value="{{ Request::query('date') }}">
        @endif
        {{-- 読み取り専用の場合、POSTリクエストが飛ばないように無効化 --}}
        @if ($isReadOnly)
            {{-- @method('GET') は不要ですが、ここでは安全のため何も設定しません --}}
        @endif
        <input type="hidden" name="target_date" value="{{ $attendance->target_date ?? Request::query('date') }}">
        <div class="card-container">
            <table class="detail-table">
                <tr>
                    <th>名前</th>
                    <td>{{ $attendance->user->name }}</td>
                </tr>
                <tr>
                    <th>日付</th>
                    <td>
                        @php
                            // 💡 修正: clock_in が null の場合、URLの date パラメータから日付を取得し Carbon 化して表示用に使用する
                            $displayDate = $attendance->clock_in ?? \Carbon\Carbon::parse(Request::query('date'));
                        @endphp
                        {{-- 💡 修正: $displayDate を使用する --}}
                        <span class="detail-time">{{ $displayDate->format('Y年') }}</span>
                        <span class="detail-time">{{ $displayDate->format('m月d日') }}</span>
                    </td>
                </tr>
                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        {{-- 💡 clock_inの value と disabled 属性 (optional()でNullセーフ) --}}
                        <input type="time" name="clock_in"
                            value="{{
                                // 1. 新規勤怠申請の場合、JSONデータから取得
                                $isNewAttendance
                                ? ($newAttendanceData['clock_in'] ?? '')
                                // 2. 単独の出勤時刻修正申請の場合
                                : ($isReadOnly && optional($stampCorrectionRequest)->type === 'clock_in'
                                    ? optional(\Carbon\Carbon::parse(optional($stampCorrectionRequest)->requested_time))->format('H:i')
                                    // 3. 通常/修正申請以外のモードの場合
                                    : old('clock_in', optional($attendance->clock_in)->format('H:i'))
                                )
                            }}"
                        class="time-input @error('clock_in') is-invalid @enderror"
                        {{ $isReadOnly ? 'disabled' : '' }}>
                        <span class="detail-style" style="margin: 0 8px;">〜</span>
                        {{-- 💡 clock_outの value と disabled 属性 (optional()でNullセーフ) --}}
                        <input type="time" name="clock_out"
                            value="{{
                                // 1. 新規勤怠申請の場合、JSONデータから取得
                                $isNewAttendance
                                ? ($newAttendanceData['clock_out'] ?? '')
                                // 2. 単独の退勤時刻修正申請の場合
                                : ($isReadOnly && optional($stampCorrectionRequest)->type === 'clock_out'
                                    ? optional(\Carbon\Carbon::parse(optional($stampCorrectionRequest)->requested_time))->format('H:i')
                                    // 3. 通常/修正申請以外のモードの場合
                                    : old('clock_out', optional($attendance->clock_out)->format('H:i'))
                                )
                            }}"
                        class="time-input @error('clock_out') is-invalid @enderror"
                        {{ $isReadOnly ? 'disabled' : '' }}>
                    </td>
                </tr>

                @foreach($attendance->breaks as $index => $break)
                <tr>
                    <th>休憩</th>
                    <td>
                        {{-- break_update の申請内容を取得 --}}
                @php
                    $isBreakUpdated = $isReadOnly && $stampCorrectionRequest->type === 'break_update' && $stampCorrectionRequest->original_break_id === $break->id;
                    $requestedData = optional($stampCorrectionRequest)->requested_data;
                    $requestedBreakData = null;

                    if ($isBreakUpdated) {
                        if (is_string($requestedData)) {
                            $requestedBreakData = json_decode($requestedData, true);
                        } else {
                            $requestedBreakData = $requestedData;
                        }
                    }
                @endphp
                        
                        {{-- 💡 既存休憩の start_time の value と disabled 属性 (optional()でNullセーフ) --}}
                        <input type="time" name="breaks[{{ $break->id }}][start_time]"
                            value="{{ $isBreakUpdated ? \Carbon\Carbon::parse($requestedBreakData['start'])->format('H:i') : old("breaks.{$break->id}.start_time", optional($break->start_time)->format('H:i')) }}"
                            class="time-input @error("breaks.{$break->id}.start_time") is-invalid @enderror"
                            {{ $isReadOnly ? 'disabled' : '' }}>
                        <span class="detail-style" style="margin: 0 8px;">〜</span>
                        {{-- 💡 既存休憩の end_time の value と disabled 属性 (optional()でNullセーフ) --}}
                        <input type="time" name="breaks[{{ $break->id }}][end_time]"
                            value="{{ $isBreakUpdated ? \Carbon\Carbon::parse($requestedBreakData['end'])->format('H:i') : old("breaks.{$break->id}.end_time", optional($break->end_time)->format('H:i')) }}"
                            class="time-input @error("breaks.{$break->id}.end_time") is-invalid @enderror"
                            {{ $isReadOnly ? 'disabled' : '' }}>

                    </td>
                </tr>
                @endforeach

                {{-- 💡 新規追加休憩の表示ロジック --}}
                @php
                    $isBreakAdded = $isReadOnly && optional($stampCorrectionRequest)->type === 'break_add';

                    $newBreakRequestedData = optional($stampCorrectionRequest)->requested_data;
                    $newBreakData = null;
                    
                    if ($isBreakAdded) {
                        if (is_string($newBreakRequestedData)) {
                            $newBreakData = json_decode($newBreakRequestedData, true);
                        } else {
                            $newBreakData = $newBreakRequestedData;
                        }
                    }
                @endphp
                
                {{-- 通常モード（$isReadOnlyがfalse）か、新規追加の申請（$isBreakAddedがtrue）の場合に表示 --}}
                @if (!$isReadOnly || $isBreakAdded)
                <tr>
                    <th>休憩</th>
                    <td>
                        {{-- 💡 新規休憩の start_time の value と disabled 属性 --}}
                        <input type="time" name="new_break[start_time]"
                            value="{{ $isBreakAdded ? \Carbon\Carbon::parse($newBreakData['start'])->format('H:i') : old('new_break.start_time') }}"
                            class="time-input @error('new_break.start_time') is-invalid @enderror"
                            {{ $isReadOnly ? 'disabled' : '' }}>
                        <span class="detail-style" style="margin: 0 8px;">〜</span>
                        {{-- 💡 新規休憩の end_time の value と disabled 属性 --}}
                        <input type="time" name="new_break[end_time]"
                            value="{{ $isBreakAdded ? \Carbon\Carbon::parse($newBreakData['end'])->format('H:i') : old('new_break.end_time') }}"
                            class="time-input @error('new_break.end_time') is-invalid @enderror"
                            {{ $isReadOnly ? 'disabled' : '' }}>

                    </td>
                </tr>
                @endif

                <tr>
					<th>備考</th>
					<td>

						{{-- 💡 備考の value と disabled 属性 --}}
						<textarea name="remarks"
								class="remarks-input @error('remarks') is-invalid @enderror"
								placeholder="修正理由を記入してください"
								{{ $isReadOnly ? 'disabled' : '' }}
						>{{ old('remarks', $remarksValue) }}</textarea>
					</td>
				</tr>
            </table>
        </div>

        <div class="form-actions">
            {{-- 💡 読み取り専用の場合はボタンを非表示にする --}}
            @if (!$isReadOnly)
                <button type="submit" class="correction-button">修正</button>
            @endif
            @if ($isReadOnly)
        <div class="alert alert-info">
            <p class="alert_p">*承認待ちのため修正はできません。</p>
        </div>
    @endif
        </div>
    </form>
</div>
@endsection