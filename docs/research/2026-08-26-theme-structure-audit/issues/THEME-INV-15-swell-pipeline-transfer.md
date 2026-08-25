# THEME-INV-15: SWELL の解析・変換パイプラインを中間 JSON 設計へ転用できるか検証する

labels: investigation, architecture, priority:medium
depends: THEME-INV-01, THEME-INV-07

## 背景（`11-reverse-swell.md` §4・§6 / `12-mechanism-comparison.md` §4・§5）
SWELL には、中間 JSON パイプラインと同型の機構が既に実装されている。

1. **`Pre_Parse_Blocks`（2 パスのドライラン）** — `wp_head(0)` で本文を
   `do_shortcode` → `parse_blocks` で再帰走査し、ブログパーツ・同期パターンは参照先まで展開。
   ウィジェットは `ob_start()` → 実出力 → `ob_clean()` で捨てながら使用ブロックを収集。
2. **`content_filter` の優先度設計** — ショートコード展開(11)・動的ブロック展開(9) の後、
   優先度 12 に変換群を揃える。二重適用防止フラグ（`$added_toc`）付き。
3. **`is_rest()` による保存/表示の分離** — REST 経由では変換を通さない。
   = 「保存されている HTML」と「表示される HTML」を意図的に別物として扱っている。
4. **プレースホルダ方式の目次** — 本文には `<div class="swell-toc-placeholder">` だけを置き、
   後段が実体へ置換する。

## 調査項目
1. ①の走査を「使用ブロック収集」ではなく**中間 JSON 抽出**に転用した場合の過不足を評価する
   （innerBlocks 再帰・参照展開・ショートコード先行展開はそのまま使えるか）
2. ②の優先度設計を、中間 JSON の「生成時 / レンダリング時 / 表示時」3 レイヤへ写像する
3. ③の分離が中間 JSON の正本性（保存＝JSON、表示＝派生）とどう対応するか整理する
4. ④のプレースホルダ方式を、中間 JSON の「後段で解決するノード」の表現として採用できるか判定する
5. 参照展開（`loos/blog-parts` / `core/block`）を中間 JSON でどう表すか（THEME-INV-04 と接続）

## 完了条件
- [ ] 4 機構それぞれについて「転用可 / 参考のみ / 不採用」の判定と根拠がある
- [ ] 中間 JSON パイプラインの 3 レイヤ定義が SWELL の実装例と対応付けられている
- [ ] 転用する場合の最小実装範囲が特定されている
