<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=1280">
    <title>{{ $title }}｜チームチャットアプリ</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="app">
    <header class="topbar">
        <div class="topbar__brand"><span class="brandmark">チ</span>チームチャットアプリ</div>
        <div class="topbar__search">
            <div class="search-input">
                <svg class="ic" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="7" cy="7" r="4.5"/><path d="M10.5 10.5 14 14" stroke-linecap="round"/></svg>
                <span>メッセージを検索</span>
            </div>
        </div>
        <div class="topbar__me">
            <span class="nm">{{ auth()->user()->display_name }}</span>
            <form method="POST" action="{{ route('logout') }}" style="display:inline">
                @csrf
                <button type="submit" style="background:none;border:0;padding:0;font:inherit;cursor:pointer">ログアウト</button>
            </form>
        </div>
    </header>
    <div class="app__body">
        <nav class="sidebar">
            <div class="side-group">チャンネル</div>
            @foreach (($sidebarChannels ?? []) as $sidebarChannel)
                <a class="side-item @if(isset($channel) && $channel->id === $sidebarChannel->id) is-selected @endif" href="{{ route('channels.show', $sidebarChannel) }}">
                    <span class="sig">
                        @if ($sidebarChannel->visibility === 'private')
                            <svg class="ic" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3.25" y="7" width="9.5" height="6.5" rx="1.5"/><path d="M5.5 7V5a2.5 2.5 0 0 1 5 0v2"/></svg>
                        @else
                            #
                        @endif
                    </span>
                    <span class="nm">{{ $sidebarChannel->name }}</span>
                </a>
            @endforeach
            <div class="side-foot"><a href="{{ route('channels.index') }}">チャンネル一覧を見る</a></div>
        </nav>
        <main class="main main--scroll">
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
