# THEME-INV-02: 動的ブロック（render_callback）の意味論と再現性を調べる

labels: investigation, blocks, determinism, priority:high
depends: THEME-INV-01

> **状態: 一次完了**（2026-08-26）／レポート: `../reports/INV-02-dynamic-render-semantics.md`
> 動的ブロックは **7 種**（本文の「9 種以上」は誤り・訂正済み）。
> **6 種は正規化で決定論レンダラに載る／`paidpost` のみ載らない**と判定。
> `register_block_style('core/list')` 2 件を新規発見。
> **残**: 残り 6 コールバックの精読（特に `button` 実使用 339）。

## 背景（実測）
テーマA の 25 ブロック中、少なくとも 9 種が `render_callback` を持つ SSR ブロック
（postcard / postlist / paidpost / slider / button / blogcard / category ほか）。
保存されるのは属性だけで、**HTML はレンダリング時にテーマ設定と DB から生成される**。
テーマB 側も `post-link` `post-list` `blog-parts` `ad-tag` `rss` `restricted-area` が同種。

一方 agent-neo の方針は「中間 JSON → 決定論レンダラ」。SSR ブロックは
「出力が環境（オプション・投稿 DB・ログイン状態）に依存する」ため、決定論の前提と衝突する。

## 調査項目
1. 各 SSR ブロックの出力が何に依存しているか（オプション / 投稿 / ユーザー状態 / 時刻）を分類
2. 依存が「サイト設定のみ」のものは決定論レンダラで再現可能か検証
3. 投稿 DB 参照型（postlist / blogcard / post-link）は**参照 ID を中間 JSON に持つ**方式で足りるか
4. ログイン状態依存（`only_login` `restricted-area` `paidpost`）はスコープ内か外かを PO へ上申

## 完了条件
- [ ] SSR ブロック一覧に「依存要因」「決定論再現の可否」「必要な中間 JSON 属性」が埋まっている
- [ ] 再現不能なものが理由付きで隔離され、スコープ判断が PO へ上申されている
