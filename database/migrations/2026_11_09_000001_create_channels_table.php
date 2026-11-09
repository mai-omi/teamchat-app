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
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            // チャンネル名の一意性は DB の UNIQUE では持たない（可視範囲内の一意性はアプリ側で判定。data.md §0・§2-2）。
            $table->string('name', 50);
            $table->string('description', 200)->nullable();
            $table->enum('visibility', ['public', 'private']);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
