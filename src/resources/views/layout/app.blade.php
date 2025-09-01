<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>勤怠管理アプリ</title>
  <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
  <link rel="stylesheet" href="{{ asset('css/common.css')}}">
  @yield('css')
</head>

<body>
  <header class="header">
    <img class="header__logo" src="{{ asset('/img/logo.png') }}" alt="コーチテックのロゴ">

    {{-- ナビゲーションメニュー --}}
    <nav class="header__nav">
      <ul class="nav__list">
        <li class="nav__item"><a href="">勤怠</a></li>
        <li class="nav__item"><a href="">勤怠一覧</a></li>
        <li class="nav__item"><a href="">申請</a></li>
        <li class="nav__item">
          <form action="" method="POST">
            @csrf
            <button type="submit" class="logout-button">ログアウト</button>
          </form>
        </li>
      </ul>
    </nav>
      @yield('link')
  </header>
    <div class="content">
      @yield('content')
    </div>
  </div>
</body>
</html>