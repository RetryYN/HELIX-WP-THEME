---
name: agent-neo-seo-skill-suite
description: Use this project-local skill suite when AGENT NEO agents design, audit, implement, or operate SEO features for a WordPress FSE theme and Automation SEO integration. Routes work across strategy, on-page, technical SEO, content, analytics, UX/CRO, AI/LLMO, growth, international, off-page, GitHub skill forks, and optional automated crawling skills.
---

# AGENT NEO SEO Skill Suite

## References

- 元SEOスキルマップ正本: `references/seo-skill-map.md`
- カバレッジ対応表: `references/coverage-matrix.md`
- 共有SEO契約: `references/agent-seo-contracts.md`
- WPテーマ境界: `references/wp-theme-seo-boundary.md`
- 調査ソース: `references/research-sources.md`

このフォルダは、プロジェクト内のAIエージェントが参照するローカルSEOスキル群です。外部にインストールするCodex skillではなく、AGENT NEOのテーマ設計・Automation SEO連携・運用監査で使う判断基準として扱います。

## 使う順番

新規テーマ設計:
1. `seo-strategy`
2. `seo-technical`
3. `seo-onpage`
4. `seo-content`
5. `seo-analytics`
6. `seo-ai-llmo`
7. `seo-ux-cro`
8. `seo-growth`

既存テーマ監査:
1. `seo-analytics`
2. `seo-technical`
3. `seo-onpage`
4. `seo-content`
5. `seo-ai-llmo`
6. `seo-growth`

GitHubをスキルのフォーク・配布元として使う場合:
1. `seo-skill-fork`
2. `seo-growth`
3. `seo-analytics`

Webサイトの巡回監査を別途自動化する場合:
1. `seo-automation-crawl`
2. `seo-technical`
3. `seo-analytics`
4. `seo-growth`

多言語・海外展開:
1. `seo-strategy`
2. `seo-international`
3. `seo-technical`
4. `seo-content`

被リンク・PR施策:
1. `seo-strategy`
2. `seo-offpage`
3. `seo-analytics`
4. `seo-growth`

## AGENT NEOで必ず守る境界

- テーマ本体は、HTML構造、FSEテンプレート、theme.json、セクション設計、表示速度、アクセシビリティ、基本的な構造化データの出力責務を持つ。
- Automation SEOまたはコアプラグインは、GSC/GA4連携、キーワードクラスタ、計測、A/B、IndexNow、監査ジョブ、AI生成、監査ログ、API認証を持つ。
- テーマだけにAI原価、外部APIキー、重い監査ジョブ、個人情報、認証情報を持たせない。
- すべてのAI操作は `dryRun`、差分、監査ログ、ロールバック情報、権限チェックを持つ。
- SEOの成功判定は、検索順位だけでなく、indexability、CTR、CV、CWV、内部リンク、構造化データ、引用されやすさで見る。

## 共通アウトプット

各SEO作業は以下のどれかを返します。

- `seo-audit-report`: 現状評価、重大度、根拠URL、修正案、担当境界。
- `seo-implementation-plan`: 実装対象、受入条件、JSON契約、テスト、リスク。
- `seo-change-request`: 変更差分、期待効果、ロールバック、計測方法。
- `seo-operation-runbook`: 定期実行、失敗時対応、認証、通知、保管期間。

## エスカレーション

次は人間確認が必要です。

- 医療、金融、法律などYMYL領域の主張作成。
- 外部APIキー、OAuth、GA/GSC/広告アカウント、個人情報の接続。
- 競合テーマのコード、画像、固有CSS、文言の流用判断。
- 価格表示、景表法、薬機法、アフィリエイト表記、ステマ規制に関わる表示。
