# AGENT-NEO L6 統合検証記録（G6 RC 判定）

> 対象: AGENT-NEO（1st-party WordPress FSE テーマ + agent-neo-core / companion plugin）
> 実施: 2026-06-27 / 検証環境: docker 実 WordPress 6.9.4（localhost:8086）
> 関連ゲート: G5（デザイン凍結 PASS 済）→ **G6（RC 判定）**

## 1. 検証ワークストリームと判定

| # | 領域 | 成果物 | 判定 |
|---|------|--------|------|
| 1 | テクニカル SEO（Google 検索セントラル正本） | `docs/seo/google-search-central-audit.md` | 修正後 **準拠 ~92%**（加重）。CR/IM 解消 |
| 2 | 性能 | 本書 §4 | **GO**（競合比最小アセット。重大課題なし） |
| 3 | セキュリティ③ | `docs/security/g6-security-review.md` | **条件付き PASS**（条件2点・本書 §5） |
| 4 | E2E 統合 | `tests/e2e/live_docker/`（run_e2e.sh） | **全 PASS**（page_type/CTA/HMAC/a11y） |
| 5 | 運用準備 | `docs/L6-ops-readiness-review.md` / `docs/L6-ops-runbook.md` | 充足（配布/鍵管理/監視/ロールバック） |

## 2. テクニカル SEO（Google 検索セントラル準拠）

19 公式ページを正本にチェックリストを構築し全項目を実機判定。**正本で先行監査の優先度を訂正**（Sitelinks 検索ボックス=2024-11 廃止 / FAQ リッチリザルト=2026-05 廃止 → 対象外）。

解消した確定欠陥（実機検証済）:
- **CR-1** BlogPosting `author.name` 空 → `resolve_author_id()`（post_author=0 時は管理者フォールバック）+ display_name→nicename→login フォールバックで必ず非空に。
- **CR-2** 固定ページに BlogPosting 誤適用 → `is_singular('post')` のみ BlogPosting、page は **WebPage** ノード。og:type も post=article / page・home=website に修正。
- **IM-1** meta description 全ページ出力（singular=抜粋 / home=タグライン / archive=term description）。
- **IM-2** 著者・日付アーカイブ・404 に `wp_robots` で noindex+follow。
- **IM-3** home・アーカイブに canonical 出力。
- **IM-7** description の HTML エンティティ（`&hellip;` 等）を `html_entity_decode`+`wp_strip_all_tags` で除去。
- **m-3** generator メタ秘匿。

carry（owner データ／アセット依存・拡張点実装済）:
- og:image デフォルト画像（テーマ同梱 1200×630 アセット未配置）。フォールバック実装済、画像配置で有効化。
- Organization `sameAs` / 著者 `sameAs`・`jobTitle`（`agent_neo_organization_same_as` フィルタ / `agent_neo_job_title` ユーザーメタ / user_url の拡張点を用意。owner 入力で充足）。
- favicon / 画像 sitemap / 日本語スラッグ %XX / HTML パンくず最終項目（Minor）。

## 3. E2E 統合（live-docker）

wp-phpunit が CI 非可搬のため、実 docker WP に対する再現可能 E2E を `tests/e2e/live_docker/` に固定化（`run_e2e.sh` で一括実行）:
- `e2e_page_type`：home/lp/post/category/404 の page_type 分離（5 ケース）。
- `e2e_cta_instrumentation`：LP 5 CTA + 記事 article_cta の affiliate 計装（9 ケース）。
- `e2e_tracking_roundtrip`：全イベント種別の HMAC 署名 POST 200 + 旧署名 401 の負の対照（10 ケース）。token 解決をサーバ `tracking_secrets()` と同順にし、frontend↔サーバ token 整合性アサーションを内蔵。
- `e2e_a11y`：主要5ページ light axe serious/critical=0。

**最新実行: 4 スクリプト全 PASS。** 検証中に発見した E2E スクリプト自身の bash pitfall（`set -o pipefail` 下で `echo|grep -q` が早期 exit→SIGPIPE で偽 FAIL）を here-string 化で修正（製品は正常）。

## 4. 性能

- 外部アセット **2 CSS + 3 JS**（競合 ThemeB 10–20 / テーマA 5–15 本より最小）。HTML 119–151KB。PHP 独自クエリ 1 箇所。CWV 静的推定良好（LCP=テキスト見出し / CLS 対策済 / システムフォント）。
- `SCRIPT_DEBUG=true` による interactivity/debug.js（101KB）は **dev 用 docker-compose.yml 限定**。AGENT-NEO はテーマ+plugin 配布物であり本番顧客 WP は SCRIPT_DEBUG off → WP コアが minified 版をロード。**製品影響なし**。
- carry（本番運用最適化・テーマ責務外）: consent.js の render-block 改善 / 本番 nginx ページキャッシュ / ad-tracking.js の defer。

## 5. セキュリティ③ と carry disposition

詳細 `docs/security/g6-security-review.md`。判定 = **条件付き PASS**。

| carry | P | disposition |
|-------|---|-------------|
| 空 metadata `{}`↔`[]` canonical 不一致 | P3 | page_type 常時付与で発火不能（masked）。記録のみ |
| **token 解決順不整合（実機確定）** | P2 | `agent_neo_site_token` は本番未書込（test fixture のみ）。本番に残ると frontend イベント全 401 無言消失 → **本番前チェック必須** |
| `metadata.href` の `javascript:` スキーム | P2/P3 | export-contract に consumer `esc_url` 責務を追記済。export は edit_posts 認証下・フロント描画経路なし |
| server-side consent 検証なし | P3 | PII 不含で受容。法務確認推奨 |

**今セッションで解消**: HMAC canonical 非対称（JS トップレベルのみ↔PHP 再帰 sort）による viewable_impression 消失回帰 → JS 再帰 canonicalize で解消（実 POST 実証 旧401/新200）。

### 本番配布前 必須確認（G6 → L7 デプロイ条件）
1. `wp option get agent_neo_site_token` が空/未設定であること（残存時は削除）。デプロイ後 smoke test で tracking イベント 200（特に viewable_impression）を目視。
2. `export-contract.md` の consumer `esc_url` 責務（追記済）を Automation SEO 側実装で遵守。

## 6. 品質ゲート実績

- `vendor/bin/phpunit --testsuite unit`：**144 tests PASS**（SEO/page_type/CTA/export テスト追加）
- `--testsuite security`：**48 tests PASS**
- `bin/check-theme-quality.sh`：**PASS**（GATE1-6 / Theme Review）
- `run_e2e.sh`：**4/4 PASS**
- axe light/dark（主要画面）：serious/critical **0**（SNS share の WCAG1.4.3 意図的例外のみ）

## 7. G6 RC 判定

**条件付き GO（Release Candidate 承認）**

- 機能・統合・SEO・a11y・性能の重大ブロッカーはゼロ。
- セキュリティ③は条件付き PASS（本番前必須確認 2 点は §5 に明記、L7 デプロイ手順で担保）。
- 残 carry はいずれも owner データ依存／本番運用最適化／consumer 責務であり、RC を妨げない。

次フェーズ: **L7 デプロイ**（§5 の本番前必須確認を smoke test に組み込む）。
