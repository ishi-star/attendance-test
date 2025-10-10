@extends('admin.layouts.admin_app')

@section('title', '申請一覧画面')

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
        margin-right: -1px;
    }
    .request-tab.active { 
        border-bottom: 1px solid white;
        background: white; 
        font-weight: bold; 
        z-index: 10;
        position: relative;
    }
    .attendance-list-container {
        max-width: 800px;
        margin: 0 auto 50px;
    }
    .attendance-table th.table-cell {
        text-align: center;
    }
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
     /* 承認済みテーブルを非表示にする初期設定 */
    #approved-requests {
        display: none;
    }
</style>
@endsection

@section('content')
<h2 class="list-heading">申請一覧</h2>

{{-- タブ構造 --}}
<div class="request-tabs">
    <div class="request-tab active" data-tab="pending">承認待ち</div>
    <div class="request-tab" data-tab="approved">承認済み</div> 
</div>

<div class="attendance-list-container">
    {{-- 承認待ちテーブル --}}
    <div id="pending-requests">
        <table class="attendance-table">
            <thead class="table-header">
                <tr>
                    <th class="table-cell">状態</th>
                    <th class="table-cell">名前</th>
                    <th class="table-cell">対象日時</th>
                    <th class="table-cell">申請理由</th>
                    <th class="table-cell">申請日時</th>
                    <th class="table-cell">詳細</th>
                </tr>
            </thead>
            <tbody class="table-body">
                @forelse($groupedPendingRequests as $group)
                <tr class="table-row">
                    <td class="table-cell">承認待ち</td>
                    <td class="table-cell">{{ $group['user']->name }}</td>
                    <td class="table-cell">{{ $group['date'] }}</td>
                    <td class="table-cell">{{ $group['reason'] }}</td> 
                    <td class="table-cell">{{ $group['requests']->first()->created_at->format('Y/m/d') }}</td>
                    <td class="table-cell">
                        <a href="{{ route('admin.request.detail', ['id' => $group['requests']->first()->id]) }}">詳細</a>
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

    {{-- 承認済みテーブル --}}
    <div id="approved-requests">
        <table class="attendance-table">
            <thead class="table-header">
                <tr>
                    <th class="table-cell">状態</th>
                    <th class="table-cell">名前</th>
                    <th class="table-cell">対象日時</th>
                    <th class="table-cell">申請理由</th>
                    <th class="table-cell">承認日時</th> {{-- ヘッダーを「承認日時」に変更 --}}
                    <th class="table-cell">詳細</th>
                </tr>
            </thead>
            <tbody class="table-body">
                @forelse($groupedApprovedRequests as $group)
                <tr class="table-row">
                    <td class="table-cell">承認済み</td>
                    <td class="table-cell">{{ $group['user']->name }}</td>
                    <td class="table-cell">{{ $group['date'] }}</td>
                    <td class="table-cell">{{ $group['reason'] }}</td> 
                    <td class="table-cell">{{ $group['requests']->first()->updated_at->format('Y/m/d') }}</td> {{-- 承認済みの最終更新日を表示 --}}
                    <td class="table-cell">
                        <a href="{{ route('admin.request.detail', ['id' => $group['requests']->first()->id]) }}">詳細</a>
                    </td>
                </tr>
                @empty
                <tr class="table-row">
                    <td class="table-cell" colspan="6">承認済みの申請はありません。</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        console.log("JavaScript is loading!"); // ★★★ これを追加 ★★★

        const tabs = document.querySelectorAll('.request-tab');
        const pendingContainer = document.getElementById('pending-requests');
        const approvedContainer = document.getElementById('approved-requests');

                // ★ 初期表示の状態を強制 (CSSで隠れていない場合の対策) ★
        if (pendingContainer && approvedContainer) {
            pendingContainer.style.display = 'block';
            approvedContainer.style.display = 'none';
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                console.log("Tab clicked:", this.getAttribute('data-tab')); // ★★★ これを追加 ★★★
                
                // 1. タブのアクティブ状態を切り替え
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                const targetTab = this.getAttribute('data-tab');

                // 2. 表示するコンテンツを切り替え
                if (targetTab === 'pending') {
                    pendingContainer.style.display = 'block';
                    approvedContainer.style.display = 'none';
                } else if (targetTab === 'approved') {
                    pendingContainer.style.display = 'none';
                    approvedContainer.style.display = 'block';
                }
            });
        });
    });
</script>
@endsection