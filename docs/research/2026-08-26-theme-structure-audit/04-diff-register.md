# 差分レジスタ — テーマA × テーマB × HELIX-WP-THEME(agent-neo)

判定値: `対応済` / `部分` / `欠落` / `思想差`（＝埋めるのでなく設計判断が要る差）

## 0. 一枚表

| 軸 | テーマA | テーマB | agent-neo | 差分判定 |
|---|---|---|---|---|
| テーマ形式 | クラシック | クラシック+ブロック | **FSE / theme.json v3** | 思想差 |
| カスタムブロック | 25（`themeA-blocks/`） | 50（`themeB/`） | **1**（`agent-neo/embed`） | 欠落 |
| ブロック登録方式 | functions.php 一括・単一バンドル | block.json + 自前ビルド | — | 思想差 |
| パターン | 0 | `themeB-pattern/*` 少数 | **24 + section-registry** | 優位 |
| ショートコード | 6（実使用 186） | 20（実使用 0） | 0 | 部分 |
| CPT | 0 | **3**（lp / blog_parts / ad_tag・全て show_in_rest） | 0 | 欠落 |
| ウィジェットエリア | 11 | **24** | **0** | 欠落 |
| メニュー | 3 | 6 | **0** | 欠落 |
| 設定の持ち方 | `themeA_*` 個別 **1,225 キー** | 単一配列（既定 540 キー） | theme.json + config/*.json | 思想差 |
| CSS トークン | 151 var（カスタマイザ由来） | 155 var（PHP 動的生成） | theme.json palette 8 + fontSizes 6 + spacing scale | 部分 |
| 構造化データ | Organization/Person/ListItem/ImageObject | Article/WebSite/WebPage/CollectionPage/Breadcrumb/SearchAction ほか | BlogPosting/WebPage/WebSite/Breadcrumb ほか | 部分 |
| 目次 | 外部プラグイン RTOC | **テーマ内蔵** `themeB_toc` | レンダラ生成 | 部分 |
| 拡張点（自前 filter） | **1** | **79** | （プラグイン側で提供） | 思想差 |
| REST | 2（内部用途・未認証） | 14（`wp/v2` 相乗り） | **34 コントローラ**（`agent-neo/v1`） | 優位 |
| 決済・会員 | Stripe 同梱・paidpost | restricted-area / only_login | 無し | 思想差（スコープ判断） |
| A/B テスト | 無し | `themeB/ab-test` | ab-test コントローラ | 対応済 |

## 1. agent-neo に「無い」もの（実運用が依存している面）

| # | 欠落 | 実測根拠 | 影響 |
|---|---|---|---|
| D-01 | 記事内広告 / CV ゾーン | テーマA 11 ウィジェットエリア（post-top/start/end/bottom・relatedpost-bottom・sidebar-tracking）/ テーマB 24（single_cta・before/after_related ほか） | 収益導線を置く場所が無い |
| D-02 | 再利用パーツ機構 | テーマB `blog_parts` CPT（REST 可）/ テーマA 番号スロット shortcode | 共通パーツの一元管理が不可 |
| D-03 | LP 機構 | テーマB CPT `lp` + `single-lp.php` / テーマA `template-full-width.php` | agent-neo は `page-lp-sample.html` + lp-* パターン 12 で代替 — CPT でない差 |
| D-04 | 装飾ブロック語彙 | 実使用 simplebox 697・button 339・blogcard 330・fukidashi 186 / themeB/dt-dd 各 106・faq-item 56・step-item 28 | 既存記事をそのまま描画できない |
| D-05 | 追尾サイドバー / 固定ボトム | テーマA `sidebar-tracking` / テーマB `fix_sidebar` `fix_bottom_menu` | 主要 CV 面が欠落 |
| D-06 | 目次 | テーマA=RTOC プラグイン / テーマB=内蔵 | 記事構造の必須要素 |
| D-07 | ナビゲーション | 両テーマ 3〜6 メニュー / agent-neo 0 | サイト回遊が組めない |

## 2. agent-neo が「持っていて両テーマに無い」もの

| # | 優位 | 内容 |
|---|---|---|
| A-01 | エージェント接点 | REST 34 コントローラ + MCP + CLI + 中間 JSON（テーマA は REST 0、テーマB は wp/v2 相乗り 14） |
| A-02 | セクション台帳 | `section-registry.json` による pattern↔section_id↔template の機械可読対応 |
| A-03 | 宣言的トークン | theme.json v3 + styles/{light,dark}.json（両テーマは PHP 生成 or カスタマイザ依存） |
| A-04 | 契約スキーマ | JSON Schema 6 + openapi.yaml（両テーマは契約文書なし） |
| A-05 | ポリシー分離 | asset-policy / third-party-tags / web-vitals-budget / boundary-guard |

## 3. 「思想差」— 埋めるのでなく判断が要る差

| # | 論点 | テーマA | テーマB | agent-neo | 判断すべきこと |
|---|---|---|---|---|---|
| P-01 | 設定の正本 | 個別オプション 1,225 | 単一配列 540 | JSON ファイル | 運用サイト移管時に何を正本にするか |
| P-02 | ブロックの所属 | テーマ内蔵・環境変数結合 | テーマ内蔵・block.json | パターン化して自作しない | Graphix NEO はブロックを持つのか持たないのか |
| P-03 | 描画の決定論 | 9/25 が render_callback（SSR） | 静的 save 中心 | 中間 JSON → 決定論レンダラ | SSR ブロックは中間 JSON へ写像できるか |
| P-04 | 拡張点 | filter 1 本 + pluggable ガード無し＝介入不能 | filter 79 本 + pluggable 関数群＝介入前提 | REST/契約で介入 | 移植先の介入モデル |
| P-05 | 収益機構 | Stripe 同梱・有料記事 | restricted-area | 無し | 会員・課金をスコープに入れるか |

## 4. 実使用に基づく移植優先度（証跡ベース）

**第 1 群（実使用 100 超・欠けると既存記事が壊れる）**
`themeA-blocks/simplebox`(697) / `themeA-blocks/button`(339) / `themeA-blocks/blogcard`(330) /
`themeA-blocks/fukidashi`(186)+`[themeA_fukidashi]`(186) / `themeA-blocks/comparechild`(177) /
`themeB/dt`(106) / `themeB/dd`(106)

**第 2 群（30〜100）**
`themeB/list`(67) / `themeA-blocks/compare`(59) / `themeB/faq-item`(56) / `flexible-table-block/table`(46) /
`themeA-blocks/postlist`(38) / `themeA-blocks/designtitle`(37) / `themeA-blocks/background`(33) /
`themeB/dl`(30) / `themeB/cap-block`(29)

**第 3 群（30 未満・後回し可）** 残り 30 種弱
**未使用（実データ 0）** テーマB の `lp` / `blog_parts` / `ad_tag` CPT・ショートコード 20 種全部・テーマA の 13 ブロック

> 注: 「未使用」は今の 2 サイトでの話。テーマB 側は公開記事 7 本（AI 産の対照群）しか無いため、
> 母数が小さい。判断材料としては topic-A（59 本・人間執筆）の分布を主に見るべき。
