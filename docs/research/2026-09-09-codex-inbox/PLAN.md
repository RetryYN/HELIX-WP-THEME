# codex-inbox — Claude → codex の通知経路（consumer 側実装）

- 実施日: 2026-09-09。位置づけ: 通知経路の PoC 実装と実機証跡。要求の確定ではない。
- 契機: WP-THEME #180。PR #174 の Claude 収束レビューで、Claude の返しが codex に自動では届かないことを 8 周分実測した。
- PO 判断: 「このなかに入っている開発ツールとしての HELIX は修正していいが、別リポジトリは触るのを禁止する」。
  上流 `RetryYN/HELIX-HARNESS` には書かず、本リポジトリ配下のスクリプトと `.codex/hooks.json` だけで実装する。

## 事実（上流の確認、read-only）

- 上流 main `52d0ec5`（pin `19d0bffd` から 1150 commit 先）でも codex 宛て wake は存在しない。
  `codex-inbox` / `notify-codex` / `codex-memory-wake` で `src/` `docs/` を検索して 0 件。
- 同じ非対称は上流 **HELIX #532**（bug, backlog）に「Codex は Claude を起こせるが Claude は Codex を起こせない」として
  起票済み。提案は `claude-inbox:` / `codex-inbox:` の対称 prefix。上位に **#854**（Notification Fabric）。
- 経路の実態（本リポジトリで実測）:

| 経路 | 方向 | 保存場所 | worktree をまたぐか |
|---|---|---|---|
| `github pr-notify` / `memory notify-claude` | codex → Claude | common dir `helix-runtime/claude-memory-wake/` | またぐ |
| `pr-review-receipt --apply` | Claude → codex | common dir `helix-runtime/claude-pr-convergence/receipts/` | またぐ（push は無い） |
| `memory write` | 双方向 | worktree の `.helix/memory/*.jsonl` | またがない |
| **`codex-inbox`（本実装）** | Claude → codex | common dir `helix-runtime/codex-memory-wake/` | またぐ |

## 実装

| ファイル | 役割 |
|---|---|
| `scripts/lib/codex-inbox.mjs` | envelope 生成・spool への atomic 書き込み（tmp → rename）・一覧・配送。operationId で idempotent。自己通知（runtime=codex）拒否。本文 8,000 字上限。spool ファイル名は「読める断片 + id の完全 sha256」（`/` と `:` が同じ `_` に潰れて衝突しない）。入口で完全検証（schema / id と key の対応 / body と digest / provenance / ファイル名 identity）し、不正 entry は `rejected` に隔離して後続を配送する（削除しない） |
| `scripts/notify-codex.mjs` | 送信 CLI。`memory notify-claude` の対称 |
| `scripts/codex-inbox.mjs` | 受信 CLI。`deliver` は未配送を `[HELIX_CODEX_INBOX]` 境界で stderr に出し、書き出し成功後に `.delivered` マーカーを置く。既定 exit 2（Stop hook で停止を止める）。hook 呼び出しのため例外は fail-open |
| `.codex/hooks.json` | SessionStart に `deliver --exit-code 0`（表示のみ）、Stop に `deliver --exit-code 2` を追加 |
| `AGENTS.md` | codex 向けの手順と、通知本文を正本にしない規則 |
| `tests/unit/codex-inbox.test.mjs` | 10 検査。prefix / id 導出、自己通知・不正入力の拒否、idempotent と common dir 配置、**別 worktree からの可視性と配送**、書き出し失敗時に delivered にしない、壊れた spool の無視、**衝突する id の path 分離**、**部分的に正しい entry の隔離と後続配送**、**CLI が hook stdin の session_id を読む（ESM）**、**空・破損・別 id・偽 ackDigest の delivered marker を未配送扱いし再配送** |

envelope は `claude-memory-wake` の `MemoryEntryV2` を簡略化した独自 schema `helix-codex-inbox-entry.v1`。
同梱 HELIX のソースにはパッチしない（`memory-v2` の内部型に依存すると pin 更新で壊れるため）。

## 実機証跡（2026-09-09）

- `node --test tests/unit/codex-inbox.test.mjs` — 10 / 10 pass。
- Codex 独立レビュー（PR #182、HEAD `b928d19`）の blocker 3 件を修正: ESM で `require` を使い session_id が常に落ちていた／`/` と `:` の潰しで spool path が衝突した／schema と id だけの entry が format で例外になり後続を止めた。いずれも負例テストを追加。
  既存 spool（5 件）は新しいファイル名規約へ rename 済み（内容・delivered マーカーは保持）。
- Codex 再レビュー（HEAD `0d7d11e`）の新規 blocker 1 件を修正: delivered marker を存在だけで判定していた → marker も tmp → rename で atomic に書き、読取時に id / receiverSession / ackDigest / deliveredAt を検証。空・破損・別 id の marker は未配送として再配送する。
- Codex 再レビュー 2（HEAD `69fe5c5`）の残 blocker を修正: ackDigest を形式だけで通していた → 書込み時と同じ `sha256(formatCodexInboxMessage(entry))` を再計算して突合し、形式が正しくても不一致なら未配送として再送する。
- 疎通: 統合層の submodule checkout から `notify-codex` → spool は Git common dir 配下
  `helix-runtime/codex-memory-wake/inbox/` に作成 → `deliver` が本文を出し exit 2 →
  **codex が作業する別 worktree から `list` で同じ entry が `delivered` として見える**。疎通 entry は削除済み。

## 限界と残件

- Codex CLI の Stop hook が exit 2 を「停止せず継続」と解釈するかは Codex 側の実装依存。Claude Code と同じ hooks.json 形式を
  採っているため同挙動を期待するが、**未検証**。解釈されない場合でも SessionStart の表示と AGENTS.md の手動手順で届く。
- hook が実行されない環境（Hosted / API）では AGENTS.md の手動手順に依存する（`claude-inbox` と同じ制約）。
- PR 単位の supersede（新 HEAD の通知が旧通知を無効化する）は持たない。key に HEAD を含めるか、受信側が current HEAD を
  再取得することで代替する。
- 上流へ戻す場合は #532 の提案（`--target <runtime>` を持つ汎用 inbox）に合わせて置き換える。本実装はそれまでの consumer 側の橋。
