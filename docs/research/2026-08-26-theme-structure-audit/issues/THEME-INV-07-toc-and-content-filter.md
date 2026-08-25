# THEME-INV-07: 目次と本文フィルタ機構の方式を決める

labels: investigation, content-pipeline, priority:medium
depends: THEME-INV-02

## 背景（実測）
- JIN:R: **外部プラグイン依存**（`rich-table-of-content` が active。テーマ内に目次実装なし）
- SWELL: **テーマ内蔵**。`swell_toc` ショートコード + `lib/content_filter.php` による本文への自動挿入。
  さらに `classes/Pre_Parse_Blocks.php` でブロックを事前解析している
- agent-neo: レンダラ生成 + 中間 JSON の `toc:false` フラグ（既存方針）

「本文に対して後段でフィルタを掛ける」機構（目次挿入・広告 h2 前挿入・PR 表記・遅延読み込み）は
3 テーマで実装位置がばらばら。中間 JSON パイプラインではこれをどこで行うかの決定が要る。

## 調査項目
1. SWELL `content_filter.php` / `Pre_Parse_Blocks.php` が本文に対して行う変換を全列挙
2. JIN:R 側の対応する変換（`ad-finish.php` `ad-related.php` 等）を全列挙
3. これらが「生成時（中間 JSON）」「レンダリング時」「表示時フィルタ」のどこに属すべきかを分類
4. 目次を中間 JSON の一級要素にするか、レンダラの派生物にするかを決める

## 完了条件
- [ ] 本文変換の全一覧（テーマ × 変換 × タイミング）が存在する
- [ ] 各変換の担当レイヤ（生成 / レンダリング / 表示）が割り当てられている
- [ ] 目次の扱いが 1 案に決まっている
