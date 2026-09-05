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
<nav class="wt-lp-fixed wt-lp-fixed--sp-bottom-bar" aria-label="固定 CTA"><a href="#lp-sections">概要</a><a href="#contact">相談する</a></nav><a class="wt-lp-fixed wt-lp-fixed--float-cta wt-lp-cta-action" href="#contact">無料で相談する</a>
<!-- /wp:html -->
