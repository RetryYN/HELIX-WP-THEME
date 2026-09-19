# HOMEパターンの固定ページ再利用（Issue #189）

HOMEパターンは任意の固定ページへ挿入できるものとして扱う。基底の`theme.css`と補正の`home-completion.css`は同じ公開面へ読み込む。補正の通常規則は`.wt-home`内、固定導線とfooter補償は`body:has(.wt-home)`に束縛する。HOMEを含まないページのDOMや寸法へ影響させない。

WordPressの[Patternsの説明](https://developer.wordpress.org/themes/patterns/)（2026-09-15確認）でもブロック群を様々な場面で再利用するモデルを示している。front pageだけに補正を限定すると、同じブロック内容に異なる結果が生じる。

`node scripts/verify-home-pattern-reuse.mjs`は専用HELIX Content Labの一時固定ページにHOME hero/sectionsを挿入し、PC1440/SP375・JS有無で実ページを読み込む。beforeは同一内容で補正CSS応答だけを空にし、afterと写真パネルの背景・横溢れを比較する。これは旧コミット全体の比較ではない。非HOME2面は補正CSSの有無でmainのDOMと寸法が変わらないことを検査する。作成IDと所有markerを照合してfinally削除する。

既にEditorへは補正CSSが登録されている。今回の自動検査は公開固定ページを対象とし、Site Editorで全挿入型を保存・再入場する実操作、任意の第三者template、全固定導線型の交差検査は親の独立検収対象として残る。
