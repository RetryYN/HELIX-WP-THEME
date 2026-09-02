# HELIX-WP-THEME Session Handoff（2026-08-29）

旧版（2026-05-03・AGENT NEO 時代の L1 凍結準備）は git 履歴を参照。現行は L0 改定ドラフト（PR #48）を
入力とした **監査 → PoC 証跡** 段階であり、要求 freeze・設計・実装には進んでいない。

## 1. 現在サマリ
- テーマ main = `7df6108`（#61 merge 後）。統合層 `HELIX-MARKETING-HARNESS` の pin は同日更新（ずれていれば pin 更新待ち）。
- テーマ構造監査（root #2 と INV/CAT/JSON/GATE 全 task・finding #21）は Issue 上すべて close 済み。
  証跡は `docs/research/2026-08-26-theme-structure-audit/`（PR #50）、伏せ字ガードは PR #51。
- L0 改定ドラフト「構造自由・破壊域停止」+ L3-A5 PoC 棚卸しは PR #48 で main 収録済み（ドラフト扱い）。
- 設計 PoC 3 本（一貫性ゲート・スタイル 3 案・共有パーツ）は PO 判断（08-29）で採用され、#53/#54/#55 として main 収録済み（下記 §3）。

## 2. 直近 merge（2026-08-26〜29）
#23 伏せ字化 / #24 article-cta validation / #33 theme.json 既定プリセット / #48 L0 改定ドラフト /
#49 PHPCS 19 件 / #50 監査証跡 / #51 伏せ字ガード /
#53 一貫性ゲート G-T1/T1b/T2/T3/S1/S2 + トークン構造規約（外部デザインツールの取り込み経路は持たない）+ 共有パーツ 20 本 /
#54 スタイルバリエーション 3 案（編集誌・奥行き・業務）/ #55 パターン 27 本・スタイル 4 本の多様性拡充 + `docs/design/parts-catalog.md` /
#58 既存 14 本の CSS 変数エスケープ・cover 形式（#56 #57）/ #60 G-E1 Block validation 33 パターン修正（#59、ローカル docker WP 7.1 の証跡は `docs/research/2026-08-29-ge1-local/`）。

## 3. PO 判断（2026-08-29）と PoC 枝の帰結
| 元 PR | 判断 | 帰結 |
|---|---|---|
| #36 一貫性ゲート + 外部ツール取り込み | 構造は参考にするが外部ツールは使わない | 取り込み経路を除いて #53 に再構成・merge。#36 は superseded で close |
| #38 スタイル 3 案 | 不足分含めすべて採用 | #54 に rebase・merge。#38 は close |
| #39 共有パーツ | すべて採用 | #53 に内包して merge。#39 は close |
| #48 L0 改定 | 多様性を出しパーツ・スタイルを既存より多く作って試験 | #55（+27 パターン・+4 スタイル、ゲート FAIL=0）で実施 |

各 merge は Sol 最終レビュー PASS を条件に PO が許可。

## 4. PO 判断待ち（merge ブロッカーなし・Issue は close 済みで運用判断のみ未了）
- サーバ層対処: `.user.ini` の display_errors=Off（#21）、未認証 REST の遮断（#15）、ベンダー報告要否。
- `theme_mods` 235 キーの分類（値の読み取りを伴う）。
- `reports/INV-*.md` 8 本への 2026-08-27 実測の反映は #61 で反映済み。
- G-E1 は全 71 パターン invalid=0（2026-08-30、ローカル docker WP 7.1）。リモート PoC サイトには #58 時点の枝が配備されたままなので、次回 main で再配備する。
- #60 の font-size プリセット化で lp-proof 3.5→3rem、lp-pricing 2.5→3rem / 2→2.25rem に変わった（意図した割り切り）。

## 5. 規律メモ
- 公開リポ。第三者製品名・サイト特定情報は伏せ字（対応表はリポ外）。commit 前に
  統合層の `scripts/check-public-safety.sh --staged`（本リポの pre-commit hook が同等検査を実行）と、監査成果には `verify-public-redaction.sh`（#51）を通す。
- 新しい証跡で結論を更新するときは `reports/INV-*.md` の該当節を先に読み、要約層だけを更新しない
  （PROGRESS.md 自己点検 2026-08-27 参照）。
- 攻略系の実証結論はメイン会話へ再叙述せず、番号・パスで指す。

## 6. 読む順
1. 本ファイル
2. `docs/research/2026-08-26-theme-structure-audit/PROGRESS.md`
3. `docs/requirements/authority.md`（要求正本）と `docs/planning/L0-agent-controlled-variety.md`（L0 企画・要求の起点）
4. `docs/research/2026-08-26-theme-structure-audit/reports/INV-*.md`
