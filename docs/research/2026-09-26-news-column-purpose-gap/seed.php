<?php
/**
 * Seed a synthetic, idempotent article-purpose fixture in the isolated local WP instance.
 * Run with: docker exec -i <wordpress-container> php < seed.php
 */

declare(strict_types=1);

$base_url = getenv( 'HELIX_ARTICLE_BASE_URL' ) ?: 'http://127.0.0.1:18112';
$_SERVER['HTTP_HOST']       = (string) parse_url( $base_url, PHP_URL_HOST );
$_SERVER['REQUEST_URI']     = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['SERVER_PORT']     = '80';
define( 'WP_INSTALLING', true );
require '/var/www/html/wp-load.php';
add_filter(
	'pre_wp_mail',
	static function () {
		return true;
	}
);

if (! is_blog_installed()) {
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	wp_install(
		'HELIX Article Purpose PoC',
		'local-review',
		'article-purpose@example.invalid',
		0,
		'',
		bin2hex(random_bytes(24))
	);
}

update_option( 'home', $base_url );
update_option( 'siteurl', $base_url );
update_option( 'blog_public', 0 );
update_option( 'permalink_structure', '/%category%/%postname%/' );
switch_theme( 'helix-wt' );
set_theme_mod( 'wt_cat_filter', 'year' );
remove_theme_mod( 'cat_filter' );

/**
 * @return int
 */
function helix_article_ensure_category( string $name, string $slug, int $parent = 0 ): int {
	$existing = get_term_by( 'slug', $slug, 'category' );
	if ( $existing instanceof WP_Term ) {
		return (int) $existing->term_id;
	}

	$created = wp_insert_term(
		$name,
		'category',
		array(
			'slug'   => $slug,
			'parent' => $parent,
		)
	);
	if ( is_wp_error( $created ) ) {
		throw new RuntimeException( $created->get_error_message() );
	}

	return (int) $created['term_id'];
}

$news_id       = helix_article_ensure_category( 'ニュース・発表', 'news-releases' );
$news_product  = helix_article_ensure_category( '製品発表', 'product-news', $news_id );
$news_notice   = helix_article_ensure_category( 'お知らせ', 'announcements', $news_id );
$search_id     = helix_article_ensure_category( '検索流入', 'search-intent' );
$search_guide  = helix_article_ensure_category( '導入ガイド', 'guides', $search_id );
$column_id     = helix_article_ensure_category( 'コラム', 'columns' );
$column_people = helix_article_ensure_category( '組織づくり', 'organization', $column_id );
$column_data   = helix_article_ensure_category( 'データ活用', 'data', $column_id );
wp_update_term(
	$news_id,
	'category',
	array( 'description' => '新製品の発表と運営からのお知らせを、日付・分類から探せる一覧です。' )
);
wp_update_term(
	$column_id,
	'category',
	array( 'description' => '実務の学びと考え方をテーマ別に紹介するコラムです。' )
);
wp_update_term(
	$search_id,
	'category',
	array( 'description' => '検索で訪れた読者の疑問を解決する解説記事です。' )
);

$fixtures = array(
	array(
		'slug'        => 'search-guide-sample-2026',
		'title'       => '架空導入ガイドの記事例（2026）',
		'date'        => '2026-06-18 09:00:00',
		'categories'  => array( $search_id, $search_guide ),
		'content'     => 'これは検索で訪れた読者向けの記事目的分類を確認する架空ガイドです。',
	),
	array(
		'slug'        => 'product-launch-sample-2026',
		'title'       => '架空製品の発表例（2026）',
		'date'        => '2026-09-22 09:00:00',
		'categories'  => array( $news_id, $news_product ),
		'content'     => 'これは一覧と詳細表示を確認するための架空の製品発表です。',
	),
	array(
		'slug'        => 'service-notice-sample-2025',
		'title'       => '架空サービスのお知らせ例（2025）',
		'date'        => '2025-12-15 09:00:00',
		'categories'  => array( $news_id, $news_notice ),
		'content'     => 'これは日付絞り込みと詳細表示を確認するための架空のお知らせです。',
	),
	array(
		'slug'        => 'organization-column-sample-2026',
		'title'       => '架空組織づくりコラム（2026）',
		'date'        => '2026-08-03 09:00:00',
		'categories'  => array( $column_id, $column_people ),
		'content'     => 'これはコラム目的とテーマ導線を確認するための架空記事です。',
	),
	array(
		'slug'        => 'data-column-sample-2025',
		'title'       => '架空データ活用コラム（2025）',
		'date'        => '2025-10-02 09:00:00',
		'categories'  => array( $column_id, $column_data ),
		'content'     => 'これは年別一覧とテーマ導線を確認するための架空記事です。',
	),
);

$seeded = array();
foreach ( $fixtures as $fixture ) {
	$existing = get_page_by_path( $fixture['slug'], OBJECT, 'post' );
	$post     = array(
		'post_type'     => 'post',
		'post_status'   => 'publish',
		'post_name'     => $fixture['slug'],
		'post_title'    => $fixture['title'],
		'post_content'  => $fixture['content'],
		'post_date'     => $fixture['date'],
		'post_category' => $fixture['categories'],
	);
	if ( $existing instanceof WP_Post ) {
		$post['ID'] = $existing->ID;
		$post_id    = wp_update_post( $post, true );
	} else {
		$post_id = wp_insert_post( $post, true );
	}
	if ( is_wp_error( $post_id ) ) {
		throw new RuntimeException( $post_id->get_error_message() );
	}
	$seeded[] = (int) $post_id;
}

// Remove the default sample post so global sidebar widgets cannot suggest that it is fixture content.
$hello_world = get_page_by_path( 'hello-world', OBJECT, 'post' );
if ( $hello_world instanceof WP_Post ) {
	wp_delete_post( $hello_world->ID, true );
}

flush_rewrite_rules( false );

echo wp_json_encode(
	array(
		'wordpress' => get_bloginfo( 'version' ),
		'theme'     => wp_get_theme()->get_stylesheet(),
		'post_type' => 'post',
		'post_ids'  => $seeded,
		'categories' => array(
			'news'    => $news_id,
			'columns' => $column_id,
		),
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
) . PHP_EOL;
