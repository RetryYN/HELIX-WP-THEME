# THEME-INV-04: 再利用パーツ機構（blog_parts / 番号スロット）の抽象化契約を決める

labels: investigation, contracts, cpt, priority:high
depends: THEME-INV-03

> **状態: 一次完了**（2026-08-26）／レポート: `../reports/INV-04-reusable-parts-mechanism.md`
> **参照（ID）で持ち、解決に使った版と digest を記録する**と決定。循環参照・欠落・下書きの規約を定義。
> `PartsAdapter` の最小インタフェースを定義し、Graphix NEO は テーマB 方式
> （`public=false` + `show_in_rest=true` の CPT）を採ることを推奨。
> **残**: テーマA の番号スロット実装の所在特定。

## 背景（実測）
- テーマB: CPT **`blog_parts`**（public=false / supports=title,editor / **show_in_rest=true**）+ CPT **`ad_tag`**。
  ブロック `themeB/blog-parts` `themeB/ad-tag` とショートコード `[blog_parts]` `[ad_tag]` から参照。
  → **REST から機械的に読み書きできる正本**
- テーマA: CPT **無し**。再利用パーツは番号スロット型 shortcode + テーマオプション（`themeA_*` 1,225 キーの一部）
  → **REST 経路が無く、オプション書き換えしか手が無い**
- agent-neo: CPT 0・該当機構なし（パターンは静的ファイル）

現状の実データは両サイトとも `blog_parts` 0 件（テーマB サイトは記事 7 本のみ）。
**機構としては存在するが運用では未使用**という状態を、移植判断でどう扱うか決める必要がある。

## 調査項目
1. `blog_parts` / `ad_tag` の保存形（post_content にブロック列）と参照形（ブロック属性の ID 参照）を採取
2. テーマA の番号スロット（1〜10 上限）の実体（theme options のどのキーか）を特定し、上限の根拠を確認
3. 「再利用パーツ」を中間 JSON でどう表すか — 実体埋め込み（展開）か参照（ID）かの設計選択肢を整理
4. per-site アダプタで吸収する場合の最小契約（read / list / resolve）を定義

## 完了条件
- [ ] 両実装の保存形・参照形が証跡付きで記録されている
- [ ] 中間 JSON における表現方式（展開 vs 参照）が根拠付きで 1 案に絞られている
- [ ] per-site アダプタの最小インタフェースが定義されている
