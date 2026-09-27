<?php
/** Temporary, owner-guarded probe fixture for the existing compare-article pattern. */
declare(strict_types=1);

require '/var/www/html/wp-load.php';

$action = getenv('COMPARE_PROBE_ACTION') ?: 'inspect';
$slug   = getenv('COMPARE_PROBE_SLUG') ?: '';
$owner  = getenv('COMPARE_PROBE_OWNER') ?: '';
$id     = (int) (getenv('COMPARE_PROBE_POST_ID') ?: 0);
$themeFile = get_theme_file_path('patterns/compare-article.php');
$patternHash = is_file($themeFile) ? hash_file('sha256', $themeFile) : null;
$registered = WP_Block_Patterns_Registry::get_instance()->is_registered('helix-wt/compare-article');

$reply = static function (array $value): void {
	echo wp_json_encode($value, JSON_UNESCAPED_SLASHES) . "\n";
};

$findBySlug = static function (string $name): array {
	return get_posts(
		array(
			'name'           => $name,
			'post_type'      => 'any',
			'post_status'    => 'any',
			'numberposts'    => 2,
			'fields'         => 'ids',
			'suppress_filters' => true,
		)
	);
};

if ( 'inspect' === $action ) {
	$reply(
		array(
			'action'          => 'inspect',
			'wordpressVersion' => get_bloginfo('version'),
			'phpVersion'       => PHP_VERSION,
			'theme'            => get_stylesheet(),
			'patternRegistered' => $registered,
			'patternSha256'    => $patternHash,
			'slugExists'       => '' !== $slug && ! empty($findBySlug($slug)),
		)
	);
	exit(0);
}

if ( 'create' === $action ) {
	$expectedHash = getenv('COMPARE_PROBE_EXPECTED_PATTERN_SHA256') ?: '';
	if ( ! preg_match('/^compare-pattern-probe-[a-f0-9]{16}$/', $slug) || '' === $owner ) {
		fwrite(STDERR, "invalid fixture identity\n");
		exit(2);
	}
	if ( ! $registered || ! hash_equals($expectedHash, (string) $patternHash) || ! empty($findBySlug($slug)) ) {
		fwrite(STDERR, "pattern precondition or unique slug check failed\n");
		exit(3);
	}
	$postId = wp_insert_post(
		array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_title'   => 'Temporary comparison-pattern probe',
			'post_content' => '<!-- wp:pattern {"slug":"helix-wt/compare-article"} /-->',
			'meta_input'   => array('_helix_compare_probe_owner' => $owner),
		),
		true
	);
	if ( is_wp_error($postId) || ! $postId || get_post_meta((int) $postId, '_helix_compare_probe_owner', true) !== $owner ) {
		$partial = ! is_wp_error($postId) && $postId ? get_post((int) $postId) : null;
		if ( $partial && 'post' === $partial->post_type && $slug === $partial->post_name
			&& '<!-- wp:pattern {"slug":"helix-wt/compare-article"} /-->' === $partial->post_content ) {
			wp_delete_post((int) $postId, true);
		}
		fwrite(STDERR, "fixture creation did not establish ownership\n");
		exit(4);
	}
	$reply(array('action' => 'create', 'postId' => (int) $postId, 'slug' => $slug, 'permalink' => get_permalink((int) $postId)));
	exit(0);
}

if ( 'delete' === $action ) {
	$post = $id ? get_post($id) : null;
	if ( ! $post && '' !== $slug && '' !== $owner ) {
		foreach ( $findBySlug($slug) as $candidateId ) {
			if ( get_post_meta((int) $candidateId, '_helix_compare_probe_owner', true) === $owner ) {
				$post = get_post((int) $candidateId);
				$id   = (int) $candidateId;
				break;
			}
		}
	}
	if ( $post ) {
		if ( 'post' !== $post->post_type || $slug !== $post->post_name || '' === $owner || get_post_meta($id, '_helix_compare_probe_owner', true) !== $owner ) {
			fwrite(STDERR, "fixture ownership guard refused deletion\n");
			exit(5);
		}
		wp_delete_post($id, true);
	}
	$remaining = '' !== $slug ? $findBySlug($slug) : array();
	$reply(array('action' => 'delete', 'deletedOrAbsent' => 0 === $id || ! get_post($id), 'slugAbsent' => empty($remaining)));
	exit(0);
}

fwrite(STDERR, "unknown action\n");
exit(6);
