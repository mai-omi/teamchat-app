---
name: webapp-uat
description: >
  Playwright MCP を使って Web アプリの受け入れ条件を 1件ずつブラウザで確認し、
  スクリーンショット付きの検証レポートを作成するスキル。
  次のような依頼が来たらこのスキルを使う：
  - 「Playwright で〜を確かめて」「ブラウザで動作確認して」
  - 箇条書きの受け入れ条件を貼り付けて「確認して」と頼む
  - 「UAT を実施して」「スクリーンショットを撮りながら検証して」
  - 「usera / userb でログインして〜が動くか確認して」
argument-hint: "<受け入れ条件のテキスト> または <受け入れ条件が書かれたファイルのパス>"
---

# Webapp UAT スキル

## 目的

Playwright MCP でブラウザを操作し、受け入れ条件を 1件ずつ検証する。
各条件について **満たしている / 満たしていない** を判定し、根拠とスクリーンショットをセットで報告する。

---

## 最初にやること：受け入れ条件を取得する

引数（`$ARGUMENTS`）の内容によって次のどちらかを行う：

**A. 条件のテキストが直接渡された場合**
そのまま受け入れ条件として使う。

**B. ファイルパスが渡された場合**（`.md` / `.txt` など、または `specs/` 以下のパスなど）
`Read` ツールでそのファイルを開き、書かれている受け入れ条件を読んでから始める。

ファイルパスかどうかの判断基準：スラッシュ（`/`）を含む、または既知の拡張子（`.md` `.txt`）で終わる場合はファイルパスと見なす。

**C. イシュー番号や機能の名前（認証・チャンネル・メンバー管理・メッセージ・スレッド・検索・公開API）だけが渡された場合**
`docs/design/acceptance.md` を読み、該当する「単位」の行を受け入れ条件として使う。

---

## 使うアカウント

このアプリの受け入れ条件を確認するときは、`docs/spec.md`（5-4. 初期データ）で固定されている次の3アカウントを使う：

- `sato@example.com` / `password`（佐藤 太郎。「開発」と「採用プロジェクト」を作った人）
- `suzuki@example.com` / `password`（鈴木 花子。「採用プロジェクト」のメンバー）
- `takahashi@example.com` / `password`（高橋 健。**「採用プロジェクト」のメンバーではない人**）

他人のものは触れない・見られないはずのものが見えない、という条件を確かめるときは、
アカウントを行き来する。プライベートチャンネルの確認には高橋を使う。

---

## 事前準備

### スクリーンショット保存先を作成

```bash
mkdir -p uat-shots
```

ファイル名は `01-<何の確認か>.png` のように **番号 + 内容** で付ける。

### Playwright MCP ツールのロード

`ToolSearch` で以下をまとめてロードする（まだロードしていない場合）：

```
select:mcp__playwright__browser_navigate,mcp__playwright__browser_snapshot,mcp__playwright__browser_take_screenshot,mcp__playwright__browser_find,mcp__playwright__browser_fill_form,mcp__playwright__browser_type,mcp__playwright__browser_click,mcp__playwright__browser_evaluate
```

---

## ログイン / ログアウト

> **重要**: この環境では `browser_click` でフォームの送信ボタンを押しても
> フォームが送信されないことがある。ログイン・ログアウト・フォーム送信は
> 必ず以下の JS 方式を使う。

### ログイン手順

```
1. browser_navigate → http://<host>/login
2. browser_fill_form でメール・パスワードを入力
3. browser_evaluate で form.submit() を呼ぶ
```

```js
// browser_evaluate で実行
() => { document.querySelector('form').submit(); return 'submitted'; }
```

ログイン後のリダイレクト先を `browser_find` で確認し、期待するユーザー名が
ヘッダーに表示されていれば成功。

### ログアウト手順

```js
// browser_evaluate で実行
() => {
  const f = Array.from(document.querySelectorAll('form'))
              .find(f => f.action.includes('logout'));
  if (f) { f.submit(); return 'logout ok'; }
  return 'no logout form';
}
```

---

## フォーム送信（リプライ投稿など）

`browser_click` が効かない場合は `fetch` API で POST する。
これにより CSRF トークンを含めた正規のリクエストをサーバーに送れる。

```js
// browser_evaluate で実行（content に送りたいテキストを入れる）
async () => {
  const form = document.querySelector('form[action*="<endpoint>"]');
  const fd = new FormData(form);
  fd.set('<field_name>', '<送信内容>');
  const resp = await fetch(form.action, {
    method: 'POST',
    body: new URLSearchParams(fd),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    redirect: 'follow'
  });
  return { status: resp.status, url: resp.url };
}
```

**fetch 後はページがリロードされない**ため、`browser_navigate` で同じ URL を
開き直してから `browser_snapshot` / `browser_find` で結果を確認する。

### フォームのフィールド名を調べる方法

`browser_evaluate` で以下を実行するとフォーム情報を一覧できる：

```js
() => {
  return Array.from(document.querySelectorAll('form')).map(f => ({
    action: f.action,
    method: f.method,
    fields: Array.from(f.querySelectorAll('input,textarea,select')).map(e => ({
      name: e.name, type: e.type || e.tagName
    }))
  }));
}
```

---

## 削除フォームの送信

削除ボタンも `browser_click` が効かないことがある。
フォームを `browser_evaluate` で直接 submit する：

```js
() => {
  const f = document.querySelector('form[action*="/<resource>/"]');
  if (!f) return 'no delete form';
  f.submit();
  return 'delete submitted: ' + f.action;
}
```

---

## 条件ごとの確認フロー

各受け入れ条件について次の順で進める：

1. **操作**: ブラウザを操作して条件をテストする状態を作る
2. **確認**: `browser_snapshot` または `browser_find` でDOMの状態を確認する
3. **スクリーンショット**: `browser_take_screenshot` で保存する
   - `filename: "uat-shots/<番号>-<内容を表す名前>.png"`
   - `fullPage: true` を使うと全体が写る
4. **DB 確認**（必要な場合）: `Bash` でサーバーサイドの状態を確認する
5. **判定**: 満たしている / 満たしていない を明確に述べ、根拠を示す

---

## レポートフォーマット

すべての条件を確認したら次の形式でまとめて報告する：

```
### 1. <受け入れ条件の文言>
**✅ 満足** または **❌ 未満足**
理由: <DOM の状態・スクリーンショットの内容など具体的な根拠>
証拠: `uat-shots/<ファイル名>`
```

---

## よくある問題と対処

| 問題 | 対処 |
|------|------|
| `browser_click` でボタンを押してもフォームが送信されない | `browser_evaluate` で `form.submit()` を呼ぶ |
| `form.submit()` を呼んだが DB に保存されない | `fetch` API で POST する（上記参照） |
| `browser_find` の ref が古くて見つからない | `browser_snapshot` を再取得してから ref を使う |
| ログイン後もゲスト扱いになる | `browser_find` でヘッダーのユーザー名を確認する |
| fetch 後に件数が変わっていない | `browser_navigate` でページをリロードしてから確認する |
