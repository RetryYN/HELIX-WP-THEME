<?php
require_once '/var/www/html/wp-load.php';

$action = getenv('LOADMORE_PROBE_ACTION') ?: 'inspect';
$owner = getenv('LOADMORE_PROBE_OWNER') ?: '';
$term_slug = getenv('LOADMORE_PROBE_TERM_SLUG') ?: '';
$post_slugs = json_decode(getenv('LOADMORE_PROBE_POST_SLUGS') ?: '[]', true);
$source_files = json_decode(getenv('LOADMORE_PROBE_SOURCE_FILES') ?: '[]', true);
$same_date_mode = '1' === getenv('LOADMORE_PROBE_SAME_DATE');
$marker = 'Helix local load-more INP probe ' . $owner;
$title_for = static function ($index) use ($owner) { return 'Local performance fixture ' . substr($owner, 0, 10) . ' ' . $index; };
$query_pages = static function ($term_slug_value, $page_size, $orderby) {
	$pages = [];
	for ($page = 1; $page <= 2; $page++) {
		$args = ['category_name' => $term_slug_value, 'posts_per_page' => $page_size, 'paged' => $page];
		if (null !== $orderby) { $args['orderby'] = $orderby; }
		$query = new WP_Query($args);
		$pages[] = ['page' => $page, 'ids' => array_map('intval', wp_list_pluck($query->posts, 'ID')),
			'request' => $query->request, 'orderby' => $query->get('orderby'), 'order' => $query->get('order'),
			'postsPerPage' => (int) $query->get('posts_per_page'), 'foundPosts' => (int) $query->found_posts];
		wp_reset_postdata();
	}
	return $pages;
};

$respond = static function ($payload) { echo wp_json_encode($payload), "\n"; };

$exists = static function () use ($term_slug, $post_slugs) {
	$term = '' !== $term_slug ? get_term_by('slug', $term_slug, 'category') : false;
	$posts = [];
	foreach ($post_slugs as $slug) {
		$post = get_page_by_path($slug, OBJECT, 'post');
		if ($post) { $posts[] = ['id' => (int) $post->ID, 'slug' => $slug]; }
	}
	return ['termExists' => (bool) $term, 'posts' => $posts];
};

if ('inspect' === $action) {
	$hashes = [];
	foreach ($source_files as $relative) {
		$path = trailingslashit(get_template_directory()) . ltrim($relative, '/');
		$hashes[$relative] = is_file($path) ? hash_file('sha256', $path) : null;
	}
	$respond([
		'action' => 'inspect', 'wordpressVersion' => get_bloginfo('version'), 'phpVersion' => PHP_VERSION,
		'theme' => get_stylesheet(), 'themeDirectory' => get_template_directory(),
		'postsPerPage' => (int) get_option('posts_per_page'), 'sourceHashes' => $hashes,
		'existing' => $exists(),
	]);
	exit(0);
}

if ('create' === $action) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$term_created = false;
	$term_id = 0;
	$created_posts = [];
	$errors = [];
	$featured_image_id = 0;
	if (get_term_by('slug', $term_slug, 'category')) { $errors[] = 'unique fixture term slug already exists'; }
	foreach ($post_slugs as $slug) { if (get_page_by_path($slug, OBJECT, 'post')) { $errors[] = 'unique fixture post slug already exists'; } }
	if (!$errors) {
		$inserted = wp_insert_term('Temporary local INP fixture', 'category', ['slug' => $term_slug, 'description' => $marker]);
		if (is_wp_error($inserted)) { $errors[] = $inserted->get_error_message(); }
		else {
			$term_created = true;
			$term_id = (int) $inserted['term_id'];
			if (!add_term_meta($term_id, '_helix_loadmore_probe_owner', $owner, true)) { $errors[] = 'term owner metadata could not be recorded'; }
		}
	}
	if (!$errors) {
		$image_path = trailingslashit(get_template_directory()) . 'assets/img/case-factory.jpg';
		$tmp_path = wp_tempnam(basename($image_path));
		if (!$tmp_path || !copy($image_path, $tmp_path)) { $errors[] = 'bundled featured image copy failed'; }
		else {
			$featured_image_id = media_handle_sideload(['name' => 'loadmore-performance-fixture.jpg', 'tmp_name' => $tmp_path], 0,
				'Temporary load-more card image');
			if (is_wp_error($featured_image_id)) { @unlink($tmp_path); $errors[] = $featured_image_id->get_error_message(); $featured_image_id = 0; }
			else {
				$featured_image_id = (int) $featured_image_id;
				if (!add_post_meta($featured_image_id, '_helix_loadmore_probe_owner', $owner, true)
					|| !hash_equals($owner, (string) get_post_meta($featured_image_id, '_helix_loadmore_probe_owner', true))) {
					wp_delete_attachment($featured_image_id, true);
					$featured_image_id = 0;
					$errors[] = 'featured image owner metadata could not be verified';
				}
			}
		}
	}
	if (!$errors) {
		$same_gmt_date = $same_date_mode ? current_time('mysql', true) : null;
		$same_local_date = $same_gmt_date ? get_date_from_gmt($same_gmt_date) : null;
		foreach ($post_slugs as $index => $slug) {
			$title = $title_for($index + 1);
			$post_args = [
				'post_type' => 'post', 'post_status' => 'publish', 'post_title' => $title,
				'post_name' => $slug, 'post_content' => '<p>Temporary local performance fixture.</p>',
				'post_excerpt' => '', 'comment_status' => 'closed', 'ping_status' => 'closed',
			];
			if ($same_date_mode) { $post_args['post_date'] = $same_local_date; $post_args['post_date_gmt'] = $same_gmt_date; }
			$post_id = wp_insert_post($post_args, true);
			if (is_wp_error($post_id)) { $errors[] = $post_id->get_error_message(); break; }
			$post_id = (int) $post_id;
			$created_posts[] = ['id' => $post_id, 'slug' => $slug, 'url' => get_permalink($post_id)];
			if (!add_post_meta($post_id, '_helix_loadmore_probe_owner', $owner, true)) {
				$errors[] = 'post owner metadata could not be recorded for ' . $slug;
				break;
			}
			$assigned = wp_set_post_terms($post_id, [$term_id], 'category', false);
			if (is_wp_error($assigned)) { $errors[] = $assigned->get_error_message(); break; }
			if (!$featured_image_id || !set_post_thumbnail($post_id, $featured_image_id)) { $errors[] = 'featured image binding failed'; break; }
		}
	}
	$respond(['action' => 'create', 'ok' => !$errors, 'termId' => $term_id,
		'termUrl' => $term_id ? get_term_link($term_id, 'category') : null,
		'postIds' => $created_posts, 'expectedPosts' => count($post_slugs), 'postsPerPage' => (int) get_option('posts_per_page'),
		'sameDateMode' => $same_date_mode, 'sameFixturePostDateUtc' => $same_gmt_date ?? null,
		'postDates' => array_map(static function ($item) {
			$post = get_post($item['id']);
			return ['id' => (int) $post->ID, 'post_date' => $post->post_date, 'post_date_gmt' => $post->post_date_gmt];
		}, $created_posts),
		'dateOnlyPages' => $term_id ? $query_pages($term_slug, (int) get_option('posts_per_page'), null) : [],
		'dateThenIdPages' => $term_id ? $query_pages($term_slug, (int) get_option('posts_per_page'), ['date' => 'DESC', 'ID' => 'DESC']) : [],
		'featuredImageId' => (int) $featured_image_id,
		'featuredImageOwnerVerified' => $featured_image_id && hash_equals($owner,
			(string) get_post_meta((int) $featured_image_id, '_helix_loadmore_probe_owner', true)),
		'featuredImagePostCount' => count(array_filter($created_posts,
			static function ($item) use ($featured_image_id) { return (int) get_post_thumbnail_id((int) $item['id']) === $featured_image_id; })),
		'errors' => $errors]);
	exit(0);
}

if ('delete' === $action) {
	$deleted = [];
	$notOwned = [];
	foreach ($post_slugs as $index => $slug) {
		$post = get_page_by_path($slug, OBJECT, 'post');
		if (!$post) { continue; }
		$owned = hash_equals($owner, (string) get_post_meta($post->ID, '_helix_loadmore_probe_owner', true))
			|| hash_equals($title_for($index + 1), (string) $post->post_title);
		if (!$owned) { $notOwned[] = $slug; continue; }
		wp_delete_post($post->ID, true);
		$deleted[] = ['id' => (int) $post->ID, 'slug' => $slug];
	}
	$deleted_images = [];
	$image_ids = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => -1,
		'fields' => 'ids', 'meta_key' => '_helix_loadmore_probe_owner', 'meta_value' => $owner]);
	foreach ($image_ids as $image_id) {
		if (hash_equals($owner, (string) get_post_meta((int) $image_id, '_helix_loadmore_probe_owner', true))) {
			wp_delete_attachment((int) $image_id, true);
			$deleted_images[] = (int) $image_id;
		}
	}
	$term = get_term_by('slug', $term_slug, 'category');
	$term_deleted = false;
	if ($term && !is_wp_error($term)) {
		$owned_term = hash_equals($owner, (string) get_term_meta($term->term_id, '_helix_loadmore_probe_owner', true))
			|| hash_equals($marker, (string) $term->description);
		$remaining_objects = get_objects_in_term((int) $term->term_id, 'category');
		$remaining_objects = is_wp_error($remaining_objects) ? ['term relationship lookup failed'] : $remaining_objects;
		if ($owned_term && !$remaining_objects) {
			delete_term_meta($term->term_id, '_helix_loadmore_probe_owner');
			wp_delete_term($term->term_id, 'category');
			$term_deleted = true;
		} elseif (!$owned_term || $remaining_objects) { $notOwned[] = 'term:' . $term_slug; }
	}
	$after = $exists();
	$remaining_images = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => -1,
		'fields' => 'ids', 'meta_key' => '_helix_loadmore_probe_owner', 'meta_value' => $owner]);
	$respond(['action' => 'delete', 'deletedPosts' => $deleted, 'notOwned' => $notOwned,
		'termDeleted' => $term_deleted, 'termAbsent' => !$after['termExists'], 'fixturePostsAbsent' => !$after['posts'],
		'postsRemaining' => $after['posts'], 'deletedImages' => $deleted_images, 'imagesAbsent' => empty($remaining_images)]);
	exit(0);
}

$respond(['action' => $action, 'ok' => false, 'error' => 'unknown action']);
exit(0);
