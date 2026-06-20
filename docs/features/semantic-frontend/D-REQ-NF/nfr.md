# semantic-frontend — D-REQ-NF（非機能要件）

## 概要

`semantic-frontend` の非機能要件は、セマンティックなフロントエンド構造が性能・アクセシビリティ・国際化・配布品質・AI 運用継続性の観点で維持されることを保証する。Core Web Vitals 基準・日本市場向けの基本 a11y 配慮・JSON-LD 検証・Crawler Access Matrix 整合性を品質ゲートとして定義する。

## 非機能要件の分類

| 観点 | 要件ID | 件数 |
|---|---|---|
| 性能 | SNF-001〜003 | 3 |
| アクセシビリティ | SNF-004〜006 | 3 |
| 国際化 | SNF-007〜008 | 2 |
| 配布品質 | SNF-009〜011, SNF-015 | 4 |
| AI 運用性 | SNF-012〜014 | 3 |

## 詳細要件

| ID | 要件名 | 観点 | 説明 | 優先度 | 上位 L1 ID |
|---|---|---|---|---|---|
| SNF-001 | Core Web Vitals | 性能 | LCP 2.5s 以下、INP 200ms 以下、CLS 0.1 以下を代表テンプレート（LP/HP/記事）で保証する | P0 | REQ-NF-001 |
| SNF-002 | 条件付きアセット | 性能 | JSON-LD・計測スクリプト・A/B スクリプトは使用ページのみ読み込み、全ページ読み込みを禁止する | P0 | REQ-NF-001 |
| SNF-003 | LCP 画像制御 | 性能 | Hero ブロックの LCP 候補画像に `fetchpriority="high"` と `loading="eager"` を付与し、遅延読み込みを抑制する | P0 | REQ-NF-001 |
| SNF-004 | a11y 基本配慮 | アクセシビリティ | axe-core による自動検査を CI に組み込み、Critical/Serious 指摘 0 件を目安にしつつ、日本市場向けの基本配慮を満たす | P1 | REQ-NF-005 |
| SNF-005 | フォーカス可視性 | アクセシビリティ | `:focus-visible` によるフォーカスリングを全インタラクション要素に実装し、CTA・フォーム・ナビが keyboard 操作可能 | P1 | REQ-NF-005 |
| SNF-006 | aria-label 網羅 | アクセシビリティ | 図像のみの CTA・ハンバーガーメニュー・ランキング星評価に適切な aria-label または aria-labelledby を付与する | P1 | REQ-NF-005 |
| SNF-007 | i18n 対応 | 国際化 | 全 UI 文字列を `__()` / `_e()` でラップし、`agent-neo.pot` に抽出できる状態にする | P1 | REQ-NF-006 |
| SNF-008 | 日本語 / 英語ロケール | 国際化 | `ja` および `en_US` ロケールで表示・JSON-LD・OGP・構造化データが正しく出力される | P1 | REQ-NF-006 |
| SNF-009 | JSON-LD 検証 | 配布品質 | Google Rich Results Test または Schema.org validator による JSON-LD 検証を CI に組み込む | P0 | REQ-NF-016 |
| SNF-010 | JSON-LD 重複抑制 | 配布品質 | canonical 重複・Schema type 重複を検出し、SEO Core 以外の JSON-LD 重複出力を警告またはブロックする | P0 | REQ-F-011 |
| SNF-011 | robots/canonical 整合性 | 配布品質 | Crawler Access Matrix の設定と robots.txt の実際の内容が一致することを差分チェックで確認する | P0 | REQ-NF-015 |
| SNF-012 | AI Snapshot 鮮度 | AI 運用性 | `content_hash` が変化したページのスナップショットは24時間以内に更新する | P1 | REQ-NF-015 |
| SNF-013 | AI Snapshot 公開境界 | AI 運用性 | スナップショット API が非公開・パスワード保護・会員限定ページの本文を返さないことを統合テストで確認する | P0 | REQ-NF-015 |
| SNF-014 | data-agent 属性の安定性 | AI 運用性 | WP/テーマ/プラグインの更新後に `data-agent-section-id` 値が変化しないことを回帰テストで確認する | P0 | REQ-NF-015 |
| SNF-015 | Theme Review 準拠 | 配布品質 | WordPress Theme Review チェックリストを通過し、不適切なデータ保存・エスケープ不足・ライセンス不備がゼロ | P0 | REQ-NF-016 |

## 補足・設計指針

**JSON-LD のスコープ管理**: Article/Review/FAQPage は投稿タイプ・テンプレートに応じて条件付き出力とする。全ページに同一の Organization JSON-LD を重複出力しない。

**AI Snapshot のキャッシュ戦略**: スナップショットは WP Object Cache（Redis/Memcached）または Transient で1時間キャッシュし、投稿更新フック（`save_post`）でキャッシュを無効化する。

**アクセシビリティと計測 data 属性の共存**: `aria-` 属性と `data-agent-*` 属性は独立して管理し、計測目的で aria 属性を流用しない。

**Core Web Vitals の継続監視**: 初期リリース時に Lighthouse CI で検証した後も、RUM（Real User Monitoring）を seo-tool-connector 経由で継続計測する。CrUX のフィールドデータと Lighthouse ラボデータの両方で LCP/INP/CLS を監視する。

**i18n の実装範囲**: 初版は `ja` と `en_US` の2ロケールを対象とする。RTL（右から左）レイアウト対応は REQ-NF-006 の将来対応に含めるが、CSS 設計時に `direction: ltr` のハードコードを避け、論理プロパティ（`padding-inline-start` 等）を優先して使用する。

**フロントエンドの JSON-LD 出力タイミング**: JSON-LD は PHP 側（`wp_head` アクション）で静的に出力し、JavaScript による動的挿入を使わない。これにより SSR/OG クローラ・AI Snapshot でも JSON-LD が確実に取得できる。

**SNF-014 の自動テスト実装**: `data-agent-section-id` の値を定義した golden fixture JSON を `tests/fixtures/section-ids.json` に保持し、Playwright テストで各テンプレートの実際の HTML と比較する。WP アップデート後の CI でこのテストが実行され、値が変化した場合に PR をブロックする。

**Lighthouse CI の設定**: `.lighthouserc.json` に LCP/INP/CLS の閾値を設定し、代表ページ（LP・HP・記事・カテゴリ）の4URLを自動計測する。スコアが閾値を下回った場合は CI が失敗し、PR マージを阻止する。ローカル Lighthouse と CI 上での Lighthouse の結果を乖離させないため、`--throttling-method=simulate` と固定ネットワーク条件を設定する。

## 非機能要件と CI の対応表

| 要件 | CI ツール | 失敗時の対応 |
|---|---|---|
| SNF-001 Core Web Vitals | lighthouse-ci | PR ブロック |
| SNF-004 axe-core | @axe-core/playwright | PR ブロック |
| SNF-009 JSON-LD 検証 | schema-dts-gen / validator CLI | PR ブロック |
| SNF-011 robots 整合性 | カスタム diff スクリプト | 警告（PR はブロックしない） |
| SNF-014 data-agent 安定性 | Playwright snapshot 比較 | WP メジャー更新後に手動確認 |
| SNF-015 Theme Review | WP Theme Check plugin | リリース前 |

**Crawler Access Matrix の更新手順**: `crawler-access-matrix.json` を更新した場合は robots.txt の再生成を自動トリガーし、変更前後の diff を管理画面で確認できるようにする。検索 SEO への影響が大きい変更（Googlebot への Disallow 追加等）は risk level HIGH として管理者への警告を必須とする。

## 非機能要件の優先判定基準

| 要件 | 理由で P0 |
|---|---|
| SNF-001 Core Web Vitals | 失格条件。速度基準未達は全成功指標に影響する |
| SNF-009 JSON-LD 検証 | Rich Results 非表示リスクと構造化データ品質に直結 |
| SNF-010 JSON-LD 重複抑制 | SEO ペナルティリスクがある |
| SNF-011 robots/canonical 整合 | noindex 事故・crawler 許可漏れは致命的 |
| SNF-013 AI Snapshot 公開境界 | 非公開情報の漏洩はセキュリティインシデント |
| SNF-014 data-agent 安定性 | 安定 ID が崩れると AI 運用の全オペレーションが壊れる |

## 参照

- L1: REQ-NF-001, REQ-NF-005, REQ-NF-006, REQ-NF-015, REQ-NF-016, REQ-NF-017
- 解析レポート: 22-AIエージェント運用性（§AI Snapshot設計, §改善すれば扱いやすくなること）
