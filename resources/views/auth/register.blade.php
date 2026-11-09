@component('layouts.auth', ['title' => '新規登録'])
    <form method="POST" action="{{ route('register.store') }}">
        @csrf
        <div class="field @error('display_name') is-error @enderror">
            <label class="label">表示名<span class="req">*</span></label>
            <input class="input" type="text" name="display_name" id="display_name" maxlength="30" value="{{ old('display_name') }}">
            <div class="field-foot">
                <span class="help">メッセージに表示される名前です。</span>
                <span class="count" id="display_name_count">0 / 30</span>
            </div>
            @error('display_name')
                <span class="err">{{ $message }}</span>
            @enderror
        </div>
        <div class="field @error('email') is-error @enderror">
            <label class="label">メールアドレス<span class="req">*</span></label>
            <input class="input" type="email" name="email" value="{{ old('email') }}">
            @error('email')
                <span class="err">{{ $message }}</span>
            @enderror
        </div>
        <div class="field @error('password') is-error @enderror">
            <label class="label">パスワード<span class="req">*</span></label>
            <input class="input" type="password" name="password">
            <span class="help">8文字以上で入力してください。</span>
            @error('password')
                <span class="err">{{ $message }}</span>
            @enderror
        </div>
        <div class="field">
            <label class="label">パスワード（確認用）<span class="req">*</span></label>
            <input class="input" type="password" name="password_confirmation">
        </div>
        <button class="btn btn--primary btn--lg btn--block" type="submit">登録する</button>
    </form>
    <div class="auth__foot">すでにアカウントをお持ちの方は <a href="{{ route('login') }}">ログイン</a></div>

    <script>
        (function () {
            var input = document.getElementById('display_name');
            var count = document.getElementById('display_name_count');
            if (!input || !count) return;
            var max = 30;
            function update() {
                var len = input.value.length;
                count.textContent = len + ' / ' + max;
                count.classList.toggle('is-over', len > max);
            }
            input.addEventListener('input', update);
            update();
        })();
    </script>
@endcomponent
