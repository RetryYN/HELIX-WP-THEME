# テーマ構造照合調査（2026-08-26）

実運用 2 サイトのテーマ（JIN:R / SWELL）を XServer 経由の読み取り専用で全量調査し、
本リポの `agent-neo-theme` + `agent-neo-core` / `agent-neo-embed` と照合した記録。
テーマ側の再出発（Graphix NEO）に向けた根拠づくりとして実施。

| ファイル | 内容 |
|---|---|
| `00-REPORT.md` | 総括。方法・発見 5 件・イシュー一覧・PO 判断 |
| `01-structure-jinr.md` | JIN:R 1.4.6 構造調査（it-shukatu-college.com） |
| `02-structure-swell.md` | SWELL 構造調査（solobiz-lab.com） |
| `03-structure-agent-neo.md` | HELIX-WP-THEME（旧 AGENT NEO）構造調査 |
| `04-diff-register.md` | 差分レジスタ（17 軸 / 欠落 7 / 優位 5 / 思想差 5 / 移植優先度） |
| `10-reverse-jinr.md` | **JIN:R リバースエンジニアリング**（起動 / 設定 707 アクセサ / 2,098 行 CSS 生成 / 描画 / ブロック / h2 広告 / REST / データモデル） |
| `11-reverse-swell.md` | **SWELL リバースエンジニアリング**（クラス構成 / 設定 4 グループ + 独自テーブル / Style アキュムレータ / 2 パス解析 / 本文パイプライン / ブロック契約） |
| `12-mechanism-comparison.md` | **機構比較** — 同じ問題を 3 テーマがどう解いているか（8 観点） |
| `issues/` | 個別詳細調査イシュー草案 16 本（THEME-INV-01〜16）。**未起票** |
| `evidence/` | サーバー調査の生出力（raw） |

閲覧用ページ: https://claude.ai/code/artifact/beae459c-4555-485d-88f1-cd23423660b6

## 前提と制約

- サーバーへの**書き込みは一切していない**（`find` / `grep` / `ls` / `cat` / `sed` /
  WP-CLI の読み取りサブコマンドと SELECT のみ）。
- `marketantei.com` / `retry0907yn.com` は PO 指示（2026-08-21）のノータッチ 2 サイトのため、
  読み取りも行っていない。
- XServer 管理 API はサーバー内ファイルの読み取り経路を持たないため、ファイル調査は
  同じ XServer の SSH 車線（承認済みの接続 4 車線の 1 本）で行った。
