@component('layouts.app', ['title' => 'チャンネル一覧', 'sidebarChannels' => $sidebarChannels])
    <div class="page">
        <div class="page__head">
            <div>
                <h1 class="h-title">チャンネル一覧</h1>
                <p class="t-caption" style="margin-top:4px">公開チャンネルは、ログインしている社員なら誰でも読み書きできます。</p>
            </div>
            <a class="btn btn--primary" href="{{ route('channels.create') }}">
                <svg class="ic" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M8 3.5v9M3.5 8h9"/></svg>
                チャンネルを作る
            </a>
        </div>
        <div class="rows">
            @foreach ($channels as $channel)
                <div class="row">
                    <div class="row__main">
                        <div class="row__title">
                            <span class="sig">
                                @if ($channel->visibility === 'private')
                                    <svg class="ic" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3.25" y="7" width="9.5" height="6.5" rx="1.5"/><path d="M5.5 7V5a2.5 2.5 0 0 1 5 0v2"/></svg>
                                @else
                                    #
                                @endif
                            </span>
                            {{ $channel->name }}
                            @if ($channel->visibility === 'private')
                                <span class="badge badge--private">プライベート</span>
                            @else
                                <span class="badge badge--public">公開</span>
                            @endif
                        </div>
                        <div class="row__sub">{{ $channel->description }}{{ $channel->description ? '　·　' : '' }}作成者：{{ $channel->creator->display_name }}</div>
                    </div>
                    <div class="row__acts">
                        {{-- メンバー管理の導線はこの Issue のスコープ外（別Issue）のため出さない。 --}}
                        @if ($channel->created_by === auth()->id())
                            <a class="btn btn--sm" href="{{ route('channels.edit', $channel) }}">編集</a>
                        @endif
                        <a class="btn btn--sm" href="{{ route('channels.show', $channel) }}">開く</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endcomponent
