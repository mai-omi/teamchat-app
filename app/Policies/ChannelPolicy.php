<?php

namespace App\Policies;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ChannelPolicy
{
    /**
     * 公開チャンネルはログイン社員なら誰でも見られる。プライベートは会員のみ。
     *
     * 見られないプライベートの非会員には、権限不足（403）ではなく
     * 「存在しない」（404）を返す（存在を隠す。behavior.md §3-0／questions Q-01）。
     * そのため ModelNotFoundException を投げる（false を返すと 403 になってしまう）。
     */
    public function view(User $user, Channel $channel): bool
    {
        if ($channel->visibility === 'public') {
            return true;
        }

        if ($channel->members()->where('users.id', $user->id)->exists()) {
            return true;
        }

        throw new ModelNotFoundException;
    }

    /**
     * 編集は作成者のみ。見えるチャンネルでの権限不足は 403（view が先に通っているので、
     * ここに到達した時点で見えないプライベートの 404 は済んでいる）。
     */
    public function update(User $user, Channel $channel): bool
    {
        return $channel->created_by === $user->id;
    }

    /**
     * 削除も作成者のみ。
     */
    public function delete(User $user, Channel $channel): bool
    {
        return $channel->created_by === $user->id;
    }
}
