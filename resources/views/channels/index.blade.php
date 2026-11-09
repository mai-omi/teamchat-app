<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>チャンネル一覧｜チームチャットアプリ</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="page">
    <div class="page__head">
        <h1 class="h-title">チャンネル一覧</h1>
    </div>
    <p class="t-muted">{{ auth()->user()->display_name }} さんとしてログイン中です。</p>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn" type="submit">ログアウト</button>
    </form>
</div>
</body>
</html>
