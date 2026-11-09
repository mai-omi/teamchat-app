<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'display_name' => '佐藤 太郎',
            'email' => 'sato@example.com',
            'password' => 'password123',
        ]);
    }

    public function test_正しい入力で登録するとログイン済みでチャンネル一覧が開く(): void
    {
        $response = $this->post(route('register.store'), [
            'display_name' => '鈴木 花子',
            'email' => 'suzuki@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('channels.index'));
        $this->assertAuthenticated();
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', [
            'display_name' => '鈴木 花子',
            'email' => 'suzuki@example.com',
        ]);
    }

    public function test_登録済みのメールアドレスで登録しようとするとエラーになりアカウントは作られない(): void
    {
        $response = $this->post(route('register.store'), [
            'display_name' => '鈴木 花子',
            'email' => $this->user->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'このメールアドレスは登録済みです']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_メールアドレスの形式が不正だと登録できない(): void
    {
        $response = $this->post(route('register.store'), [
            'display_name' => '鈴木 花子',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスの形式が正しくありません']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_確認用パスワードが一致しないと登録できない(): void
    {
        $response = $this->post(route('register.store'), [
            'display_name' => '鈴木 花子',
            'email' => 'suzuki@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors(['password' => '確認用のパスワードが一致しません']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_表示名が30字ちょうどなら登録できる(): void
    {
        $name = str_repeat('あ', 30);

        $response = $this->post(route('register.store'), [
            'display_name' => $name,
            'email' => 'suzuki@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('channels.index'));
        $this->assertDatabaseHas('users', ['display_name' => $name]);
    }

    public function test_表示名が31字だと登録できない(): void
    {
        $name = str_repeat('あ', 31);

        $response = $this->post(route('register.store'), [
            'display_name' => $name,
            'email' => 'suzuki@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['display_name' => '表示名は30文字以内で入力してください']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_パスワードが8字ちょうどなら登録できる(): void
    {
        $response = $this->post(route('register.store'), [
            'display_name' => '鈴木 花子',
            'email' => 'suzuki@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response->assertRedirect(route('channels.index'));
    }

    public function test_パスワードが7字だと登録できない(): void
    {
        $response = $this->post(route('register.store'), [
            'display_name' => '鈴木 花子',
            'email' => 'suzuki@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_表示名が空白と改行だけだと空として弾かれる(): void
    {
        $response = $this->post(route('register.store'), [
            'display_name' => "   \n\n  ",
            'email' => 'suzuki@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['display_name' => '表示名を入力してください']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_登録済みのメールアドレスと正しいパスワードでログインするとチャンネル一覧が開く(): void
    {
        $response = $this->post(route('login.store'), [
            'email' => $this->user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('channels.index'));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_パスワードが間違っているとメールアドレスまたはパスワードが正しくありませんが出て入れない(): void
    {
        $response = $this->post(route('login.store'), [
            'email' => $this->user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスまたはパスワードが正しくありません']);
        $this->assertGuest();
    }

    public function test_メールアドレスが空でもログイン失敗は単一のエラー文になる(): void
    {
        $response = $this->post(route('login.store'), [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスまたはパスワードが正しくありません']);
        $this->assertGuest();
    }

    public function test_ログアウトすると未ログインに戻り保護ページはログイン画面に送られる(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();

        $response = $this->get(route('channels.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_ログイン済みでログイン画面を開くとチャンネル一覧に戻される(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('login'));

        $response->assertRedirect(route('channels.index'));
    }

    public function test_ログイン済みで新規登録画面を開くとチャンネル一覧に戻される(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('register'));

        $response->assertRedirect(route('channels.index'));
    }

    public function test_未ログインでチャンネル一覧を開こうとするとログイン画面に送られる(): void
    {
        $response = $this->get(route('channels.index'));

        $response->assertRedirect(route('login'));
    }
}
