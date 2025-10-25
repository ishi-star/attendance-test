@extends('admin.layouts.admin_app')

@section('title', '修正申請詳細（管理者）')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail-attendance.css') }}">

@endsection

@section('content')
@php
    // =================================================================
    // ★追加・修正箇所1: new_attendance のデータ解析と表示時刻の決定
    // =================================================================
    $isNewAttendance = ($requestDetail->type === 'new_attendance');
    $newAttendanceData = null;

    // new_attendanceの場合、requested_data (JSON) をパース
    if ($isNewAttendance && $requestDetail->requested_data) {
        $newAttendanceData = json_decode($requestDetail->requested_data, true);
    }

    // 表示する出勤時刻の決定:
    // 1. new_attendanceの申請JSON -> 2. 個別のclock_in申請 -> 3. 既存の勤怠時刻
    $displayClockIn = null;
    if ($isNewAttendance && $newAttendanceData && isset($newAttendanceData['clock_in'])) {
        // new_attendance申請の時刻を取得 (例: "10:00")
        $displayClockIn = $newAttendanceData['clock_in'];
    } elseif ($requests['clock_in']) {
        // 個別の clock_in 修正申請から時刻を取得 (Carbonでパース)
        $displayClockIn = \Carbon\Carbon::parse($requests['clock_in']->requested_time)->format('H:i');
    }

    // 表示する退勤時刻の決定:
    // 1. new_attendanceの申請JSON -> 2. 個別のclock_out申請 -> 3. 既存の勤怠時刻
    $displayClockOut = null;
    if ($isNewAttendance && $newAttendanceData && isset($newAttendanceData['clock_out'])) {
        // new_attendance申請の時刻を取得 (例: "19:00")
        $displayClockOut = $newAttendanceData['clock_out'];
    } elseif ($requests['clock_out']) {
        // 個別の clock_out 修正申請から時刻を取得 (Carbonでパース)
        $displayClockOut = \Carbon\Carbon::parse($requests['clock_out']->requested_time)->format('H:i');
    }
@endphp
<div class="detail-page-container">
  <h2 class="page-heading">勤怠修正申請詳細</h2>

  <form action="{{ route('admin.request.handle', ['id' => $requestDetail->id]) }}" method="POST">
    @csrf
    <input type="hidden" name="action" value="approve">
    
    @if ($errors->any())
      <div class="alert alert-danger">
        <ul>
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
          <td class="detail-name">{{ $requestDetail->user->name }}</td>
        </tr>
        <tr>
          <th>日付</th>
          <td>
            <span class="detail-time">{{ $attendance->clock_in->format('Y年') }}</span>
            <span class="detail-style"></span>
            <span class="detail-time">{{ $attendance->clock_in->format('m月d日') }}</span>
          </td>
        </tr>
        <tr>
          <th>出勤・退勤</th>
          <td>
            {{-- ★修正箇所2: 出勤時刻の表示ロジックを修正 (上で決定した $displayClockIn を使用) ★ --}}
            @if($displayClockIn)
              <span class="detail-time">{{ $displayClockIn }}</span>
            @else
              <span class="detail-time">{{ $attendance->clock_in->format('H:i') }}</span>
            @endif

            <span class="detail-style" style="margin: 0 8px;">〜</span>

            {{-- ★修正箇所3: 退勤時刻の表示ロジックを修正 (上で決定した $displayClockOut を使用) ★ --}}
            @if($displayClockOut)
              <span class="detail-time">{{ $displayClockOut }}</span>
            @else
              <span class="detail-time">{{ optional($attendance->clock_out)->format('H:i') }}</span>
            @endif
          </td>
        </tr>
        

        {{-- 既存の休憩を表示（修正申請があれば変更内容を表示） --}}
        @foreach($attendance->breaks as $index => $break)
          @php
            // この休憩に対する修正申請を探す
            $breakUpdateRequest = $requests['break_updates']->firstWhere('original_break_id', $break->id);
            // $breakUpdateRequest があればデコード
            $requestedData = $breakUpdateRequest ? json_decode($breakUpdateRequest->requested_data, true) : null;
          @endphp
          <tr>
            <th>休憩{{ $index + 1 }}</th>
            <td>
              @if($requestedData) {{-- ★★★ ここで $requestedData が null でないか確認 ★★★ --}}
                <div class="time-change">
                  <span class="detail-time">{{ \Carbon\Carbon::parse($requestedData['start'])->format('H:i') }}</span>
                  <span class="detail-style" style="margin: 0 8px;">〜</span>

                  <span class="detail-time">{{ \Carbon\Carbon::parse($requestedData['end'])->format('H:i') }}</span>
                </div>
              @else
                <span class="detail-time">{{ $break->start_time->format('H:i') }}</span>
                <span class="detail-style" style="margin: 0 8px;">〜</span>
                <span class="detail-time">{{ optional($break->end_time)->format('H:i') }}</span>
              @endif
            </td>
          </tr>
        @endforeach

        {{-- 新規追加の休憩申請を表示 --}}
        @php
            // break_addsのコレクションにnew_attendanceの休憩データを追加する準備
            $allNewBreaks = collect($requests['break_adds']);
            if ($isNewAttendance && $newAttendanceData && !empty($newAttendanceData['new_break_start'])) {
                // new_attendanceの休憩データを break_add の形式に変換してコレクションに追加
                $allNewBreaks->push((object)[
                    'requested_data' => json_encode([
                        'start' => $newAttendanceData['new_break_start'],
                        'end' => $newAttendanceData['new_break_end']
                    ])
                ]);
            }
        @endphp

        @foreach($requests['break_adds'] as $index => $breakAddRequest)
          @php
            $requestedData = json_decode($breakAddRequest->requested_data, true);
          @endphp
          <tr>
            <th>
              <span class="new-break-label">休憩{{ $attendance->breaks->count() + $index + 1 }}</span>
            </th>
            <td>
              @if($requestedData) {{-- ★★★ ここで $requestedData が null でないか確認 ★★★ --}}
                <div class="time-change">
                  <span class="detail-time">{{ \Carbon\Carbon::parse($requestedData['start'])->format('H:i') }}</span>
                  <span class="detail-style" style="margin: 0 8px;">〜</span>
                  <span class="detail-time">{{ \Carbon\Carbon::parse($requestedData['end'])->format('H:i') }}</span>
                </div>
              @else
                 <div style="color: red;">申請データが読み込めませんでした。</div>
              @endif
            </td>
          </tr>
        @endforeach

        {{-- 備考（申請理由を表示） --}}
        <tr>
          <th>備考</th>
          <td>
            <div style="white-space: pre-wrap;">{{ $requestDetail->reason ?? $attendance->remarks ?? '記載なし' }}</div>
          </td>
        </tr>
      </table>
    </div>

    <div class="form-actions">
      <button type="submit" class="approval-button">承認</button>
    </div>
  </form>
</div>
@endsection