---
layer: L1
sub_doc: functional
status: confirmed_input
pair_artifact: docs/test-design/l12-operational-value-test-design.md
authority: docs/requirements/authority.md
---

# L1 Functional Requirements

| ID | ユーザー視点の要求（拡大の提案） | 下流 family |
| --- | --- | --- |
| WT-FRL1-01 | 置き場所（面）を選べる: 記事内広告・CV・関連前後・固定ページ上下・ヘッダー内・SP 下部固定・追尾サイドバーなど、テーマA/B にあって本テーマに無い面を slot として持つ | ZONE |
| WT-FRL1-02 | 共有パーツと骨格を選べる: header / footer / sidebar / hero の複数案、テンプレ変種を GUI と AI の双方から差し替えられる。LP は投稿型で持ち（イベント / 比較特設を含む種別、ディレクトリ非依存 URL）、フォーム制御・デザイン拡張・イベント計測を備える | PARTS / LP |
| WT-FRL1-03 | 記事内語彙で書ける: 囲み・ボタン・リンクカード・吹き出し・手順・比較表・定義リスト・FAQ・タブなど実使用上位の語彙で記事を組める。目次と PR 表記が自動で出る | VOCAB |
| WT-FRL1-04 | 見た目の引き出しがある: 見出し階層・見出し / ボタンの block style・最小限の動き・レスポンシブ段・style variation でテーマA/B 並みの表現に届き、さらにサイトパターン（コーポレート / サービス / ブランド / ポータル / 比較）の品質水準まで広げる（大量調査が前提） | LOOK |
| WT-FRL1-05 | 記事単位で切り替えられる: サイドバー・目次・シェア・PR を投稿ごとに ON/OFF できる | META |
| WT-FRL1-06 | 既存サイトの設定を写せる: カスタマイザ・設定画面・ウィジェット・プリセット・独自ブロックを本テーマの JSON 資産へ写像できる | MIGRATE |
| WT-FRL1-07 | エージェントが JSON で全部を操作できる: 面・部品・値・変種・テンプレの選択、中間 JSON の抽出、再利用パーツの参照が REST / MCP / CLI から行える | AGENT |
| WT-FRL1-08 | 値は 3 域で制御される: 安全域は自由、生値は警告、破壊域は停止する。境界値は PoC で決める | VALUE |
| WT-FRL1-09 | 構造化データと AI 向け出力が単一出力元から出る: CollectionPage / SearchAction を加え、FAQ / HowTo / ItemList は語彙から自動生成する | SEO |
| WT-FRL1-10 | 実証済みパターンを台帳で GRAPHIX-NEO へ渡せる | INTAKE |

L1 の ID はユーザー要求であり、L3 の system requirement ID とは区別する。
各行はテーマA / B との PoC 比較で本テーマに不足すると分かった面・語彙・引き出しを、機械可読性を保つ形で取り込む提案である
（出典: `docs/research/2026-08-26-theme-structure-audit/04-diff-register.md`、`docs/design/catalog/customizability.md`、`docs/research/2026-08-27-poc-browser-verification/theme-comparison.md`）。
