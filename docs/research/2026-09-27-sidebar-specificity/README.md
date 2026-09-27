# サイドバーリンクの詳細度修正（Issue #376）

Issue: https://github.com/RetryYN/HELIX-WP-THEME/issues/376

## 原因と変更範囲

`0b32e652` が汎用 `.wt-side-widget a` に `:not(.wt-lp-cta-action)` を追加し、詳細度を (0,1,1) から (0,2,1) へ増やした。後置の `.wt-side-banners a` より優先され、文字が黒・中央寄せになった。news/rank/SNS/related/eventsにも同じ優先順位の問題がある。

Issue内の「#371のwrapper変更が原因」という記述は未確定の仮説。バナーの固定HTMLは旧画像更新 `0207eeb0` から `7f24af54` まで不変であり、CSSの比較と同一HTMLへのレスポンス差替えPoCから上記詳細度変更を切り分けた。#375の非選択hero画像helperはこの文字色・配置を変更しない。

通常セレクタの除外条件を `:where(:not(.wt-lp-cta-action))` に包み、CTA除外と元の詳細度を両立する。初回PoCではhoverに青文字が残り背景と重なったため、hoverも `:where(:not(.wt-lp-cta-action):hover)` とし、後置のバナー/SNS専用色を優先する。HTML・配色定義・候補は追加しない。

## 先行PoCと未完了範囲

PC1440/SP390で通常・hover・キーボードfocusを比較した。主要56検査と追加バナー12状態で期待する色・配置を確認した。CTAの色・配置・focusは変更前と一致する。計算色からのコントラストは白バナー対グラデーションの明るい端が6.70:1、CTAが5.18:1、SNSが17.13:1。ブラウザ内のCSSレスポンス差替えPoCであり、実マウントでの正式検証結果ではない。画像・計測値はgitignore済み `local-evidence/sidebar-specificity/` に保管する。

## 実マウント検証手順

HOME変更のmerge後にbaseを揃え、正規の隔離Content Lab手順で本worktreeのthemeを配信する。実ラボのソース切替・起動はこの検証器には含まない。他のproducerと並行実行しない。

依存は既存lockfileのPlaywrightと `@axe-core/playwright` を使う。対象labのloopback originを指定して実行する。

```sh
WTCF_BASE_URL=http://127.0.0.1:18258 node scripts/verify-sidebar-specificity.mjs
```

検証器はGETのみでページを表示し、DB・Docker・配信CSSを書き換えない。CSSレスポンスのSHA-256が本worktreeの `theme.css`（siteページでは `site-pages.css` も）と一致しなければ停止する。経路、HTML/CSS hash、色・配置・focus値、scoped axe結果、画像を `local-evidence/sidebar-specificity/mounted/` に保存する。

- PC1440/SP390のバナー3枚、CTA、SNS、news/rank/related/eventsを通常・hover・keyboard focusで検査する。
- バナー白文字・左寄せ、CTA既存色、SNS白文字・中央、後置widget配置をassertする。
- バナー/CTA/SNSの各状態をaxeで検査する。violationsは失敗。incomplete、特にgradientの色評価は必ず目視と計算色で補完し、結果なしをPASS扱いしない。
- widget画像と `fixed-footer-{home,site-own,site-site}-{pc,sp}.png` を実見し、focus輪郭・footer末尾の到達を確認する。
- 既存 `scripts/verify-a11y.mjs` による通常の全体gateと、必要な証拠再生成・source bindingはこの局所検査と別に実施する。

正式merge済みHOME変更 `2a4ca879` へ未commit修正を保持して前進した。専用Content Labの既存データvolumeを保全し、本worktreeのthemeをread-only mountして局所検証する。全件rebind、commit/pushは局所結果の検収後に別工程で行う。

## レビュー追補

Claudeレビュー #380 の指摘に合わせ、検証器はnews/rank/related/eventsのhover色を `theme.json` の独立した `accent` palette token（現在 `#1d4ed8`）と比較する。期待色は検査対象要素のcomputed styleから導かない。この色assertと既存のbanner/CTA固定色assertはtheme.jsonの既定variationを前提とする。`mincho` / `rules` variationを選んだラボでは別のaccentとなるため、この検証器を実行する際は既定variationを使う。tokenが欠落または不正な場合は初期の未完了reportを保存し、errorを記録してnonzeroで終了する。theme JSONと検証器をsource digestへ含め、実配信CSSの待機は明示的な30秒上限と対象stylesheet名・元エラーを含む失敗理由を持たせた。通常の対象操作や色規則は変更していない。

この追補後の検証器は静的構文確認のみで、実マウント再実行・証拠再生成は未実施である。上記430項目の結果は旧検証器の実行結果であり、新しいhover色assertの合格を示さない。TOCの挙動も未検証のまま。この作業では新たなTOC仕様や挙動を加えていない。実マウント結果とsource bindingは後続検証が必要。

## 通常配信での局所検証結果

`2a4ca879` を基点に、修正CSSを専用ラボへread-only mountして実行した。レスポンス差替えなしで配信CSSとsourceのSHA-256一致をPC/SPとも確認し、124検査が全合格した。3バナーの通常/hover/focus全18状態の見出しコントラスト下限は6.7016:1。scoped axeのviolationsは0、gradientのcolor-contrast incompleteは18件あり、これらを数値assertと画像実見で補完した。PC/SPそれぞれ表示画像9枚の未読込みは0。バナーの白文字・左寄せ、CTA/SNSの色・focus輪郭、footer末尾と固定バーの非重複を画像で確認した。

初回取得時は前要素のfocus輪郭が次のhover画像に残っていたため、検証器の状態切替でactiveElementをblurし、初回証拠を保全して再実行した。HOME単独124項目の結果は `local-evidence/sidebar-specificity/mounted-home-124/verification.json`、画像も同ディレクトリ。これは局所回帰検査の結果であり、全体axe gate・全件証拠再生成の完了は示さない。

## siteページへの汎用リンク規則の漏れ

正式再生成の途中でsite own/siteのPC/SPにバナー幅の縮小・中央寄せを検出した。`site-pages.css` の後置汎用 `.wtsite a` が共有sidebarにも適用されるためであり、色の継承はCTA/SNS、配置はnewsにも影響する。2つの汎用規則だけを `.wtsite a:where(:not(.wt-side a))` に限定する。focus規則は維持し、サイト本文リンクのタップ領域も維持する。

検証器にHOMEとsite own/siteのPC/SP、バナー幅/display、本文リンクの最小タップ領域、site CSSの配信hash照合を追加した。修正前の通常配信では288項目中120項目が不合格となり、例外は0。負例の全画像とreportを `local-evidence/sidebar-specificity/mounted-site-negative/` に保全した。`--site-only` はこのsite4条件のみを実行する。

2セレクタ修正後の通常配信ではHOME/site own/site site × PC/SPの430項目が全合格、例外0。theme/site CSSの配信hashはsourceと一致した。バナー54状態のコントラスト下限6.7016:1、scoped axe違反0、gradientのincomplete54件を数値assertで補完した。本文タップ領域も全4条件で合格。表示画像の未読込みは全6条件で0（HOME各9枚、site各2枚）。代表通常/hover/focus画像と全6末尾画像を実見し、全幅・左寄せ・白文字・focus輪郭と末尾画像表示を確認した。新しい局所結果は `local-evidence/sidebar-specificity/mounted/verification.json`。正式な証拠再生成や全体gateは別工程であり、この結果に含めない。
