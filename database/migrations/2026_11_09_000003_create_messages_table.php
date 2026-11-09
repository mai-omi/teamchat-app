<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            // NULL=チャンネル直下 / 値あり=その親への返信（1段のみ）。data.md §2-4。
            $table->foreignId('parent_message_id')->nullable()->constrained('messages')->cascadeOnDelete();
            $table->text('body');
            // 非NULL=編集済み。標準の SoftDeletes トレイトは使わず、deleted_at はこのテーブル専用の削除マーカーとして扱う。
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
