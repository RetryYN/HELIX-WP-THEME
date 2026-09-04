# 要求由来のパーツ・部品一覧とパターン選定の具体化

- 作成: 2026-09-04（PO 指示「要求から必要パーツや部品をすべて一覧化し、パターン選定の仕方まで具体化」）
- 入力: `docs/requirements/l3/requirements-ir.json`（123 件）、実サイト調査（`docs/research/2026-09-04-site-survey/`）、デザイン試作 02（`docs/research/2026-09-04-design-prototype-02/`）
- 追記 2026-09-05（2）: PO 指摘を受けて header 9 型・footer 3 型 + サイトマップ・末尾セット・リンクカード 3 型・カテゴリ ミニ HOME・404 3 変種 + CV・ゾーン Z1〜Z11・サイトパターン variation 5 本をカタログ（scratchpad、DOM 注入）で提示。反映先は #98 / #100 / #102 / #107 / #109 / #112 / #122 / #129。「h2 ×3 / h3 ×2 / ボタン ×3」は PO 指定ではなく LOOK-01 の Claude 最小案が要求文に残ったもので、下限として改定する（#122）。
- 追記 2026-09-05: PO 指示でアイキャッチ（位置 × 有無）とカード等のメディア枠（アイコン・アップロード・写真の対応幅）を追加。ダーク variation は PO 決定（WT-Q-LOOK-04 不採用）に反していたため試作から撤去。
- 位置づけ: 要求からの機械的な展開 + 試作 02 の充足状況。設計（L4）の決定ではない。「選定軸」は要求文に書かれた選択肢を列挙したもので、要求に無い選択肢は追加していない。
- 表記: 状態 = ◎ 試作 02 で実装・描画済 / ○ 部分 / △ 骨組みのみ / × 未着手。Issue 列は本 PR と同時に起票した GitHub Issue 番号（root #93、capability #94 配下の task）。

## 1. 選定の 10 軸（要求が定める「選べる場所」）

| # | 軸 | 何を選ぶか | 誰が・どこで | 由来 |
|---|---|---|---|---|
| S1 | style variation | 色 8 スラッグ + 書体系統 + 角丸 + 影の値差し替え（段の増減・スラッグ変更は不可） | Site Editor「スタイル」/ MCP パック | LOOK-02, LOOK-03, LOOK-04, NFR-VALUE-03 |
| S2 | block style | 部品単位の見た目（h2 ×3 / h3 ×2 / ボタン ×3 / 囲み・目次・PR 表記・打消し・タブ…） | ブロックのスタイル選択 / AI の語彙選択 | LOOK-01, LOOK-02, VOCAB-01/02/03, LEGAL-03 |
| S3 | パーツ案（同一 Block Types のパターン群） | header / footer / sidebar / hero の複数案から 1 つ | Site Editor のテンプレートパーツ / 設定 JSON / MCP | PARTS-01, SP-02 |
| S4 | テンプレ変種（テンプレ名） | single-2col / 1col、footer カラム数、404・検索の変種、LP 種別 | テンプレ割当（投稿・設定 JSON） | PARTS-02, TPL-01, LP-01 |
| S5 | slot / ゾーン割当 | 6 slot × 23 ゾーン語彙に creative（ID 参照）を割当、first-match-wins の overrides | 設定 JSON（WT-UI-10）/ MCP | ZONE-01/02/03, BANNER-01 |
| S6 | 投稿メタ 4 キー | sidebar / toc / share / pr の記事単位 ON/OFF・上書き | 投稿編集画面 / MCP | META-01, VOCAB-02/03 |
| S7 | 設定 JSON（サイト既定） | 目次配置方式・ページ種別表示、PR 表記デザイン、下部固定の積層、LP 種別既定、著者・レコメンド・フォント・SNS… | WT-UI-10（テーマ設定画面 1 つ）/ MCP / export・import | ADMIN-01, SNS-01, AUTHOR-01, RECO-01, LOOK-04 |
| S8 | device 別差分 JSON | 共通 fluid 定義に対する SP / PC 差分（比較表: 横スクロール / カード、タブ: アコーディオン、目次: 開閉、ギャラリー: スワイプ、CTA: 全幅 / sticky、ヘッダー配置、下部固定タブ） | 設定 JSON / Site Editor / MCP、両幅プレビュー | SP-01/02/03, ZONE-01 |
| S9 | 正本参照 | 商品正本 → 商品カード / ランキング / 比較専用テーブル / CTA 束 / レビュー、バナー正本 → ゾーン、著者正本 → 著者欄、事業者情報 → 固定ページ | 正本 ID を選ぶ（本文ブロック属性 / ゾーン割当） | SELL-01/02, BANNER-01, AUTHOR-01, PAGE-01 |
| S10 | variant（A/B） | section / hero / CTA / 商品テーブル / LP 全体の variant 登録・選択、cookie 固定割当 | WT-UI-10 / MCP、Core プラグイン API | AB-01/02/03 |

選定の流れ（要求から読める順序）: S7 でサイト既定 → S1 で見た目の土台 → S3/S4 で面の骨格 → S5 で面への割当 → 本文は S2 + S9 で組み、S8 で device 形を決め、S6 で記事単位に上書き、S10 で試験配信。AI（MCP 常用パック）は同じ軸を manifest から読んで選ぶ（AGENT-01）。

## 2. 面（テンプレート）

| 部品 | 由来 | 選定軸と選択肢 | 状態 | Issue |
|---|---|---|---|---|
| トップ（企業型 / メディア型） | PARTS-01, LOOK-03 | S3: hero 案、S5: slot、S1 | ◎ 2 型 | – |
| 記事 single-2col / single-1col | PARTS-02, META-01 | S4: テンプレ名で 2 変種、S6: sidebar/toc/share/pr | ○ 1col のみ | #95 |
| 固定ページ | PAGE-01 | S4: page / page-canvas | ◎ | – |
| LP（CPT、種別: 通常 / イベント / 比較特設） | LP-01, LP-02 | S4: 種別（JSON 列挙）、S1: LP 専用 variation、S2: LP 専用 block style、S10 | △ page テンプレ + パターン流用 | #96 |
| 一覧（カテゴリ / タグ / 日付 / 著者） | RECO-01, AUTHOR-02, SEO-01（CollectionPage） | S3: sidebar 案、S4 | △ index のみ | #97 |
| カテゴリ面「ミニ HOME」（親 › 子 › 孫の件数、読む順番、カテゴリ内人気、新着、CTA slot） | RECO-01, SEO-01, NAV-01, PO 指示 2026-09-05 | S4: 一覧変種（ミニ HOME / 単純一覧）、S7: 人気の集計方式・期間、S9: カテゴリ正本（説明・画像・読む順番）、S5: CTA slot | × 案のみ提示 | #129 |
| 検索結果（3 変種: 人気記事 / CTA / 検索語提案） | TPL-01 | S4: 変種名、noindex 既定 | × | #98 |
| 404（3 変種、各変種に CV 導線 slot: 比較記事 / LP） | TPL-01, PO 指示 2026-09-05 | S4: 変種、S5: CV slot の割当 | △ 1 変種 | #98 |
| 著者アーカイブ | AUTHOR-01/02 | S9: 著者正本 | × | #97 |
| 固定ページパターン 7 種（会社概要 / 問い合わせ / 採用 / プライバシー / 特商法 / 外部送信先一覧 / アクセシビリティ方針） | PAGE-01, LEGAL-02 | S9: 事業者情報 JSON 自動充填、外部送信先はタグ正本から自動生成 | × | #99 |

## 3. 共有パーツ（template part / slot）

| 部品 | 由来 | 選定軸と選択肢 | 状態 | Issue |
|---|---|---|---|---|
| header（複数案） | PARTS-01, SP-02 | S3: 案（ロゴ｜ナビ｜CTA｜検索の配置）、S8: SP 配置（ロゴ・ハンバーガー・検索・主要 CTA の位置と有無）、S5: ヘッダー内 slot | ○ 1 案（SP: ロゴ｜CTA｜ハンバーガー） | #100 |
| ドロワー（階層・CTA・SNS） | SP-02 | S8: 階層の深さ、CTA 有無、SNS 表示、S7: SNS 正本 | △ core overlay のみ | #100 |
| footer（カラム可変） | PARTS-02, SNS-01 | S4: テンプレ名でカラム数、S3: 案、ページトップは footer 内 | ○ 1 案 | #100 |
| sidebar / sidebar-sticky | ZONE-01, PARTS-01 | S3: 案、S5: 追尾サイドバー slot、S6: sidebar メタ | × | #95 |
| hero（複数案） | PARTS-01, AB-01 | S3: split / 全面写真 / 文字のみ / 全面動画（調査 §4 の 4 型）、S10: variant | ○ split のみ | #101 |
| お知らせバー（ヘッダー直下 slot） | ZONE-03, BANNER-01 | S9: バナー正本（有効期間・リンク・種別）、閉状態は端末側記憶 | × | #102 |
| 同意バー | TAG-03, CONSENT-01, ZONE-03 | S2: block style、S7: カテゴリ 3 種、第三者検出時は非出力 | × | #103 |
| SP 下部固定（3〜5 タブ: 電話 / メッセージアプリ / 資料 DL / 目次 / トップへ） | SP-02, ZONE-03, NFR-SP-01 | S8: タブ構成、積層順 同意 > メニュー > シェア | × | #104 |
| シェア（記事上下 / フロート / section 末尾） | SNS-01, META-01 | S7: 対象 SNS、S8: 配置、S6: share メタ | × | #105 |
| パンくず（core Breadcrumbs + BreadcrumbList） | NAV-01 | 自動導出（選択なし）、S2 | △ 手組み | #106 |
| 目次（h2/h3 機械導出） | VOCAB-02, META-01, SP-03 | S7/S6: 配置方式（固定埋め込み / フロート追従 / 開閉ボタン）・ページ種別表示、S2: 見た目、S8: SP は開閉 | △ 手組み HTML | #107 |
| PR 表記（記事上部 1 箇所） | VOCAB-03, META-01 | S2: 表示デザイン（控えめ既定）、S7/S6: 表示ページ制御、有無は機械判定 | △ 本文内の手組み注意書き | #108 |
| 著者欄 / 監修者欄 | AUTHOR-01/02 | S9: 著者正本（名前・経歴・資格・sameAs・画像） | ○ WP ユーザー情報のみ | #109 |
| アイキャッチ（位置 × 有無） | PARTS-02, META-01（キー追加候補 `eyecatch`）, LOOK-03（調査: 記事冒頭の写真有無は業種で分かれる） | S7: サイト既定の位置 5 案（A タイトル下・内容幅 / B タイトル上・全幅 / C ヒーロー重ね（タイトル白抜き） / D 横サムネ / E 非表示）、S6: 記事単位の有無・位置上書き、S8: SP/PC で比率と高さの差分、S9: 一覧カードのサムネは同じ画像を参照 | △ core post-featured-image を A 固定・有無の選択なし | #126 |
| 関連 / 人気 / おすすめ 一覧 | RECO-01 | S7: 方式（関連: カテゴリ→タグ→手動、人気: 集計方式・期間、おすすめ: 手動・並び順） | △ 手組み HTML | #110 |
| ゾーン（23 語彙）× slot（6） | ZONE-01/02/03, BANNER-01 | S5: creative 参照・overrides・面積上限・条件描画 | × | #102 |
| SP 専用広告面 | SP-02 | S5: slot 条件描画（Block Visibility 不可） | × | #102 |

## 4. 記事内語彙（core + block style 14 種、新規ブロック 6 + 1）

| 部品 | 由来 | 選定軸と選択肢 | 状態 | Issue |
|---|---|---|---|---|
| 囲み（見出し付き 3 型: 帯 / タブ / ラベル × 標準 / 注意 / 淡） | VOCAB-01 | S2: 型 × 色、S1 | ◎ | – |
| ボタン（variant 3 + microcopy + アイコン） | LOOK-01, CV-03, SELL-05 | S2: fill / outline / pill、microcopy 候補、rel 機械付与 | ○ microcopy 未 | #111 |
| リンクカード（内部: url_to_postid / 外部: 検証付き HTTP） | VOCAB-04 | 内部 / 外部の自動判別 | × | #112 |
| 吹き出し（新規ブロック） | VOCAB-01 | 左右・人物（著者正本 or 任意） | ○ HTML 手組み | #113 |
| 手順（timeline: core + style） | VOCAB-01 | S2 | ◎ | – |
| 記事一覧（本文内） | VOCAB-01, RECO-01 | S7: 方式 | △ | #110 |
| アコーディオン（details） | VOCAB-01 | S2 | ◎ | – |
| タブ（core Tabs + style、SP はアコーディオン） | VOCAB-01, SP-03 | S2、S8: SP 形 | ○ SP 積層 CSS のみ（PoC） | #114 |
| 全幅 | VOCAB-01 | alignfull | ◎ | – |
| リッチメニュー | VOCAB-01 | S2 | × | #115 |
| 会員制限 | VOCAB-01 | 表示条件（会員はテーマ外） | × | #115 |
| 比較表（手組み、先頭列固定） | VOCAB-01, SP-03 | S2、S8: 横スクロール / カード | ○ 横スクロール | #116 |
| 定義リスト | VOCAB-01 | S2 | × | #115 |
| FAQ（details、JSON-LD は出さない） | VOCAB-01, SEO-02 | S2 | ◎ | – |
| レビュー（新規） | VOCAB-01, SELL-02 | S9: 商品正本、評価バー | ○ HTML 手組み | #117 |
| 商品カード（新規） | SELL-02 | S9、リンク先種別で product snippet / merchant listing | ○ HTML 手組み | #117 |
| ランキング（新規） | SELL-02, LEGAL-03 | S9、根拠脚注の自動表示 | ○ HTML 手組み（メディア側） | #117 |
| 比較専用テーブル（新規、料金表 variant、最下行 CTA） | SELL-02, SP-03 | S9、S8: 横スクロール / カード、S10 | × | #117 |
| CTA 束（新規） | SELL-02, CV-01/03 | S9、CV ID、S10 | ○ 手組み | #117 |
| pros-cons（core + style） | SELL-02 | S2 | ◎ | – |
| ギャラリー（SP スワイプ） | SP-03 | S8 | × | #114 |
| 打消し表示（CTA と同視野・同サイズ） | LEGAL-03 | S2 | × | #118 |
| 数字訴求 / 評価バー / アイコン付きリスト | 調査由来（サービス系 41%） | S2 | ◎ | – |
| SVG アイコン（自前 36 種） | NFR-A11Y-01（label + icon）, VOCAB-01 | CSS mask クラス、currentColor | ◎ | – |
| カード / 箇条 / 手順のメディア枠（アイコンの対応幅） | VOCAB-01, PARTS-01, SELL-01（商品画像） | S2/新規ブロック属性: メディア型 5 案（A 自前 SVG から選択 / B メディアライブラリからアップロード（SVG・PNG） / C 写真 16:9 カード上部 / D 番号 / E なし）、S7: 既定のメディア型、サイズ s/m/l/xl と背景丸の有無 | △ pattern 内の img 固定（差し替えは HTML 編集） | #127 |
| 空き 1 枠 | VOCAB-01 | 未割当 | – | – |

## 5. LP・販売・CV

| 部品 | 由来 | 選定軸と選択肢 | 状態 | Issue |
|---|---|---|---|---|
| LP セクションパターン群（既存 12 本を初期群として引き継ぎ） | LP-01 | S4: 種別、S1/S2: LP 専用 | ○ 7 本（企業型流用） | #96 |
| フォーム宣言（配置・項目・送信先 JSON、利用目的・privacy link・同意必須） | LP-02, CV-02, NFR-CV-01 | S7、第三者フォーム検出時は譲る | × | #119 |
| 資料 DL（メール送付 / 即時 DL、署名付き期限 URL） | CV-02 | S7: 経路 | × | #119 |
| メッセージアプリ 友だち追加ボタン + QR（フォーム代替 CTA） | SNS-02 | S7 | × | #120 |
| SNS feed 埋め込みブロック（遅延読込） | SNS-02 | S7 | × | #120 |
| 商品正本（JSON / CPT） | SELL-01, SELL-03, SELL-05 | WT-UI-10 の商品一覧、MCP | × | #117 |
| /go/<id> 302 経路 | SELL-04 | 自動 | × | #121 |
| バナー正本（PC / SP 画像・種別・期間・PR 要否） | BANNER-01/02 | S5、S9 | × | #102 |
| 初回モーダル（LP 例外のみ） | ZONE-03, LP-01 | LP 単位設定 | × | #96 |

## 6. 見た目の軸（variation / block style / フォント / 動き）

| 部品 | 由来 | 選定軸と選択肢 | 状態 | Issue |
|---|---|---|---|---|
| style variation: サイトパターン別（コーポレート / サービス / ブランド / ポータル / 比較） | LOOK-03（調査 §4–§6） | S1: 白地 + 1 アクセント基本、企業 = 直角・影少、サービス = 角丸 8・影あり・数字、比較 = pill・情報密度、ブランド = 全面写真・大型見出し・明朝 | ○ 標準 / 明朝 / 罫線 3（ダークは WT-Q-LOOK-04 で不採用、試作から撤去済） 本 | #122 |
| 色 8 スラッグ | LOOK-02 | S1 | ◎ 9（cta-contrast 含む） | – |
| h2 ×3 / h3 ×2 / ボタン ×3 | LOOK-01 | S2 | ○ h2 2 種・h3 0・ボタン 3 | #122 |
| 和文フォント系統（ゴシック / 明朝 / 丸ゴ / 手書き・デザイン系、自己ホスト・サブセット・size-adjust・OFL 台帳） | LOOK-04, NFR-PERF-02, NFR-OSS-01 | S1/S7: 系統、Font Library はテーマ提供限定 | △ システムフォントのみ | #123 |
| 和文組版 CSS（line-break strict / overflow-wrap anywhere / text-autospace / text-wrap balance） | TYPO-01 | 既定（選択なし） | ○ 一部 | #123 |
| 列内カードの高さ自動統一（columns 内 card / price は stretch） | LOOK-01, PO 指摘 2026-09-05 | 選択なし（既定で揃える） | ◎ 試作 02 CSS で対応 | – |
| LP / HP のアニメーション語彙（出現 8 / スクロール連動 4 / hero 演出 6 / マイクロ 5 / 数値 2）+ reduced-motion・性能 guard | LOOK-01 改定候補, LP-01, NFR-A11Y-01, PO 指示 2026-09-05 | S2: pattern 属性で出現型、S7: hero 演出と既定、S8: SP は軽量型へ降格。記事面は対象外 | △ 出現 1 種（reveal.js） | #132 |
| 奥行き・立体の型（影 4 段、カード浮遊 / lift / 重なり / ガラス / 面帯、立体ボタン 5 型、文字影） | LOOK-01 改定候補, PO 指示 2026-09-05 | S1: variation で影の強さ、S2: block style で型 | △ 影 1 段・hover lift | #122 |
| 空間パターン（余白密度 3 段 × 配置 5 × グリッド 6 × 幅 4 × 重なり 3 × セクションリズム 3 × 縦リズム 3 段） | LOOK-01 改定候補, LOOK-03, VALUE-01, PO 指示 2026-09-05 | S1: 密度・縦リズム、S2: section 属性で配置 / グリッド / 幅 / 重なり、S7: リズム既定、S8: SP 降格 | △ wide / full のみ | #134 |
| 影 1 段・出現 1 種・hover transition | LOOK-01, NFR-A11Y-02 | S1: 影の強弱、reduced-motion で停止 | ◎ | – |
| 1200px 段 / alignWide / gradients / spacing 段 | LOOK-01 | theme.json | ◎（wide 1120） | – |
| 写真・色地上の文字の自動コントラスト guard（画像輝度の事前計算・スクリム自動強度・palette 組の検査・G-E1 実測） | NFR-A11Y-01, VALUE-01, PO 指示 2026-09-05 | 選択なし（自動。編集者は「写真の上に置くか」だけ選ぶ） | × | #130 |
| 値の 3 域判定（安全 / 生値 / 破壊） | VALUE-01/02 | ゲート | × | 既存 THEME-GATE 系 |

## 7. 状態・条件（見た目以外の「選ぶ場所」）

| 部品 | 由来 | 状態 | Issue |
|---|---|---|---|
| 投稿メタ 4 キー（sidebar / toc / share / pr） | META-01 | × | #124 |
| 設定 JSON（schema 付き 1 本）+ WT-UI-10 | ADMIN-01 | × | #124 |
| device 別差分 JSON + 両幅プレビュー | SP-01/03 | × | #124 |
| capability manifest（slot・ゾーン・パターン・パーツ案・variation・hook） | AGENT-01, TR-CORE-01 | × | #124 |
| A/B variant 登録 | AB-01 | × | 既存 Core プラグイン系 |

## 8. 試作 02 との差（要約）

- ◎ 14 / ○ 17 / △ 12 / × 31（2026-09-05 にアイキャッチ・メディア枠・カード高さの 3 行を追加）。見た目の土台（トークン・variation・囲み・ボタン・手順・FAQ・pros-cons・アイコン）は揃い、**「選ぶ場所」（S5 slot 割当、S6 投稿メタ、S7 設定 JSON、S8 device 差分、S9 正本参照）が未実装**。手組み HTML で置いたメディア部品・商品系は新規ブロック 6 種への置換待ち。
- のっぺり感（PO 2026-09-04 指摘）: 影 1 段・罫 1px・フラット既定の帰結。分布上は多数派だが、面の切り替え（surface 色帯・写真の重ね・大型見出しの字間・余白の緩急）で奥行きを出す余地があり、#122 のサイトパターン別 variation で扱う。
