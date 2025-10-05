@extends('admin.layouts.admin_app')

@section('title', '修正申請詳細（管理者）')

@section('css')
{{-- 既存のdetail-attendance.cssを流用 --}}
<link rel="stylesheet" href="{{ asset('css/detail-attendance.css') }}">

@endsection

@section('content')
<div class="detail-page-container">
    <h2 class="page-heading">勤怠詳細</h2>

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
                        <span class="detail-time">{{ $requestDetail->attendance->clock_in->format('Y年') }}</span>
                        <span class="detail-time">{{ $requestDetail->attendance->clock_in->format('m月d日') }}</span>
                    </td>
                </tr>
                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        {{-- 申請時刻と現行時刻を表示 (申請内容を優先) --}}
                        <span class="detail-time @if($requestDetail->type === 'clock_in') requested-time-display @endif">
                            {{ $requestDetail->type === 'clock_in' ? $requestDetail->requested_time->format('H:i') : $requestDetail->attendance->clock_in->format('H:i') }}
                        </span>
                        〜
                        <span class="detail-time @if($requestDetail->type === 'clock_out') requested-time-display @endif">
                            {{ $requestDetail->type === 'clock_out' ? $requestDetail->requested_time->format('H:i') : optional($requestDetail->attendance->clock_out)->format('H:i') }}
                        </span>
                    </td>
                </tr>
                
                {{-- ★ 既存の休憩を全て表示 ★ --}}
                @foreach($requestDetail->attendance->breaks as $index => $break)
                <tr>
                    <th>休憩{{ $index + 1 }}</th>
                    <td>
                        <span class="detail-time">{{ $break->start_time->format('H:i') }}</span>
                        〜
                        <span class="detail-time">{{ $break->end_time ? $break->end_time->format('H:i') : '' }}</span>
                    </td>
                </tr>
                @endforeach

                {{-- ★ 空状態で現状 + 1 の休憩枠を表示 ★ --}}
                @if ($requestDetail->attendance->breaks->count() < 3) {{-- 休憩が3回未満の場合を想定（任意） --}}
                <tr>
                    <th>休憩{{ $requestDetail->attendance->breaks->count() + 1 }}</th>
                    <td>
                        {{-- 空の休憩項目として表示 --}}
                        <span class="detail-time"></span>
                        〜
                        <span class="detail-time"></span>
                    </td>
                </tr>
                @endif
                
                {{-- 備考（申請理由を表示） --}}
                <tr>
                    <th>備考</th>
                    <td>
                        <span class="remarks-input">{{ $requestDetail->reason ?? $requestDetail->attendance->remarks ?? '記載なし' }}</span>
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