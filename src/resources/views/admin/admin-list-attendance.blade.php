@extends('admin.layouts.admin_app')

@section('title', '勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendances.css') }}">
@endsection

@section('content')
<div class="attendances-list">
    <div class="list__header">
        <a href="#" class="list__nav-button">＜</a>
        <span class="list__date">2023/06/01</span>
        <a href="#" class="list__nav-button">＞</a>
    </div>
    <table class="list__table">
        <thead>
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>山田 太郎</td>
                <td>09:00</td>
                <td>18:00</td>
                <td>1:00</td>
                <td>8:00</td>
                <td><a href="#">詳細</a></td>
            </tr>
        </tbody>
    </table>
</div>
@endsection