# THEME-INV-05: デザイントークンの正本と投影方式を決める

labels: investigation, design-tokens, priority:medium
depends: なし

> **状態: 一次完了**（2026-08-26）／レポート: `../reports/INV-05-design-token-projection.md`
> 仕分けを **A 意味的 / B 部品固有 / C 状態フラグ / D レイアウト寸法**の 4 分類に確定
> （C を独立させたのが要点）。**テーマA の先頭 50 件に意味的トークンが 1 つも無い**。
> 一方向投影は可能だが「C を分離」「B はレンダラの責務」の 2 条件つき。
> **残**: 151 / 155 の全量取得と全量分類。

## 背景（実測）
| テーマ | トークン数 | 保持方式 |
|---|---|---|
| テーマA | CSS 変数 **151** | カスタマイザ値（`themeA_*` 個別オプション 1,225）→ CSS 変数。`--cv-button` `--fukidashi-*` `--compare-*` `--header-style-*` など**部品の見た目そのものが変数** |
| テーマB | CSS 変数 **155** | 単一配列 `themeB_options`（既定 540 キー）→ `classes/Style/`(11 ファイル) が **PHP で動的に CSS 生成** |
| agent-neo | palette 8 + fontSizes 6 + spacingScale + custom(fontWeight/lineHeight) | **theme.json v3 静的宣言** + `styles/{light,dark}.json` |

粒度が根本的に違う。両テーマは「部品ごとの見た目」を変数にしており、agent-neo は「意味的トークン」を宣言している。

## 調査項目
1. 151 / 155 の変数を **意味的トークン（色・余白・タイポ・角丸・影）** と **部品固有スタイル**に仕分ける
2. 意味的トークンが theme.json の語彙で表現可能かを検証（不足カテゴリの洗い出し）
3. 部品固有スタイルは中間 JSON レンダラ側の責務か theme.json の `styles.blocks` かを判断
4. 「JSON デザイントークン → theme.json 投影」の既存実証（HELIX Neo）との接続点を確認

## 完了条件
- [ ] 306 変数の仕分け表（意味的 / 部品固有 / 廃棄）が存在する
- [ ] theme.json で表現できない意味的トークンが列挙されている
- [ ] トークン正本を JSON 一方向投影にできるか否かの結論が出ている
