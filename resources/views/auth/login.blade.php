@component('layouts.auth', ['title' => 'ログイン'])
    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="field">
            <label class="label">メールアドレス</label>
            <input class="input" type="email" name="email" value="{{ old('email') }}">
        </div>
        <div class="field @error('email') is-error @enderror">
            <label class="label">パスワード</label>
            <input class="input" type="password" name="password">
            @error('email')
                <span class="err">{{ $message }}</span>
            @enderror
        </div>
        <button class="btn btn--primary btn--lg btn--block" type="submit">ログイン</button>
    </form>
    <div class="auth__foot">アカウントをお持ちでない方は <a href="{{ route('register') }}">新規登録</a></div>
@endcomponent
