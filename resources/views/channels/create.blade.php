@component('layouts.app', ['title' => 'チャンネルを作る', 'sidebarChannels' => $sidebarChannels])
    <div class="page" style="max-width:680px">
        <h1 class="h-title" style="margin-bottom:var(--sp-6)">チャンネルを作る</h1>
        <div class="card">
            <div class="card__body">
                <form method="POST" action="{{ route('channels.store') }}">
                    @csrf
                    <div class="field @error('name') is-error @enderror">
                        <label class="label">チャンネル名<span class="req">*</span></label>
                        <input class="input" type="text" name="name" id="name" maxlength="50" value="{{ old('name') }}">
                        <div class="field-foot">
                            @error('name')
                                <span class="err">{{ $message }}</span>
                            @enderror
                            <span class="count" id="name_count">{{ mb_strlen(old('name', '')) }} / 50</span>
                        </div>
                    </div>
                    <div class="field @error('description') is-error @enderror">
                        <label class="label">説明</label>
                        <textarea class="textarea" name="description" id="description" maxlength="200">{{ old('description') }}</textarea>
                        <div class="field-foot">
                            @error('description')
                                <span class="err">{{ $message }}</span>
                            @else
                                <span class="help">何について話す場所かを短く書きます。</span>
                            @enderror
                            <span class="count" id="description_count">{{ mb_strlen(old('description', '')) }} / 200</span>
                        </div>
                    </div>
                    <div class="field @error('visibility') is-error @enderror">
                        <span class="label">公開範囲<span class="req">*</span></span>
                        <div class="radio-set">
                            <label class="radio @if(old('visibility', 'public') === 'public') is-checked @endif">
                                <input type="radio" name="visibility" value="public" @checked(old('visibility', 'public') === 'public')>
                                <span><span class="rt">公開</span><span class="rd" style="display:block">ログインしている社員なら誰でも読み書きできます。</span></span>
                            </label>
                            <label class="radio @if(old('visibility') === 'private') is-checked @endif">
                                <input type="radio" name="visibility" value="private" @checked(old('visibility') === 'private')>
                                <span><span class="rt">プライベート</span><span class="rd" style="display:block">追加された人だけが読み書きできます。作成者がメンバーを管理します。</span></span>
                            </label>
                        </div>
                        @error('visibility')
                            <span class="err">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="inline-2" style="justify-content:flex-end;margin-top:var(--sp-6)">
                        <a class="btn" href="{{ route('channels.index') }}">キャンセル</a>
                        <button class="btn btn--primary" type="submit">作成する</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            function bindCounter(inputId, countId, max) {
                var input = document.getElementById(inputId);
                var count = document.getElementById(countId);
                if (!input || !count) return;
                function update() {
                    var len = input.value.length;
                    count.textContent = len + ' / ' + max;
                    count.classList.toggle('is-over', len > max);
                }
                input.addEventListener('input', update);
                update();
            }
            bindCounter('name', 'name_count', 50);
            bindCounter('description', 'description_count', 200);

            document.querySelectorAll('input[name="visibility"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    document.querySelectorAll('.radio').forEach(function (label) {
                        label.classList.remove('is-checked');
                    });
                    radio.closest('.radio').classList.add('is-checked');
                });
            });
        })();
    </script>
@endcomponent
