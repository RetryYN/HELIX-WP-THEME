# 画像コーディング指示 v2（footer・記事末尾・カテゴリ面）

- 語彙: PARTS-VOCAB-v2.md（本ファイルと同じディレクトリ）を最初に Read する。
- 入力: groups/gNN.txt の各行 = `<key>\t<region:file> ...`。key は `<id>-<page>-<dev>`（page は top/article/cat、dev は sp/pc）。画像は shots/ にある。
- 各 key について画像を Read（画像）し、page に応じて記録する:
  - top: 領域 A（footer.*）
  - article: 領域 A（`--foot`）+ 領域 B（`--tail`、tail.*）
  - cat: 領域 A（`--foot`）+ 領域 C（category.* / ranking.* / list.* / pagination）
- 見えない PART は "na"、無いと判断できる PART は "none"。語彙に無い型は "other:<10 字以内>"。
- 出力: coded/gNN.jsonl に 1 行 1 key の JSON: {"key": "...", "page": "...", "dev": "...", "tags": {"footer.layout": "...", ...}}。note 等の自由記述フィールドは置かない（固有名の混入経路になるため。2026-09-05 のレビューで note から実名を除去した）。tags の値は文字列（複数は "a|b"）。
- 速度優先: 迷ったら最も近い語彙。サイト名・URL・ブランド名・商品名・固有名は絶対に書かない（key だけで指す）。note にも書かない。
- 途中で追記しながら進め（>>）、最後に行数を報告する。既に coded/gNN.jsonl に行がある key は飛ばす。
