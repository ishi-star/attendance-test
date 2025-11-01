@extends('admin.layouts.admin_app')

@section('title', 'スタッフ一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/list-attendance.css') }}">
@endsection

@section('content')

<h2 class="list-heading">スタッフ一覧</h2>

<div class="attendance-list-container">
    <table class="attendance-table">
        <thead class="table-header">
            <tr>
                <th class="table-cell">名前</th>
                <th class="table-cell">メールアドレス</th>
                <th class="table-cell">月次勤怠</th>
                {{-- 他の勤怠項目がないため、合計などは不要 --}}
            </tr>
        </thead>
        <tbody class="table-body">
            @foreach ($users as $user)
            <tr class="table-row">
                <td class="table-cell">{{ $user->name }}</td>
                <td class="table-cell">{{ $user->email }}</td>
                <td class="table-cell">
                    <a href="{{ route('admin.user.attendances', ['user' => $user->id]) }}">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection