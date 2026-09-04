# 表現タグ定義（初回画面の静止画から判定できるものだけ）

## layout（複数可）
hero-fullbleed-photo / hero-fullbleed-video(動画らしき静止フレーム) / hero-split(文字|ビジュアル左右分割) / hero-typographic(文字だけ・写真なし) / bento-grid / card-grid / editorial-magazine(段組・記事誌面風) / asymmetric-overlap(要素の重なり・ずらし) / centered-symmetric / slider-carousel(ドット・矢印あり) / sidebar-layout(記事+サイドバー)

## type（複数可）
display-oversized(PC 60px 級以上の見出し) / serif-mincho-display / condensed-or-wide / outlined-text / vertical-jp(縦書き) / en-large-jp-small(英語大+日本語小) / handwritten-script / uppercase-tracking(欧文大文字・字間広め)

## color（1〜2）
white-plus-accent / dark-base / pastel / vivid-multicolor / gradient-soft-or-mesh / monochrome-or-duotone / earth-beige / neon-on-dark

## surface（複数可）
flat / glass-blur / soft-shadow-cards / hard-outline-brutalist / grain-noise / 3d-render-cg / illustration-flat / illustration-hand / photo-people / photo-product / photo-scenery / abstract-shapes-blob / thin-rules-lines / large-numbers-stats

## shape（1）
sharp / rounded-8-16 / pill-capsule / circle-heavy

## motion-cue（静止画から推定できるもののみ、複数可）
marquee-ticker / splash-loader / custom-cursor-hint / parallax-layers-hint / none-visible

## nav（複数可）
hamburger-only / visible-text-nav / floating-pill-nav / header-cta-button / bottom-fixed-bar(SP) / cookie-consent-visible

## vibe（1 つ）
corporate-trust / tech-modern / warm-lifestyle / luxury-minimal / playful-pop / editorial / experimental-artistic / affiliate-dense(情報詰め込み・比較系)

## 検証規則（2026-09-05 追記）

- タグは所属する分類にのみ記録する（色タグを surface に入れない等）。集計前に語彙外・分類違反を検出して差し戻す。2026-09-05 に 5 件を是正し tag-rates.json を再生成した。
- ローダー・ゲート画面が初回画面だった場合は `splash-loader` を motion_cue に記録する。nav / type は「その画面で見えたもの」を記録し（ハンバーガーだけ見えれば `hamburger-only`）、何も見えなければ空 = 不明（na）とする。「無し」とは区別する。既存データ 194 件はこの運用で記録済み。
