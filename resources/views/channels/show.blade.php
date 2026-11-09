@component('layouts.app', ['title' => $channel->name, 'sidebarChannels' => $sidebarChannels, 'channel' => $channel])
    <div class="ch-head">
        <div class="ch-head__main">
            <div class="ch-head__name">
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
            @if ($channel->description)
                <div class="ch-head__desc">{{ $channel->description }}</div>
            @endif
        </div>
        @if ($channel->created_by === auth()->id())
            <div class="ch-head__acts">
                <a class="btn btn--sm" href="{{ route('channels.edit', $channel) }}">編集</a>
                <a class="btn btn--sm btn--danger" href="{{ route('channels.edit', $channel) }}">削除</a>
            </div>
        @endif
    </div>
    <div class="page">
        <p class="t-caption">メッセージ一覧はこの先の実装単位（Issue）で作ります。</p>
    </div>
@endcomponent
