<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * チャンネル直下のメッセージとその返信（1段のみ）を1テーブルで表す。data.md §2-4。
 *
 * この Issue ではスキーマ・最小限のモデルのみを用意する（コントローラ・ルート・ポリシーは別Issue）。
 * `deleted_at` は本文を保持したまま立てる削除マーカーであり、Laravel 標準の SoftDeletes トレイトは使わない
 * （CLAUDE.md・data.md §0・§2-4）。
 */
class Message extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'channel_id',
        'user_id',
        'parent_message_id',
        'body',
        'edited_at',
        'deleted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 返信（parent_message_id が非NULL）の場合の、親となる直下メッセージ。
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_message_id');
    }

    /**
     * この（直下）メッセージへの返信。削除済みの返信も含む（Q-16）。
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'parent_message_id');
    }
}
