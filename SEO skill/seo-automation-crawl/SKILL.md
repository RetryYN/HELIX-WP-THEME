---
name: seo-automation-crawl
description: Use when implementing or designing automated website crawling and SEO monitoring with GitHub Actions, cron, Playwright, Lighthouse, sitemap crawlers, URL Inspection API, GA4/GSC sync, IndexNow, and alerting.
---

# seo-automation-crawl

## Purpose

GitHub Actionsや外部ジョブでWebサイトを定期巡回し、SEO劣化、indexability問題、表示速度、構造化データ、リンク切れを自動検出する。

## Inputs

- sitemap.xml、robots.txt、対象URLリスト、重要URL、認証要否。
- GSC/GA4認証情報、PageSpeed API、IndexNow key、通知先。
- GitHub Actions workflow、Secrets、実行頻度、レポート保存先。

## Process

1. URLソースを sitemap、重要URL、直近変更URL、GSC上位URLに分ける。
2. robots.txt、HTTP status、canonical、noindex、title、description、h1、JSON-LDを取得する。
3. PlaywrightまたはLighthouseでレンダリング、CWV近似、JSエラー、スクリーンショットを取る。
4. URL Inspection APIで重要URLのindex status、canonical、rich resultsを確認する。
5. 差分をMarkdown、JSON、CSVで保存し、重大エラーだけ通知する。
6. 新規・更新URLはIndexNowの対象にできるが、Googleへの直接通知とは扱わない。

## Outputs

- `crawlRunReport`
- `seoRegressionReport`
- `urlInspectionBatch`
- `lighthouseSummary`
- `brokenLinkReport`
- `indexNowSubmissionLog`

## GitHub Actions Rules

- GitHubは自動でWeb巡回しない。巡回ロジックをworkflowに実装する。
- `schedule` はデフォルトブランチで動く前提にする。
- cron時刻はUTCで管理し、混雑時間を避ける。
- Secretsをログに出さない。
- 外部サイトへ高頻度アクセスしない。レート制限、User-Agent、timeout、retry、robots方針を明記する。
- 監査対象サイトが他者所有の場合は明示許可が必要。

