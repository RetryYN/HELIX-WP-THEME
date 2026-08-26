# THEME-INV-11: スコープ境界（課金・会員・EC）を PO 判断へ上申する

labels: investigation, scope, po-decision, priority:low
depends: THEME-INV-02

> **状態: 一次完了 / PO 承認待ち**（2026-08-26）／レポート: `../reports/INV-11-scope-boundary.md`
> **案 A（スコープ外 + プラグイン委譲）を推奨**。
> **訂正**: `paidpost` の実使用は「本文中 16 回」ではなく**公開記事で 0 回**
> （16 はテーマソース内の文字列出現数だった）。両サイトとも公開面で課金・会員機能は未使用。
> **残**: 下書き記事での使用と Stripe 側の既存購読者の確認（PO 領域）。

## 背景（実測）
- テーマA: `vendor/stripe` **286 ファイル**を同梱し、`themeA-blocks/paidpost`（有料記事）+ `themeA_paidpost_secret_key`
  `themeA_paidpost_subscription_check` 等のオプションを持つ。**決済がテーマに内蔵されている**
- テーマB: `themeB/restricted-area` / `[only_login]` `[only_logout]` による会員限定表示。決済は持たない
- agent-neo: いずれも無し
- 実使用: topic-A の `themeA-blocks/paidpost` は本文中 16 参照（要確認 — 実記事での使用か設定由来か）

Graphix NEO が「Context Page 構造」を主題とする以上、決済・会員はスコープ外の可能性が高いが、
**既存サイト移管時に機能が消える**なら PO 判断が要る。

## 調査項目
1. topic-A で paidpost が実運用されているか（公開記事での使用実態）を読み取りで確認
2. 会員限定表示（テーマB 側）の実使用を確認
3. スコープ外とした場合に失われる機能と、代替（プラグイン委譲）の可否を整理

## 完了条件
- [ ] 課金・会員機能の実運用有無が証跡で確定している
- [ ] スコープ内 / 外の判断材料が PO へ上申されている
