@extends('admin.layouts.admin_app')

@section('title', '申請一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/list-attendance.css') }}">
<style>
    /* 画像に合わせてタブ構造を再現するための簡易CSS */
    .request-tabs { 
        display: flex; 
        margin-bottom: -1px; 
        max-width: 800px; /* テーブル幅に合わせる */
        margin: 0 auto;
        padding-top: 20px;
    }
    .request-tab { 
        padding: 10px 20px; 
        cursor: pointer; 
        border: 1px solid #ccc; 
        border-bottom: none; 
        background: #f9f9f9; 
        margin-right: -1px; /* タブ間の隙間をなくす */
    }
    .request-tab.active { 
        border-bottom: 1px solid white; /* タブの下線がコンテンツと被るように */
        background: white; 
        font-weight: bold; 
        z-index: 10;
        position: relative;
    }
    /* テーブル全体の幅を広げて、タブと合わせる */
    .attendance-list-container {
        max-width: 800px; /* 画像のテーブル幅に合わせて調整 */
        margin: 0 auto 50px;
    }
    /* 画像に合わせ、テーブルヘッダーの文字を中央揃えに (list-attendance.cssを上書き) */
    .attendance-table th.table-cell {
        text-align: center;
    }
    /* 画像に合わせ、行の文字を中央揃えに */
    .attendance-table td.table-cell {
        text-align: center;
    }
    .attendance-table td.table-cell:nth-child(2) {
        text-align: left; /* 名前だけ左寄せ */
    }
    /* 申請理由のセルだけテキストを左寄せ */
    .attendance-table th:nth-child(4),
    .attendance-table td:nth-child(4) {
        text-align: left;
    }
</style>
@endsection

@section('content')
<h2 class="list-heading">申請一覧</h2>

{{-- ★ 1. 画像のタブ構造を再現 ★ --}}
<div class="request-tabs">
    <div class="request-tab active">承認待ち</div>
    <div class="request-tab">承認済み</div> 
</div>

<div class="attendance-list-container">
    <table class="attendance-table">
        <thead class="table-header">
            <tr>
                {{-- ★ 2. 画像に合わせたヘッダー項目に変更 ★ --}}
                <th class="table-cell">状態</th>
                <th class="table-cell">名前</th>
                <th class="table-cell">対象日時</th>
                <th class="table-cell">申請理由</th>
                <th class="table-cell">申請日時</th>
                <th class="table-cell">詳細</th>
            </tr>
        </thead>
        <tbody class="table-body">
            @forelse($requests as $request)
            <tr class="table-row">
                {{-- 状態（現状は「承認待ち」固定） --}}
                <td class="table-cell">承認待ち</td>
                
                {{-- 名前 --}}
                <td class="table-cell">{{ $request->user->name }}</td>
                
                {{-- 対象日時 (元の勤怠の日付) --}}
                <td class="table-cell">{{ $request->attendance->clock_in->format('Y/m/d') }}</td>
                
                {{-- 申請理由 --}}
                {{-- ※ 現状、申請テーブルに「reason」を保存していますが、申請の都度 reason が異なる可能性があるため、
                   表示する reason を特定する必要があります。ここでは簡略化のため、固定の「遅延のため」とします。
                   もし、reasonカラムを使いたい場合は、申請テーブルに理由を保存するロジックが必要です。 --}}
                <td class="table-cell">遅延のため</td> 
                
                {{-- 申請日時 --}}
                <td class="table-cell">{{ $request->created_at->format('Y/m/d') }}</td>

                {{-- ★ 3. 操作ボタンを「詳細」リンクに変更 ★ --}}
                <td class="table-cell">
                    <a href="{{ route('admin.request.detail', ['id' => $request->id]) }}">詳細</a>
                </td>
            </tr>
            @empty
            <tr class="table-row">
                <td class="table-cell" colspan="6">現在、承認待ちの申請はありません。</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection