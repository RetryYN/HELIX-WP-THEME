---
layer: L1
sub_doc: nfr
status: confirmed_input
pair_artifact: docs/test-design/l12-operational-value-test-design.md
authority: docs/requirements/authority.md
---

# L1 Non-functional Requirements

| ID | 品質要求 | 測定方向 |
| --- | --- | --- |
| WT-NFRL1-01 | ページ型別に JS / CSS 予算を分離し、記事 15KB・LP 50KB 等の予算を超えない | performance budget test |
| WT-NFRL1-02 | 公開面と編集面は WCAG 2.2 AA を満たし、破壊域判定にコントラスト比 AA 未満を含む | accessibility test |
| WT-NFRL1-03 | 全パターン・パーツ・テンプレートの Block validation が実機で invalid=0 | G-E1 実機ゲート |
| WT-NFRL1-04 | パターン・パーツ・テンプレートの生値は baseline を超えない | G-T2 静的ゲート |
| WT-NFRL1-05 | 未認証 REST を生やさず、PHP Warning を応答本文へ漏らさず、外部 URL を未検証で取得しない | security test |
| WT-NFRL1-06 | 描画時に DB 書込（set_theme_mod / update_option）を行わない | render side-effect test |
| WT-NFRL1-07 | redirect_canonical・session など WP グローバル挙動を改変しない | global behavior test |
| WT-NFRL1-08 | 公開リポジトリに第三者製品名・実運用サイト・個人環境パス・credential を持たない | public-safety check |
| WT-NFRL1-09 | WP 6.6 以上（7.x 検証）・PHP 8.1 以上・theme.json v3・GPL 互換で動く | platform matrix test |
| WT-NFRL1-10 | ゲート・写像・生成は同一入力で同一結果を返す | determinism test |
| WT-NFRL1-11 | AI 生成コンテンツの開示（可視バッジ・JSON-LD・メタタグ）を表現できる | disclosure rendering test |
| WT-NFRL1-12 | 移行は事前スナップショットなしに設定を書き換えない | migration snapshot test |
| WT-NFRL1-13 | 訪問者の個人データを保存せず、計測は匿名イベント（cta_id 等）に限定する | privacy test |
| WT-NFRL1-14 | 編集権限は WP capability に従い、破壊域停止は権限で迂回できない | permission test |
| WT-NFRL1-15 | 有料外部サービス・外部デザインツールへの依存を持たない | cost boundary test |
| WT-NFRL1-16 | ゲート結果と実機検証を JSON 証跡として保存し、CI と PR から参照できる | observability test |
| WT-NFRL1-17 | 適用と移行は dry-run と rollback を持つ | recovery test |

## 意味境界

- `WT-NFRL1-02` は公開面・編集面の到達性と破壊域判定への組み込み、`WT-NFRL1-14` は権限による迂回不可を扱う。
- `WT-NFRL1-05` / `06` / `07` は第三者テーマ監査（INV-13 / 16 / 17）で観測した欠陥を本テーマで再発させないための教訓であり、
  第三者テーマの是正は本リポの要求ではない（HELIX-WP-HARNESS #198）。
- `WT-NFRL1-03` / `04` の数値（invalid=0、baseline 438）は 2026-08-29 時点の実測で、変更は設計判断（WT-Q-GATE-01）を要する。
