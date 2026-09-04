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
<a class="wt-pick__item" href="#"><img src="<?php echo esc_url( $u ); ?>/media-pickup-1.jpg" alt=""><span class="wt-pick__cat">動画編集</span><p class="wt-pick__title">動画編集者とは？副業はおすすめなのか、月 5 万円稼ぐための方法を完全解説</p><span class="wt-pick__date">2026.08.20</span></a>
<a class="wt-pick__item" href="#"><img src="<?php echo esc_url( $u ); ?>/media-pickup-2.jpg" alt=""><span class="wt-pick__cat">Web ライター</span><p class="wt-pick__title">Web ライターに向いている人の特徴 7 選。適性診断と挫折しない方法</p><span class="wt-pick__date">2026.08.18</span></a>
<a class="wt-pick__item" href="#"><img src="<?php echo esc_url( $u ); ?>/media-pickup-3.jpg" alt=""><span class="wt-pick__cat">経理</span><p class="wt-pick__title">副業の帳簿づけ、最初の 1 か月でやること</p><span class="wt-pick__date">2026.08.15</span></a>
<a class="wt-pick__item" href="#"><img src="<?php echo esc_url( $u ); ?>/media-pickup-4.jpg" alt=""><span class="wt-pick__cat">働き方</span><p class="wt-pick__title">在宅ワークの机まわり、3 万円で整える</p><span class="wt-pick__date">2026.08.12</span></a>
<a class="wt-pick__item" href="#"><img src="<?php echo esc_url( $u ); ?>/media-pickup-5.jpg" alt=""><span class="wt-pick__cat">学び</span><p class="wt-pick__title">オンライン講座の選び方。続いた人が最初にやったこと</p><span class="wt-pick__date">2026.08.10</span></a>
</div>
<!-- /wp:html -->
</div><!-- /wp:group -->
<!-- wp:group {"className":"wt-section wt-tabs","align":"full","layout":{"type":"constrained","wideSize":"1120px"},"style":{"spacing":{"padding":{"top":"0"}}}} -->
<div class="wp-block-group alignfull wt-section wt-tabs" style="padding-top:0">
<!-- wp:tabs --><div class="wp-block-tabs">
<!-- wp:tab {"label":"新着記事"} --><div class="wp-block-tab"><!-- wp:html -->
<div class="wt-pick">
<a class="wt-pick__item" href="#"><img src="<?php echo esc_url( $u ); ?>/media-pickup-6.jpg" alt=""><span class="wt-pick__cat">開業</span><p class="wt-pick__title">小さなお店の予約を Web に移す。初月にやった 5 つのこと</p><span class="wt-pick__date">2026.08.22</span></a>
<a class="wt-pick__item" href="#"><img src="<?php echo esc_url( $u ); ?>/media-pickup-2.jpg" alt=""><span class="wt-pick__cat">Web ライター</span><p class="wt-pick__title">初案件の単価交渉、実際に送った文面</p><span class="wt-pick__date">2026.08.21</span></a>
<a class="wt-pick__item" href="#"><img src="<?php echo esc_url( $u ); ?>/media-pickup-4.jpg" alt=""><span class="wt-pick__cat">働き方</span><p class="wt-pick__title">週 3 リモートに切り替えて変わった時間の使い方</p><span class="wt-pick__date">2026.08.19</span></a>
</div>
<!-- /wp:html --></div><!-- /wp:tab -->
<!-- wp:tab {"label":"人気記事"} --><div class="wp-block-tab"><!-- wp:html -->
<ol class="wt-rank">
<li><img src="<?php echo esc_url( $u ); ?>/media-pickup-1.jpg" alt=""><a href="#">動画編集者とは？副業はおすすめなのか、月 5 万円稼ぐための方法</a></li>
<li><img src="<?php echo esc_url( $u ); ?>/media-pickup-3.jpg" alt=""><a href="#">副業の帳簿づけ、最初の 1 か月でやること</a></li>
<li><img src="<?php echo esc_url( $u ); ?>/media-pickup-5.jpg" alt=""><a href="#">オンライン講座の選び方。続いた人が最初にやったこと</a></li>
<li><img src="<?php echo esc_url( $u ); ?>/media-pickup-6.jpg" alt=""><a href="#">小さなお店の予約を Web に移す</a></li>
<li><img src="<?php echo esc_url( $u ); ?>/media-pickup-2.jpg" alt=""><a href="#">Web ライターに向いている人の特徴 7 選</a></li>
</ol>
<!-- /wp:html --></div><!-- /wp:tab -->
</div><!-- /wp:tabs -->
</div><!-- /wp:group -->
<!-- wp:group {"className":"wt-section","align":"full","backgroundColor":"surface","layout":{"type":"constrained","wideSize":"1120px"}} -->
<div class="wp-block-group alignfull wt-section has-surface-background-color has-background">
<!-- wp:heading {"textAlign":"center","style":{"spacing":{"margin":{"top":"0","bottom":"1.25rem"}}}} --><h2 class="wp-block-heading has-text-align-center" style="margin-top:0;margin-bottom:1.25rem">カテゴリから探す</h2><!-- /wp:heading -->
<!-- wp:html -->
<div class="wt-cats alignwide">
<a href="#"><i class="wt-i wt-i--l wt-i--edit"></i>Web ライター</a><a href="#"><i class="wt-i wt-i--l wt-i--chart"></i>動画編集</a><a href="#"><i class="wt-i wt-i--l wt-i--file"></i>経理・確定申告</a><a href="#"><i class="wt-i wt-i--l wt-i--home"></i>在宅ワーク</a>
<a href="#"><i class="wt-i wt-i--l wt-i--lightbulb"></i>学び直し</a><a href="#"><i class="wt-i wt-i--l wt-i--cart"></i>開業・EC</a><a href="#"><i class="wt-i wt-i--l wt-i--trend"></i>副業の始め方</a><a href="#"><i class="wt-i wt-i--l wt-i--user"></i>体験談</a>
</div>
<!-- /wp:html -->
</div><!-- /wp:group -->
<!-- wp:group {"className":"wt-section","align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull wt-section">
<!-- wp:html -->
<div class="wt-profile"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt=""><div><p><strong>編集部 田中</strong> <span class="wt-badge">運営者</span></p><p>会社員を続けながら副業で月 20 万円。経理と Web の実務を 8 年やってきた経験から、始め方と続け方を書いています。</p><p><a href="#">プロフィールを見る <i class="wt-i wt-i--s wt-i--arrow-right"></i></a></p></div></div>
<!-- /wp:html -->
</div><!-- /wp:group -->
