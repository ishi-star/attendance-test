<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>勤怠管理アプリ共通部分</title>
  <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
  <link rel="stylesheet" href="{{ asset('css/app.css')}}">
  @yield('css')
</head>

<body>
  <header class="header">
    <img class="header__logo" src="{{ asset('/img/logo.png') }}" alt="コーチテックのロゴ">

    {{-- ナビゲーションメニュー --}}
    @if(Auth::check()) {{-- ログインしている場合のみ表示 --}}
    <nav class="header__nav">
      <ul class="nav__list">
        <li class="nav__item"><a href="/attendance">勤怠</a></li>
        <li class="nav__item"><a href="/attendance/list">勤怠一覧</a></li>
        <li class="nav__item"><a href="{{ route('request.list') }}">申請</a></li>
        <li class="nav__item">
          <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="logout-button">ログアウト</button>
          </form>
        </li>
      </ul>
    </nav>
    @endif
    @yield('link')
  </header>
  <div class="content">
    @yield('content')
  </div>
  @yield('scripts')
</body>
</html>