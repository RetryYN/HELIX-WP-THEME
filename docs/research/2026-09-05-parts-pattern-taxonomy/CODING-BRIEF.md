# 画像コーディング指示（パーツ別パターン）

- 語彙: /tmp/claude-1000/-home-tenni-dev-HELIX-MARKETING-HARNESS/6d07f64c-69bc-4f01-b637-4250e6c70d9c/scratchpad/taxonomy/PARTS-VOCAB.md を最初に Read する。
- 入力: groups/gNN.txt の各行 = `<shotKey>\t<crop files...>`。shotKey は `<pattern>-<country>-<nnn>-<page>-<dev>`（page は top/article、dev は sp/pc）。crop は crops/ にあり、`--hero`（ページ上部）`--mid`（中央）`--mid2`（記事の 1/4 地点）`--foot`（末尾）。
- 各 shotKey について crop を Read（画像）し、語彙の PART ごとに観察された型を記録する。top ページは「面: top」の PART、article ページは「面: article」の PART。見えない PART は "na"、無いと判断できる PART は "none"。語彙に無い型は "other:<10 字以内>"。
- 出力: coded/gNN.jsonl に 1 行 1 shotKey の JSON: {"key": "...", "page": "top|article", "dev": "sp|pc", "tags": {"header.layout": "...", ...}, "note": "<任意・20 字以内>"}。tags の値は文字列（複数は "a|b"）。
- 速度優先: 1 shot あたり画像 3〜4 枚。迷ったら最も近い語彙。サイト名・URL・固有名は書かない（キーだけで指す）。
- 途中で書き込みながら進め、最後に行数を報告する。
