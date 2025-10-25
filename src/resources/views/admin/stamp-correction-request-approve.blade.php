@extends('admin.layouts.admin_app')

@section('title', '修正申請詳細（管理者）')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail-attendance.css') }}">

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
            @if($requests['clock_in'])
              <span class="detail-time">{{ \Carbon\Carbon::parse($requests['clock_in']->requested_time)->format('H:i') }}</span>
            @else
              <span class="detail-time">{{ $attendance->clock_in->format('H:i') }}</span>
            @endif

            <span class="detail-style" style="margin: 0 8px;">〜</span>

            @if($requests['clock_out'])
              <span class="detail-time">{{ \Carbon\Carbon::parse($requests['clock_out']->requested_time)->format('H:i') }}</span>
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