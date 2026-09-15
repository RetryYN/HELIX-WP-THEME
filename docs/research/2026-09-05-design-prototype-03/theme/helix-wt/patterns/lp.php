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
        <h1 id="lp-hero-split-title"><span class="wt-lp-phrase"><?php esc_html_e( '比べたあとに、', 'helix-wt' ); ?></span><span class="wt-lp-phrase"><?php esc_html_e( '迷わず相談できる', 'helix-wt' ); ?></span><span class="wt-lp-phrase"><?php esc_html_e( '案内を。', 'helix-wt' ); ?></span></h1>
        <p class="wt-lp-hero__lead"><?php esc_html_e( '候補の違いを整理した読者へ、選び方と次の一歩をひとつのページで伝えます。', 'helix-wt' ); ?></p>
        <div class="wt-lp-cta wt-lp-cta--single"><a class="wt-lp-cta-action" href="#contact"><?php esc_html_e( '無料で相談する', 'helix-wt' ); ?></a></div>
        <div class="wt-lp-cta wt-lp-cta--double"><a class="wt-lp-cta-action" href="#contact"><?php esc_html_e( '無料で相談する', 'helix-wt' ); ?></a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="full" href="#comparison"><?php esc_html_e( '比較表を見る', 'helix-wt' ); ?></a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="short" href="#pricing"><?php esc_html_e( '料金を見る', 'helix-wt' ); ?></a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="trust" href="#voices"><?php esc_html_e( '利用者の声を見る', 'helix-wt' ); ?></a></div>
        <form class="wt-lp-cta wt-lp-cta--form-inline wt-lp-cta-form" method="post" action="/lp/">
          <label for="lp-email-split"><?php esc_html_e( 'メールアドレス', 'helix-wt' ); ?></label><div class="wt-lp-cta-form__row"><input id="lp-email-split" name="email" type="email" autocomplete="email" required placeholder="name@example.invalid"><button class="wt-lp-cta-action" type="submit"><?php esc_html_e( '案内を受け取る', 'helix-wt' ); ?></button></div>
        </form>
        <p class="wt-lp-hero__note"><?php esc_html_e( '相談は無料・入力は 1 分ほど', 'helix-wt' ); ?></p>
      </div>
      <div class="wt-lp-hero__media"><img src="<?php echo esc_url( $u ); ?>/hero.png" alt="" width="720" height="540" fetchpriority="high" loading="eager" decoding="async"></div>
    </div>
  </section>

  <section class="wt-lp-hero wt-lp-hero--fullbleed" data-wt-scrim aria-labelledby="lp-hero-fullbleed-title">
    <img class="wt-lp-hero__background" src="<?php echo esc_url( $u ); ?>/hero.png" alt="" width="1440" height="820" fetchpriority="high" loading="eager" decoding="async">
    <div class="wt-lp-hero__content">
      <p class="wt-eyebrow">COMPARE GUIDE</p>
      <h1 id="lp-hero-fullbleed-title"><?php esc_html_e( '選ぶための情報を、次の行動につなげる。', 'helix-wt' ); ?></h1>
      <p class="wt-lp-hero__lead"><?php esc_html_e( '数字・比較・声を一つにまとめ、読み終わった瞬間の迷いを減らします。', 'helix-wt' ); ?></p>
      <div class="wt-lp-cta wt-lp-cta--single"><a class="wt-lp-cta-action" href="#contact"><?php esc_html_e( '無料で相談する', 'helix-wt' ); ?></a></div>
      <div class="wt-lp-cta wt-lp-cta--double"><a class="wt-lp-cta-action" href="#contact"><?php esc_html_e( '無料で相談する', 'helix-wt' ); ?></a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="full" href="#comparison"><?php esc_html_e( '比較表を見る', 'helix-wt' ); ?></a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="short" href="#pricing"><?php esc_html_e( '料金を見る', 'helix-wt' ); ?></a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="trust" href="#voices"><?php esc_html_e( '利用者の声を見る', 'helix-wt' ); ?></a></div>
      <form class="wt-lp-cta wt-lp-cta--form-inline wt-lp-cta-form" method="post" action="/lp/">
        <label for="lp-email-fullbleed"><?php esc_html_e( 'メールアドレス', 'helix-wt' ); ?></label><div class="wt-lp-cta-form__row"><input id="lp-email-fullbleed" name="email" type="email" autocomplete="email" required placeholder="name@example.invalid"><button class="wt-lp-cta-action" type="submit"><?php esc_html_e( '案内を受け取る', 'helix-wt' ); ?></button></div>
      </form>
      <p class="wt-lp-hero__note"><?php esc_html_e( '相談は無料・入力は 1 分ほど', 'helix-wt' ); ?></p>
    </div>
  </section>

  <section class="wt-lp-hero wt-lp-hero--product" aria-labelledby="lp-hero-product-title">
    <div class="wt-lp-hero__product-media"><img src="<?php echo esc_url( $u ); ?>/product-a.png" alt="<?php echo esc_attr__( '商品イメージ', 'helix-wt' ); ?>" width="512" height="512" fetchpriority="high" loading="eager" decoding="async"></div>
    <div class="wt-lp-hero__copy wt-lp-hero__copy--center">
      <p class="wt-eyebrow">PRODUCT GUIDE</p>
      <h1 id="lp-hero-product-title"><?php esc_html_e( '比較した候補を、ひとつの案内に。', 'helix-wt' ); ?></h1>
      <p class="wt-lp-hero__lead"><?php esc_html_e( '仕様と使い方を見比べて、自分に合う選択肢を確認できます。', 'helix-wt' ); ?></p>
      <div class="wt-lp-cta wt-lp-cta--single"><a class="wt-lp-cta-action" href="#contact"><?php esc_html_e( '無料で相談する', 'helix-wt' ); ?></a></div>
      <div class="wt-lp-cta wt-lp-cta--double"><a class="wt-lp-cta-action" href="#contact"><?php esc_html_e( '無料で相談する', 'helix-wt' ); ?></a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="full" href="#comparison"><?php esc_html_e( '比較表を見る', 'helix-wt' ); ?></a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="short" href="#pricing"><?php esc_html_e( '料金を見る', 'helix-wt' ); ?></a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="trust" href="#voices"><?php esc_html_e( '利用者の声を見る', 'helix-wt' ); ?></a></div>
      <form class="wt-lp-cta wt-lp-cta--form-inline wt-lp-cta-form" method="post" action="/lp/">
        <label for="lp-email-product"><?php esc_html_e( 'メールアドレス', 'helix-wt' ); ?></label><div class="wt-lp-cta-form__row"><input id="lp-email-product" name="email" type="email" autocomplete="email" required placeholder="name@example.invalid"><button class="wt-lp-cta-action" type="submit"><?php esc_html_e( '案内を受け取る', 'helix-wt' ); ?></button></div>
      </form>
      <p class="wt-lp-hero__note"><?php esc_html_e( '相談は無料・入力は 1 分ほど', 'helix-wt' ); ?></p>
    </div>
  </section>

  <section class="wt-lp-hero wt-lp-hero--text-only" aria-labelledby="lp-hero-text-title">
    <div class="wt-lp-hero__copy wt-lp-hero__copy--center">
      <p class="wt-eyebrow">COMPARE GUIDE</p>
      <h1 id="lp-hero-text-title"><?php esc_html_e( '選ぶ前の疑問を、短くわかりやすく。', 'helix-wt' ); ?></h1>
      <p class="wt-lp-hero__lead"><?php esc_html_e( '比較記事で得た気づきを、相談・資料・次の確認へ自然につなぎます。', 'helix-wt' ); ?></p>
      <div class="wt-lp-cta wt-lp-cta--single"><a class="wt-lp-cta-action" href="#contact"><?php esc_html_e( '無料で相談する', 'helix-wt' ); ?></a></div>
      <div class="wt-lp-cta wt-lp-cta--double"><a class="wt-lp-cta-action" href="#contact"><?php esc_html_e( '無料で相談する', 'helix-wt' ); ?></a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="full" href="#comparison"><?php esc_html_e( '比較表を見る', 'helix-wt' ); ?></a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="short" href="#pricing"><?php esc_html_e( '料金を見る', 'helix-wt' ); ?></a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" data-lp-cta-target="trust" href="#voices"><?php esc_html_e( '利用者の声を見る', 'helix-wt' ); ?></a></div>
      <form class="wt-lp-cta wt-lp-cta--form-inline wt-lp-cta-form" method="post" action="/lp/">
        <label for="lp-email-text"><?php esc_html_e( 'メールアドレス', 'helix-wt' ); ?></label><div class="wt-lp-cta-form__row"><input id="lp-email-text" name="email" type="email" autocomplete="email" required placeholder="name@example.invalid"><button class="wt-lp-cta-action" type="submit"><?php esc_html_e( '案内を受け取る', 'helix-wt' ); ?></button></div>
      </form>
      <p class="wt-lp-hero__note"><?php esc_html_e( '相談は無料・入力は 1 分ほど', 'helix-wt' ); ?></p>
    </div>
  </section>
</div>
<!-- /wp:html -->

<!-- wp:group {"tagName":"div","anchor":"lp-sections","className":"wt-lp__sections","layout":{"type":"default"}} -->
<div class="wp-block-group wt-lp__sections" id="lp-sections">
<!-- wp:group {"tagName":"section","anchor":"proof","className":"wt-lp__section wt-lp__section--numbers","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--numbers" id="proof"><!-- wp:pattern {"slug":"helix-wt/numbers"} /--><!-- wp:html --><p class="wt-lp-source"><?php esc_html_e( '出典注記: 数字は表示方法を確認するための PoC 用の架空例です。調査条件と母数を示せる場合だけ実データへ置き換えます。', 'helix-wt' ); ?></p><!-- /wp:html --></section>
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
<div class="wt-lp-section-inner"><p class="wt-eyebrow">TRUSTED BY</p><h2><?php esc_html_e( '導入を検討したチーム', 'helix-wt' ); ?></h2><p class="wt-lp-section__note"><?php esc_html_e( '掲載許諾を持つ実ロゴではなく、汎用のロゴ枠だけを表示しています。', 'helix-wt' ); ?></p><ul class="wt-lp-logo-row" aria-label="<?php echo esc_attr__( '汎用ロゴ枠', 'helix-wt' ); ?>"><li><span aria-label="<?php echo esc_attr__( 'ロゴ枠 1', 'helix-wt' ); ?>">01</span></li><li><span aria-label="<?php echo esc_attr__( 'ロゴ枠 2', 'helix-wt' ); ?>">02</span></li><li><span aria-label="<?php echo esc_attr__( 'ロゴ枠 3', 'helix-wt' ); ?>">03</span></li><li><span aria-label="<?php echo esc_attr__( 'ロゴ枠 4', 'helix-wt' ); ?>">04</span></li><li><span aria-label="<?php echo esc_attr__( 'ロゴ枠 5', 'helix-wt' ); ?>">05</span></li></ul></div>
<!-- /wp:html -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"voices","className":"wt-lp__section wt-lp__section--testimonials","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--testimonials" id="voices">
<!-- wp:html -->
<div class="wt-lp-section-inner"><p class="wt-eyebrow">TESTIMONIALS</p><h2><?php esc_html_e( '相談してから、選び方が整理できた。', 'helix-wt' ); ?></h2><p class="wt-lp-section__note"><?php esc_html_e( '声 3 件中 2 件を例として掲載しています（内容は PoC 用の架空文）。', 'helix-wt' ); ?></p><div class="wt-lp-testimonial-grid"><figure class="wt-lp-testimonial"><blockquote><?php esc_html_e( '候補の違いが表になっていたので、家族にも説明しやすくなりました。', 'helix-wt' ); ?></blockquote><figcaption><?php esc_html_e( '利用者 A', 'helix-wt' ); ?></figcaption></figure><figure class="wt-lp-testimonial"><blockquote><?php esc_html_e( '相談前に確認する項目がわかり、聞きたいことを準備できました。', 'helix-wt' ); ?></blockquote><figcaption><?php esc_html_e( '利用者 B', 'helix-wt' ); ?></figcaption></figure></div></div>
<!-- /wp:html -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"wt-lp__section wt-lp__section--pricing","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--pricing"><!-- wp:pattern {"slug":"helix-wt/pricing"} /--></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"comparison","className":"wt-lp__section wt-lp__section--comparison","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--comparison" id="comparison">
<!-- wp:group {"className":"wt-lp-section-inner","layout":{"type":"default"}} -->
<div class="wp-block-group wt-lp-section-inner"><!-- wp:html --><p class="wt-eyebrow">COMPARISON</p><h2><?php esc_html_e( '候補を同じ基準で比べる', 'helix-wt' ); ?></h2><p class="wt-lp-section__note"><?php esc_html_e( '数値・条件は比較方法を示すための架空例です。', 'helix-wt' ); ?></p><!-- /wp:html -->
<!-- wp:table {"className":"is-style-wt-compare wt-lp-comparison-table"} -->
<figure class="wp-block-table is-style-wt-compare wt-lp-comparison-table"><table><thead><tr><th><?php esc_html_e( '比較項目', 'helix-wt' ); ?></th><th><?php esc_html_e( '候補 A', 'helix-wt' ); ?></th><th><?php esc_html_e( '候補 B', 'helix-wt' ); ?></th><th><?php esc_html_e( '候補 C', 'helix-wt' ); ?></th></tr></thead><tbody><tr><td><?php esc_html_e( '準備時間', 'helix-wt' ); ?></td><td><?php esc_html_e( '短い', 'helix-wt' ); ?></td><td><?php esc_html_e( '標準', 'helix-wt' ); ?></td><td><?php esc_html_e( '長い', 'helix-wt' ); ?></td></tr><tr><td><?php esc_html_e( '案内の量', 'helix-wt' ); ?></td><td><?php esc_html_e( '3 項目', 'helix-wt' ); ?></td><td><?php esc_html_e( '5 項目', 'helix-wt' ); ?></td><td><?php esc_html_e( '7 項目', 'helix-wt' ); ?></td></tr><tr><td><?php esc_html_e( '相談方法', 'helix-wt' ); ?></td><td><?php esc_html_e( 'メール', 'helix-wt' ); ?></td><td><?php esc_html_e( 'フォーム', 'helix-wt' ); ?></td><td><?php esc_html_e( '電話', 'helix-wt' ); ?></td></tr></tbody><tfoot><tr><td colspan="4"><?php esc_html_e( '比較軸は目的に応じて確認してください。', 'helix-wt' ); ?></td></tr></tfoot></table><figcaption><?php esc_html_e( '比較表の表示例（PoC 用の架空データ）。', 'helix-wt' ); ?></figcaption></figure>
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
<div class="wt-lp-section-inner"><p class="wt-eyebrow">BADGES</p><h2><?php esc_html_e( '確認できる安心材料', 'helix-wt' ); ?></h2><p class="wt-lp-section__note"><?php esc_html_e( '第三者の受賞名や認証名は使わず、汎用バッジ枠のみを表示しています。', 'helix-wt' ); ?></p><ul class="wt-lp-badge-row" aria-label="<?php echo esc_attr__( '汎用バッジ枠', 'helix-wt' ); ?>"><li><span><?php esc_html_e( '確認済み', 'helix-wt' ); ?></span><small><?php esc_html_e( '枠 01', 'helix-wt' ); ?></small></li><li><span><?php esc_html_e( '安全設計', 'helix-wt' ); ?></span><small><?php esc_html_e( '枠 02', 'helix-wt' ); ?></small></li><li><span><?php esc_html_e( '案内品質', 'helix-wt' ); ?></span><small><?php esc_html_e( '枠 03', 'helix-wt' ); ?></small></li></ul></div>
<!-- /wp:html -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"cases","className":"wt-lp__section wt-lp__section--interview","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--interview" id="cases">
<!-- wp:html -->
<div class="wt-lp-section-inner"><p class="wt-eyebrow">CASE</p><h2><?php esc_html_e( '相談したあとに、何が変わったか。', 'helix-wt' ); ?></h2><p class="wt-lp-section__note"><?php esc_html_e( '導入事例は PoC 用の架空文。既定は台帳の多数派「summary-card（遷移リンクなしのサマリー表示）」。', 'helix-wt' ); ?></p>
<div class="wt-lp-interview wt-lp-interview--summary-card"><article class="wt-lp-interview__card"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="56" height="56" loading="lazy" decoding="async"><div><p class="wt-lp-interview__meta"><?php esc_html_e( '導入企業 A・総務', 'helix-wt' ); ?></p><h3><?php esc_html_e( '比較表を社内説明に転用できた', 'helix-wt' ); ?></h3><p class="wt-lp-interview__num"><b>-38</b><small><?php esc_html_e( '% 検討時間', 'helix-wt' ); ?></small></p><p><?php esc_html_e( '候補の違いが表になっていたので、稟議の説明資料をそのまま作れました。', 'helix-wt' ); ?></p></div></article><article class="wt-lp-interview__card"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="56" height="56" loading="lazy" decoding="async"><div><p class="wt-lp-interview__meta"><?php esc_html_e( '導入企業 B・情シス', 'helix-wt' ); ?></p><h3><?php esc_html_e( '初回相談で要件が固まった', 'helix-wt' ); ?></h3><p class="wt-lp-interview__num"><b>2</b><small><?php esc_html_e( '週間で導入', 'helix-wt' ); ?></small></p><p><?php esc_html_e( '聞くべき項目が先に分かり、相談 1 回で条件を決められました。', 'helix-wt' ); ?></p></div></article><article class="wt-lp-interview__card"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="56" height="56" loading="lazy" decoding="async"><div><p class="wt-lp-interview__meta"><?php esc_html_e( '個人利用 C', 'helix-wt' ); ?></p><h3><?php esc_html_e( '返品を気にせず選べた', 'helix-wt' ); ?></h3><p class="wt-lp-interview__num"><b>30</b><small><?php esc_html_e( '日 返品可', 'helix-wt' ); ?></small></p><p><?php esc_html_e( '保証と返品条件を並べて見られたので、最初の 1 台を安心して選べました。', 'helix-wt' ); ?></p></div></article></div>
<div class="wt-lp-interview wt-lp-interview--link-card"><a class="wt-lp-interview__card wt-lp-interview__card--link" href="#cases"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="56" height="56" loading="lazy" decoding="async"><div><p class="wt-lp-interview__meta"><?php esc_html_e( '導入企業 A・総務', 'helix-wt' ); ?></p><h3><?php esc_html_e( '比較表を社内説明に転用できた', 'helix-wt' ); ?></h3><span class="wt-lp-interview__more"><?php esc_html_e( '記事を読む →', 'helix-wt' ); ?></span></div></a><a class="wt-lp-interview__card wt-lp-interview__card--link" href="#cases"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="56" height="56" loading="lazy" decoding="async"><div><p class="wt-lp-interview__meta"><?php esc_html_e( '導入企業 B・情シス', 'helix-wt' ); ?></p><h3><?php esc_html_e( '初回相談で要件が固まった', 'helix-wt' ); ?></h3><span class="wt-lp-interview__more"><?php esc_html_e( '記事を読む →', 'helix-wt' ); ?></span></div></a><a class="wt-lp-interview__card wt-lp-interview__card--link" href="#cases"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="56" height="56" loading="lazy" decoding="async"><div><p class="wt-lp-interview__meta"><?php esc_html_e( '個人利用 C', 'helix-wt' ); ?></p><h3><?php esc_html_e( '返品を気にせず選べた', 'helix-wt' ); ?></h3><span class="wt-lp-interview__more"><?php esc_html_e( '記事を読む →', 'helix-wt' ); ?></span></div></a></div>
<ul class="wt-lp-interview wt-lp-interview--logo-only" aria-label="<?php echo esc_attr__( '導入企業のロゴ枠', 'helix-wt' ); ?>"><li><span aria-label="<?php echo esc_attr__( 'ロゴ枠 1', 'helix-wt' ); ?>">LOGO 1</span></li><li><span aria-label="<?php echo esc_attr__( 'ロゴ枠 2', 'helix-wt' ); ?>">LOGO 2</span></li><li><span aria-label="<?php echo esc_attr__( 'ロゴ枠 3', 'helix-wt' ); ?>">LOGO 3</span></li><li><span aria-label="<?php echo esc_attr__( 'ロゴ枠 4', 'helix-wt' ); ?>">LOGO 4</span></li><li><span aria-label="<?php echo esc_attr__( 'ロゴ枠 5', 'helix-wt' ); ?>">LOGO 5</span></li><li><span aria-label="<?php echo esc_attr__( 'ロゴ枠 6', 'helix-wt' ); ?>">LOGO 6</span></li></ul></div>
<!-- /wp:html -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"reviews","className":"wt-lp__section wt-lp__section--review","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--review" id="reviews">
<!-- wp:html -->
<div class="wt-lp-section-inner"><p class="wt-eyebrow">REVIEWS</p><h2><?php esc_html_e( '使った人の評価', 'helix-wt' ); ?></h2><p class="wt-lp-section__note"><?php esc_html_e( '口コミは PoC 用の架空文。既定は台帳の多数派「quote+photo（引用 + 顔写真）」。', 'helix-wt' ); ?></p>
<div class="wt-lp-review wt-lp-review--quote-photo"><figure class="wt-lp-review__item"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="48" height="48" loading="lazy" decoding="async"><blockquote><?php esc_html_e( '比較の基準が先に示されていたので、迷う時間が減りました。', 'helix-wt' ); ?></blockquote><figcaption><?php esc_html_e( '30 代・在宅ワーク', 'helix-wt' ); ?></figcaption></figure><figure class="wt-lp-review__item"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="48" height="48" loading="lazy" decoding="async"><blockquote><?php esc_html_e( '相談後に届いた資料が、そのまま家族への説明に使えました。', 'helix-wt' ); ?></blockquote><figcaption><?php esc_html_e( '40 代・自営業', 'helix-wt' ); ?></figcaption></figure><figure class="wt-lp-review__item"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="48" height="48" loading="lazy" decoding="async"><blockquote><?php esc_html_e( '価格だけでなく保証の差を見せてくれたのが決め手でした。', 'helix-wt' ); ?></blockquote><figcaption><?php esc_html_e( '20 代・会社員', 'helix-wt' ); ?></figcaption></figure></div>
<div class="wt-lp-review wt-lp-review--stars-count"><p class="wt-lp-review__summary"><span class="wt-lp-review__stars" aria-label="<?php echo esc_attr__( '5 点満点中 4.6', 'helix-wt' ); ?>"><i>★★★★★</i><b>4.6</b></span><span class="wt-lp-review__count"><?php esc_html_e( '128 件の評価（PoC 用の架空値）', 'helix-wt' ); ?></span></p><ul class="wt-lp-review__bars"><li><span>5</span><span class="wt-lp-review__bar"><i style="--v:62"></i></span><b>62%</b></li><li><span>4</span><span class="wt-lp-review__bar"><i style="--v:24"></i></span><b>24%</b></li><li><span>3</span><span class="wt-lp-review__bar"><i style="--v:9"></i></span><b>9%</b></li><li><span>2</span><span class="wt-lp-review__bar"><i style="--v:3"></i></span><b>3%</b></li><li><span>1</span><span class="wt-lp-review__bar"><i style="--v:2"></i></span><b>2%</b></li></ul></div>
<div class="wt-lp-review wt-lp-review--satisfaction-number"><div class="wt-lp-review__big"><b>97.2<small>%</small></b><span><?php esc_html_e( '相談後の満足度', 'helix-wt' ); ?></span></div><p class="wt-lp-review__basis"><?php esc_html_e( '2026 年 1〜6 月・相談者 250 名アンケート（PoC 用の架空値。実データでは調査条件と母数を併記する）', 'helix-wt' ); ?></p></div></div>
<!-- /wp:html -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"trust","className":"wt-lp__section wt-lp__section--rating","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--rating" id="trust">
<!-- wp:html -->
<div class="wt-lp-section-inner"><p class="wt-eyebrow">TRUST</p><h2><?php esc_html_e( '第三者による確認', 'helix-wt' ); ?></h2><p class="wt-lp-section__note"><?php esc_html_e( '受賞名・認証名・媒体名は使わず汎用枠のみ。既定は台帳の多数派「certification（認証・許認可）」。', 'helix-wt' ); ?></p>
<ul class="wt-lp-rating wt-lp-rating--certification" aria-label="<?php echo esc_attr__( '認証枠', 'helix-wt' ); ?>"><li><i class="wt-i wt-i--l wt-i--shield" aria-hidden="true"></i><b><?php esc_html_e( '認証枠 1', 'helix-wt' ); ?></b><small><?php esc_html_e( '情報管理の第三者認証', 'helix-wt' ); ?></small></li><li><i class="wt-i wt-i--l wt-i--shield" aria-hidden="true"></i><b><?php esc_html_e( '認証枠 2', 'helix-wt' ); ?></b><small><?php esc_html_e( '業界団体の登録', 'helix-wt' ); ?></small></li><li><i class="wt-i wt-i--l wt-i--shield" aria-hidden="true"></i><b><?php esc_html_e( '認証枠 3', 'helix-wt' ); ?></b><small><?php esc_html_e( '決済の安全基準', 'helix-wt' ); ?></small></li></ul>
<ul class="wt-lp-rating wt-lp-rating--client-logos" aria-label="<?php echo esc_attr__( '取引先ロゴ枠', 'helix-wt' ); ?>"><li><span aria-label="<?php echo esc_attr__( 'ロゴ枠 1', 'helix-wt' ); ?>">LOGO 1</span></li><li><span aria-label="<?php echo esc_attr__( 'ロゴ枠 2', 'helix-wt' ); ?>">LOGO 2</span></li><li><span aria-label="<?php echo esc_attr__( 'ロゴ枠 3', 'helix-wt' ); ?>">LOGO 3</span></li><li><span aria-label="<?php echo esc_attr__( 'ロゴ枠 4', 'helix-wt' ); ?>">LOGO 4</span></li><li><span aria-label="<?php echo esc_attr__( 'ロゴ枠 5', 'helix-wt' ); ?>">LOGO 5</span></li><li><span aria-label="<?php echo esc_attr__( 'ロゴ枠 6', 'helix-wt' ); ?>">LOGO 6</span></li><li><span aria-label="<?php echo esc_attr__( 'ロゴ枠 7', 'helix-wt' ); ?>">LOGO 7</span></li><li><span aria-label="<?php echo esc_attr__( 'ロゴ枠 8', 'helix-wt' ); ?>">LOGO 8</span></li></ul>
<ul class="wt-lp-rating wt-lp-rating--award-badge" aria-label="<?php echo esc_attr__( '受賞バッジ枠', 'helix-wt' ); ?>"><li><i class="wt-i wt-i--l wt-i--crown" aria-hidden="true"></i><b><?php esc_html_e( '受賞枠 1', 'helix-wt' ); ?></b><small><?php esc_html_e( '2026 年・部門賞', 'helix-wt' ); ?></small></li><li><i class="wt-i wt-i--l wt-i--crown" aria-hidden="true"></i><b><?php esc_html_e( 'ランキング枠', 'helix-wt' ); ?></b><small><?php esc_html_e( '比較媒体 A 部門 1 位', 'helix-wt' ); ?></small></li><li><i class="wt-i wt-i--l wt-i--crown" aria-hidden="true"></i><b><?php esc_html_e( '掲載枠', 'helix-wt' ); ?></b><small><?php esc_html_e( '媒体ロゴ枠 ×3', 'helix-wt' ); ?></small></li></ul></div>
<!-- /wp:html -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"download","className":"wt-lp__section wt-lp__section--download","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--download" id="download">
<!-- wp:html -->
<div class="wt-lp-section-inner"><p class="wt-eyebrow">DOWNLOAD</p><h2><?php esc_html_e( '比較のチェックリストを受け取る', 'helix-wt' ); ?></h2><p class="wt-lp-section__note"><?php esc_html_e( '既定は台帳の多数派「button-to-form（ボタンでフォームへ）」。', 'helix-wt' ); ?></p>
<div class="wt-lp-download wt-lp-download--button-to-form"><div class="wt-lp-download__card"><img src="<?php echo esc_url( $u ); ?>/feature-1.png" alt="<?php echo esc_attr__( '資料の表紙イメージ', 'helix-wt' ); ?>" width="160" height="200" loading="lazy" decoding="async"><div><h3><?php esc_html_e( '選び方チェックリスト（PDF・12 ページ）', 'helix-wt' ); ?></h3><ul><li><?php esc_html_e( '比較 8 項目の見方', 'helix-wt' ); ?></li><li><?php esc_html_e( '相談前に確認する条件', 'helix-wt' ); ?></li><li><?php esc_html_e( '保証・返品の読み方', 'helix-wt' ); ?></li></ul><a class="wt-lp-cta-action" href="#lp-form"><?php esc_html_e( '資料を受け取る', 'helix-wt' ); ?></a></div></div></div>
<form class="wt-lp-download wt-lp-download--form-inline wt-lp-form-inline" action="#download" method="get" data-wt-poc-form="no-submit"><div class="wt-lp-download__card"><img src="<?php echo esc_url( $u ); ?>/feature-1.png" alt="<?php echo esc_attr__( '資料の表紙イメージ', 'helix-wt' ); ?>" width="160" height="200" loading="lazy" decoding="async"><div><h3><?php esc_html_e( '選び方チェックリスト（PDF・12 ページ）', 'helix-wt' ); ?></h3><label for="lp-dl-name"><?php esc_html_e( 'お名前', 'helix-wt' ); ?></label><input id="lp-dl-name" name="name" type="text" autocomplete="name" required><label for="lp-dl-email"><?php esc_html_e( 'メールアドレス', 'helix-wt' ); ?></label><input id="lp-dl-email" name="email" type="email" autocomplete="email" required><button class="wt-lp-cta-action" type="button" aria-describedby="lp-dl-note"><?php esc_html_e( 'ダウンロードする', 'helix-wt' ); ?></button><p class="wt-lp-form__note" id="lp-dl-note"><?php esc_html_e( 'PoC のため送信ボタンは無効（type=button）で、入力内容はどこにも送られません。実装時に送信先を設定します。', 'helix-wt' ); ?></p></div></div></form></div>
<!-- /wp:html -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"lp-form","className":"wt-lp__section wt-lp__section--form","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--form" id="lp-form">
<!-- wp:html -->
<div class="wt-lp-section-inner"><p class="wt-eyebrow">FORM</p><h2><?php esc_html_e( '相談を申し込む', 'helix-wt' ); ?></h2><p class="wt-lp-section__note"><?php esc_html_e( '既定は台帳の多数派「external（外部フォームへ遷移）」。埋め込みは 5 項目の例。', 'helix-wt' ); ?></p>
<div class="wt-lp-form wt-lp-form--external"><div class="wt-lp-form__external"><p><?php esc_html_e( '申込フォームは別ページで開きます（所要 2 分・5 項目）。', 'helix-wt' ); ?></p><a class="wt-lp-cta-action" href="#lp-form" rel="nofollow"><?php esc_html_e( '申込フォームへ進む', 'helix-wt' ); ?> <i class="wt-i wt-i--s wt-i--external" aria-hidden="true"></i></a><p class="wt-lp-form__note"><?php esc_html_e( '遷移先は PoC のためダミーのアンカーです。', 'helix-wt' ); ?></p></div></div>
<form class="wt-lp-form wt-lp-form--inline wt-lp-form-inline" action="#lp-form" method="get" data-wt-poc-form="no-submit"><div class="wt-lp-form__grid"><div><label for="lp-f-name"><?php esc_html_e( 'お名前', 'helix-wt' ); ?></label><input id="lp-f-name" name="name" type="text" autocomplete="name" required></div><div><label for="lp-f-company"><?php esc_html_e( '会社名（任意）', 'helix-wt' ); ?></label><input id="lp-f-company" name="company" type="text" autocomplete="organization"></div><div><label for="lp-f-email"><?php esc_html_e( 'メールアドレス', 'helix-wt' ); ?></label><input id="lp-f-email" name="email" type="email" autocomplete="email" required></div><div><label for="lp-f-tel"><?php esc_html_e( '電話番号（任意）', 'helix-wt' ); ?></label><input id="lp-f-tel" name="tel" type="tel" autocomplete="tel"></div><div class="wt-lp-form__full"><label for="lp-f-note"><?php esc_html_e( '相談したいこと', 'helix-wt' ); ?></label><textarea id="lp-f-note" name="note" rows="3"></textarea></div></div><label class="wt-lp-form__agree"><input type="checkbox" name="agree" required> <span><?php esc_html_e( 'プライバシーポリシーに同意する', 'helix-wt' ); ?></span></label><button class="wt-lp-cta-action" type="button" aria-describedby="lp-f-note-poc"><?php esc_html_e( 'この内容で申し込む', 'helix-wt' ); ?></button><p class="wt-lp-form__note" id="lp-f-note-poc"><?php esc_html_e( 'PoC のため送信ボタンは無効（type=button）で、入力内容はどこにも送られません。実装時に送信先を設定します。', 'helix-wt' ); ?></p></form></div>
<!-- /wp:html -->
<!-- wp:group {"className":"wt-lp-form wt-lp-form--block","layout":{"type":"constrained"}} -->
<div class="wp-block-group wt-lp-form wt-lp-form--block"><!-- wp:helix-wt/form {"hideTitle":true} /--></div>
<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"line","className":"wt-lp__section wt-lp__section--line","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--line" id="line">
<!-- wp:html -->
<div class="wt-lp-section-inner"><p class="wt-eyebrow">LINE</p><h2><?php esc_html_e( 'チャットで気軽に相談', 'helix-wt' ); ?></h2><p class="wt-lp-section__note"><?php esc_html_e( '既定は台帳の多数派「button」。QR 型は SP では QR を出さずボタンに切り替える。アイコンは自作の吹き出しグリフ（WT-EVT-0276）。リンク先は PoC のためダミーのアンカー。', 'helix-wt' ); ?></p>
<div class="wt-lp-line wt-lp-line--button"><a class="wt-lp-line__btn" href="#line" rel="nofollow"><span class="wt-lp-line__mark" aria-hidden="true"><i class="wt-i wt-i--bubble"></i></span><?php esc_html_e( 'LINE で友だち追加', 'helix-wt' ); ?></a><p class="wt-lp-form__note"><?php esc_html_e( '返信は営業時間内・無理な勧誘はしません。', 'helix-wt' ); ?></p></div>
<div class="wt-lp-line wt-lp-line--qr"><div class="wt-lp-line__qrwrap"><svg class="wt-lp-line__qr" viewBox="0 0 21 21" role="img" aria-label="<?php echo esc_attr__( 'QR コードの表示枠（PoC 用のダミー模様。実運用では公式アカウントの QR 画像を置く）', 'helix-wt' ); ?>" width="120" height="120"><rect x="0" y="0" width="1" height="1"/><rect x="0" y="1" width="1" height="1"/><rect x="0" y="2" width="1" height="1"/><rect x="0" y="3" width="1" height="1"/><rect x="0" y="4" width="1" height="1"/><rect x="0" y="5" width="1" height="1"/><rect x="0" y="6" width="1" height="1"/><rect x="0" y="9" width="1" height="1"/><rect x="0" y="12" width="1" height="1"/><rect x="0" y="14" width="1" height="1"/><rect x="0" y="15" width="1" height="1"/><rect x="0" y="16" width="1" height="1"/><rect x="0" y="17" width="1" height="1"/><rect x="0" y="18" width="1" height="1"/><rect x="0" y="19" width="1" height="1"/><rect x="0" y="20" width="1" height="1"/><rect x="1" y="0" width="1" height="1"/><rect x="1" y="1" width="1" height="1"/><rect x="1" y="4" width="1" height="1"/><rect x="1" y="6" width="1" height="1"/><rect x="1" y="7" width="1" height="1"/><rect x="1" y="10" width="1" height="1"/><rect x="1" y="13" width="1" height="1"/><rect x="1" y="14" width="1" height="1"/><rect x="1" y="16" width="1" height="1"/><rect x="1" y="19" width="1" height="1"/><rect x="1" y="20" width="1" height="1"/><rect x="2" y="0" width="1" height="1"/><rect x="2" y="2" width="1" height="1"/><rect x="2" y="3" width="1" height="1"/><rect x="2" y="4" width="1" height="1"/><rect x="2" y="6" width="1" height="1"/><rect x="2" y="14" width="1" height="1"/><rect x="2" y="16" width="1" height="1"/><rect x="2" y="17" width="1" height="1"/><rect x="2" y="18" width="1" height="1"/><rect x="2" y="20" width="1" height="1"/><rect x="3" y="0" width="1" height="1"/><rect x="3" y="2" width="1" height="1"/><rect x="3" y="3" width="1" height="1"/><rect x="3" y="4" width="1" height="1"/><rect x="3" y="6" width="1" height="1"/><rect x="3" y="9" width="1" height="1"/><rect x="3" y="12" width="1" height="1"/><rect x="3" y="14" width="1" height="1"/><rect x="3" y="15" width="1" height="1"/><rect x="3" y="16" width="1" height="1"/><rect x="3" y="17" width="1" height="1"/><rect x="3" y="18" width="1" height="1"/><rect x="3" y="20" width="1" height="1"/><rect x="4" y="0" width="1" height="1"/><rect x="4" y="1" width="1" height="1"/><rect x="4" y="2" width="1" height="1"/><rect x="4" y="3" width="1" height="1"/><rect x="4" y="4" width="1" height="1"/><rect x="4" y="6" width="1" height="1"/><rect x="4" y="7" width="1" height="1"/><rect x="4" y="10" width="1" height="1"/><rect x="4" y="13" width="1" height="1"/><rect x="4" y="14" width="1" height="1"/><rect x="4" y="16" width="1" height="1"/><rect x="4" y="17" width="1" height="1"/><rect x="4" y="18" width="1" height="1"/><rect x="4" y="19" width="1" height="1"/><rect x="4" y="20" width="1" height="1"/><rect x="5" y="0" width="1" height="1"/><rect x="5" y="6" width="1" height="1"/><rect x="5" y="14" width="1" height="1"/><rect x="5" y="20" width="1" height="1"/><rect x="6" y="0" width="1" height="1"/><rect x="6" y="1" width="1" height="1"/><rect x="6" y="2" width="1" height="1"/><rect x="6" y="3" width="1" height="1"/><rect x="6" y="4" width="1" height="1"/><rect x="6" y="5" width="1" height="1"/><rect x="6" y="6" width="1" height="1"/><rect x="6" y="9" width="1" height="1"/><rect x="6" y="12" width="1" height="1"/><rect x="6" y="14" width="1" height="1"/><rect x="6" y="15" width="1" height="1"/><rect x="6" y="16" width="1" height="1"/><rect x="6" y="17" width="1" height="1"/><rect x="6" y="18" width="1" height="1"/><rect x="6" y="19" width="1" height="1"/><rect x="6" y="20" width="1" height="1"/><rect x="7" y="1" width="1" height="1"/><rect x="7" y="4" width="1" height="1"/><rect x="7" y="7" width="1" height="1"/><rect x="7" y="10" width="1" height="1"/><rect x="7" y="13" width="1" height="1"/><rect x="7" y="16" width="1" height="1"/><rect x="7" y="19" width="1" height="1"/><rect x="9" y="0" width="1" height="1"/><rect x="9" y="3" width="1" height="1"/><rect x="9" y="6" width="1" height="1"/><rect x="9" y="9" width="1" height="1"/><rect x="9" y="12" width="1" height="1"/><rect x="9" y="15" width="1" height="1"/><rect x="9" y="18" width="1" height="1"/><rect x="10" y="1" width="1" height="1"/><rect x="10" y="4" width="1" height="1"/><rect x="10" y="7" width="1" height="1"/><rect x="10" y="10" width="1" height="1"/><rect x="10" y="13" width="1" height="1"/><rect x="10" y="16" width="1" height="1"/><rect x="10" y="19" width="1" height="1"/><rect x="12" y="0" width="1" height="1"/><rect x="12" y="3" width="1" height="1"/><rect x="12" y="6" width="1" height="1"/><rect x="12" y="9" width="1" height="1"/><rect x="12" y="12" width="1" height="1"/><rect x="12" y="15" width="1" height="1"/><rect x="12" y="18" width="1" height="1"/><rect x="13" y="1" width="1" height="1"/><rect x="13" y="4" width="1" height="1"/><rect x="13" y="7" width="1" height="1"/><rect x="13" y="10" width="1" height="1"/><rect x="13" y="13" width="1" height="1"/><rect x="13" y="16" width="1" height="1"/><rect x="13" y="19" width="1" height="1"/><rect x="14" y="0" width="1" height="1"/><rect x="14" y="1" width="1" height="1"/><rect x="14" y="2" width="1" height="1"/><rect x="14" y="3" width="1" height="1"/><rect x="14" y="4" width="1" height="1"/><rect x="14" y="5" width="1" height="1"/><rect x="14" y="6" width="1" height="1"/><rect x="15" y="0" width="1" height="1"/><rect x="15" y="3" width="1" height="1"/><rect x="15" y="6" width="1" height="1"/><rect x="15" y="9" width="1" height="1"/><rect x="15" y="12" width="1" height="1"/><rect x="15" y="15" width="1" height="1"/><rect x="15" y="18" width="1" height="1"/><rect x="16" y="0" width="1" height="1"/><rect x="16" y="1" width="1" height="1"/><rect x="16" y="2" width="1" height="1"/><rect x="16" y="3" width="1" height="1"/><rect x="16" y="4" width="1" height="1"/><rect x="16" y="6" width="1" height="1"/><rect x="16" y="7" width="1" height="1"/><rect x="16" y="10" width="1" height="1"/><rect x="16" y="13" width="1" height="1"/><rect x="16" y="16" width="1" height="1"/><rect x="16" y="19" width="1" height="1"/><rect x="17" y="0" width="1" height="1"/><rect x="17" y="2" width="1" height="1"/><rect x="17" y="3" width="1" height="1"/><rect x="17" y="4" width="1" height="1"/><rect x="17" y="6" width="1" height="1"/><rect x="18" y="0" width="1" height="1"/><rect x="18" y="2" width="1" height="1"/><rect x="18" y="3" width="1" height="1"/><rect x="18" y="4" width="1" height="1"/><rect x="18" y="6" width="1" height="1"/><rect x="18" y="9" width="1" height="1"/><rect x="18" y="12" width="1" height="1"/><rect x="18" y="15" width="1" height="1"/><rect x="18" y="18" width="1" height="1"/><rect x="19" y="0" width="1" height="1"/><rect x="19" y="1" width="1" height="1"/><rect x="19" y="4" width="1" height="1"/><rect x="19" y="6" width="1" height="1"/><rect x="19" y="7" width="1" height="1"/><rect x="19" y="10" width="1" height="1"/><rect x="19" y="13" width="1" height="1"/><rect x="19" y="16" width="1" height="1"/><rect x="19" y="19" width="1" height="1"/><rect x="20" y="0" width="1" height="1"/><rect x="20" y="1" width="1" height="1"/><rect x="20" y="2" width="1" height="1"/><rect x="20" y="3" width="1" height="1"/><rect x="20" y="4" width="1" height="1"/><rect x="20" y="5" width="1" height="1"/><rect x="20" y="6" width="1" height="1"/></svg><div><p class="wt-only-pc"><b><?php esc_html_e( 'QR を読み取って LINE で友だち追加', 'helix-wt' ); ?></b></p><p class="wt-lp-form__note wt-only-pc"><?php esc_html_e( 'スマートフォンのカメラで読み取ると友だち追加画面が開きます。', 'helix-wt' ); ?></p><p class="wt-only-sp"><b><?php esc_html_e( 'LINE で友だち追加して相談', 'helix-wt' ); ?></b></p><p class="wt-lp-form__note wt-only-sp"><?php esc_html_e( 'スマートフォンではボタンからそのまま友だち追加できます。', 'helix-wt' ); ?></p><a class="wt-lp-line__btn wt-lp-line__btn--sp" href="#line" rel="nofollow"><span class="wt-lp-line__mark" aria-hidden="true"><i class="wt-i wt-i--bubble"></i></span><?php esc_html_e( 'LINE で友だち追加', 'helix-wt' ); ?></a></div></div></div></div>
<!-- /wp:html -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"wt-lp__section wt-lp__section--cta-band wt-lp-cta-band wt-lp-cta-band--one","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--cta-band wt-lp-cta-band wt-lp-cta-band--one"><div class="wt-lp-cta-band__inner"><p class="wt-eyebrow">NEXT STEP</p><h2><?php esc_html_e( 'まずは選び方を整理しませんか。', 'helix-wt' ); ?></h2><p><?php esc_html_e( '気になる点を確認してから、次の案内へ進めます。', 'helix-wt' ); ?></p><a class="wt-lp-cta-action" href="#contact"><?php esc_html_e( '無料で相談する', 'helix-wt' ); ?></a></div></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"wt-lp__section wt-lp__section--cta-band wt-lp-cta-band wt-lp-cta-band--two","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--cta-band wt-lp-cta-band wt-lp-cta-band--two"><div class="wt-lp-cta-band__inner"><p class="wt-eyebrow">CHECKLIST</p><h2><?php esc_html_e( '比較の前提を確認してみる。', 'helix-wt' ); ?></h2><p><?php esc_html_e( '必要な情報だけを受け取り、納得できる候補を探せます。', 'helix-wt' ); ?></p><a class="wt-lp-cta-action" href="#comparison"><?php esc_html_e( '比較表を見る', 'helix-wt' ); ?></a></div></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"contact","className":"wt-lp__section wt-lp__section--cta-band wt-lp-cta-band wt-lp-cta-band--three","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull wt-lp__section wt-lp__section--cta-band wt-lp-cta-band wt-lp-cta-band--three" id="contact"><div class="wt-lp-cta-band__inner"><p class="wt-eyebrow">CONTACT</p><h2><?php esc_html_e( '選んだあとも、相談できます。', 'helix-wt' ); ?></h2><p><?php esc_html_e( '入力項目を増やさず、最初の質問だけを受け付けています。', 'helix-wt' ); ?></p><a class="wt-lp-cta-action" href="#lp-hero"><?php esc_html_e( '相談を始める', 'helix-wt' ); ?></a></div></section>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:html -->
<aside class="wt-lp-legal" aria-labelledby="lp-legal-title"><p id="lp-legal-title" class="wt-eyebrow">NOTICE</p><p><del><?php esc_html_e( '相談満足度 No.1', 'helix-wt' ); ?></del><sup><a href="#lp-footnote-1">※1</a></sup></p><p id="lp-footnote-1" class="wt-lp-legal__footnote"><?php esc_html_e( '※1 表示例の数値・表現は PoC 用の架空情報です。調査条件・母数を確認できる場合だけ使用します。', 'helix-wt' ); ?></p><p class="wt-lp-pr"><span>PR</span><?php esc_html_e( '本ページには案内広告を含みます。', 'helix-wt' ); ?></p></aside>
<nav class="wt-lp-fixed wt-lp-fixed--sp-bottom-bar" aria-label="<?php echo esc_attr__( '固定 CTA', 'helix-wt' ); ?>"><a href="#lp-sections"><?php esc_html_e( '概要', 'helix-wt' ); ?></a><a href="#contact"><?php esc_html_e( '相談する', 'helix-wt' ); ?></a></nav><a class="wt-lp-fixed wt-lp-fixed--float-cta wt-lp-cta-action" href="#contact"><?php esc_html_e( '無料で相談する', 'helix-wt' ); ?></a><a class="wt-lp-fixed wt-lp-fixed--line-sticky wt-lp-line__btn" href="#contact" rel="nofollow" aria-label="<?php echo esc_attr__( 'LINE で相談する（追尾ボタン。PoC ではお問い合わせ帯へのアンカー）', 'helix-wt' ); ?>"><span class="wt-lp-line__mark" aria-hidden="true"><i class="wt-i wt-i--bubble"></i></span><span class="wt-lp-fixed__label"><?php esc_html_e( '相談する', 'helix-wt' ); ?></span></a>
<!-- /wp:html -->
