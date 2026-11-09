@component('layouts.app', ['title' => 'チャンネルを編集する', 'sidebarChannels' => $sidebarChannels, 'channel' => $channel])
    <div class="page" style="max-width:680px">
        <h1 class="h-title" style="margin-bottom:var(--sp-6)">チャンネルを編集する</h1>
        <div class="card" style="margin-bottom:var(--sp-8)">
            <div class="card__body">
                <form method="POST" action="{{ route('channels.update', $channel) }}">
                    @csrf
                    @method('PATCH')
                    <div class="field @error('name') is-error @enderror">
                        <label class="label">チャンネル名<span class="req">*</span></label>
                        <input class="input" type="text" name="name" id="name" maxlength="50" value="{{ old('name', $channel->name) }}">
                        <div class="field-foot">
                            @error('name')
                                <span class="err">{{ $message }}</span>
                            @else
                                <span class="help">
                                    @if ($channel->visibility === 'public')
                                        公開チャンネルです。公開範囲はあとから変更できません。
                                    @else
                                        プライベートチャンネルです。公開範囲はあとから変更できません。
                                    @endif
                                </span>
                            @enderror
                            <span class="count" id="name_count">{{ mb_strlen(old('name', $channel->name)) }} / 50</span>
                        </div>
                    </div>
                    <div class="field @error('description') is-error @enderror">
                        <label class="label">説明</label>
                        <textarea class="textarea" name="description" id="description" maxlength="200">{{ old('description', $channel->description) }}</textarea>
                        <div class="field-foot">
                            @error('description')
                                <span class="err">{{ $message }}</span>
                            @else
                                <span class="help"></span>
                            @enderror
                            <span class="count" id="description_count">{{ mb_strlen(old('description', $channel->description)) }} / 200</span>
                        </div>
                    </div>
                    <div class="inline-2" style="justify-content:flex-end;margin-top:var(--sp-6)">
                        <a class="btn" href="{{ route('channels.show', $channel) }}">キャンセル</a>
                        <button class="btn btn--primary" type="submit">保存する</button>
                    </div>
                </form>
            </div>
        </div>

        <h2 class="h-heading" style="margin-bottom:var(--sp-3)">チャンネルを削除する</h2>
        <div class="danger-box">
            <div class="spread" style="margin-bottom:var(--sp-4)" id="delete-summary">
                <div>
                    <div class="t-label" style="color:var(--danger)">このチャンネルを削除します</div>
                    <div class="t-caption">メッセージと返信もすべて削除され、元に戻せません。</div>
                </div>
                <button class="btn btn--danger" type="button" id="delete-open">削除する</button>
            </div>
            <div class="card" style="border-color:var(--danger-line); {{ $errors->has('confirm_name') ? '' : 'display:none' }}" id="delete-confirm">
                <div class="card__body">
                    <div class="h-heading" style="margin-bottom:var(--sp-2)">「{{ $channel->name }}」を削除しますか？</div>
                    <p class="t-body t-muted" style="margin-bottom:var(--sp-4)">メッセージ {{ $messageCount }}件と返信 {{ $replyCount }}件も削除されます。この操作は取り消せません。確認のためチャンネル名を入力してください。</p>
                    <form method="POST" action="{{ route('channels.destroy', $channel) }}">
                        @csrf
                        @method('DELETE')
                        <div class="field @error('confirm_name') is-error @enderror" style="margin-bottom:0">
                            <input class="input" name="confirm_name" placeholder="{{ $channel->name }}" style="max-width:280px">
                            @error('confirm_name')
                                <span class="err">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="inline-2" style="justify-content:flex-end;margin-top:var(--sp-5)">
                            <button class="btn" type="button" id="delete-cancel">やめる</button>
                            <button class="btn btn--danger-solid" type="submit">削除する</button>
                        </div>
                    </form>
                </div>
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

            var openBtn = document.getElementById('delete-open');
            var cancelBtn = document.getElementById('delete-cancel');
            var confirmBox = document.getElementById('delete-confirm');
            if (openBtn && confirmBox) {
                openBtn.addEventListener('click', function () {
                    confirmBox.style.display = '';
                });
            }
            if (cancelBtn && confirmBox) {
                cancelBtn.addEventListener('click', function () {
                    confirmBox.style.display = 'none';
                });
            }
        })();
    </script>
@endcomponent
