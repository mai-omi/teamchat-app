<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyChannelRequest;
use App\Http\Requests\StoreChannelRequest;
use App\Http\Requests\UpdateChannelRequest;
use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChannelController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $channels = Channel::with('creator')
            ->visibleTo($user)
            ->orderBy('created_at')
            ->get();

        return view('channels.index', [
            'channels' => $channels,
            'sidebarChannels' => $this->sidebarChannels($user),
        ]);
    }

    public function create(): View
    {
        return view('channels.create', [
            'sidebarChannels' => $this->sidebarChannels(Auth::user()),
        ]);
    }

    public function store(StoreChannelRequest $request): RedirectResponse
    {
        $user = $request->user();

        $channel = Channel::create([
            'name' => $request->string('name')->trim()->toString(),
            'description' => $request->filled('description')
                ? $request->string('description')->trim()->toString()
                : null,
            'visibility' => $request->input('visibility'),
            'created_by' => $user->id,
        ]);

        // 作成者は自動的にメンバーになる。公開チャンネルは会員行を作らない（data.md §2-3）。
        if ($channel->visibility === 'private') {
            $channel->members()->attach($user->id);
        }

        return redirect()->route('channels.show', $channel);
    }

    public function show(Channel $channel): View
    {
        $this->authorize('view', $channel);

        return view('channels.show', [
            'channel' => $channel->load('creator'),
            'sidebarChannels' => $this->sidebarChannels(Auth::user()),
        ]);
    }

    public function edit(Channel $channel): View
    {
        $this->authorize('view', $channel);
        $this->authorize('update', $channel);

        $messageCount = Message::where('channel_id', $channel->id)
            ->whereNull('parent_message_id')
            ->count();
        $replyCount = Message::where('channel_id', $channel->id)
            ->whereNotNull('parent_message_id')
            ->count();

        return view('channels.edit', [
            'channel' => $channel,
            'sidebarChannels' => $this->sidebarChannels(Auth::user()),
            'messageCount' => $messageCount,
            'replyCount' => $replyCount,
        ]);
    }

    public function update(UpdateChannelRequest $request, Channel $channel): RedirectResponse
    {
        $this->authorize('view', $channel);
        $this->authorize('update', $channel);

        $channel->update([
            'name' => $request->string('name')->trim()->toString(),
            'description' => $request->filled('description')
                ? $request->string('description')->trim()->toString()
                : null,
        ]);

        return redirect()->route('channels.show', $channel);
    }

    public function destroy(DestroyChannelRequest $request, Channel $channel): RedirectResponse
    {
        $this->authorize('view', $channel);
        $this->authorize('delete', $channel);

        // チャンネル削除は唯一の物理削除。中のメッセージ・返信・会員もまとめて消える
        // （DB の cascadeOnDelete。data.md §3）。
        $channel->delete();

        return redirect()->route('channels.index');
    }

    /**
     * サイドバーに並べる、利用者から見えるチャンネル（公開すべて＋自分がメンバーのプライベート）。
     *
     * @return Collection<int, Channel>
     */
    private function sidebarChannels(User $user): Collection
    {
        return Channel::visibleTo($user)->orderBy('created_at')->get();
    }
}
