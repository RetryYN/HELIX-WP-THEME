# PoC 検証台（使い捨て）ブラウザ巡回による全テンプレ・パーツ・パターン描画確認（2026-08-27）

- 対象: 使い捨て PoC 検証台（本番非該当・ドメインは非公開）。WP 7.1、親 agent-neo-theme 0.1.0 + 子 helix-neo 0.1.0。
- 配備テーマは本リポ HEAD と一致（md5 比較。差分は `templates/page-lp-sample.html` の末尾改行のみ）。
- 手段: Playwright/Chromium、desktop 1366px + mobile 390px、18 URL × 2 viewport = 36 ページ。
  各ページで HTTP status・header/main/footer 存在・ブロック数・PHP Warning 混入・JSON-LD 型・canonical・
  console error/warning・pageerror・4xx/5xx 副次リクエスト・横スクロール・img alt を採取し、全ページを full-page screenshot。
- 未割当だった `page-lp-sample` / `blank` / `no-header` は PoC 側に検証用固定ページを作成して確認。

## 網羅

| 区分 | 対象 | 結果 |
|---|---|---|
| templates (10) | front-page, single, page, page-lp-sample, blank, no-header, archive(category/author/tag/date), search, 404, index(投稿一覧は front-page 経由) | 全て 200（404 テンプレは 404）で描画 |
| parts (4) | header, footer, post-header, post-footer | 想定テンプレで描画。blank は header/footer なし、no-header は footer のみ（設計通り） |
| patterns home-* (7) | hero/gateway/overview/cases/resources/trust/final-cta | front-page で全セクション描画 |
| patterns lp-* (13) | hero/problem/agitation/solution/feature/benefit/use-case/proof/comparison/pricing/faq/final-cta | page-lp-sample で全セクション描画 |
| patterns 記事系 (3) + footer-credit | article-cta / author-profile / share-buttons / footer-credit | single と全ページ footer で描画 |
| patterns hero | 挿入用の単独パターン（テンプレ非束縛） | 対象外（テンプレに含まれない） |

## 結果サマリ

- PHP Warning/Notice の HTML 混入: **0 / 36**
- pageerror（JS 例外）: **0 / 36**、横スクロール発生: **0 / 36**
- JSON-LD: 全ページ有効（Organization+WebSite を基底に、single は Person+BlogPosting+BreadcrumbList、page は WebPage+BreadcrumbList）
- canonical: 404 以外で出力（404 に canonical が無いのは正しい）
- 目視: front-page / LP / single(mobile) のレイアウト崩れなし。Cookie 同意バーが描画され操作可能。

## 軽微所見（テーマ起因ではない・未対応区分に入れない）

1. single/page の h1 ×2 — 投稿本文側に h1 が含まれる（コンテンツ側の問題）。
2. img alt 欠落 ×1（single 2 件） — アイキャッチ添付ファイルに alt 未設定（メディア側）。
3. console warning「iframe sandbox allow-scripts + allow-same-origin」 — 埋め込み分離 PoC（TC067）で意図した設定。
4. 404 ページの console error は当該 URL の 404 応答そのもの。

## 既知の残件（本検証で新規に増えたものなし）

- CI `PHPCS / WPCS lint` は `plugins/agent-neo-core/blocks/disclosure/render.php` の PrefixAllGlobals 違反 13 件で fail。
  main の既存コードで、workflow が `plugins/**` 変更時のみ起動するため main では未検出。PR #23 の伏せ字化とは無関係。

## 生データ

`crawl-summary.md`（36 行の採取表）。スクリーンショットと JSON はリポジトリ外に保持。

## 追記（2026-08-27）: 管理画面操作による実動確認

- HEAD のテーマ・plugin を PoC 検証台へ再配備し、親→子テーマの順で再有効化（WP-CLI）。
- ブラウザで wp-admin にログインし、テーマ画面（有効: HELIX Neo）、サイトエディタ起動、テンプレート一覧
  （フロントページ / 個別投稿 / 固定ページ / 404 / 検索結果 / アーカイブ / LP）表示を確認。
- 新規投稿にパターン（hero, article-cta）を挿入して公開、フロントで両パターンの描画と PHP Warning 0 を確認。
- 全 24 パターンをブロックエディタで読み込み、Block validation を採取:
  **`agent-neo/article-cta` のみ core/group（padding shorthand）と core/button（`has-custom-font-size` 欠落）が invalid**。
  他 23 パターンは invalid=0。修正は `hotfix/article-cta-block-validation`（Draft PR）。修正後 invalid=0 を再確認。
