<?php
/**
 * Title: ヒーロー（分割）
 * Slug: helix-wt/hero-split
 * Categories: helix-wt
 * Description: 文字｜ビジュアルの左右分割。SP は縦積み
 */
$u = get_theme_file_uri( "assets/img" );
?>
<!-- wp:group {"className":"wt-hero","align":"full","layout":{"type":"constrained","wideSize":"1120px"}} -->
<div class="wp-block-group alignfull wt-hero">
<!-- wp:columns {"align":"wide","verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"3rem"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center">
<!-- wp:column {"width":"55%"} --><div class="wp-block-column" style="flex-basis:55%">
<!-- wp:paragraph {"className":"wt-eyebrow"} --><p class="wt-eyebrow">Web marketing for SMB</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"fontSize":"hero","style":{"typography":{"lineHeight":"1.3"}}} --><h1 class="wp-block-heading has-hero-font-size" style="line-height:1.3">問い合わせが増えるホームページを、3 か月で。</h1><!-- /wp:heading -->
<!-- wp:paragraph {"fontSize":"l","textColor":"mute","style":{"spacing":{"margin":{"top":"1rem"}}}} --><p class="has-mute-color has-text-color has-l-font-size" style="margin-top:1rem">制作から運用・改善まで一社で。中小企業 320 社の集客を支援してきたチームが、成果の出る導線を設計します。</p><!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","flexWrap":"wrap"}} --><div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"cta","textColor":"cta-contrast"} --><div class="wp-block-button"><a class="wp-block-button__link has-cta-contrast-color has-cta-background-color has-text-color has-background wp-element-button" href="#contact">無料で相談する</a></div><!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#cases">導入事例を見る</a></div><!-- /wp:button -->
</div><!-- /wp:buttons -->
<!-- wp:paragraph {"fontSize":"xs","textColor":"mute"} --><p class="has-mute-color has-text-color has-xs-font-size">初回相談 60 分無料 ・ 契約の縛りなし</p><!-- /wp:paragraph -->
</div><!-- /wp:column -->
<!-- wp:column {"width":"45%"} --><div class="wp-block-column" style="flex-basis:45%">
<!-- wp:group {"className":"wt-hero__visual","layout":{"type":"flow"}} --><div class="wp-block-group wt-hero__visual"><img src="<?php echo esc_url( $u ); ?>/hero.png" alt=""></div><!-- /wp:group -->
</div><!-- /wp:column -->
</div><!-- /wp:columns -->
</div><!-- /wp:group -->
