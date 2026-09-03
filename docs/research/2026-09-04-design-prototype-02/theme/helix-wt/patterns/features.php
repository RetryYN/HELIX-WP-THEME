<?php
/**
 * Title: 特徴 3 つ
 * Slug: helix-wt/features
 * Categories: helix-wt
 * Description: カード 3 列（SP 1 列）
 */
$u = get_theme_file_uri( "assets/img" );
?>
<!-- wp:group {"className":"wt-section wt-reveal","align":"full","layout":{"type":"constrained","wideSize":"1120px"}} -->
<div class="wp-block-group alignfull wt-section wt-reveal" id="service">
<!-- wp:paragraph {"className":"wt-eyebrow","align":"center"} --><p class="has-text-align-center wt-eyebrow">Why us</p><!-- /wp:paragraph -->
<!-- wp:heading {"textAlign":"center","style":{"spacing":{"margin":{"top":"0"}}}} --><h2 class="wp-block-heading has-text-align-center" style="margin-top:0">選ばれる 3 つの理由</h2><!-- /wp:heading -->
<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"1.5rem","top":"1.5rem"},"margin":{"top":"2rem"}}}} --><div class="wp-block-columns alignwide" style="margin-top:2rem">
<!-- wp:column --><div class="wp-block-column"><!-- wp:group {"className":"is-style-wt-card","layout":{"type":"flow"}} --><div class="wp-block-group is-style-wt-card"><!-- wp:html --><img class="wt-feature-icon" src="<?php echo esc_url( $u ); ?>/feature-1.png" alt=""><!-- /wp:html --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">成果から逆算した設計</h3><!-- /wp:heading --><!-- wp:paragraph {"fontSize":"s","textColor":"mute"} --><p class="has-mute-color has-text-color has-s-font-size">目標の問い合わせ数から必要な流入と導線を決め、ページ構成に落とします。見た目から入りません。</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column -->
<!-- wp:column --><div class="wp-block-column"><!-- wp:group {"className":"is-style-wt-card","layout":{"type":"flow"}} --><div class="wp-block-group is-style-wt-card"><!-- wp:html --><img class="wt-feature-icon" src="<?php echo esc_url( $u ); ?>/feature-2.png" alt=""><!-- /wp:html --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">公開後の改善が標準</h3><!-- /wp:heading --><!-- wp:paragraph {"fontSize":"s","textColor":"mute"} --><p class="has-mute-color has-text-color has-s-font-size">月次で計測結果を共有し、見出し・CTA・フォームを実データで直します。</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column -->
<!-- wp:column --><div class="wp-block-column"><!-- wp:group {"className":"is-style-wt-card","layout":{"type":"flow"}} --><div class="wp-block-group is-style-wt-card"><!-- wp:html --><img class="wt-feature-icon" src="<?php echo esc_url( $u ); ?>/feature-3.png" alt=""><!-- /wp:html --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">自社で更新できる</h3><!-- /wp:heading --><!-- wp:paragraph {"fontSize":"s","textColor":"mute"} --><p class="has-mute-color has-text-color has-s-font-size">標準のブロックエディターだけで更新できる作りにし、担当者向けの操作研修まで含みます。</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column -->
</div><!-- /wp:columns -->
</div><!-- /wp:group -->
