<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}｜チームチャットアプリ</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="auth">
    <div class="auth__card">
        <div class="auth__brand"><span class="brandmark">チ</span>チームチャットアプリ</div>
        <h1 class="h-display" style="text-align:center;margin-bottom:var(--sp-6);font-size:var(--fs-title)">{{ $title }}</h1>
        {{ $slot }}
    </div>
</div>
</body>
</html>
