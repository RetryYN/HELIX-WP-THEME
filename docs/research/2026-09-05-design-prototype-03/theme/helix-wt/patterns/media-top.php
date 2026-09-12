<?php
/**
 * Title: メディア トップ（ピックアップ・タブ・カテゴリ・ランキング・プロフィール）
 * Slug: helix-wt/media-top
 * Categories: helix-wt
 * Description: テーマB 系の構造参照
 */
$u = get_theme_file_uri( "assets/img" );
?>
<!-- wp:group {"className":"wt-section","align":"full","layout":{"type":"constrained","wideSize":"1120px"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|50"}}}} -->
<div class="wp-block-group alignfull wt-section" style="padding-top:var(--wp--preset--spacing--50)">
<!-- wp:paragraph {"className":"wt-eyebrow"} --><p class="wt-eyebrow">Pickup</p><!-- /wp:paragraph -->
<!-- wp:html -->
<div class="wt-pick wt-pick--hero alignwide">
<a class="wt-pick__item" href="#"><img src="<?php echo esc_url( $u ); ?>/media-pickup-1.jpg" alt=""><span class="wt-pick__cat"><?php esc_html_e( '動画編集', 'helix-wt' ); ?></span><p class="wt-pick__title"><?php esc_html_e( '動画編集者とは？副業はおすすめなのか、月 5 万円稼ぐための方法を完全解説', 'helix-wt' ); ?></p><span class="wt-pick__date">2026.08.20</span></a>
<a class="wt-pick__item" href="#"><img src="<?php echo esc_url( $u ); ?>/media-pickup-2.jpg" alt=""><span class="wt-pick__cat"><?php esc_html_e( 'Web ライター', 'helix-wt' ); ?></span><p class="wt-pick__title"><?php esc_html_e( 'Web ライターに向いている人の特徴 7 選。適性診断と挫折しない方法', 'helix-wt' ); ?></p><span class="wt-pick__date">2026.08.18</span></a>
<a class="wt-pick__item" href="#"><img src="<?php echo esc_url( $u ); ?>/media-pickup-3.jpg" alt=""><span class="wt-pick__cat"><?php esc_html_e( '経理', 'helix-wt' ); ?></span><p class="wt-pick__title"><?php esc_html_e( '副業の帳簿づけ、最初の 1 か月でやること', 'helix-wt' ); ?></p><span class="wt-pick__date">2026.08.15</span></a>
<a class="wt-pick__item" href="#"><img src="<?php echo esc_url( $u ); ?>/media-pickup-4.jpg" alt=""><span class="wt-pick__cat"><?php esc_html_e( '働き方', 'helix-wt' ); ?></span><p class="wt-pick__title"><?php esc_html_e( '在宅ワークの机まわり、3 万円で整える', 'helix-wt' ); ?></p><span class="wt-pick__date">2026.08.12</span></a>
<a class="wt-pick__item" href="#"><img src="<?php echo esc_url( $u ); ?>/media-pickup-5.jpg" alt=""><span class="wt-pick__cat"><?php esc_html_e( '学び', 'helix-wt' ); ?></span><p class="wt-pick__title"><?php esc_html_e( 'オンライン講座の選び方。続いた人が最初にやったこと', 'helix-wt' ); ?></p><span class="wt-pick__date">2026.08.10</span></a>
</div>
<!-- /wp:html -->
</div><!-- /wp:group -->
<!-- wp:group {"className":"wt-section wt-tabs","align":"full","layout":{"type":"constrained","wideSize":"1120px"},"style":{"spacing":{"padding":{"top":"0"}}}} -->
<div class="wp-block-group alignfull wt-section wt-tabs" style="padding-top:0">
<!-- wp:tabs --><div class="wp-block-tabs">
<!-- wp:tab {"label":"新着記事"} --><div class="wp-block-tab"><!-- wp:html -->
<div class="wt-pick">
<a class="wt-pick__item" href="#"><img src="<?php echo esc_url( $u ); ?>/media-pickup-6.jpg" alt=""><span class="wt-pick__cat"><?php esc_html_e( '開業', 'helix-wt' ); ?></span><p class="wt-pick__title"><?php esc_html_e( '小さなお店の予約を Web に移す。初月にやった 5 つのこと', 'helix-wt' ); ?></p><span class="wt-pick__date">2026.08.22</span></a>
<a class="wt-pick__item" href="#"><img src="<?php echo esc_url( $u ); ?>/media-pickup-2.jpg" alt=""><span class="wt-pick__cat"><?php esc_html_e( 'Web ライター', 'helix-wt' ); ?></span><p class="wt-pick__title"><?php esc_html_e( '初案件の単価交渉、実際に送った文面', 'helix-wt' ); ?></p><span class="wt-pick__date">2026.08.21</span></a>
<a class="wt-pick__item" href="#"><img src="<?php echo esc_url( $u ); ?>/media-pickup-4.jpg" alt=""><span class="wt-pick__cat"><?php esc_html_e( '働き方', 'helix-wt' ); ?></span><p class="wt-pick__title"><?php esc_html_e( '週 3 リモートに切り替えて変わった時間の使い方', 'helix-wt' ); ?></p><span class="wt-pick__date">2026.08.19</span></a>
</div>
<!-- /wp:html --></div><!-- /wp:tab -->
<!-- wp:tab {"label":"人気記事"} --><div class="wp-block-tab"><!-- wp:html -->
<ol class="wt-rank">
<li><img src="<?php echo esc_url( $u ); ?>/media-pickup-1.jpg" alt=""><a href="#"><?php esc_html_e( '動画編集者とは？副業はおすすめなのか、月 5 万円稼ぐための方法', 'helix-wt' ); ?></a></li>
<li><img src="<?php echo esc_url( $u ); ?>/media-pickup-3.jpg" alt=""><a href="#"><?php esc_html_e( '副業の帳簿づけ、最初の 1 か月でやること', 'helix-wt' ); ?></a></li>
<li><img src="<?php echo esc_url( $u ); ?>/media-pickup-5.jpg" alt=""><a href="#"><?php esc_html_e( 'オンライン講座の選び方。続いた人が最初にやったこと', 'helix-wt' ); ?></a></li>
<li><img src="<?php echo esc_url( $u ); ?>/media-pickup-6.jpg" alt=""><a href="#"><?php esc_html_e( '小さなお店の予約を Web に移す', 'helix-wt' ); ?></a></li>
<li><img src="<?php echo esc_url( $u ); ?>/media-pickup-2.jpg" alt=""><a href="#"><?php esc_html_e( 'Web ライターに向いている人の特徴 7 選', 'helix-wt' ); ?></a></li>
</ol>
<!-- /wp:html --></div><!-- /wp:tab -->
</div><!-- /wp:tabs -->
</div><!-- /wp:group -->
<!-- wp:group {"className":"wt-section","align":"full","backgroundColor":"surface","layout":{"type":"constrained","wideSize":"1120px"}} -->
<div class="wp-block-group alignfull wt-section has-surface-background-color has-background">
<!-- wp:heading {"textAlign":"center","style":{"spacing":{"margin":{"top":"0","bottom":"1.25rem"}}}} --><h2 class="wp-block-heading has-text-align-center" style="margin-top:0;margin-bottom:1.25rem"><?php esc_html_e( 'カテゴリから探す', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<!-- wp:html -->
<div class="wt-cats alignwide">
<a href="#"><i class="wt-i wt-i--l wt-i--edit"></i><?php esc_html_e( 'Web ライター', 'helix-wt' ); ?></a><a href="#"><i class="wt-i wt-i--l wt-i--chart"></i><?php esc_html_e( '動画編集', 'helix-wt' ); ?></a><a href="#"><i class="wt-i wt-i--l wt-i--file"></i><?php esc_html_e( '経理・確定申告', 'helix-wt' ); ?></a><a href="#"><i class="wt-i wt-i--l wt-i--home"></i><?php esc_html_e( '在宅ワーク', 'helix-wt' ); ?></a>
<a href="#"><i class="wt-i wt-i--l wt-i--lightbulb"></i><?php esc_html_e( '学び直し', 'helix-wt' ); ?></a><a href="#"><i class="wt-i wt-i--l wt-i--cart"></i><?php esc_html_e( '開業・EC', 'helix-wt' ); ?></a><a href="#"><i class="wt-i wt-i--l wt-i--trend"></i><?php esc_html_e( '副業の始め方', 'helix-wt' ); ?></a><a href="#"><i class="wt-i wt-i--l wt-i--user"></i><?php esc_html_e( '体験談', 'helix-wt' ); ?></a>
</div>
<!-- /wp:html -->
</div><!-- /wp:group -->
<!-- wp:group {"className":"wt-section","align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull wt-section">
<!-- wp:html -->
<div class="wt-profile"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt=""><div><p><strong><?php esc_html_e( '編集部 田中', 'helix-wt' ); ?></strong> <span class="wt-badge"><?php esc_html_e( '運営者', 'helix-wt' ); ?></span></p><p><?php esc_html_e( '会社員を続けながら副業で月 20 万円。経理と Web の実務を 8 年やってきた経験から、始め方と続け方を書いています。', 'helix-wt' ); ?></p><p><a href="#"><?php esc_html_e( 'プロフィールを見る', 'helix-wt' ); ?> <i class="wt-i wt-i--s wt-i--arrow-right"></i></a></p></div></div>
<!-- /wp:html -->
</div><!-- /wp:group -->
