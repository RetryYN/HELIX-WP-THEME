<?php
/** Learning pages: hierarchy, learning sequence and in-page contents are distinct navigations. */
defined( 'ABSPATH' ) || exit;
function wtcf_learning_search_form( $value = '' ) {
	return '<form class="wtlearn-search" role="search" method="get" action="' . esc_url( get_post_type_archive_link( 'wt_learning' ) ) . '"><label for="learn-query">学習・ヘルプを検索</label><div><input id="learn-query" name="learn_q" type="search" maxlength="200" value="' . esc_attr( $value ) . '" placeholder="知りたいことを入力"><button type="submit">検索</button></div></form>';
}
function wtcf_render_learning() {
	if ( is_singular() && post_password_required() ) { return get_the_password_form(); }
	if ( ! function_exists( 'wtcf_learning_search' ) ) { return '<p>学習データを取得できません。</p>'; }
	$archive = get_post_type_archive_link( 'wt_learning' );
	$design = wtcf_choice( 'design', 'editorial', wtcf_manifest()['designs'] );
	$kinds = array( 'course' => '学習ガイド', 'lesson' => 'レッスン', 'glossary' => '用語集', 'help' => 'ヘルプ' );
	$data = is_singular() ? wtcf_learning_display( get_the_ID() ) : null;
	ob_start(); ?>
	<div class="wtcf wtcf--<?php echo esc_attr( $design ); ?> wtlearn">
	<a class="wtlearn-skip" href="#learning-main">本文へ移動</a>
	<header class="wtcf-header"><a class="wtcf-brand" href="<?php echo esc_url( $archive ); ?>">HELIX<span>LEARNING CENTER</span></a><nav aria-label="学習の入口"><?php echo wtcf_link( $archive, '学習・ヘルプ一覧' ); ?></nav></header>
	<?php if ( $data ) : ?>
	<nav class="wtlearn-breadcrumbs" aria-label="階層"><ol><li><?php echo wtcf_link( $archive, '学習・ヘルプ' ); ?></li><?php foreach ( $data['ancestors'] as $ancestor ) { echo '<li>' . wtcf_link( $ancestor['url'], $ancestor['title'] ) . '</li>'; } ?><li aria-current="page"><?php echo esc_html( $data['title'] ); ?></li></ol></nav>
	<?php endif; ?>
	<main id="learning-main" class="wtcf-main" tabindex="-1">
	<?php if ( ! $data ) : $results = wtcf_learning_search(); ?>
	<header class="wtcf-intro"><p class="wtcf-kicker">LEARN / FIND / TRY</p><h1>知りたいことから、<br>できることへ。</h1><p class="wtcf-lead">はじめての学習から、作業中の疑問まで。ひとつずつ確かめながら進めましょう。</p></header>
	<?php echo wtcf_learning_search_form( $results['query'] ); ?>
	<div class="wtlearn-results"><h2><?php echo $results['query'] !== '' ? '「' . esc_html( $results['query'] ) . '」の検索結果' : '学習・ヘルプ一覧'; ?></h2><p><?php echo (int) $results['count']; ?>件</p></div>
	<?php if ( ! $results['items'] ) : ?><section class="wtlearn-empty"><h2>一致する内容が見つかりませんでした</h2><p>短い言葉で検索するか、一覧からテーマを選んでください。</p><?php echo wtcf_link( $archive, '一覧へ戻る' ); ?></section><?php endif; ?>
	<div class="wtcf-list"><?php foreach ( $results['items'] as $item ) : ?><article><h2><?php echo wtcf_link( $item['url'], $item['title'] ); ?></h2><p><?php echo esc_html( $item['summary'] ); ?></p></article><?php endforeach; ?></div>
	<?php if ( $results['adjusted'] && $results['pages'] ) : ?><p class="wtlearn-page-adjustment">指定されたページがないため、最後のページを表示しています。</p><?php endif; ?>
	<nav class="wtlearn-pagination" aria-label="一覧のページ送り"><?php
	if ( $results['pages'] ) { echo '<span aria-current="page">' . (int) $results['page'] . ' / ' . (int) $results['pages'] . ' ページ</span>'; }
	if ( $results['page'] > 1 ) { echo wtcf_link( add_query_arg( array( 'learn_q' => $results['query'], 'learn_page' => $results['page'] - 1 ), $archive ), '前のページ' ); }
	if ( $results['page'] < $results['pages'] ) { echo wtcf_link( add_query_arg( array( 'learn_q' => $results['query'], 'learn_page' => $results['page'] + 1 ), $archive ), '次のページ' ); }
	?></nav>
	<?php else : ?>
	<div class="wtlearn-layout"><aside class="wtlearn-sidebar"><?php echo wtcf_learning_search_form(); ?>
	<?php if ( $data['lessons'] ) : ?><nav aria-label="講座のレッスン"><h2>学ぶ順番（<?php echo count( $data['lessons'] ); ?>レッスン）</h2><ol><?php foreach ( $data['lessons'] as $lesson ) { echo '<li><a href="' . esc_url( $lesson['url'] ) . '"' . ( $lesson['id'] === $data['id'] ? ' aria-current="page"' : '' ) . '>' . esc_html( $lesson['title'] ) . '</a></li>'; } ?></ol></nav><?php endif; ?>
	</aside><article class="wtlearn-article"><header class="wtcf-intro"><p class="wtcf-kicker"><?php echo esc_html( $kinds[ $data['kind'] ] ); ?></p><h1><?php echo esc_html( $data['title'] ); ?></h1><p class="wtcf-lead"><?php echo esc_html( $data['summary'] ); ?></p></header>
	<?php if ( $data['sections'] ) : ?><nav class="wtlearn-toc" aria-label="このページの目次"><h2><?php echo $data['kind'] === 'glossary' ? '用語を選ぶ' : 'このページで分かること'; ?></h2><ol><?php foreach ( $data['sections'] as $i => $section ) { echo '<li>' . wtcf_link( '#learn-section-' . $i, $section['title'] ) . '</li>'; } ?></ol></nav><?php endif; ?>
	<div class="wtcf-prose"><?php echo wp_kses_post( do_blocks( serialize_blocks( $data['intro_blocks'] ) ) ); ?>
	<?php foreach ( $data['sections'] as $i => $section ) : ?>
	<section id="learn-section-<?php echo (int) $i; ?>" class="wtlearn-section" tabindex="-1">
	<?php if ( $data['kind'] === 'help' ) : ?><details><summary><?php echo esc_html( $section['title'] ); ?></summary><?php echo wp_kses_post( do_blocks( serialize_blocks( $section['blocks'] ) ) ); ?></details>
	<?php else : ?><h2><?php echo esc_html( $section['title'] ); ?></h2><?php echo wp_kses_post( do_blocks( serialize_blocks( $section['blocks'] ) ) ); ?><?php endif; ?>
	</section><?php endforeach; ?></div>
	<nav class="wtlearn-pagination" aria-label="前後のレッスン"><?php
	if ( $data['previous'] ) { echo wtcf_link( $data['previous']['url'], '← ' . $data['previous']['title'], 'wtlearn-previous' ); }
	if ( $data['next'] ) { echo wtcf_link( $data['next']['url'], $data['next']['title'] . ' →', 'wtlearn-next' ); }
	?></nav></article></div>
	<?php endif; ?>
	</main><footer class="wtcf-footer"><p>HELIX / 一歩ずつ、理解を深める。</p><?php echo wtcf_link( $archive, '学習・ヘルプへ戻る' ); ?></footer></div>
	<?php return ob_get_clean();
}
add_action( 'init', function () { register_block_type( 'helix-wt/learning', array( 'render_callback' => 'wtcf_render_learning' ) ); } );
