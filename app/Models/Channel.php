<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'visibility',
        'created_by',
    ];

    /**
     * 作成者（編集・削除・メンバー管理の権限者。交代なし）。
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * プライベートチャンネルの会員（公開チャンネルは行を持たない。data.md §2-3）。
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * ある利用者から見えるチャンネル（公開すべて ＋ 自分がメンバーのプライベートだけ）に絞る。
     * data.md §0／screens.md §3-0／questions Q-01。
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user) {
            $query->where('visibility', 'public')
                ->orWhereHas('members', function (Builder $query) use ($user) {
                    $query->where('users.id', $user->id);
                });
        });
    }
}
