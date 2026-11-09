<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['display_name' => '佐藤 太郎']);
        $this->otherUser = User::factory()->create(['display_name' => '鈴木 花子']);
    }

    // TV-2-01 ---------------------------------------------------------

    public function test_公開チャンネルを作成すると作成者のメンバー行は作られない(): void
    {
        $response = $this->actingAs($this->user)->post(route('channels.store'), [
            'name' => '全体連絡',
            'description' => '全員に共有したいこと',
            'visibility' => 'public',
        ]);

        $channel = Channel::where('name', '全体連絡')->first();
        $response->assertRedirect(route('channels.show', $channel));
        $this->assertDatabaseCount('channel_user', 0);
    }

    public function test_プライベートチャンネルを作成すると作成者が自動でメンバーになる(): void
    {
        $response = $this->actingAs($this->user)->post(route('channels.store'), [
            'name' => '採用プロジェクト',
            'description' => '採用まわりの進行',
            'visibility' => 'private',
        ]);

        $channel = Channel::where('name', '採用プロジェクト')->first();
        $response->assertRedirect(route('channels.show', $channel));
        $this->assertDatabaseHas('channel_user', [
            'channel_id' => $channel->id,
            'user_id' => $this->user->id,
        ]);
    }

    // TV-2-02 ---------------------------------------------------------

    public function test_一覧には公開チャンネルすべてと自分が会員のプライベートだけが並ぶ(): void
    {
        $publicChannel = Channel::factory()->create(['visibility' => 'public']);
        $myPrivateChannel = Channel::factory()->private()->create(['created_by' => $this->user->id]);
        $myPrivateChannel->members()->attach($this->user->id);
        $othersPrivateChannel = Channel::factory()->private()->create(['created_by' => $this->otherUser->id]);
        $othersPrivateChannel->members()->attach($this->otherUser->id);

        $response = $this->actingAs($this->user)->get(route('channels.index'));

        $response->assertOk();
        $response->assertSee($publicChannel->name);
        $response->assertSee($myPrivateChannel->name);
        $response->assertDontSee($othersPrivateChannel->name);
    }

    // TV-2-03 ---------------------------------------------------------

    public function test_チャンネル名が50字ちょうどなら作成できる(): void
    {
        $name = str_repeat('あ', 50);

        $response = $this->actingAs($this->user)->post(route('channels.store'), [
            'name' => $name,
            'visibility' => 'public',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('channels', ['name' => $name]);
    }

    public function test_チャンネル名が51字だと作成できない(): void
    {
        $name = str_repeat('あ', 51);

        $response = $this->actingAs($this->user)->post(route('channels.store'), [
            'name' => $name,
            'visibility' => 'public',
        ]);

        $response->assertSessionHasErrors(['name' => 'チャンネル名は50文字以内で入力してください']);
        $this->assertDatabaseCount('channels', 0);
    }

    public function test_説明が200字ちょうどなら作成できる(): void
    {
        $description = str_repeat('あ', 200);

        $response = $this->actingAs($this->user)->post(route('channels.store'), [
            'name' => '開発',
            'description' => $description,
            'visibility' => 'public',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('channels', ['description' => $description]);
    }

    public function test_説明が201字だと作成できない(): void
    {
        $description = str_repeat('あ', 201);

        $response = $this->actingAs($this->user)->post(route('channels.store'), [
            'name' => '開発',
            'description' => $description,
            'visibility' => 'public',
        ]);

        $response->assertSessionHasErrors(['description' => '説明は200文字以内で入力してください']);
        $this->assertDatabaseCount('channels', 0);
    }

    public function test_説明が空でも作成できる(): void
    {
        $response = $this->actingAs($this->user)->post(route('channels.store'), [
            'name' => '雑談',
            'description' => '',
            'visibility' => 'public',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('channels', ['name' => '雑談', 'description' => null]);
    }

    public function test_チャンネル名が空白と改行だけだと空として弾かれる(): void
    {
        $response = $this->actingAs($this->user)->post(route('channels.store'), [
            'name' => "   \n\n  ",
            'visibility' => 'public',
        ]);

        $response->assertSessionHasErrors(['name' => 'チャンネル名を入力してください']);
        $this->assertDatabaseCount('channels', 0);
    }

    // TV-2-04 ---------------------------------------------------------

    public function test_同じ名前の公開チャンネルがあると作成できない(): void
    {
        Channel::factory()->create(['name' => '開発', 'visibility' => 'public']);

        $response = $this->actingAs($this->user)->post(route('channels.store'), [
            'name' => '開発',
            'visibility' => 'public',
        ]);

        $response->assertSessionHasErrors(['name' => '同じ名前のチャンネルがすでにあります']);
        $this->assertDatabaseCount('channels', 1);
    }

    public function test_見えないプライベートと同じ名前は可視範囲外なので作成できる(): void
    {
        $othersPrivateChannel = Channel::factory()->private()->create(['name' => '採用プロジェクト', 'created_by' => $this->otherUser->id]);
        $othersPrivateChannel->members()->attach($this->otherUser->id);

        $response = $this->actingAs($this->user)->post(route('channels.store'), [
            'name' => '採用プロジェクト',
            'visibility' => 'public',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseCount('channels', 2);
    }

    public function test_編集で他のチャンネルと同じ名前にすると弾かれる(): void
    {
        Channel::factory()->create(['name' => '雑談', 'visibility' => 'public']);
        $channel = Channel::factory()->create(['name' => '開発', 'visibility' => 'public', 'created_by' => $this->user->id]);

        $response = $this->actingAs($this->user)->patch(route('channels.update', $channel), [
            'name' => '雑談',
            'description' => null,
        ]);

        $response->assertSessionHasErrors(['name' => '同じ名前のチャンネルがすでにあります']);
        $this->assertDatabaseHas('channels', ['id' => $channel->id, 'name' => '開発']);
    }

    // TV-2-05 ---------------------------------------------------------

    public function test_作成者が名前と説明を編集すると保存される(): void
    {
        $channel = Channel::factory()->create(['name' => '開発', 'description' => '旧説明', 'visibility' => 'public', 'created_by' => $this->user->id]);

        $response = $this->actingAs($this->user)->patch(route('channels.update', $channel), [
            'name' => '開発チーム',
            'description' => '新しい説明',
        ]);

        $response->assertRedirect(route('channels.show', $channel));
        $this->assertDatabaseHas('channels', [
            'id' => $channel->id,
            'name' => '開発チーム',
            'description' => '新しい説明',
        ]);
    }

    public function test_編集で公開範囲を送っても変更されない(): void
    {
        $channel = Channel::factory()->create(['name' => '開発', 'visibility' => 'public', 'created_by' => $this->user->id]);

        $this->actingAs($this->user)->patch(route('channels.update', $channel), [
            'name' => '開発',
            'description' => null,
            'visibility' => 'private',
        ]);

        $this->assertDatabaseHas('channels', ['id' => $channel->id, 'visibility' => 'public']);
    }

    public function test_作成者以外が編集しようとすると403になる(): void
    {
        $channel = Channel::factory()->create(['visibility' => 'public', 'created_by' => $this->user->id]);

        $response = $this->actingAs($this->otherUser)->get(route('channels.edit', $channel));

        $response->assertForbidden();
    }

    public function test_作成者以外が更新しようとすると403になる(): void
    {
        $channel = Channel::factory()->create(['name' => '開発', 'visibility' => 'public', 'created_by' => $this->user->id]);

        $response = $this->actingAs($this->otherUser)->patch(route('channels.update', $channel), [
            'name' => '乗っ取り',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('channels', ['id' => $channel->id, 'name' => '開発']);
    }

    // TV-2-06 ---------------------------------------------------------

    public function test_削除確認でチャンネル名が一致すると削除される(): void
    {
        $channel = Channel::factory()->create(['name' => '開発', 'visibility' => 'public', 'created_by' => $this->user->id]);

        $response = $this->actingAs($this->user)->delete(route('channels.destroy', $channel), [
            'confirm_name' => '開発',
        ]);

        $response->assertRedirect(route('channels.index'));
        $this->assertDatabaseMissing('channels', ['id' => $channel->id]);
    }

    public function test_削除確認でチャンネル名が一致しないと削除されない(): void
    {
        $channel = Channel::factory()->create(['name' => '開発', 'visibility' => 'public', 'created_by' => $this->user->id]);

        $response = $this->actingAs($this->user)->delete(route('channels.destroy', $channel), [
            'confirm_name' => '違う名前',
        ]);

        $response->assertSessionHasErrors(['confirm_name' => 'チャンネル名が一致しません']);
        $this->assertDatabaseHas('channels', ['id' => $channel->id]);
    }

    // TV-2-07 ---------------------------------------------------------

    public function test_チャンネル削除でメッセージと返信と会員もまとめて削除される(): void
    {
        $channel = Channel::factory()->private()->create(['name' => '採用プロジェクト', 'created_by' => $this->user->id]);
        $channel->members()->attach([$this->user->id, $this->otherUser->id]);

        $parentMessage = Message::factory()->create(['channel_id' => $channel->id, 'user_id' => $this->user->id]);
        Message::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $this->otherUser->id,
            'parent_message_id' => $parentMessage->id,
        ]);

        $response = $this->actingAs($this->user)->delete(route('channels.destroy', $channel), [
            'confirm_name' => '採用プロジェクト',
        ]);

        $response->assertRedirect(route('channels.index'));
        $this->assertDatabaseMissing('channels', ['id' => $channel->id]);
        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseCount('channel_user', 0);
    }

    // TV-2-08 ---------------------------------------------------------

    public function test_見えないプライベートチャンネルを開こうとすると404になる(): void
    {
        $channel = Channel::factory()->private()->create(['created_by' => $this->otherUser->id]);
        $channel->members()->attach($this->otherUser->id);

        $response = $this->actingAs($this->user)->get(route('channels.show', $channel));

        $response->assertNotFound();
    }

    public function test_見えないプライベートチャンネルの編集は404になる(): void
    {
        $channel = Channel::factory()->private()->create(['created_by' => $this->otherUser->id]);
        $channel->members()->attach($this->otherUser->id);

        $response = $this->actingAs($this->user)->get(route('channels.edit', $channel));

        $response->assertNotFound();
    }

    public function test_見えないプライベートチャンネルの更新は404になる(): void
    {
        $channel = Channel::factory()->private()->create(['name' => '採用プロジェクト', 'created_by' => $this->otherUser->id]);
        $channel->members()->attach($this->otherUser->id);

        $response = $this->actingAs($this->user)->patch(route('channels.update', $channel), [
            'name' => '乗っ取り',
        ]);

        $response->assertNotFound();
    }

    public function test_見えないプライベートチャンネルの削除は404になる(): void
    {
        $channel = Channel::factory()->private()->create(['name' => '採用プロジェクト', 'created_by' => $this->otherUser->id]);
        $channel->members()->attach($this->otherUser->id);

        $response = $this->actingAs($this->user)->delete(route('channels.destroy', $channel), [
            'confirm_name' => '採用プロジェクト',
        ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('channels', ['id' => $channel->id]);
    }

    public function test_見えるチャンネルで作成者以外が削除しようとすると403になる(): void
    {
        $channel = Channel::factory()->create(['name' => '開発', 'visibility' => 'public', 'created_by' => $this->user->id]);

        $response = $this->actingAs($this->otherUser)->delete(route('channels.destroy', $channel), [
            'confirm_name' => '開発',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('channels', ['id' => $channel->id]);
    }

    // AC-2-1..4 直接確認 ------------------------------------------------

    public function test_作成すると自分がそのチャンネルのメンバーになっている(): void
    {
        $response = $this->actingAs($this->user)->post(route('channels.store'), [
            'name' => '採用プロジェクト2',
            'visibility' => 'private',
        ]);

        $channel = Channel::where('name', '採用プロジェクト2')->first();
        $response->assertRedirect(route('channels.show', $channel));
        $this->assertTrue($channel->members()->where('users.id', $this->user->id)->exists());
    }

    public function test_未ログインでチャンネル作成画面を開こうとするとログイン画面に送られる(): void
    {
        $response = $this->get(route('channels.create'));

        $response->assertRedirect(route('login'));
    }
}
