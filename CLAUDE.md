# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repository is

A learning exercise (Tutorial 16, "総仕上げ"): starting from a fixed client spec and a pixel mockup, the student designs and implements a team chat app end-to-end. Both the working code and a matching design-doc set are the deliverable — the design docs in `docs/design/` are binding, not optional scaffolding, and must be read before writing feature code.

## 正本の在りか

中身はここに写さない。必要になったら開くこと。

| 何が書いてあるか | どこにあるか |
|:--|:--|
| 仕様書（クライアントの要求） | `docs/spec.md`。**変えない** |
| 設計書一式（このアプリの決めごとの正本） | `docs/design/`（構成は `docs/design/README.md`） |
| 画面の見本 | `mockup/`（`index.html` から全画面） |
| 受け入れ条件とテスト観点 | `docs/design/acceptance.md` |

実装と設計書が食い違ったら、**コードではなく `docs/design/` を先に直す**。設計書は提出物の中心で、最後まで実物と一致させる。

## Project status

This is a vanilla Laravel 10 skeleton with **no feature code written yet** (`app/` only has the framework defaults — `User` model, base `Controller`, standard middleware/providers). Read `docs/spec.md` and `docs/design/` before writing any feature code — they encode decisions (data model, permissions, routing, error semantics) that aren't guessable from an empty `app/` tree.

- `docs/spec.md` — client requirements. **Never edit this file.** Anything missing or ambiguous belongs in `docs/design/questions.md`, not assumed silently.
- `docs/design/` — the design documents, authored by the user, which this implementation is bound to.

## Domain model (from `docs/design/data.md`)

Four domain tables, no class diagrams — Eloquent model = one table, following Laravel's MVC conventions directly:

- **`users`**: `display_name` (≤30 chars, duplicates allowed), `email` (unique), `password`.
- **`channels`**: `name` (≤50 chars, unique **within visible scope**, enforced in app code not a DB UNIQUE — must not leak private channel existence), `description` (≤200, nullable), `visibility` (`public`|`private`, immutable after creation), `created_by`. Creator automatically becomes a member; ownership never transfers.
- **`channel_user`**: pivot table, **private channels only** (public channels have no membership rows). Composite PK `(channel_id, user_id)`, no surrogate id.
- **`messages`**: single table for both top-level messages and replies via `parent_message_id` (`NULL` = top-level, set = a one-level-deep reply to that top-level message). `edited_at` non-null = "edited" marker; `deleted_at` non-null = soft-deleted, but **`body` is kept** (deleted messages render as a tombstone "このメッセージは削除されました", not removed) — this is a custom soft-delete column, not a framework convention to assume elsewhere. Reply counts include deleted replies; replies can still be added to a tombstoned message.

Only channel deletion (F-07) is a real physical delete (cascades to its messages and `channel_user` rows). Message "deletion" is always the `deleted_at` soft-delete described above.

## Authorization model (from `docs/design/permissions-api.md`)

- Laravel Policies/Gates, enforced via route `can:` middleware + controller `authorize()`. Input validation is via FormRequest.
- Visibility: any logged-in employee can read/write **public** channels. **Private** channels are visible only to `channel_user` members.
- **Critical rule — existence hiding**: any failure touching a private channel the user can't see (view, edit, delete, members, or messages within it) returns **404**, never 403, so the channel's existence isn't leaked. A real permission failure on a channel the user *can* see returns 403. This 403-vs-404 split applies uniformly across web routes and the public API — see `behavior.md` §3-0 before adding any new endpoint touching channels.
- Only a channel's creator can edit/delete it or manage its (private-only) members. Only a message's author can edit/delete that message — the channel creator has no special rights over others' messages.
- The public read-only API (`/api/channels`, `/api/channels/{channel}/messages`) needs no auth, returns JSON only, never includes private-channel data (not even by name) or PII (emails), and excludes deleted messages/their reply counts entirely — see `permissions-api.md` §3 for exact response shapes and the `{"message": "..."}` JSON error convention (404 for both "not found" and "is private", same body, on purpose).

## Routing (from `docs/design/permissions-api.md` §2)

Standard RESTful routes with route-model binding on `{channel}`/`{message}`/`{user}`. Thread replies get their own `POST` route but reuse the normal message routes for edit/delete. Message deletion is `DELETE` but is a logical (soft) delete server-side. See the file for the full route table before adding/renaming routes — route names and controller action names are already fixed there.

## Development conventions

- バリデーションエラーメッセージはすべて日本語で出力する（`docs/design/screens.md` §4 の文言・言い回しに合わせる）。
- マイグレーション実行後は出力を必ず確認し、エラーがないことを見てから次の作業に進む。
- 一覧表示のクエリには `with()` による Eager Loading が付いているかを確認する（N+1防止）。チャンネル一覧・メッセージ一覧・検索結果など、一覧系の画面・APIで特に注意。
- Follow the user's instructions precisely, and within that scope act autonomously: gather the necessary context and complete the requested work end-to-end in this run, asking questions only when essential information is missing or the instructions are critically ambiguous.

## 守ってほしい書き方

`docs/design/` の決めごとのうち、**実装を始めると気づかずに破りやすいもの**だけを挙げる。理由と詳細は在りか側にあり、ここには写さない。`/review-diff` は差分が届いたあとに効くスキルなので、ここは書く前から効かせる決めとして置く。

| 決め | 在りか |
|:--|:--|
| 見られないプライベートに関わる失敗は 403 ではなく 404 | `docs/design/behavior.md` §3-0 |
| `messages` の削除は `deleted_at` のみ。標準の `SoftDeletes` トレイトは使わない | `docs/design/data.md` §0・§2-4 |
| チャンネル名の一意判定は DB の UNIQUE を使わず、アプリ側（可視範囲内）で行う | `docs/design/data.md` §0・§2-2 |
| チャンネルの `visibility` は作成後に変更不可 | `docs/design/data.md` §2-2 |
| チャンネル作成者に他人のメッセージへの特別な権利はない。メンバー管理でも作成者自身は外せない | `docs/design/data.md` §2-3、`docs/design/permissions-api.md` 権限マトリクス注d・注e |
| 返信件数のカウント方針は画面と公開APIで意図的に異なる | `docs/design/permissions-api.md` §3-2 |
| 公開APIはプライベートチャンネルの情報を名前も含めて一切返さない | `docs/design/permissions-api.md` §3 |
| 公開APIの404は「存在しない」と「プライベート」で同一レスポンス | `docs/design/permissions-api.md` §3-3 |
| ルート名・コントローラのアクション名は固定。スレッド返信の編集・削除は専用ルートを作らず通常のメッセージルートを使う | `docs/design/permissions-api.md` §2 |
| 入力の空判定は前後の空白・改行を除去してから行う | `docs/design/behavior.md` §3-1 |

## プルリクエスト

イシュー1件に1本のブランチを切り、プルリクエストで受け取る。

本文には、テストの実行結果と、受け入れ条件それぞれの判定を載せ、最後に `Closes #<イシュー番号>` を入れる。これから実装するPRで繰り返し使う型を次に置く。`docs/design/acceptance.md` の受け入れ条件（Issue単位・2〜4行）に対応させる。

```markdown
## Summary
- <このPRで追加・変更したこと。1〜3行。関係する F-xx／SC-xx があれば添える>

## Test results
- <./vendor/bin/sail artisan test の結果。件数・Pass/Fail>

## Acceptance criteria
- <docs/design/acceptance.md の受け入れ条件を1行ずつ。条件 → ○/× の判定>

Closes #<イシュー番号>
```

## コマンド

すべて Sail（Docker）経由。素の `php artisan` は DB に繋がらない。

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test
./vendor/bin/sail down
```

テストは `phpunit.xml` で `DB_DATABASE=testing` に切り替わる。この `testing` DB は MySQL コンテナ初回起動時の init スクリプトが作るので、DB が無いと言われたら `sail down -v` からやり直す。

Seeding: the spec (§5-4) requires fixed demo data (3 accounts, 4 channels including one private, messages with edited/deleted/threaded examples) — implement via seeders, not manual DB edits, so `migrate` (without `--seed`) stays clean and seeding is reproducible.

## 注意

`.env` の中身は読まない。設定値を確認したいときは `.env.example` を見る。
