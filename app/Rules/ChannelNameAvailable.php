<?php

namespace App\Rules;

use App\Models\Channel;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * チャンネル名の一意性は DB の UNIQUE ではなく、可視範囲内（公開すべて＋自分がメンバーの
 * プライベート）でアプリ側が判定する（見えない private の存在を漏らさないため。
 * data.md §0・§2-2）。
 */
class ChannelNameAvailable implements ValidationRule
{
    public function __construct(
        private readonly User $user,
        private readonly ?int $exceptChannelId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = Channel::query()
            ->visibleTo($this->user)
            ->where('name', $value);

        if ($this->exceptChannelId !== null) {
            $query->whereKeyNot($this->exceptChannelId);
        }

        if ($query->exists()) {
            $fail('同じ名前のチャンネルがすでにあります');
        }
    }
}
