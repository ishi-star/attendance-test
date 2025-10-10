@extends('admin.layouts.admin_app')

@section('title', '修正申請詳細（管理者）')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail-attendance.css') }}">
<style>
  /* 修正箇所をハイライト表示 */
  .requested-time {
    background-color: #fff3cd;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: bold;
    color: #856404;
  }
  
  .original-time {
    text-decoration: line-through;
    color: #999;
    margin-right: 8px;
  }
  
  .time-change {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  
  .arrow {
    color: #666;
    font-weight: bold;
  }
  
  .approval-button {
    background: #28a745;
    color: #fff;
    border: none;
    padding: 12px 40px;
    margin-top: 10px;
    font-size: 16px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.3s ease;
  }
  
  .approval-button:hover {
    background: #218838;
  }
  
  .new-break-label {
    color: #28a745;
    font-weight: bold;
  }
</style>
@endsection

@section('content')
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
          <td>{{ $requestDetail->user->name }}</td>
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
            <div class="time-change">
              @if($requests['clock_in'])
                <span class="original-time">{{ $attendance->clock_in->format('H:i') }}</span>
                <span class="arrow">→</span>
                <span class="requested-time">{{ \Carbon\Carbon::parse($requests['clock_in']->requested_time)->format('H:i') }}</span>
              @else
                <span class="detail-time">{{ $attendance->clock_in->format('H:i') }}</span>
              @endif
              <span style="margin: 0 8px;">〜</span>
              @if($requests['clock_out'])
                <span class="original-time">{{ optional($attendance->clock_out)->format('H:i') }}</span>
                <span class="arrow">→</span>
                <span class="requested-time">{{ \Carbon\Carbon::parse($requests['clock_out']->requested_time)->format('H:i') }}</span>
              @else
                <span class="detail-time">{{ optional($attendance->clock_out)->format('H:i') }}</span>
              @endif
            </div>
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
                  <span class="original-time">{{ $break->start_time->format('H:i') }}</span>
                  <span class="arrow">→</span>
                  <span class="requested-time">{{ \Carbon\Carbon::parse($requestedData['start'])->format('H:i') }}</span>
                  <span style="margin: 0 8px;">〜</span>
                  <span class="original-time">{{ optional($break->end_time)->format('H:i') }}</span>
                  <span class="arrow">→</span>
                  <span class="requested-time">{{ \Carbon\Carbon::parse($requestedData['end'])->format('H:i') }}</span>
                </div>
              @else
                <span class="detail-time">{{ $break->start_time->format('H:i') }}</span>
                <span style="margin: 0 8px;">〜</span>
                <span class="detail-time">{{ optional($break->end_time)->format('H:i') }}</span>
              @endif
            </td>
          </tr>
        @endforeach

{{-- 新規追加の休憩申請を表示 --}}
        @foreach($requests['break_adds'] as $index => $breakAddRequest)
          @php
            $requestedData = json_decode($breakAddRequest->requested_data, true);
          @endphp
          <tr>
            <th>
              <span class="new-break-label">休憩{{ $attendance->breaks->count() + $index + 1 }}（新規）</span>
            </th>
            <td>
              @if($requestedData) {{-- ★★★ ここで $requestedData が null でないか確認 ★★★ --}}
                <div class="time-change">
                  <span class="requested-time">{{ \Carbon\Carbon::parse($requestedData['start'])->format('H:i') }}</span>
                  <span style="margin: 0 8px;">〜</span>
                  <span class="requested-time">{{ \Carbon\Carbon::parse($requestedData['end'])->format('H:i') }}</span>
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
      <button type="submit" class="approval-button">承認する</button>
    </div>
  </form>
</div>
@endsection