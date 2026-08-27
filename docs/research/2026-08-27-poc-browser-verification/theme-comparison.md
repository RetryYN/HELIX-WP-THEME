# 本テーマ vs テーマA / テーマB — PoC 検証台での操作確認と非コード単位の差分（2026-08-27）

同一 XServer 上の使い捨て PoC 検証台 3 面（各 WP 7.1、同一管理者）で、本テーマ（HELIX Neo / AGENT NEO）、テーマA、テーマB を
**同じ手順**で操作した。フロント 6 ページ種別 × 2 viewport の巡回、管理画面ログイン、テーマ画面、サイトエディタ、
カスタマイザー、投稿エディタ（ブロック・パターン挿入ツール）を Playwright で操作し、目視（スクリーンショット）と DOM/JS API の採取を行った。
固有名は伏せ字（テーマA = 市販テーマ 1、テーマB = 市販テーマ 2）。

## 1. 操作可否（管理画面）

| 操作 | 本テーマ | テーマA | テーマB |
|---|---|---|---|
| ログイン / テーマ画面 | ✓ | ✓ | ✓ |
| サイトエディタ（FSE） | ✓ 正式（block theme） | △ 開くがテンプレ編集対象なし（classic） | △ 同左（classic、独自 LP / ブログパーツ CPT あり） |
| カスタマイザー | 22 control（WP 標準のみ） | **1,045 control / 1,059 setting** | **520 control / 540 setting** |
| 投稿エディタ | ✓ iframe 化 | ✓ iframe 化、JS エラー 1 件（`addEventListener` on null） | ✓ iframe 化 |
| テーマ独自ブロック | 1（embed） | **24**（属性合計 559） | **33**（属性合計 408） |
| 独自パターン | 24（テーマ提供） | 0（core 11 のみ） | 13 |
| 投稿メタ（登録済み） | footnotes のみ | **22 キー**（表示切替・SEO・動画等） | 1 キー + 標準 |
| 管理メニュー追加 | 1（AGENT NEO） | 1（テーマ設定） | **5**（LP / ブログパーツ / 広告タグ / ふきだし / テーマ設定）+ CPT 2 |
| エディタ側パネル追加 | なし | 4（動画・表示・SEO・head タグ） | メタボックス 2（カスタム CSS&JS / テーマ設定） |
| theme.json | 完全（settings 13 キー / styles 6 キー） | 部分（styles.css なし） | 部分（styles.css なし） |

## 2. パーツ構成（フロント目視 + DOM 検出、desktop/single）

| パーツ | 本テーマ | テーマA | テーマB |
|---|---|---|---|
| ヘッダー / ナビ / ハンバーガー | ✓ / ✓ / ✓ | ✓ / ✓ / ✓ | ✓ / ✓ / ✓ |
| ヒーロー（トップ） | ✓ パターン（home-hero） | ✓ カスタマイザー MV（97 control） | ✓ カスタマイザー MV（109 control）※既定は非表示 |
| サイドバー | ✓（子テーマ 2 カラム） | –（1 カラム既定） | ✓（追尾・トップ専用など 4 種） |
| パンくず | ✓（記事上） | ✓（**フッター側**） | ✓（ヘッダー直下） |
| 目次 | ✓ | ✓ | ✓（カスタマイザー 17 control） |
| シェアボタン | ✓ 4 種（記事末） | ✓ 4 種（帯） | ✓ 6 種（追従サイド + 記事末） |
| 著者ボックス | ✓ | ✓（画像未設定 placeholder） | ✓ |
| 関連記事 | ✓ | – | ✓ |
| 記事末 CTA | ✓ パターン（article-cta） | –（ウィジェット領域 6 箇所で代替） | –（ウィジェット領域 / ブログパーツで代替） |
| 前後記事ナビ | ✓ | – | – |
| コメント | ✓ | – | ✓ |
| Cookie 同意バー | ✓ | – | ✓（記事のみ検出） |
| ページトップ | – | – | – |

※ 本テーマの「固定ページ」行は最新ページが `blank` テンプレ検証ページだったため header/h1 なしで採取されている（設計通り）。

## 3. 出力構造・配信（desktop/single）

| 指標 | 本テーマ | テーマA | テーマB |
|---|---|---|---|
| HTTP 200・PHP Warning 混入 | ✓ / 0 | ✓ / 0 | ✓ / 0 |
| JSON-LD | Organization+WebSite+Person+BlogPosting+BreadcrumbList（1 graph） | BreadcrumbList, Article（分離、`@type` 空のスクリプト 1 本） | Organization+WebSite+WebPage+Article+BreadcrumbList |
| canonical / meta description / OGP | ✓ / ✓ / ✓（全ページ） | ✓ / △（front・search・404 で欠落） / ✓ | △（front・archive・search で欠落）/ – / –（SEO プラグイン前提） |
| `wp-block-*` 要素数 | 159 | 1 | 27 |
| テーマ CSS 接頭辞 | `an-` 55 | 1 文字接頭辞（`d-` `a-` `c-` `t-`） | 1 文字接頭辞（`c-` `p-` `l-`） |
| CSS link / script / jQuery | 4 / 4 / なし | 6 / 13 / **あり** | 7 / 5 / なし |
| インライン style | 49 KB | 43 KB | 45 KB |
| DOM ノード | 333 | 153 | 305 |
| img alt 欠落 | 0 | 1 | 1（front は 5） |
| 横スクロール（mobile） | なし | なし | なし |

## 4. 「操作系を本テーマと同じ JSON へ置き換えられるか」の一次分類

カスタマイザー control を実機から採取し（テーマA 400/1,045、テーマB 400/520 をサンプル）、control 種別と所属セクションで機械分類した。

| 置換先 | テーマA | テーマB | 備考 |
|---|---|---|---|
| theme.json palette / styles（色） | 80 | 55 | そのまま JSON 化可 |
| theme.json settings/styles（寸法・タイポ） | 36 | 35 | 同上 |
| option JSON（text） | 62 | 125 | 値は JSON 化可。**意味論（どこに出るか）はテンプレ側で再現が必要** |
| option JSON（enum/toggle） | 57 | 127 | 同上。表示 ON/OFF はテンプレ・パターンの有無へ変換 |
| wp_navigation（メニュー） | 8 | 9 | JSON 化可 |
| template-part / pattern（ウィジェット領域） | 10 | 25 | **構造変換**（ウィジェット → ブロック） |
| option JSON + attachment（画像） | 12 | 21 | メディア移送を伴う |
| custom CSS → styles.css | 1 | 1 | JSON 化可 |
| テーマ独自 UI（プリセット・吹き出し・タブ制御） | **76** | 0 | 手動対応表が必要 |
| hidden（JS 内部状態） | **52** | 0 | **JSON 化不可**。JS が生成する派生値で、要解析 |

ブロック属性はテーマA 559 / テーマB 408 のうち **json source が 554 / 382**（残りは `html` / `children` / `attribute` = 本文 HTML 由来）。
属性値そのものは JSON で持てるが、テーマA は 24 ブロック中ほぼ全てが `supports` 無指定・`styles` 0 で、
描画意味論は render 側（PHP/JS）に閉じている。テーマB は `anchor|className` を持ち `styles` を 3〜4 種持つブロックがあり、
core ブロック + block style への写像が比較的容易。

### 結論（一次）
- **テーマB**: 控えめに見て control の 9 割超が「値は JSON 化可」。残りはウィジェット領域の構造変換（25）と画像移送（21）。
  独自 CPT（LP / ブログパーツ）は wp_block（同期パターン）+ テンプレへ写像可能。
- **テーマA**: 約 7 割が「値は JSON 化可」だが、**hidden 52 + 独自 UI 76（サンプル比 32%）**が JSON へ直接落ちない。
  さらに投稿メタ 22 キーによる記事単位の表示切替が本テーマにない概念で、テンプレ分岐か block 属性へ設計し直す必要がある。
- いずれも「値の JSON 化」と「意味論の再現」は別問題で、後者は本テーマのテンプレ・パターン・block style 側の受け皿設計になる
  （INV-09 サイト設定正本、INV-14 ブロック属性表の後続）。

## 生データ
スクリーンショット（3 テーマ × 6 ページ × 2 viewport + 管理画面各 5 枚）と JSON はリポジトリ外に保持。
