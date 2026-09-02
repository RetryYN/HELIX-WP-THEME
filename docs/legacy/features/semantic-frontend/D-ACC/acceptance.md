# semantic-frontend — D-ACC（受入条件）

## 概要

`semantic-frontend` の受入条件は、フロントエンド HTML の意味的品質・AI 可読性・構造化データ・アクセシビリティ・Crawler Access Matrix 整合性を自動テストと実査で確認する。

## 受入条件テーブル

| ID | 対応要件 | テスト条件 | 期待結果 | 測定方法 |
|---|---|---|---|---|
| ACC-SF-001 | SF-001 | 代表 LP・HP・BLP を表示し、セクションと CTA の HTML を確認する | 全主要セクションに `data-agent-section-id`、全 CTA に `data-cta-id` が存在する | HTML 解析 + 自動検査 |
| ACC-SF-002 | SF-002 | WP コアアップデート前後で `data-agent-section-id` の値を比較する | 値が変化しない | 回帰テスト |
| ACC-SF-003 | SF-003 | `GET /wp-json/agent-neo/v1/public/pages/{id}/snapshot` を呼ぶ | section 一覧・CTA label・JSON-LD 状態・canonical・robots が返る | API contract test |
| ACC-SF-004 | SF-003 | 非公開投稿の snapshot を認証なしで取得しようとする | 404 または空の data が返り、本文が含まれない | security test |
| ACC-SF-005 | SF-004 | robots.txt を取得し、`crawler-access-matrix.json` と比較する | OAI-SearchBot の設定・GPTBot の設定が matrix と一致する | diff check |
| ACC-SF-006 | SF-005 | 記事ページの `<head>` を確認する | schema.org/Article の JSON-LD に author, datePublished, headline が含まれる | JSON-LD validation |
| ACC-SF-007 | SF-006 | FAQ セクション（`data-agent-role="faq"`）を持つページを表示する | schema.org/FAQPage の JSON-LD が自動生成される | Rich Results Test |
| ACC-SF-008 | SF-007 | Review ブロックを持つ記事を表示する | schema.org/Review に reviewRating, author, itemReviewed が含まれる | JSON-LD validation |
| ACC-SF-009 | SF-008 | HP を表示する | schema.org/Organization に name, url, logo が含まれる | JSON-LD validation |
| ACC-SF-010 | SF-009 | カテゴリページでパンくず JSON-LD を確認する | schema.org/BreadcrumbList の URL が canonical と一致する | JSON-LD + canonical check |
| ACC-SF-011 | SF-010 | axe-core を代表ページ（LP/HP/記事）で実行する | Critical/Serious 指摘 0 件 | CI axe-core test |
| ACC-SF-012 | SF-010 | CTA ボタンを keyboard のみで操作する | Tab で到達・Enter で活性化・focus-visible リングが表示される | keyboard test |
| ACC-SF-013 | SF-011 | JavaScript を無効にしてタブ付きコンテンツブロックを表示する | 全タブのコンテンツが HTML に存在し、読み取り可能 | JS-disabled crawl test |
| ACC-SF-014 | SF-014 | BLP（記事 LP）を有効化した記事を表示する | 記事下 CTA に `data-cta-id` と `data-service-id` が存在し、計測イベントが発火する | 計測テスト |
| ACC-SF-015 | SF-015 | 複数サービス（service_id 3件）を持つ HP の Gateway Grid を表示する | 各サービスカードに `data-service-id` が付与されている | HTML 解析 |
| ACC-SF-016 | SNF-001 | 代表 LP/HP/記事で Lighthouse を実行する | LCP 2.5s 以下、INP 200ms 以下、CLS 0.1 以下 | Lighthouse/CrUX |
| ACC-SF-017 | SNF-007 | `wp i18n make-pot` を実行する | UI 文字列が全て `agent-neo.pot` に抽出される | i18n test |
| ACC-SF-018 | SNF-010 | Yoast SEO 有効化環境で LP を表示する | JSON-LD が重複出力されない（または競合警告が管理画面に表示される） | JSON-LD duplicate check |

## 異常系・境界値

| ID | 条件 | 期待動作 |
|---|---|---|
| ACC-SF-ERR-001 | `section_id` が重複する LP | ビルド時または save_post フックで重複を検出して管理画面に警告を表示する |
| ACC-SF-ERR-002 | JSON-LD の必須フィールド（datePublished）が未設定 | 管理画面に SEO 警告を表示し、出力を抑制する |
| ACC-SF-ERR-003 | Crawler Access Matrix の設定と robots.txt が不整合 | robots.txt 生成時に差分を検出して管理画面に警告を出す |
| ACC-SF-ERR-004 | JS 無効環境でアコーディオン要素が折りたたまれたままになる | FAQ 本文が `<noscript>` タグまたは HTML 展開で読み取れる |

## 受入条件のカバレッジマップ

| 要件 | ACC ID |
|---|---|
| SF-001 data-agent 属性 | ACC-SF-001 |
| SF-002 安定 DOM anchor | ACC-SF-002 |
| SF-003 AI Snapshot | ACC-SF-003, 004 |
| SF-004 Crawler Access Matrix | ACC-SF-005 |
| SF-005 Article JSON-LD | ACC-SF-006 |
| SF-006 FAQ JSON-LD | ACC-SF-007 |
| SF-007 Review JSON-LD | ACC-SF-008 |
| SF-008 Organization JSON-LD | ACC-SF-009 |
| SF-009 Breadcrumb JSON-LD | ACC-SF-010 |
| SF-010 a11y 基本配慮 | ACC-SF-011, 012 |
| SF-011 JS 非依存 | ACC-SF-013 |
| SF-014 BLP 計測 ID | ACC-SF-014 |
| SF-015 service-aware IA | ACC-SF-015 |
| SNF-001 Core Web Vitals | ACC-SF-016 |
| SNF-007 i18n | ACC-SF-017 |
| SNF-010 JSON-LD 重複抑制 | ACC-SF-018 |

## テスト自動化の構成

| テスト種別 | ツール | 実行タイミング |
|---|---|---|
| JSON-LD 検証 | schema.org validator（CLI）/ Google Rich Results API | PR マージ時 |
| axe-core a11y | @axe-core/playwright | PR マージ時 |
| Lighthouse | lighthouse-ci | リリース前 |
| data-agent 安定性回帰 | Playwright + HTML スナップショット比較 | WP メジャー更新後 |
| AI Snapshot API contract | hurl / Postman | PR マージ時 |
| robots.txt 整合性 | カスタムスクリプト（matrix vs 実ファイル diff） | Settings 更新時 |

## ID 命名規約の違反検出

`save_post` フックで section_id の命名規則（`sec_` prefix + alphanumeric）を検証し、命名規則違反は管理画面に警告を表示する。重複 section_id は同一ページ内で禁止し、保存前に重複チェックを実施する。

## 参照

- L1: ACC-005, ACC-006, ACC-011, ACC-012, ACC-NF-005, ACC-NF-009, ACC-NF-010, ACC-NF-011
- 解析レポート: 22-AIエージェント運用性（§AGENT NEOに追加すべき契約, §AI Snapshot設計）
