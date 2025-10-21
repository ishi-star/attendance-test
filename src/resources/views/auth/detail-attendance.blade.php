@extends('layout.app')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail-attendance.css') }}">

@endsection

@section('content')

    {{-- 💡 isReadOnly フラグと備考の値の定義を挿入 --}}
    @php
        // $stampCorrectionRequest が存在すれば true (申請詳細表示モード)
        // 変数が定義されていない場合に備えて isset() でチェックする
        $isReadOnly = isset($stampCorrectionRequest);

        // ★★★ 追加: 承認待ちフラグ ★★★
        // statusカラムに 'pending' が設定されていると想定
        $isPending = $isReadOnly && optional($stampCorrectionRequest)->status === 'pending';

        // 申請データが存在する場合、表示すべき備考の理由 (reasonカラムを想定) を取得
        // $stampCorrectionRequest が存在しない場合は null を使用
        $remarksValue = $isReadOnly ? optional($stampCorrectionRequest)->reason : old('remarks');

        // 変数が存在しない場合に、後のコードで参照エラーが出ないように初期化する
        if (!$isReadOnly) {
            $stampCorrectionRequest = null;
        }
    @endphp

    {{-- 💡全体エラー表示 (修正申請画面でのみ表示) --}}
    @if (!$isReadOnly && $errors->any())
        <div class="alert alert-danger" style="color: red; margin-bottom: 20px; padding: 10px; border: 1px solid red; background-color: #fdd;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

<div class="detail-page-container">
    <h2 class="page-heading">勤怠詳細</h2>

    {{-- 💡 フォームの action とクラスを isReadOnly に応じて制御 --}}
    <form action="{{ $isReadOnly ? '#' : route('attendance.request', ['id' => $attendance->id]) }}"
          method="POST"
          class="{{ $isReadOnly ? 'is-readonly-mode' : '' }} {{ $isPending ? 'is-pending-request' : '' }}">
        @csrf

        {{-- 読み取り専用の場合、POSTリクエストが飛ばないように無効化 --}}
        @if ($isReadOnly)
            {{-- @method('GET') は不要ですが、ここでは安全のため何も設定しません --}}
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
                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        {{-- 💡 clock_inの value と disabled 属性 --}}
                        <input type="time" name="clock_in"
                        value="{{ $isReadOnly && $stampCorrectionRequest->type === 'clock_in' ? \Carbon\Carbon::parse($stampCorrectionRequest->requested_time)->format('H:i') : old('clock_in', $attendance->clock_in->format('H:i')) }}"
                        class="time-input @error('clock_in') is-invalid @enderror"
                        {{ $isReadOnly ? 'disabled' : '' }}>
                        〜
                        {{-- 💡 clock_outの value と disabled 属性 --}}
                        <input type="time" name="clock_out"
                        value="{{ $isReadOnly && $stampCorrectionRequest->type === 'clock_out' ? \Carbon\Carbon::parse($stampCorrectionRequest->requested_time)->format('H:i') : old('clock_out', $attendance->clock_out ? $attendance->clock_out->format('H:i') : '') }}"
                        class="time-input @error('clock_out') is-invalid @enderror"
                        {{ $isReadOnly ? 'disabled' : '' }}>
                    </td>
                </tr>

                @foreach($attendance->breaks as $index => $break)
                <tr>
                    <th>休憩{{ $index + 1 }}</th>
                    <td>
                        {{-- break_update の申請内容を取得 --}}
                @php
                    $isBreakUpdated = $isReadOnly && $stampCorrectionRequest->type === 'break_update' && $stampCorrectionRequest->original_break_id === $break->id;
                    // ↓ この行か次の行あたりに怪しい空白が紛れている可能性があります
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
                        
                        {{-- 💡 既存休憩の start_time の value と disabled 属性 --}}
                        <input type="time" name="breaks[{{ $break->id }}][start_time]"
                            value="{{ $isBreakUpdated ? \Carbon\Carbon::parse($requestedBreakData['start'])->format('H:i') : old("breaks.{$break->id}.start_time", optional($break->start_time)->format('H:i')) }}"
                            class="time-input @error("breaks.{$break->id}.start_time") is-invalid @enderror"
                            {{ $isReadOnly ? 'disabled' : '' }}>
                        〜
                        {{-- 💡 既存休憩の end_time の value と disabled 属性 --}}
                        <input type="time" name="breaks[{{ $break->id }}][end_time]"
                            value="{{ $isBreakUpdated ? \Carbon\Carbon::parse($requestedBreakData['end'])->format('H:i') : old("breaks.{$break->id}.end_time", optional($break->end_time)->format('H:i')) }}"
                            class="time-input @error("breaks.{$break->id}.end_time") is-invalid @enderror"
                            {{ $isReadOnly ? 'disabled' : '' }}>

                    </td>
                </tr>
                @endforeach

                {{-- 💡 新規追加休憩の表示ロジック --}}
                @php
                    $isBreakAdded = $isReadOnly && $stampCorrectionRequest->type === 'break_add';

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
                    <th>休憩{{ $attendance->breaks->count() + 1 }}
                        @if($isBreakAdded) <span style="color: red;">(申請内容)</span> @endif
                    </th>
                    <td>
                        {{-- 💡 新規休憩の start_time の value と disabled 属性 --}}
                        <input type="time" name="new_break[start_time]"
                            value="{{ $isBreakAdded ? \Carbon\Carbon::parse($newBreakData['start'])->format('H:i') : old('new_break.start_time') }}"
                            class="time-input @error('new_break.start_time') is-invalid @enderror"
                            {{ $isReadOnly ? 'disabled' : '' }}>
                        〜
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
                        >{{ $remarksValue }}</textarea>

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