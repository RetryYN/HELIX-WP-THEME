---
name: seo-skill-fork
description: Use when managing SEO skill packs through GitHub forks: upstream synchronization, project-specific skill customization, pull request review, versioning, release notes, and safe distribution to AGENT NEO agents.
---

# seo-skill-fork

## Purpose

GitHubを、AGENT NEOのSEOスキル群をフォーク、配布、更新、レビューする基盤として使う。Webサイト巡回ではなく、スキルそのもののライフサイクル管理を担当する。

## Inputs

- upstream SEO skill repository
- project fork repository
- branch naming policy
- skill change request
- release version
- target project package: personal, corporate, agency, enterprise

## Process

1. upstreamを正本、forkをプロジェクト差分として定義する。
2. fork内の変更を、共通化すべきものと顧客固有に残すものに分類する。
3. `SKILL.md` の frontmatter、trigger、inputs、process、outputs、rules をレビューする。
4. 参照URLは一次情報を優先し、アクセス日を記録する。
5. upstream同期時は破壊的変更、名称変更、契約変更、禁止事項変更を確認する。
6. 共通価値がある改善はPull Requestでupstreamへ戻す。
7. release notesに追加、変更、非推奨、削除、移行手順を書く。

## Outputs

- `skillForkPlan`
- `skillChangeReview`
- `upstreamSyncReport`
- `skillReleaseNotes`
- `projectOverridePolicy`

## Repository Layout

推奨構成:

```text
SEO skill/
  SKILL.md
  references/
  seo-strategy/
  seo-onpage/
  seo-technical/
  seo-content/
  seo-analytics/
  seo-ai-llmo/
  seo-ux-cro/
  seo-growth/
  seo-international/
  seo-offpage/
  seo-automation-crawl/
  seo-skill-fork/
```

## Branch Rules

- `main`: 配布可能な安定版。
- `develop`: 次版統合。
- `project/<name>`: 顧客またはプロジェクト固有差分。
- `skill/<skill-name>`: 個別スキル改修。
- `research/<topic>`: 調査結果反映。

## Review Checklist

- `name` と `description` はエージェントがトリガー判断できる具体性がある。
- 入力、処理、出力がJSON契約に落とせる。
- 禁止事項とエスカレーション条件がある。
- WordPressテーマ側とAutomation SEO側の責務境界が明確。
- ソースは公式または一次情報を優先している。
- 顧客固有情報、APIキー、認証情報、未公開戦略を含まない。

## AGENT NEO Rules

- forkはコピーではなく、差分管理の単位として扱う。
- upstream同期前に既存プロジェクトの運用ルールを壊さないか確認する。
- スキル変更は実装変更と同じくレビュー対象にする。
- スキル内に実運用シークレットを書かない。

