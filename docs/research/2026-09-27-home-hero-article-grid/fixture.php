<?php
declare(strict_types=1);

require '/var/www/html/wp-load.php';

$action = getenv('ISSUE_377_ACTION') ?: 'inspect';
$owner = getenv('ISSUE_377_OWNER') ?: '';
$slug = getenv('ISSUE_377_SLUG') ?: '';
$sourceFiles = json_decode((string) getenv('ISSUE_377_SOURCE_FILES'), true);
$reply = static function (array $payload): void {
	echo wp_json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n";
};
$findPost = static function () use ($slug): ?WP_Post {
	$post = $slug !== '' ? get_page_by_path($slug, OBJECT, 'post') : null;
	return $post instanceof WP_Post ? $post : null;
};
$ownedImages = static function () use ($owner): array {
	return get_posts(array(
		'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => -1,
		'fields' => 'ids', 'meta_key' => '_helix_issue_377_owner', 'meta_value' => $owner,
	));
};
$themeRoot = get_stylesheet_directory();
$sourceHashes = array();
foreach (is_array($sourceFiles) ? $sourceFiles : array() as $relative) {
	$file = $themeRoot . '/' . ltrim((string) $relative, '/');
	$sourceHashes[(string) $relative] = is_file($file) ? hash_file('sha256', $file) : null;
}

if ('inspect' === $action) {
	$post = $findPost();
	$template = get_block_template(get_stylesheet() . '//front-page', 'wp_template');
	$reply(array(
		'action' => 'inspect', 'wordpressVersion' => get_bloginfo('version'), 'phpVersion' => PHP_VERSION,
		'theme' => get_stylesheet(), 'themeVersion' => wp_get_theme()->get('Version'),
		'homeUrl' => home_url('/'), 'homeHeroOption' => get_theme_mod('wt_home_hero', false),
		'templatePresent' => $template instanceof WP_Block_Template,
		'sourceHashes' => $sourceHashes,
		'slugAbsent' => !$post,
		'slugOwnerMatches' => !$post || ($owner !== '' && hash_equals($owner, (string) get_post_meta($post->ID, '_helix_issue_377_owner', true))),
		'ownedImages' => array_map('intval', $ownedImages()),
	));
	exit(0);
}

if ('create' === $action) {
	$errors = array();
	$postId = 0;
	$attachmentId = 0;
	$tmpPath = '';
	if (!preg_match('/^issue-377-image-[a-f0-9]{16}$/', $slug)
		|| !preg_match('/^[a-f0-9]{48}$/', $owner) || $findPost() || count($ownedImages()) > 0) {
		$errors[] = 'invalid or colliding fixture identity';
	}
	$imagePath = get_theme_file_path('assets/img/case-factory.jpg');
	if (!$errors && !is_file($imagePath)) { $errors[] = 'bundled fixture source image missing'; }
	if (!$errors) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$tmpPath = wp_tempnam(basename($imagePath));
		if (!$tmpPath || !copy($imagePath, $tmpPath)) { $errors[] = 'fixture image copy failed'; }
	}
	if (!$errors) {
		$attachmentId = media_handle_sideload(array('name' => 'issue-377-featured-image.jpg', 'tmp_name' => $tmpPath), 0,
			'Local article-grid image-loading fixture');
		$tmpPath = '';
		if (is_wp_error($attachmentId)) { $errors[] = 'fixture image attachment failed'; $attachmentId = 0; }
		else {
			$attachmentId = (int) $attachmentId;
			if (!add_post_meta($attachmentId, '_helix_issue_377_owner', $owner, true)
				|| !hash_equals($owner, (string) get_post_meta($attachmentId, '_helix_issue_377_owner', true))) {
				$errors[] = 'fixture image owner verification failed';
			}
		}
	}
	if (!$errors) {
		$postId = wp_insert_post(array(
			'post_type' => 'post', 'post_status' => 'publish', 'post_name' => $slug,
			'post_title' => 'Temporary featured-image loading fixture',
			'post_content' => '<!-- wp:paragraph --><p>Temporary owned fixture for local image-loading verification.</p><!-- /wp:paragraph -->',
		), true);
		if (is_wp_error($postId) || !$postId) { $errors[] = 'fixture post creation failed'; $postId = 0; }
		else {
			$postId = (int) $postId;
			if (!add_post_meta($postId, '_helix_issue_377_owner', $owner, true)
				|| !hash_equals($owner, (string) get_post_meta($postId, '_helix_issue_377_owner', true))) {
				$errors[] = 'fixture post owner verification failed';
			}
			if (!$attachmentId || !set_post_thumbnail($postId, $attachmentId)
				|| (int) get_post_thumbnail_id($postId) !== $attachmentId) {
				$errors[] = 'fixture featured image binding failed';
			}
		}
	}
	if ($tmpPath && is_file($tmpPath)) { @unlink($tmpPath); }
	if ($errors) {
		$post = $findPost();
		if ($post && hash_equals($owner, (string) get_post_meta($post->ID, '_helix_issue_377_owner', true))) { wp_delete_post($post->ID, true); }
		foreach ($ownedImages() as $imageId) { wp_delete_attachment((int) $imageId, true); }
		$reply(array('action' => 'create', 'created' => false, 'errors' => $errors));
		exit(0);
	}
	$homePosts = get_posts(array(
		'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 3,
		'orderby' => 'date', 'order' => 'DESC', 'ignore_sticky_posts' => true, 'suppress_filters' => false,
	));
	$reply(array(
		'action' => 'create', 'created' => true,
		'ownerVerified' => hash_equals($owner, (string) get_post_meta($postId, '_helix_issue_377_owner', true))
			&& hash_equals($owner, (string) get_post_meta($attachmentId, '_helix_issue_377_owner', true)),
		'permalink' => get_permalink($postId),
		'inHomeQuery' => in_array($postId, array_map('intval', wp_list_pluck($homePosts, 'ID')), true),
	));
	exit(0);
}

if ('delete' === $action) {
	$notOwned = array();
	$post = $findPost();
	if ($post) {
		if ($owner === '' || !hash_equals($owner, (string) get_post_meta($post->ID, '_helix_issue_377_owner', true))) {
			$notOwned[] = 'post';
		} else { wp_delete_post($post->ID, true); }
	}
	$deletedImages = array();
	foreach ($ownedImages() as $imageId) {
		if (hash_equals($owner, (string) get_post_meta((int) $imageId, '_helix_issue_377_owner', true))) {
			wp_delete_attachment((int) $imageId, true);
			$deletedImages[] = (int) $imageId;
		} else { $notOwned[] = 'image'; }
	}
	$reply(array(
		'action' => 'delete', 'postAbsent' => !$findPost(),
		'ownedImagesAbsent' => count($ownedImages()) === 0,
		'deletedImageCount' => count($deletedImages), 'notOwned' => $notOwned,
	));
	exit(0);
}

$reply(array('action' => $action, 'errors' => array('unknown action')));
exit(2);
