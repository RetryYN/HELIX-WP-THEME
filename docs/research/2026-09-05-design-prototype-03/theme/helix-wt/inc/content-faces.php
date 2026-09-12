<?php
/** Content-face rendering. Entitlement and publication decisions remain in the companion plugin. */
defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', function () {
	if ( is_singular( 'wt_lp' ) ) { wp_enqueue_script( 'helix-wt-form', get_theme_file_uri( 'assets/js/form.js' ), array(), '0.3.21', true ); }
	if ( is_singular( array( 'wt_paid', 'wt_interview', 'wt_blp', 'wt_lp', 'wt_learning' ) ) || is_post_type_archive( array( 'wt_paid', 'wt_interview', 'wt_blp', 'wt_lp', 'wt_learning' ) ) ) {
		wp_enqueue_style( 'wt-content-faces', get_theme_file_uri( 'assets/css/content-faces.css' ), array( 'helix-wt' ), '0.1.0' );
	}
} );

function wtcf_choice( $key, $fallback, $allowed ) {
	$value = isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ? sanitize_key( wp_unslash( $_GET[ $key ] ) ) : $fallback;
	return in_array( $value, $allowed, true ) ? $value : $fallback;
}
function wtcf_link( $url, $label, $class = '' ) {
	return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
}

function wtcf_render_face() {
	if ( is_singular() && post_password_required() ) { return get_the_password_form(); }
	if ( ! function_exists( 'wtcf_display' ) ) { return '<p>' . esc_html__( 'コンテンツの表示サービスに接続できません。', 'helix-wt' ) . '</p>'; }
	$manifest = wtcf_manifest();
	$design = wtcf_choice( 'design', 'editorial', $manifest['designs'] );
	$owner = wtcf_choice( 'ownership', 'inherit', $manifest['ownership'] );
	ob_start();
	echo wtcf_shared_part( 'header' );
	?>
	<div class="wtcf wtcf--<?php echo esc_attr( $design ); ?>" data-ownership="<?php echo esc_attr( $owner ); ?>">
	<?php if ( ! wtcf_shared_chrome() && $owner !== 'off' ) : ?>
	<header class="wtcf-header"><a class="wtcf-brand" href="<?php echo esc_url( get_post_type_archive_link( 'wt_paid' ) ); ?>">HELIX <span><?php echo $owner === 'own' ? 'FIELD NOTES' : 'JOURNAL & IDEAS'; ?></span></a><nav aria-label="<?php echo esc_attr__( 'コンテンツ案内', 'helix-wt' ); ?>">
	<?php echo wtcf_link( get_post_type_archive_link( 'wt_paid' ), __( '読む', 'helix-wt' ) ) . wtcf_link( get_post_type_archive_link( 'wt_interview' ), __( '人を知る', 'helix-wt' ) ) . wtcf_link( get_post_type_archive_link( 'wt_blp' ), __( '考える', 'helix-wt' ) ); ?>
	</nav></header>
	<?php endif; ?>
	<?php echo wtcf_shared_layout_start(); ?><main id="content" class="wtcf-main">
	<?php if ( is_post_type_archive() ) : ?>
		<header class="wtcf-intro"><p class="wtcf-kicker">THE COLLECTION</p><h1><?php post_type_archive_title(); ?></h1><p><?php esc_html_e( '考え方を、次の一歩へ。テーマを選んでじっくり読む。', 'helix-wt' ); ?></p></header>
		<div class="wtcf-list">
		<?php while ( have_posts() ) : the_post(); $item = wtcf_display( get_the_ID() );
		if ( ! $item ) { $item = array( 'type' => get_post_type(), 'title' => get_the_title(), 'url' => get_permalink(), 'summary' => __( '閲覧にはパスワードが必要です。', 'helix-wt' ) ); } ?>
		<article><p class="wtcf-kicker"><?php echo esc_html( $manifest['types'][ $item['type'] ]['label'] ); ?></p><h2><?php echo wtcf_link( $item['url'], $item['title'] ); ?></h2><p><?php echo esc_html( $item['summary'] ); ?></p><?php if ( isset( $item['price'] ) ) { echo '<p>' . esc_html( $item['price'] ) . '</p>'; } ?></article>
		<?php endwhile; ?>
		</div><?php the_posts_pagination(); ?>
	<?php else : $item = wtcf_display( get_the_ID() ); if ( $item ) : ?>
		<header class="wtcf-intro"><p class="wtcf-kicker"><?php echo esc_html( $manifest['types'][ $item['type'] ]['label'] ); ?> / 01</p><h1><?php echo esc_html( $item['title'] ); ?></h1><p class="wtcf-lead"><?php echo esc_html( $item['summary'] ); ?></p></header>
		<?php if ( $item['type'] === 'wt_paid' ) : $view = wtcf_choice( 'view', 'preview', $manifest['views'] ); ?>
		<div class="wtcf-reading-layout"><article class="wtcf-prose">
		<p class="wtcf-byline"><?php esc_html_e( 'HELIX 編集室 · 2026年9月8日 · 約8分', 'helix-wt' ); ?></p>
		<nav class="wtcf-view-nav" aria-label="<?php echo esc_attr__( '記事の表示用途', 'helix-wt' ); ?>"><?php echo wtcf_link( add_query_arg( 'view', 'sales' ), __( '内容・購入案内', 'helix-wt' ) ) . wtcf_link( add_query_arg( 'view', 'preview' ), __( '試し読み', 'helix-wt' ) ) . wtcf_link( add_query_arg( 'view', 'body' ), __( '本文を読む', 'helix-wt' ) ); ?></nav>
		<?php if ( $view === 'sales' ) : ?>
		<h2><?php esc_html_e( 'この記事で持ち帰れること', 'helix-wt' ); ?></h2><ol><?php foreach ( $item['chapters'] as $chapter ) { echo '<li>' . esc_html( $chapter ) . '</li>'; } ?></ol>
		<?php else : echo wp_kses_post( $item['content'] ); endif; ?>
		<?php if ( $item['granted'] && $view !== 'sales' ) : ?>
		<section class="wtcf-fulltext" data-access="granted"><p class="wtcf-access"><?php esc_html_e( '閲覧権限を確認しました', 'helix-wt' ); ?></p><?php echo wp_kses_post( $item['body'] ); ?></section>
		<?php elseif ( ! $item['granted'] ) : ?>
		<section class="wtcf-paywall" data-access="preview"><p class="wtcf-kicker">CONTINUE READING</p><h2><?php esc_html_e( 'ここから先は、実践の手順へ。', 'helix-wt' ); ?></h2><p><?php esc_html_e( '全文では判断の手順と、現場で使える確認項目を紹介します。', 'helix-wt' ); ?></p><p><?php esc_html_e( 'ログイン済みでも、この記事の閲覧権限が必要です。', 'helix-wt' ); ?></p><?php echo wtcf_link( wp_login_url( get_permalink() ), __( 'ログインして権限を確認', 'helix-wt' ), 'wtcf-button' ); ?></section>
		<?php endif; ?>
		</article><aside class="wtcf-aside"><p class="wtcf-kicker">READING PLAN</p><h2><?php echo $item['billing'] === 'subscription' ? esc_html__( '定期購読で読む', 'helix-wt' ) : esc_html__( 'この記事を買い切りで読む', 'helix-wt' ); ?></h2><p class="wtcf-price"><?php echo esc_html( $item['price'] ); ?></p><p><?php echo $item['billing'] === 'subscription' ? esc_html__( '購読中の対象記事を閲覧するプラン。', 'helix-wt' ) : esc_html__( 'この1記事を選び、必要なときに読み返すプラン。', 'helix-wt' ); ?></p><details><summary><?php esc_html_e( '購入手続きについて', 'helix-wt' ); ?></summary><p><?php esc_html_e( 'この検証環境では決済しません。実際の購入は外部サービスとの接続後に行います。', 'helix-wt' ); ?></p></details><p><?php esc_html_e( '試し読みで内容を確認してから選べます。', 'helix-wt' ); ?></p></aside></div>
		<?php elseif ( $item['type'] === 'wt_interview' ) : ?>
		<div class="wtcf-reading-layout"><article class="wtcf-prose">
		<?php echo wp_kses_post( $item['content'] ); ?>
		<?php foreach ( $item['exchanges'] as $exchange ) : $person = $item['people'][ $exchange['speaker'] ] ?? null; if ( ! $person ) { continue; } ?>
		<section class="wtcf-exchange" data-speaker="<?php echo esc_attr( $exchange['speaker'] ); ?>"><h2><?php echo esc_html( $exchange['question'] ); ?></h2><div class="wtcf-answer"><span class="wtcf-avatar" aria-hidden="true"><?php echo esc_html( $person['mark'] ); ?></span><div><p class="wtcf-person"><?php echo esc_html( $person['name'] ); ?></p><p><?php echo esc_html( $exchange['answer'] ); ?></p></div></div></section>
		<?php endforeach; ?>
		<p class="wtcf-confirmed"><?php esc_html_e( '掲載内容は出演者確認済みです。人物・団体は検証用の架空設定です。', 'helix-wt' ); ?></p>
		</article><aside class="wtcf-aside"><p class="wtcf-kicker">PEOPLE</p><?php foreach ( $item['people'] as $id => $person ) { echo '<section data-person="' . esc_attr( $id ) . '"><h2>' . esc_html( $person['name'] ) . '</h2><p>' . esc_html( $person['affiliation'] ) . '</p><p>' . esc_html( $person['bio'] ) . '</p></section>'; } ?></aside></div>
		<?php else : ?>
		<div class="wtcf-prose wtcf-story">
		<?php echo wp_kses_post( $item['content'] ); ?>
		<?php foreach ( $item['sections'] as $i => $section ) : ?>
		<section class="wtcf-story-section"><p class="wtcf-kicker">POINT <?php echo (int) $i + 1; ?></p><h2><?php echo esc_html( $section['title'] ); ?></h2><p><?php echo esc_html( $section['text'] ); ?></p></section>
		<?php endforeach; ?>
		<?php if ( $item['type'] === 'wt_blp' && $item['target'] ) : ?>
		<section class="wtcf-next"><p class="wtcf-kicker">YOUR NEXT STEP</p><h2><?php esc_html_e( '自分たちの課題に当てはめてみる。', 'helix-wt' ); ?></h2><p><?php esc_html_e( '提供内容・進め方・申込条件は、サービス案内で確認できます。', 'helix-wt' ); ?></p><?php echo wtcf_link( $item['target'], __( 'サービス内容と相談の進め方を見る →', 'helix-wt' ), 'wtcf-button' ); ?></section>
		<?php elseif ( $item['type'] === 'wt_lp' ) : ?>
		<section class="wtcf-next" id="apply"><p class="wtcf-kicker">START A CONVERSATION</p><h2><?php esc_html_e( 'まずは、課題をひとつ共有する。', 'helix-wt' ); ?></h2><p><?php esc_html_e( '入力・確認の操作を試せます。相談内容は保存・送信されません。', 'helix-wt' ); ?></p><?php echo do_blocks( '<!-- wp:helix-wt/form /-->' ); ?></section>
		<?php endif; ?>
		</div>
		<?php endif; ?>
	<?php endif; endif; ?>
	</main><?php echo wtcf_shared_layout_end(); ?><?php if ( ! wtcf_shared_chrome() ) : ?><footer class="wtcf-footer"><p><?php esc_html_e( 'HELIX / 読むことから、動き出す。', 'helix-wt' ); ?></p><a href="#content"><?php esc_html_e( '本文の先頭へ戻る ↑', 'helix-wt' ); ?></a></footer><?php endif; ?></div><?php echo wtcf_shared_part( 'footer' ); ?>
	<?php return ob_get_clean();
}
add_action( 'init', function () { register_block_type( 'helix-wt/content-face', array( 'render_callback' => 'wtcf_render_face' ) ); } );

require_once __DIR__ . '/learning.php';

// The reference card is theme presentation; the plugin supplies records only.
add_shortcode( 'wtcf_interview_card', function ( $attributes ) {
	if ( ! function_exists( 'wtcf_display' ) ) { return ''; }
	$slug = sanitize_title( $attributes['slug'] ?? '' );
	$post = get_page_by_path( $slug, OBJECT, 'wt_interview' );
	if ( ! $post || $post->post_status !== 'publish' || post_password_required( $post ) ) { return ''; }
	$data = wtcf_display( $post->ID );
	if ( ! $data || ! $data['confirmed'] ) { return ''; }
	return '<aside class="wtcf-reference"><p>' . esc_html__( 'VOICE / 制作の現場から', 'helix-wt' ) . '</p><h2><a href="' . esc_url( $data['url'] ) . '">' . esc_html( $data['title'] ) . '</a></h2><p>' . esc_html( $data['summary'] ) . '</p></aside>';
} );

require_once __DIR__ . '/site-pages.php';
