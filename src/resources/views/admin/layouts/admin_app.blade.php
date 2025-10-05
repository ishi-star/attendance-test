<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>勤怠管理アプリ管理者共通部分</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @yield('css')
</head>

<body>
    <header class="header">
        <img class="header__logo" src="{{ asset('/img/logo.png') }}" alt="コーチテックのロゴ">
        {{-- ログイン済みで、かつ管理者である場合のみナビゲーションを表示 --}}
        @if(Auth::check() && optional(Auth::user())->is_admin)
            <nav class="header__nav">
                <ul class="nav__list">
                    <li class="nav__item">
                        <a href="">勤怠一覧</a>
                    </li>
                    <li class="nav__item">
                        <a href="{{ route('admin.users') }}">スタッフ一覧</a>
                    </li>
                    <li class="nav__item">
                        <a href="{{ route('admin.requests') }}">申請一覧</a>
                    </li>
                    <li class="nav__item">
                        <form action="{{ route('admin.logout') }}" method="POST">
                            @csrf
                            <button class="logout-button">ログアウト</button>
                        </form>
                    </li>
                </ul>
            </nav>
            @endif
    </header>

    <main>
        @yield('content')
    </main>
</body>
</html>