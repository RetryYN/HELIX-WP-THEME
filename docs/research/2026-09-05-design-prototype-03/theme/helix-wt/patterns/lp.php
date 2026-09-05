<?php
/**
 * Title: LP（比較媒体用・段4）
 * Slug: helix-wt/lp
 * Categories: helix-wt
 * Description: サービス訴求 LP の hero、信頼、比較、料金、FAQ、CTA slot
 */
$u = get_theme_file_uri( 'assets/img' );
?>
<!-- wp:html -->
<div class="wt-lp-hero-slot" id="lp-hero">
  <section class="wt-lp-hero wt-lp-hero--split" aria-labelledby="lp-hero-split-title">
    <div class="wt-lp-hero__grid">
      <div class="wt-lp-hero__copy">
        <p class="wt-eyebrow">COMPARE GUIDE</p>
        <h1 id="lp-hero-split-title">比べたあとに、迷わず相談できる案内を。</h1>
        <p class="wt-lp-hero__lead">候補の違いを整理した読者へ、選び方と次の一歩をひとつのページで伝えます。</p>
        <div class="wt-lp-cta wt-lp-cta--single"><a class="wt-lp-cta-action" href="#contact">無料で相談する</a></div>
        <div class="wt-lp-cta wt-lp-cta--double"><a class="wt-lp-cta-action" href="#contact">無料で相談する</a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="full" href="#comparison">比較表を見る</a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="short" href="#pricing">料金を見る</a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="trust" href="#voices">利用者の声を見る</a></div>
        <form class="wt-lp-cta wt-lp-cta--form-inline wt-lp-cta-form" method="post" action="/lp/">
          <label for="lp-email-split">メールアドレス</label><div class="wt-lp-cta-form__row"><input id="lp-email-split" name="email" type="email" autocomplete="email" required placeholder="name@example.invalid"><button class="wt-lp-cta-action" type="submit">案内を受け取る</button></div>
        </form>
        <p class="wt-lp-hero__note">相談は無料・入力は 1 分ほど</p>
      </div>
      <div class="wt-lp-hero__media"><img src="<?php echo esc_url( $u ); ?>/hero.png" alt="" width="720" height="540" fetchpriority="high" loading="eager" decoding="async"></div>
    </div>
  </section>

  <section class="wt-lp-hero wt-lp-hero--fullbleed" data-wt-scrim aria-labelledby="lp-hero-fullbleed-title">
    <img class="wt-lp-hero__background" src="<?php echo esc_url( $u ); ?>/hero.png" alt="" width="1440" height="820" fetchpriority="high" loading="eager" decoding="async">
    <div class="wt-lp-hero__content">
      <p class="wt-eyebrow">COMPARE GUIDE</p>
      <h1 id="lp-hero-fullbleed-title">選ぶための情報を、次の行動につなげる。</h1>
      <p class="wt-lp-hero__lead">数字・比較・声を一つにまとめ、読み終わった瞬間の迷いを減らします。</p>
      <div class="wt-lp-cta wt-lp-cta--single"><a class="wt-lp-cta-action" href="#contact">無料で相談する</a></div>
      <div class="wt-lp-cta wt-lp-cta--double"><a class="wt-lp-cta-action" href="#contact">無料で相談する</a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="full" href="#comparison">比較表を見る</a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="short" href="#pricing">料金を見る</a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="trust" href="#voices">利用者の声を見る</a></div>
      <form class="wt-lp-cta wt-lp-cta--form-inline wt-lp-cta-form" method="post" action="/lp/">
        <label for="lp-email-fullbleed">メールアドレス</label><div class="wt-lp-cta-form__row"><input id="lp-email-fullbleed" name="email" type="email" autocomplete="email" required placeholder="name@example.invalid"><button class="wt-lp-cta-action" type="submit">案内を受け取る</button></div>
      </form>
      <p class="wt-lp-hero__note">相談は無料・入力は 1 分ほど</p>
    </div>
  </section>

  <section class="wt-lp-hero wt-lp-hero--product" aria-labelledby="lp-hero-product-title">
    <div class="wt-lp-hero__product-media"><img src="<?php echo esc_url( $u ); ?>/product-a.png" alt="商品イメージ" width="512" height="512" fetchpriority="high" loading="eager" decoding="async"></div>
    <div class="wt-lp-hero__copy wt-lp-hero__copy--center">
      <p class="wt-eyebrow">PRODUCT GUIDE</p>
      <h1 id="lp-hero-product-title">比較した候補を、ひとつの案内に。</h1>
      <p class="wt-lp-hero__lead">仕様と使い方を見比べて、自分に合う選択肢を確認できます。</p>
      <div class="wt-lp-cta wt-lp-cta--single"><a class="wt-lp-cta-action" href="#contact">無料で相談する</a></div>
      <div class="wt-lp-cta wt-lp-cta--double"><a class="wt-lp-cta-action" href="#contact">無料で相談する</a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="full" href="#comparison">比較表を見る</a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="short" href="#pricing">料金を見る</a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="trust" href="#voices">利用者の声を見る</a></div>
      <form class="wt-lp-cta wt-lp-cta--form-inline wt-lp-cta-form" method="post" action="/lp/">
        <label for="lp-email-product">メールアドレス</label><div class="wt-lp-cta-form__row"><input id="lp-email-product" name="email" type="email" autocomplete="email" required placeholder="name@example.invalid"><button class="wt-lp-cta-action" type="submit">案内を受け取る</button></div>
      </form>
      <p class="wt-lp-hero__note">相談は無料・入力は 1 分ほど</p>
    </div>
  </section>

  <section class="wt-lp-hero wt-lp-hero--text-only" aria-labelledby="lp-hero-text-title">
    <div class="wt-lp-hero__copy wt-lp-hero__copy--center">
      <p class="wt-eyebrow">COMPARE GUIDE</p>
      <h1 id="lp-hero-text-title">選ぶ前の疑問を、短くわかりやすく。</h1>
      <p class="wt-lp-hero__lead">比較記事で得た気づきを、相談・資料・次の確認へ自然につなぎます。</p>
      <div class="wt-lp-cta wt-lp-cta--single"><a class="wt-lp-cta-action" href="#contact">無料で相談する</a></div>
      <div class="wt-lp-cta wt-lp-cta--double"><a class="wt-lp-cta-action" href="#contact">無料で相談する</a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="full" href="#comparison">比較表を見る</a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="short" href="#pricing">料金を見る</a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="trust" href="#voices">利用者の声を見る</a></div>
      <form class="wt-lp-cta wt-lp-cta--form-inline wt-lp-cta-form" method="post" action="/lp/">
        <label for="lp-email-text">メールアドレス</label><div class="wt-lp-cta-form__row"><input id="lp-email-text" name="email" type="email" autocomplete="email" required placeholder="name@example.invalid"><button class="wt-lp-cta-action" type="submit">案内を受け取る</button></div>
      </form>
      <p class="wt-lp-hero__note">相談は無料・入力は 1 分ほど</p>
    </div>
  </section>
</div>
<!-- /wp:html -->

<!-- wp:group {"tagName":"div","anchor":"lp-sections","className":"wt-lp__sections","layout":{"type":"default"}} -->
<div class="wp-block-group wt-lp__sections" id="lp-sections">
<!-- wp:group {"tagName":"section","anchor":"proof","className":"wt-lp__section wt-lp__section--numbers","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--numbers" id="proof"><!-- wp:pattern {"slug":"helix-wt/numbers"} /--><!-- wp:html --><p class="wt-lp-source">出典注記: 数字は表示方法を確認するための PoC 用の架空例です。調査条件と母数を示せる場合だけ実データへ置き換えます。</p><!-- /wp:html --></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"wt-lp__section wt-lp__section--features","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--features"><!-- wp:pattern {"slug":"helix-wt/features"} /--></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"flow","className":"wt-lp__section wt-lp__section--steps","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--steps" id="flow"><!-- wp:pattern {"slug":"helix-wt/steps"} /--></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"logos","className":"wt-lp__section wt-lp__section--logos","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--logos" id="logos">
<!-- wp:html -->
<div class="wt-lp-section-inner"><p class="wt-eyebrow">TRUSTED BY</p><h2>導入を検討したチーム</h2><p class="wt-lp-section__note">掲載許諾を持つ実ロゴではなく、汎用のロゴ枠だけを表示しています。</p><ul class="wt-lp-logo-row" aria-label="汎用ロゴ枠"><li><span aria-label="ロゴ枠 1">01</span></li><li><span aria-label="ロゴ枠 2">02</span></li><li><span aria-label="ロゴ枠 3">03</span></li><li><span aria-label="ロゴ枠 4">04</span></li><li><span aria-label="ロゴ枠 5">05</span></li></ul></div>
<!-- /wp:html -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"voices","className":"wt-lp__section wt-lp__section--testimonials","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--testimonials" id="voices">
<!-- wp:html -->
<div class="wt-lp-section-inner"><p class="wt-eyebrow">TESTIMONIALS</p><h2>相談してから、選び方が整理できた。</h2><p class="wt-lp-section__note">声 3 件中 2 件を例として掲載しています（内容は PoC 用の架空文）。</p><div class="wt-lp-testimonial-grid"><figure class="wt-lp-testimonial"><blockquote>候補の違いが表になっていたので、家族にも説明しやすくなりました。</blockquote><figcaption>利用者 A</figcaption></figure><figure class="wt-lp-testimonial"><blockquote>相談前に確認する項目がわかり、聞きたいことを準備できました。</blockquote><figcaption>利用者 B</figcaption></figure></div></div>
<!-- /wp:html -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"wt-lp__section wt-lp__section--pricing","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--pricing"><!-- wp:pattern {"slug":"helix-wt/pricing"} /--></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"comparison","className":"wt-lp__section wt-lp__section--comparison","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--comparison" id="comparison">
<!-- wp:group {"className":"wt-lp-section-inner","layout":{"type":"default"}} -->
<div class="wp-block-group wt-lp-section-inner"><!-- wp:html --><p class="wt-eyebrow">COMPARISON</p><h2>候補を同じ基準で比べる</h2><p class="wt-lp-section__note">数値・条件は比較方法を示すための架空例です。</p><!-- /wp:html -->
<!-- wp:table {"className":"is-style-wt-compare wt-lp-comparison-table"} -->
<figure class="wp-block-table is-style-wt-compare wt-lp-comparison-table"><table><thead><tr><th>比較項目</th><th>候補 A</th><th>候補 B</th><th>候補 C</th></tr></thead><tbody><tr><td>準備時間</td><td>短い</td><td>標準</td><td>長い</td></tr><tr><td>案内の量</td><td>3 項目</td><td>5 項目</td><td>7 項目</td></tr><tr><td>相談方法</td><td>メール</td><td>フォーム</td><td>電話</td></tr></tbody><tfoot><tr><td colspan="4">比較軸は目的に応じて確認してください。</td></tr></tfoot></table><figcaption>比較表の表示例（PoC 用の架空データ）。</figcaption></figure>
<!-- /wp:table -->
</div>
<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"wt-lp__section wt-lp__section--faq","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--faq"><!-- wp:pattern {"slug":"helix-wt/faq"} /--></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"badges","className":"wt-lp__section wt-lp__section--badges","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--badges" id="badges">
<!-- wp:html -->
<div class="wt-lp-section-inner"><p class="wt-eyebrow">BADGES</p><h2>確認できる安心材料</h2><p class="wt-lp-section__note">第三者の受賞名や認証名は使わず、汎用バッジ枠のみを表示しています。</p><ul class="wt-lp-badge-row" aria-label="汎用バッジ枠"><li><span>確認済み</span><small>枠 01</small></li><li><span>安全設計</span><small>枠 02</small></li><li><span>案内品質</span><small>枠 03</small></li></ul></div>
<!-- /wp:html -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"cases","className":"wt-lp__section wt-lp__section--interview","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--interview" id="cases">
<!-- wp:html -->
<div class="wt-lp-section-inner"><p class="wt-eyebrow">CASE</p><h2>相談したあとに、何が変わったか。</h2><p class="wt-lp-section__note">導入事例は PoC 用の架空文。既定は台帳の多数派「summary-card（遷移リンクなしのサマリー表示）」。</p>
<div class="wt-lp-interview wt-lp-interview--summary-card"><article class="wt-lp-interview__card"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="56" height="56" loading="lazy" decoding="async"><div><p class="wt-lp-interview__meta">導入企業 A・総務</p><h3>比較表を社内説明に転用できた</h3><p class="wt-lp-interview__num"><b>-38</b><small>% 検討時間</small></p><p>候補の違いが表になっていたので、稟議の説明資料をそのまま作れました。</p></div></article><article class="wt-lp-interview__card"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="56" height="56" loading="lazy" decoding="async"><div><p class="wt-lp-interview__meta">導入企業 B・情シス</p><h3>初回相談で要件が固まった</h3><p class="wt-lp-interview__num"><b>2</b><small>週間で導入</small></p><p>聞くべき項目が先に分かり、相談 1 回で条件を決められました。</p></div></article><article class="wt-lp-interview__card"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="56" height="56" loading="lazy" decoding="async"><div><p class="wt-lp-interview__meta">個人利用 C</p><h3>返品を気にせず選べた</h3><p class="wt-lp-interview__num"><b>30</b><small>日 返品可</small></p><p>保証と返品条件を並べて見られたので、最初の 1 台を安心して選べました。</p></div></article></div>
<div class="wt-lp-interview wt-lp-interview--link-card"><a class="wt-lp-interview__card wt-lp-interview__card--link" href="#cases"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="56" height="56" loading="lazy" decoding="async"><div><p class="wt-lp-interview__meta">導入企業 A・総務</p><h3>比較表を社内説明に転用できた</h3><span class="wt-lp-interview__more">記事を読む →</span></div></a><a class="wt-lp-interview__card wt-lp-interview__card--link" href="#cases"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="56" height="56" loading="lazy" decoding="async"><div><p class="wt-lp-interview__meta">導入企業 B・情シス</p><h3>初回相談で要件が固まった</h3><span class="wt-lp-interview__more">記事を読む →</span></div></a><a class="wt-lp-interview__card wt-lp-interview__card--link" href="#cases"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="56" height="56" loading="lazy" decoding="async"><div><p class="wt-lp-interview__meta">個人利用 C</p><h3>返品を気にせず選べた</h3><span class="wt-lp-interview__more">記事を読む →</span></div></a></div>
<ul class="wt-lp-interview wt-lp-interview--logo-only" aria-label="導入企業のロゴ枠"><li><span aria-label="ロゴ枠 1">LOGO 1</span></li><li><span aria-label="ロゴ枠 2">LOGO 2</span></li><li><span aria-label="ロゴ枠 3">LOGO 3</span></li><li><span aria-label="ロゴ枠 4">LOGO 4</span></li><li><span aria-label="ロゴ枠 5">LOGO 5</span></li><li><span aria-label="ロゴ枠 6">LOGO 6</span></li></ul></div>
<!-- /wp:html -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"reviews","className":"wt-lp__section wt-lp__section--review","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--review" id="reviews">
<!-- wp:html -->
<div class="wt-lp-section-inner"><p class="wt-eyebrow">REVIEWS</p><h2>使った人の評価</h2><p class="wt-lp-section__note">口コミは PoC 用の架空文。既定は台帳の多数派「quote+photo（引用 + 顔写真）」。</p>
<div class="wt-lp-review wt-lp-review--quote-photo"><figure class="wt-lp-review__item"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="48" height="48" loading="lazy" decoding="async"><blockquote>比較の基準が先に示されていたので、迷う時間が減りました。</blockquote><figcaption>30 代・在宅ワーク</figcaption></figure><figure class="wt-lp-review__item"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="48" height="48" loading="lazy" decoding="async"><blockquote>相談後に届いた資料が、そのまま家族への説明に使えました。</blockquote><figcaption>40 代・自営業</figcaption></figure><figure class="wt-lp-review__item"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="48" height="48" loading="lazy" decoding="async"><blockquote>価格だけでなく保証の差を見せてくれたのが決め手でした。</blockquote><figcaption>20 代・会社員</figcaption></figure></div>
<div class="wt-lp-review wt-lp-review--stars-count"><p class="wt-lp-review__summary"><span class="wt-lp-review__stars" aria-label="5 点満点中 4.6"><i>★★★★★</i><b>4.6</b></span><span class="wt-lp-review__count">128 件の評価（PoC 用の架空値）</span></p><ul class="wt-lp-review__bars"><li><span>5</span><span class="wt-lp-review__bar"><i style="--v:62"></i></span><b>62%</b></li><li><span>4</span><span class="wt-lp-review__bar"><i style="--v:24"></i></span><b>24%</b></li><li><span>3</span><span class="wt-lp-review__bar"><i style="--v:9"></i></span><b>9%</b></li><li><span>2</span><span class="wt-lp-review__bar"><i style="--v:3"></i></span><b>3%</b></li><li><span>1</span><span class="wt-lp-review__bar"><i style="--v:2"></i></span><b>2%</b></li></ul></div>
<div class="wt-lp-review wt-lp-review--satisfaction-number"><div class="wt-lp-review__big"><b>97.2<small>%</small></b><span>相談後の満足度</span></div><p class="wt-lp-review__basis">2026 年 1〜6 月・相談者 250 名アンケート（PoC 用の架空値。実データでは調査条件と母数を併記する）</p></div></div>
<!-- /wp:html -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"trust","className":"wt-lp__section wt-lp__section--rating","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--rating" id="trust">
<!-- wp:html -->
<div class="wt-lp-section-inner"><p class="wt-eyebrow">TRUST</p><h2>第三者による確認</h2><p class="wt-lp-section__note">受賞名・認証名・媒体名は使わず汎用枠のみ。既定は台帳の多数派「certification（認証・許認可）」。</p>
<ul class="wt-lp-rating wt-lp-rating--certification" aria-label="認証枠"><li><i class="wt-i wt-i--l wt-i--shield" aria-hidden="true"></i><b>認証枠 1</b><small>情報管理の第三者認証</small></li><li><i class="wt-i wt-i--l wt-i--shield" aria-hidden="true"></i><b>認証枠 2</b><small>業界団体の登録</small></li><li><i class="wt-i wt-i--l wt-i--shield" aria-hidden="true"></i><b>認証枠 3</b><small>決済の安全基準</small></li></ul>
<ul class="wt-lp-rating wt-lp-rating--client-logos" aria-label="取引先ロゴ枠"><li><span aria-label="ロゴ枠 1">LOGO 1</span></li><li><span aria-label="ロゴ枠 2">LOGO 2</span></li><li><span aria-label="ロゴ枠 3">LOGO 3</span></li><li><span aria-label="ロゴ枠 4">LOGO 4</span></li><li><span aria-label="ロゴ枠 5">LOGO 5</span></li><li><span aria-label="ロゴ枠 6">LOGO 6</span></li><li><span aria-label="ロゴ枠 7">LOGO 7</span></li><li><span aria-label="ロゴ枠 8">LOGO 8</span></li></ul>
<ul class="wt-lp-rating wt-lp-rating--award-badge" aria-label="受賞バッジ枠"><li><i class="wt-i wt-i--l wt-i--crown" aria-hidden="true"></i><b>受賞枠 1</b><small>2026 年・部門賞</small></li><li><i class="wt-i wt-i--l wt-i--crown" aria-hidden="true"></i><b>ランキング枠</b><small>比較媒体 A 部門 1 位</small></li><li><i class="wt-i wt-i--l wt-i--crown" aria-hidden="true"></i><b>掲載枠</b><small>媒体ロゴ枠 ×3</small></li></ul></div>
<!-- /wp:html -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"download","className":"wt-lp__section wt-lp__section--download","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--download" id="download">
<!-- wp:html -->
<div class="wt-lp-section-inner"><p class="wt-eyebrow">DOWNLOAD</p><h2>比較のチェックリストを受け取る</h2><p class="wt-lp-section__note">既定は台帳の多数派「button-to-form（ボタンでフォームへ）」。</p>
<div class="wt-lp-download wt-lp-download--button-to-form"><div class="wt-lp-download__card"><img src="<?php echo esc_url( $u ); ?>/feature-1.png" alt="資料の表紙イメージ" width="160" height="200" loading="lazy" decoding="async"><div><h3>選び方チェックリスト（PDF・12 ページ）</h3><ul><li>比較 8 項目の見方</li><li>相談前に確認する条件</li><li>保証・返品の読み方</li></ul><a class="wt-lp-cta-action" href="#lp-form">資料を受け取る</a></div></div></div>
<form class="wt-lp-download wt-lp-download--form-inline wt-lp-form-inline" action="#download" method="get" data-wt-poc-form="no-submit"><div class="wt-lp-download__card"><img src="<?php echo esc_url( $u ); ?>/feature-1.png" alt="資料の表紙イメージ" width="160" height="200" loading="lazy" decoding="async"><div><h3>選び方チェックリスト（PDF・12 ページ）</h3><label for="lp-dl-name">お名前</label><input id="lp-dl-name" name="name" type="text" autocomplete="name" required><label for="lp-dl-email">メールアドレス</label><input id="lp-dl-email" name="email" type="email" autocomplete="email" required><button class="wt-lp-cta-action" type="button" aria-describedby="lp-dl-note">ダウンロードする</button><p class="wt-lp-form__note" id="lp-dl-note">PoC のため送信ボタンは無効（type=button）で、入力内容はどこにも送られません。実装時に送信先を設定します。</p></div></div></form></div>
<!-- /wp:html -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"lp-form","className":"wt-lp__section wt-lp__section--form","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--form" id="lp-form">
<!-- wp:html -->
<div class="wt-lp-section-inner"><p class="wt-eyebrow">FORM</p><h2>相談を申し込む</h2><p class="wt-lp-section__note">既定は台帳の多数派「external（外部フォームへ遷移）」。埋め込みは 5 項目の例。</p>
<div class="wt-lp-form wt-lp-form--external"><div class="wt-lp-form__external"><p>申込フォームは別ページで開きます（所要 2 分・5 項目）。</p><a class="wt-lp-cta-action" href="#lp-form" rel="nofollow">申込フォームへ進む <i class="wt-i wt-i--s wt-i--external" aria-hidden="true"></i></a><p class="wt-lp-form__note">遷移先は PoC のためダミーのアンカーです。</p></div></div>
<form class="wt-lp-form wt-lp-form--inline wt-lp-form-inline" action="#lp-form" method="get" data-wt-poc-form="no-submit"><div class="wt-lp-form__grid"><div><label for="lp-f-name">お名前</label><input id="lp-f-name" name="name" type="text" autocomplete="name" required></div><div><label for="lp-f-company">会社名（任意）</label><input id="lp-f-company" name="company" type="text" autocomplete="organization"></div><div><label for="lp-f-email">メールアドレス</label><input id="lp-f-email" name="email" type="email" autocomplete="email" required></div><div><label for="lp-f-tel">電話番号（任意）</label><input id="lp-f-tel" name="tel" type="tel" autocomplete="tel"></div><div class="wt-lp-form__full"><label for="lp-f-note">相談したいこと</label><textarea id="lp-f-note" name="note" rows="3"></textarea></div></div><label class="wt-lp-form__agree"><input type="checkbox" name="agree" required> <span>プライバシーポリシーに同意する</span></label><button class="wt-lp-cta-action" type="button" aria-describedby="lp-f-note-poc">この内容で申し込む</button><p class="wt-lp-form__note" id="lp-f-note-poc">PoC のため送信ボタンは無効（type=button）で、入力内容はどこにも送られません。実装時に送信先を設定します。</p></form></div>
<!-- /wp:html -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"line","className":"wt-lp__section wt-lp__section--line","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--line" id="line">
<!-- wp:html -->
<div class="wt-lp-section-inner"><p class="wt-eyebrow">LINE</p><h2>チャットで気軽に相談</h2><p class="wt-lp-section__note">既定は台帳の多数派「button」。QR 型は SP では QR を出さずボタンに切り替える。リンク先は PoC のためダミーのアンカー。</p>
<div class="wt-lp-line wt-lp-line--button"><a class="wt-lp-line__btn" href="#line" rel="nofollow"><span class="wt-lp-line__mark" aria-hidden="true">LINE</span>友だち追加して相談する</a><p class="wt-lp-form__note">返信は営業時間内・無理な勧誘はしません。</p></div>
<div class="wt-lp-line wt-lp-line--qr"><div class="wt-lp-line__qrwrap"><svg class="wt-lp-line__qr" viewBox="0 0 21 21" role="img" aria-label="QR コードの表示枠（PoC 用のダミー模様。実運用では公式アカウントの QR 画像を置く）" width="120" height="120"><rect x="0" y="0" width="1" height="1"/><rect x="0" y="1" width="1" height="1"/><rect x="0" y="2" width="1" height="1"/><rect x="0" y="3" width="1" height="1"/><rect x="0" y="4" width="1" height="1"/><rect x="0" y="5" width="1" height="1"/><rect x="0" y="6" width="1" height="1"/><rect x="0" y="9" width="1" height="1"/><rect x="0" y="12" width="1" height="1"/><rect x="0" y="14" width="1" height="1"/><rect x="0" y="15" width="1" height="1"/><rect x="0" y="16" width="1" height="1"/><rect x="0" y="17" width="1" height="1"/><rect x="0" y="18" width="1" height="1"/><rect x="0" y="19" width="1" height="1"/><rect x="0" y="20" width="1" height="1"/><rect x="1" y="0" width="1" height="1"/><rect x="1" y="1" width="1" height="1"/><rect x="1" y="4" width="1" height="1"/><rect x="1" y="6" width="1" height="1"/><rect x="1" y="7" width="1" height="1"/><rect x="1" y="10" width="1" height="1"/><rect x="1" y="13" width="1" height="1"/><rect x="1" y="14" width="1" height="1"/><rect x="1" y="16" width="1" height="1"/><rect x="1" y="19" width="1" height="1"/><rect x="1" y="20" width="1" height="1"/><rect x="2" y="0" width="1" height="1"/><rect x="2" y="2" width="1" height="1"/><rect x="2" y="3" width="1" height="1"/><rect x="2" y="4" width="1" height="1"/><rect x="2" y="6" width="1" height="1"/><rect x="2" y="14" width="1" height="1"/><rect x="2" y="16" width="1" height="1"/><rect x="2" y="17" width="1" height="1"/><rect x="2" y="18" width="1" height="1"/><rect x="2" y="20" width="1" height="1"/><rect x="3" y="0" width="1" height="1"/><rect x="3" y="2" width="1" height="1"/><rect x="3" y="3" width="1" height="1"/><rect x="3" y="4" width="1" height="1"/><rect x="3" y="6" width="1" height="1"/><rect x="3" y="9" width="1" height="1"/><rect x="3" y="12" width="1" height="1"/><rect x="3" y="14" width="1" height="1"/><rect x="3" y="15" width="1" height="1"/><rect x="3" y="16" width="1" height="1"/><rect x="3" y="17" width="1" height="1"/><rect x="3" y="18" width="1" height="1"/><rect x="3" y="20" width="1" height="1"/><rect x="4" y="0" width="1" height="1"/><rect x="4" y="1" width="1" height="1"/><rect x="4" y="2" width="1" height="1"/><rect x="4" y="3" width="1" height="1"/><rect x="4" y="4" width="1" height="1"/><rect x="4" y="6" width="1" height="1"/><rect x="4" y="7" width="1" height="1"/><rect x="4" y="10" width="1" height="1"/><rect x="4" y="13" width="1" height="1"/><rect x="4" y="14" width="1" height="1"/><rect x="4" y="16" width="1" height="1"/><rect x="4" y="17" width="1" height="1"/><rect x="4" y="18" width="1" height="1"/><rect x="4" y="19" width="1" height="1"/><rect x="4" y="20" width="1" height="1"/><rect x="5" y="0" width="1" height="1"/><rect x="5" y="6" width="1" height="1"/><rect x="5" y="14" width="1" height="1"/><rect x="5" y="20" width="1" height="1"/><rect x="6" y="0" width="1" height="1"/><rect x="6" y="1" width="1" height="1"/><rect x="6" y="2" width="1" height="1"/><rect x="6" y="3" width="1" height="1"/><rect x="6" y="4" width="1" height="1"/><rect x="6" y="5" width="1" height="1"/><rect x="6" y="6" width="1" height="1"/><rect x="6" y="9" width="1" height="1"/><rect x="6" y="12" width="1" height="1"/><rect x="6" y="14" width="1" height="1"/><rect x="6" y="15" width="1" height="1"/><rect x="6" y="16" width="1" height="1"/><rect x="6" y="17" width="1" height="1"/><rect x="6" y="18" width="1" height="1"/><rect x="6" y="19" width="1" height="1"/><rect x="6" y="20" width="1" height="1"/><rect x="7" y="1" width="1" height="1"/><rect x="7" y="4" width="1" height="1"/><rect x="7" y="7" width="1" height="1"/><rect x="7" y="10" width="1" height="1"/><rect x="7" y="13" width="1" height="1"/><rect x="7" y="16" width="1" height="1"/><rect x="7" y="19" width="1" height="1"/><rect x="9" y="0" width="1" height="1"/><rect x="9" y="3" width="1" height="1"/><rect x="9" y="6" width="1" height="1"/><rect x="9" y="9" width="1" height="1"/><rect x="9" y="12" width="1" height="1"/><rect x="9" y="15" width="1" height="1"/><rect x="9" y="18" width="1" height="1"/><rect x="10" y="1" width="1" height="1"/><rect x="10" y="4" width="1" height="1"/><rect x="10" y="7" width="1" height="1"/><rect x="10" y="10" width="1" height="1"/><rect x="10" y="13" width="1" height="1"/><rect x="10" y="16" width="1" height="1"/><rect x="10" y="19" width="1" height="1"/><rect x="12" y="0" width="1" height="1"/><rect x="12" y="3" width="1" height="1"/><rect x="12" y="6" width="1" height="1"/><rect x="12" y="9" width="1" height="1"/><rect x="12" y="12" width="1" height="1"/><rect x="12" y="15" width="1" height="1"/><rect x="12" y="18" width="1" height="1"/><rect x="13" y="1" width="1" height="1"/><rect x="13" y="4" width="1" height="1"/><rect x="13" y="7" width="1" height="1"/><rect x="13" y="10" width="1" height="1"/><rect x="13" y="13" width="1" height="1"/><rect x="13" y="16" width="1" height="1"/><rect x="13" y="19" width="1" height="1"/><rect x="14" y="0" width="1" height="1"/><rect x="14" y="1" width="1" height="1"/><rect x="14" y="2" width="1" height="1"/><rect x="14" y="3" width="1" height="1"/><rect x="14" y="4" width="1" height="1"/><rect x="14" y="5" width="1" height="1"/><rect x="14" y="6" width="1" height="1"/><rect x="15" y="0" width="1" height="1"/><rect x="15" y="3" width="1" height="1"/><rect x="15" y="6" width="1" height="1"/><rect x="15" y="9" width="1" height="1"/><rect x="15" y="12" width="1" height="1"/><rect x="15" y="15" width="1" height="1"/><rect x="15" y="18" width="1" height="1"/><rect x="16" y="0" width="1" height="1"/><rect x="16" y="1" width="1" height="1"/><rect x="16" y="2" width="1" height="1"/><rect x="16" y="3" width="1" height="1"/><rect x="16" y="4" width="1" height="1"/><rect x="16" y="6" width="1" height="1"/><rect x="16" y="7" width="1" height="1"/><rect x="16" y="10" width="1" height="1"/><rect x="16" y="13" width="1" height="1"/><rect x="16" y="16" width="1" height="1"/><rect x="16" y="19" width="1" height="1"/><rect x="17" y="0" width="1" height="1"/><rect x="17" y="2" width="1" height="1"/><rect x="17" y="3" width="1" height="1"/><rect x="17" y="4" width="1" height="1"/><rect x="17" y="6" width="1" height="1"/><rect x="18" y="0" width="1" height="1"/><rect x="18" y="2" width="1" height="1"/><rect x="18" y="3" width="1" height="1"/><rect x="18" y="4" width="1" height="1"/><rect x="18" y="6" width="1" height="1"/><rect x="18" y="9" width="1" height="1"/><rect x="18" y="12" width="1" height="1"/><rect x="18" y="15" width="1" height="1"/><rect x="18" y="18" width="1" height="1"/><rect x="19" y="0" width="1" height="1"/><rect x="19" y="1" width="1" height="1"/><rect x="19" y="4" width="1" height="1"/><rect x="19" y="6" width="1" height="1"/><rect x="19" y="7" width="1" height="1"/><rect x="19" y="10" width="1" height="1"/><rect x="19" y="13" width="1" height="1"/><rect x="19" y="16" width="1" height="1"/><rect x="19" y="19" width="1" height="1"/><rect x="20" y="0" width="1" height="1"/><rect x="20" y="1" width="1" height="1"/><rect x="20" y="2" width="1" height="1"/><rect x="20" y="3" width="1" height="1"/><rect x="20" y="4" width="1" height="1"/><rect x="20" y="5" width="1" height="1"/><rect x="20" y="6" width="1" height="1"/></svg><div><p><b>QR を読み取って友だち追加</b></p><p class="wt-lp-form__note">SP ではこの枠がボタンに切り替わります。</p><a class="wt-lp-line__btn wt-lp-line__btn--sp" href="#line" rel="nofollow"><span class="wt-lp-line__mark" aria-hidden="true">LINE</span>友だち追加して相談する</a></div></div></div></div>
<!-- /wp:html -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"wt-lp__section wt-lp__section--cta-band wt-lp-cta-band wt-lp-cta-band--one","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--cta-band wt-lp-cta-band wt-lp-cta-band--one"><div class="wt-lp-cta-band__inner"><p class="wt-eyebrow">NEXT STEP</p><h2>まずは選び方を整理しませんか。</h2><p>気になる点を確認してから、次の案内へ進めます。</p><a class="wt-lp-cta-action" href="#contact">無料で相談する</a></div></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"wt-lp__section wt-lp__section--cta-band wt-lp-cta-band wt-lp-cta-band--two","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--cta-band wt-lp-cta-band wt-lp-cta-band--two"><div class="wt-lp-cta-band__inner"><p class="wt-eyebrow">CHECKLIST</p><h2>比較の前提を確認してみる。</h2><p>必要な情報だけを受け取り、納得できる候補を探せます。</p><a class="wt-lp-cta-action" href="#comparison">比較表を見る</a></div></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"contact","className":"wt-lp__section wt-lp__section--cta-band wt-lp-cta-band wt-lp-cta-band--three","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--cta-band wt-lp-cta-band wt-lp-cta-band--three" id="contact"><div class="wt-lp-cta-band__inner"><p class="wt-eyebrow">CONTACT</p><h2>選んだあとも、相談できます。</h2><p>入力項目を増やさず、最初の質問だけを受け付けています。</p><a class="wt-lp-cta-action" href="#lp-hero">相談を始める</a></div></section>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:html -->
<aside class="wt-lp-legal" aria-labelledby="lp-legal-title"><p id="lp-legal-title" class="wt-eyebrow">NOTICE</p><p><del>相談満足度 No.1</del><sup><a href="#lp-footnote-1">※1</a></sup></p><p id="lp-footnote-1" class="wt-lp-legal__footnote">※1 表示例の数値・表現は PoC 用の架空情報です。調査条件・母数を確認できる場合だけ使用します。</p><p class="wt-lp-pr"><span>PR</span>本ページには案内広告を含みます。</p></aside>
<nav class="wt-lp-fixed wt-lp-fixed--sp-bottom-bar" aria-label="固定 CTA"><a href="#lp-sections">概要</a><a href="#contact">相談する</a></nav><a class="wt-lp-fixed wt-lp-fixed--float-cta wt-lp-cta-action" href="#contact">無料で相談する</a><a class="wt-lp-fixed wt-lp-fixed--line-sticky wt-lp-line__btn" href="#contact" rel="nofollow" aria-label="LINE で相談する（追尾ボタン。PoC ではお問い合わせ帯へのアンカー）"><span class="wt-lp-line__mark" aria-hidden="true">LINE</span><span class="wt-lp-fixed__label">相談する</span></a>
<!-- /wp:html -->
