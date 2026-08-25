# THEME-INV-11: スコープ境界（課金・会員・EC）を PO 判断へ上申する

labels: investigation, scope, po-decision, priority:low
depends: THEME-INV-02

## 背景（実測）
- JIN:R: `vendor/stripe` **286 ファイル**を同梱し、`jinr-blocks/paidpost`（有料記事）+ `jinr_paidpost_secret_key`
  `jinr_paidpost_subscription_check` 等のオプションを持つ。**決済がテーマに内蔵されている**
- SWELL: `loos/restricted-area` / `[only_login]` `[only_logout]` による会員限定表示。決済は持たない
- agent-neo: いずれも無し
- 実使用: it-shukatu の `jinr-blocks/paidpost` は本文中 16 参照（要確認 — 実記事での使用か設定由来か）

Graphix NEO が「Context Page 構造」を主題とする以上、決済・会員はスコープ外の可能性が高いが、
**既存サイト移管時に機能が消える**なら PO 判断が要る。

## 調査項目
1. it-shukatu で paidpost が実運用されているか（公開記事での使用実態）を読み取りで確認
2. 会員限定表示（SWELL 側）の実使用を確認
3. スコープ外とした場合に失われる機能と、代替（プラグイン委譲）の可否を整理

## 完了条件
- [ ] 課金・会員機能の実運用有無が証跡で確定している
- [ ] スコープ内 / 外の判断材料が PO へ上申されている
