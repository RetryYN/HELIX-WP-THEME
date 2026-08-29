# HELIX-WP-THEME Session Handoff（2026-08-29）

旧版（2026-05-03・AGENT NEO 時代の L1 凍結準備）は git 履歴を参照。現行は L0 改定ドラフト（PR #48）を
入力とした **監査 → PoC 証跡** 段階であり、要求 freeze・設計・実装には進んでいない。

## 1. 現在サマリ
- main = `f470360`。統合層 `HELIX-MARKETING-HARNESS` の pin も同 commit。
- テーマ構造監査（root #2 と INV/CAT/JSON/GATE 全 task・finding #21）は Issue 上すべて close 済み。
  証跡は `docs/research/2026-08-26-theme-structure-audit/`（PR #50）、伏せ字ガードは PR #51。
- L0 改定ドラフト「構造自由・破壊域停止」+ L3-A5 PoC 棚卸しは PR #48 で main 収録済み（ドラフト扱い）。
- 設計 PoC 3 枝は Draft PR として再開中（下記 §3）。ready/merge は Codex lane 委譲。

## 2. 直近 merge（2026-08-26〜28）
#23 伏せ字化 / #24 article-cta validation / #33 theme.json 既定プリセット / #48 L0 改定ドラフト /
#49 PHPCS 19 件 / #50 監査証跡 / #51 伏せ字ガード。

## 3. 未 merge の設計 PoC 枝（Draft PR・レビュー対応済み）
| PR | 枝 | 内容 |
|---|---|---|
| #36 | `feature/design-consistency-gates` | 一貫性ゲート G-T1/T2/T3/S1/S2 と Figma 取り込み経路 |
| #38 | `design/style-variations-3` | スタイルバリエーション 3 案（編集誌・奥行き・業務） |
| #39 | `design/shared-parts-patterns` | 共有パーツのパターン化（ヘッダー 7 / フッター 5 / サイドバー 5） |

いずれも PoC 証跡の位置づけ。採用可否と要求への昇格は PO 判断。

## 4. PO 判断待ち（技術ブロッカーなし）
- サーバ層対処: `.user.ini` の display_errors=Off（#21）、未認証 REST の遮断（#15）、ベンダー報告要否。
- `theme_mods` 235 キーの分類（値の読み取りを伴う）。
- `reports/INV-*.md` 8 本への 2026-08-27 実測の反映（現状は PROGRESS と Issue コメントのみ）。
- 上記 §3 の 3 枝の採用可否。

## 5. 規律メモ
- 公開リポ。第三者製品名・サイト特定情報は伏せ字（対応表はリポ外）。commit 前に
  `bash scripts/check-public-safety.sh --staged` 相当を通す。
- 新しい証跡で結論を更新するときは `reports/INV-*.md` の該当節を先に読み、要約層だけを更新しない
  （PROGRESS.md 自己点検 2026-08-27 参照）。
- 攻略系の実証結論はメイン会話へ再叙述せず、番号・パスで指す。

## 6. 読む順
1. 本ファイル
2. `docs/research/2026-08-26-theme-structure-audit/PROGRESS.md`
3. `docs/planning/L0-planning.md` と PR #48 のドラフト
4. `docs/research/2026-08-26-theme-structure-audit/reports/INV-*.md`
