# 権限・ルーティング・公開API

`docs/spec.md`（仕様書）・`mockup/`・確定した `docs/design/`（`features.md`・`questions.md`・`screens.md`・`data.md`）をもとにした、裏側の設計のうち **権限マトリクス・ルーティング一覧・公開APIの設計** を置きます。

- 根拠は spec・mockup・`docs/design/` にあるものだけに限り、書かれていないことは想像で足していません（足した判断は「自分で決めた」と明記）。
- 図は Markdown 内の Mermaid で書きます（README「そのほかの決まり」）。この文書に図はありません（表が中心のため）。状態遷移図・シーケンス図は `behavior.md` 側です。
- 機能ID（F-xx）は `features.md`、画面ID（SC-xx）は `screens.md`、テーブルは `data.md` の定義に合わせています。
- 権限・可視範囲は **画面とAPIの双方で守る**（`features.md` の土台）。本書の権限マトリクスは web 画面と公開API の両方に効くものとして読みます。

---

## 0. この設計の前提

| 決めごと | 内容 | 根拠 |
|:--|:--|:--|
| 認可の持ち方 | Laravel の Policy / Gate で判定し、ルートの `can:` ミドルウェアと コントローラの `authorize()` で効かせる。字数・必須などの入力チェックは FormRequest（画面のエラーは `screens.md` §4 と対） | README（MVC の形）／`features.md` 土台 |
| 可視範囲の原則 | 公開チャンネル＝ログイン社員は全員が閲覧・書き込み可。プライベート＝`channel_user` の会員だけ。**見られないプライベートは「存在自体」を隠す**（一覧に出さない／直接URLは 404） | spec 3-2・3-3／Q-01 |
| 作成者だけの操作 | チャンネルの編集・削除・メンバー管理は作成者のみ。作成者の交代はなし | spec 3-2・3-3／回答ログ |
| 本人だけの操作 | メッセージの編集・削除は投稿者本人のみ。チャンネル作成者でも他人のメッセージは操作不可 | spec 3-4／Q-07 |
| 公開API | ログイン不要の **読み取り専用**。JSON を返す。個人を特定しうる情報（メール等）は含めない。削除済みは完全除外 | spec 3-7／Q-14・Q-15 |

---

## 1. 権限マトリクス

機能（F-xx）を行、利用者の区分を列にし、**○＝できる／△＝条件つきでできる／×＝できない** で示します。△の条件は表の下にまとめます。

### 列（利用者の区分）の意味

「対象チャンネル」「対象メッセージ」は、その操作が向いている先のことです。チャンネルやメッセージを特定しない機能（登録・ログイン・チャンネル作成・検索・公開API）では、②〜④は単に「ログイン社員」として読みます。

| 列 | 区分 | 定義 |
|:--|:--|:--|
| ① | 未ログイン | ログインしていない人（公開APIの利用者を含む） |
| ② | 社員・対象外 | ログイン社員だが、**対象チャンネルを見られない**（自分がメンバーでないプライベート） |
| ③ | 社員・閲覧可 | 対象チャンネルを**見られる**社員（公開チャンネルの全社員／プライベートのメンバー）。非作成者 |
| ④ | 作成者 | 対象チャンネルの作成者 |

- メッセージの「投稿者本人」は、③または④の人のうち、その行を書いた人です。本人に限る操作は △ で表し、条件を下に書きます。

### マトリクス

| 機能 | ① 未ログイン | ② 社員・対象外 | ③ 社員・閲覧可 | ④ 作成者 |
|:--|:--:|:--:|:--:|:--:|
| F-01 新規登録 | ○ | ×<sup>a</sup> | ×<sup>a</sup> | ×<sup>a</sup> |
| F-02 ログイン | ○ | ×<sup>a</sup> | ×<sup>a</sup> | ×<sup>a</sup> |
| F-03 ログアウト | × | ○ | ○ | ○ |
| F-04 チャンネル作成 | × | ○ | ○ | ○ |
| F-05 チャンネル一覧表示 | × | ○<sup>b</sup> | ○<sup>b</sup> | ○<sup>b</sup> |
| F-06 チャンネル編集 | × | ×<sup>c</sup> | × | ○ |
| F-07 チャンネル削除 | × | ×<sup>c</sup> | × | ○ |
| F-08 メンバー管理 | × | ×<sup>c</sup> | × | △<sup>d</sup> |
| F-09 メッセージ一覧表示 | × | × | ○ | ○ |
| F-10 メッセージ投稿 | × | × | ○ | ○ |
| F-11 メッセージ編集 | × | × | △<sup>e</sup> | △<sup>e</sup> |
| F-12 メッセージ削除 | × | × | △<sup>e</sup> | △<sup>e</sup> |
| F-13 スレッド返信 | × | × | ○<sup>f</sup> | ○<sup>f</sup> |
| F-14 メッセージ検索 | × | ○<sup>g</sup> | ○<sup>g</sup> | ○<sup>g</sup> |
| F-15 公開API：公開チャンネル一覧 | ○ | ○ | ○ | ○ |
| F-16 公開API：公開チャンネルのメッセージ一覧 | ○ | ○ | ○ | ○ |

### △・注の条件

- **a**（F-01・F-02）：登録・ログインは未ログイン専用（`guest` ミドルウェア）。ログイン済みで開いたら SC-03 へリダイレクト（`behavior.md` 例外方針）。②〜④の × は「ログイン済みなので対象外」の意。
- **b**（F-05）：一覧・サイドバーに並ぶのは **公開チャンネルすべて＋自分がメンバーのプライベートだけ**。見られないプライベートは行自体を出さない（Q-01）。誰が見ても「できる」が、見える範囲は人によって違う。
- **c**（F-06・F-07・F-08 の②）：対象が見られないプライベートの場合、権限がないことを 403 で返すと存在が漏れるため **404** で返す（存在を隠す。Q-01／`behavior.md`）。見えるチャンネルでの権限不足は 403。
- **d**（F-08 メンバー管理）：**対象がプライベートチャンネルで、かつ作成者のときだけ**。公開チャンネルにメンバー管理はない（spec 3-3）。公開チャンネルの作成者でも F-08 は × になる。
- **e**（F-11・F-12 メッセージ編集・削除）：**自分が書いたメッセージに限る**。チャンネル作成者でも他人のメッセージは編集・削除できない（Q-07）。削除済み（墓標）になったメッセージは本人でも再編集・再削除しない（`behavior.md` 状態遷移）。
- **f**（F-13 スレッド返信）：対象チャンネルを見られる人は返信できる。**削除済み（墓標）メッセージにも返信を続けられる**（回答ログ）。返信は1段のみ（Q-06）。
- **g**（F-14 検索）：検索はログイン必須（回答ログ）。結果は **自分が見られるチャンネルのメッセージだけ**に絞る（spec 3-6／Q-04）。見える範囲は人によって違うが、機能としては全ログイン社員が使える。削除済みは結果に出さない（Q-05）。

### この表だけでは守れないもの：検索（F-14）と公開API（F-15・F-16）

F-14・F-15・F-16 は、列が **ほぼ全部 ○** に見えます。ここを「全部埋まっている＝素通しでも大丈夫」と読まないでください。**この3つは、○×（＝誰がその機能を呼べるか）では守りきれない**種類の機能です。守りの本体は「呼べるかどうか」ではなく、**返す行をどこまで絞るか（行レベルの可視範囲）**にあり、それは機能ごとの許可/不許可の表では表せません。

| 機能 | ○ が意味するのは | 本当の守り（行レベルの絞り込み。表では表せない） |
|:--|:--|:--|
| F-14 検索 | ログイン社員なら検索窓を使える | 結果を **自分が見られるチャンネル（公開すべて＋会員のプライベート）のメッセージだけ**に絞り、削除済みは外す。メンバーでないプライベートの本文は**絶対に結果へ出さない**（spec 3-6／Q-01・Q-04・Q-05）。コントローラ／クエリのスコープで担保する |
| F-15・F-16 公開API | 未ログインを含め誰でも叩ける | 返す行を **公開チャンネルのみ／削除済み除外／直下のみ／表示名のみ**に絞る。プライベートは 404 で存在ごと隠す。**入口を開ける代わりに、出す中身を絞る**（spec 3-7／Q-01・Q-14・Q-15。§3 で定義） |

- 要するにこの2系統は、**「誰が呼べるか」ではなく「何が返るか」で守ります**。マトリクスが ○ でも素通しではなく、クエリのスコープ（検索）と API レスポンスの絞り込み（公開API）＝ §2-3 の Policy・§3 の公開API・`behavior.md` の例外方針（403／404 の使い分け）と**セットで初めて守られます**。
- 逆に、F-06〜F-13 のように「対象が特定の1件」に向く操作は、○×と Policy（§2-3）で素直に守れます。表の読み分けの基準はここです。

---

## 2. ルーティング一覧

Laravel の標準的な RESTful ルートに乗せます。`{channel}` `{message}` `{user}` はルートモデルバインディング。認可は Policy（`ChannelPolicy`・`MessagePolicy`）を `can:` ミドルウェアで効かせます。

- 返信（スレッド）は `parent_message_id` を持つ **messages の1行**なので（`data.md` §2-4）、返信の投稿だけ専用ルートを置き、返信の**編集・削除は通常のメッセージルート**（`{message}` にその返信を渡す）を使い回します。
- メッセージの削除は論理削除（`deleted_at` を付ける）で、HTTP は `DELETE` を使います。物理削除が起きるのはチャンネル削除（F-07）だけです（`data.md` §3）。

### 2-1. web ルート（`routes/web.php`）

| メソッド | URI | ルート名 | コントローラ@アクション | 認可・ミドルウェア | 画面/機能 |
|:--|:--|:--|:--|:--|:--|
| GET | `/register` | `register` | `RegisteredUserController@create` | `guest` | SC-02／F-01 |
| POST | `/register` | `register.store` | `RegisteredUserController@store` | `guest` | F-01 |
| GET | `/login` | `login` | `AuthenticatedSessionController@create` | `guest` | SC-01／F-02 |
| POST | `/login` | `login.store` | `AuthenticatedSessionController@store` | `guest` | F-02 |
| POST | `/logout` | `logout` | `AuthenticatedSessionController@destroy` | `auth` | 上部バー／F-03 |
| GET | `/channels` | `channels.index` | `ChannelController@index` | `auth` | SC-03／F-05 |
| GET | `/channels/create` | `channels.create` | `ChannelController@create` | `auth` | SC-08／F-04 |
| POST | `/channels` | `channels.store` | `ChannelController@store` | `auth` | F-04 |
| GET | `/channels/{channel}` | `channels.show` | `ChannelController@show` | `auth` + `can:view,channel` | SC-04／F-09 |
| GET | `/channels/{channel}/edit` | `channels.edit` | `ChannelController@edit` | `auth` + `can:update,channel` | SC-09／F-06 |
| PATCH | `/channels/{channel}` | `channels.update` | `ChannelController@update` | `auth` + `can:update,channel` | F-06 |
| DELETE | `/channels/{channel}` | `channels.destroy` | `ChannelController@destroy` | `auth` + `can:delete,channel` | F-07 |
| GET | `/channels/{channel}/members` | `channels.members.index` | `ChannelMemberController@index` | `auth` + `can:manageMembers,channel` | SC-07／F-08 |
| POST | `/channels/{channel}/members` | `channels.members.store` | `ChannelMemberController@store` | `auth` + `can:manageMembers,channel` | F-08 |
| DELETE | `/channels/{channel}/members/{user}` | `channels.members.destroy` | `ChannelMemberController@destroy` | `auth` + `can:manageMembers,channel` | F-08 |
| GET | `/channels/{channel}/messages/{message}/thread` | `channels.threads.show` | `ThreadController@show` | `auth` + `can:view,channel` | SC-05／F-13 |
| POST | `/channels/{channel}/messages` | `channels.messages.store` | `MessageController@store` | `auth` + `can:view,channel` | SC-04／F-10 |
| POST | `/channels/{channel}/messages/{message}/replies` | `channels.messages.replies.store` | `ReplyController@store` | `auth` + `can:view,channel` | SC-05／F-13 |
| PATCH | `/channels/{channel}/messages/{message}` | `channels.messages.update` | `MessageController@update` | `auth` + `can:update,message` | SC-04/05／F-11 |
| DELETE | `/channels/{channel}/messages/{message}` | `channels.messages.destroy` | `MessageController@destroy` | `auth` + `can:delete,message` | SC-04/05／F-12 |
| GET | `/search` | `search` | `SearchController@index` | `auth` | SC-06／F-14 |

### 2-2. API ルート（`routes/api.php`）

| メソッド | URI | ルート名 | コントローラ@アクション | 認可・ミドルウェア | 機能 |
|:--|:--|:--|:--|:--|:--|
| GET | `/api/channels` | `api.channels.index` | `Api\ChannelController@index` | なし（公開） | F-15 |
| GET | `/api/channels/{channel}/messages` | `api.channels.messages.index` | `Api\MessageController@index` | なし（公開） | F-16 |

- 書き込み・変更の API は置きません（spec 3-7／`features.md`）。上記2本以外の API URI・メソッドは 404／405（`behavior.md` 例外方針）。

### 2-3. Policy での判定の要点

| Policy@メソッド | 許可条件 | 不許可のとき |
|:--|:--|:--|
| `ChannelPolicy@view` | 公開チャンネル → ログイン社員なら誰でも可／プライベート → 会員のみ | **プライベートの非会員は 404**（存在を隠す。Q-01）。公開で未ログインは `auth` で 302 |
| `ChannelPolicy@update` / `@delete` | `channel.created_by == auth()->id()` | 見える人なら 403／見えないプライベートなら 404 |
| `ChannelPolicy@manageMembers` | 作成者 **かつ** `visibility == private` | 公開チャンネルは 403（存在は隠さない）／見えないプライベートは 404 |
| `MessagePolicy@update` / `@delete` | `message.user_id == auth()->id()`（本人のみ。Q-07）。かつ `deleted_at` が未設定 | 403（墓標の再操作も不可） |

---

## 3. 公開APIの設計（F-15・F-16）

社内ポータルなど別の仕組みから、公開チャンネルの内容を読み取るための **読み取り専用 JSON API** です（spec 3-7）。ログイン不要。プライベートチャンネルの情報は、チャンネル名も含めて一切返しません（spec 3-7／Q-01）。

### 共通の決まり

| 項目 | 決め | 根拠 |
|:--|:--|:--|
| 認証 | 不要（公開） | spec 3-7 |
| メソッド | `GET` のみ。書き込み・変更は提供しない | spec 3-7／Q-14 |
| 形式 | `Content-Type: application/json; charset=utf-8`。本体は UTF-8 の JSON | spec 3-7（JSONで返す＝Q-14） |
| 返す範囲 | **公開チャンネル（`visibility == public`）のみ**。プライベートは一切含めない | spec 3-7／Q-01 |
| 個人情報 | メールアドレス等は返さない。投稿者は **表示名のみ** | Q-14 |
| 削除済みメッセージ | **完全に除外**（本文も「削除されました」も出さず、一覧にも出さない） | Q-14 |
| 返信（スレッド） | 一覧に返すのは**チャンネル直下**（`parent_message_id IS NULL`）のみ。返信は本文として含めず **件数だけ** | Q-15 |
| 日時の形式 | 機械可読の **ISO 8601（日本時間オフセット付き、例 `2026-08-17T09:12:00+09:00`）**。画面表示の `2026/08/17 14:32` とは別（自分で決めた／下の「決めること」も参照） | spec 3-4（いつ書いたか）／design-guide §2 は画面表示の規定 |
| 包み方 | 一覧は `{"data": [ ... ]}` で包む（将来のページング余地のため） | 自分で決めた |

### 3-1. F-15　GET `/api/channels`（公開チャンネル一覧）

**入力**：なし（パラメータなし）。

**出力（200 OK）**：公開チャンネルを `created_at` 昇順で返す。

```json
{
  "data": [
    {
      "id": 1,
      "name": "全体連絡",
      "description": "社内の連絡事項を共有する場所です。",
      "created_at": "2026-08-10T09:00:00+09:00"
    },
    {
      "id": 2,
      "name": "開発",
      "description": "開発に関する相談や進捗を書きます。",
      "created_at": "2026-08-11T10:30:00+09:00"
    }
  ]
}
```

- `description` は任意項目なので、未設定なら `null`（`data.md` §2-2）。
- プライベートチャンネルは、この配列に **一切現れない**（名前も出さない。Q-01）。
- 作成者名などは含めない（必要最小限。個人情報を広げないため。Q-14）。

### 3-2. F-16　GET `/api/channels/{channel}/messages`（公開チャンネルのメッセージ一覧）

**入力**：
| 場所 | 名前 | 必須 | 説明 |
|:--|:--|:--|:--|
| パス | `channel` | ○ | 公開チャンネルの `id`。公開チャンネルでないか存在しなければ 404（下記エラー） |

**出力（200 OK）**：指定した公開チャンネルの **直下メッセージ**（削除済みを除く）を `created_at` 昇順で返す。

```json
{
  "channel": {
    "id": 2,
    "name": "開発",
    "description": "開発に関する相談や進捗を書きます。"
  },
  "data": [
    {
      "id": 101,
      "body": "リリースは来週の水曜で確定しました。",
      "author": { "display_name": "佐藤 太郎" },
      "reply_count": 3,
      "edited": false,
      "posted_at": "2026-08-17T09:12:00+09:00"
    },
    {
      "id": 102,
      "body": "了解です。資料を更新しておきます。",
      "author": { "display_name": "鈴木 花子" },
      "reply_count": 0,
      "edited": true,
      "posted_at": "2026-08-17T10:05:00+09:00"
    }
  ]
}
```

- `author` は **表示名のみ**。メールや id は返さない（Q-14）。
- `edited` は `edited_at` が非 `NULL` なら `true`（spec 3-4／`data.md` §2-4）。編集時刻そのものは返さない（画面でも出さないため。`screens.md` §3-4）。
- `reply_count` は返信の件数。**削除された返信は数えない**（公開APIは削除の痕跡を外に出さない方針＝Q-14 に合わせる）。これはアプリ内表示（Q-16：削除済み返信も数える）と**あえて変える**判断で、アプリ内と公開APIで件数が食い違うことを承知のうえ **2026-11-09 に確定**しています。
- 削除済み（墓標）の直下メッセージは、この配列に **現れない**（Q-14）。

### 3-3. エラーの返し方（公開API 共通）

公開APIのエラーは **HTML エラーページではなく JSON** で返します（機械が読むため）。本体は `{"message": "..."}` の形で統一します（Laravel の既定の形に合わせる）。

| 状況 | HTTP | 本体（例） | 補足 |
|:--|:--|:--|:--|
| 指定チャンネルが存在しない／**プライベートだった** | 404 | `{ "message": "指定されたチャンネルは見つかりません。" }` | 未存在とプライベートで**同一レスポンス**（プライベートの存在を判別させない。Q-01／spec 3-7） |
| 読み取り専用の URI に `GET` 以外 | 405 | `{ "message": "このAPIは読み取り専用です。" }` | 書き込み・変更は提供しない（spec 3-7） |
| 用意していない API パス | 404 | `{ "message": "見つかりません。" }` | 2本のルート以外 |
| サーバー内部エラー | 500 | `{ "message": "サーバーでエラーが発生しました。" }` | 詳細（スタックトレース等）は外に出さない |

- `{channel}` の 404 は、**公開でないチャンネルを指定したとき**も含みます。ここを 403 や「プライベートです」と返すと、プライベートの存在が漏れるためです（Q-01）。
