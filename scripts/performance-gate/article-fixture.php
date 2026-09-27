<?php
/** Temporary owner-guarded representative article with a bundled featured image. */
declare(strict_types=1);

require '/var/www/html/wp-load.php';

$action = getenv('ARTICLE_PROBE_ACTION') ?: 'inspect';
$slug   = getenv('ARTICLE_PROBE_SLUG') ?: '';
$owner  = getenv('ARTICLE_PROBE_OWNER') ?: '';
$reply  = static function (array $value): void { echo wp_json_encode($value, JSON_UNESCAPED_SLASHES) . "\n"; };
$findPost = static function (string $name): ?WP_Post {
	$post = '' !== $name ? get_page_by_path($name, OBJECT, 'post') : null;
	return $post instanceof WP_Post ? $post : null;
};
$ownedImages = static function (string $token): array {
	return get_posts(array(
		'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => -1,
		'fields' => 'ids', 'meta_key' => '_helix_article_probe_owner', 'meta_value' => $token,
	));
};

if ('inspect' === $action) {
	$post = $findPost($slug);
	$reply(array(
		'action' => 'inspect', 'wordpressVersion' => get_bloginfo('version'), 'theme' => get_stylesheet(),
		'slugExists' => (bool) $post,
		'ownerMatches' => $post && hash_equals($owner, (string) get_post_meta($post->ID, '_helix_article_probe_owner', true)),
		'ownedImages' => array_map('intval', $ownedImages($owner)),
	));
	exit(0);
}

if ('create' === $action) {
	if ( ! preg_match('/^performance-article-[a-f0-9]{16}$/', $slug) || '' === $owner || $findPost($slug)) {
		fwrite(STDERR, "invalid or colliding fixture identity\n");
		exit(2);
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$imagePath = get_theme_file_path('assets/img/case-tax.jpg');
	if ( ! is_file($imagePath)) { fwrite(STDERR, "bundled featured image is missing\n"); exit(3); }
	$tmpPath = wp_tempnam(basename($imagePath));
	if ( ! $tmpPath || ! copy($imagePath, $tmpPath)) { fwrite(STDERR, "image copy failed\n"); exit(4); }
	$attachmentId = media_handle_sideload(array('name' => 'article-performance-featured.jpg', 'tmp_name' => $tmpPath), 0, 'Performance article fixture image');
	if ( is_wp_error($attachmentId)) { @unlink($tmpPath); fwrite(STDERR, "image attachment failed\n"); exit(5); }
	$attachmentId = (int) $attachmentId;
	if ( ! add_post_meta($attachmentId, '_helix_article_probe_owner', $owner, true)
		|| ! hash_equals($owner, (string) get_post_meta($attachmentId, '_helix_article_probe_owner', true))) {
		wp_delete_attachment($attachmentId, true);
		fwrite(STDERR, "featured image ownership metadata failed\n");
		exit(6);
	}
	$content = implode("\n", array(
		'<!-- wp:paragraph --><p>道具やサービスを選ぶときは、価格や機能の一覧だけでなく、実際に使う場面を先に思い浮かべると、比べる条件を整理しやすくなります。毎日の作業で困っていることと、これから変えたいことを書き出してみましょう。</p><!-- /wp:paragraph -->',
		'<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">使う場面と優先順位を決める</h2><!-- /wp:heading -->',
		'<!-- wp:paragraph --><p>候補を調べる前に、誰が、どこで、どのくらいの頻度で使うのかを確認します。必要な機能をすべて並べるより、欠かせない条件と、あれば便利な条件を分けておくと、情報が増えても判断の軸を保てます。</p><!-- /wp:paragraph -->',
		'<!-- wp:paragraph --><p>担当者ごとに違う希望がある場合は、実際の手順を一緒にたどり、時間がかかる作業や引き継ぎで迷いやすい点を確かめます。小さな不便を具体的な場面に結び付けることで、導入後に確かめたいことも明確になります。</p><!-- /wp:paragraph -->',
		'<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">条件をそろえて候補を比べる</h2><!-- /wp:heading -->',
		'<!-- wp:paragraph --><p>候補ごとに初期費用、継続費用、サポート範囲、使い始めるまでの準備を同じ形式で記録します。費用は一度きりの金額だけでなく、更新や追加作業も含めて確認し、必要な期間で比べることが大切です。</p><!-- /wp:paragraph -->',
		'<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">試してから導入を決める</h2><!-- /wp:heading -->',
		'<!-- wp:paragraph --><p>説明資料だけでは分からない操作や役割分担は、少人数で試すと見つけやすくなります。実際の業務に近い例を使い、開始前に決めた条件を満たすか、無理なく続けられるかを確認します。</p><!-- /wp:paragraph -->',
		'<!-- wp:paragraph --><p>試した結果は、良かった点と残った課題を分けて記録します。導入後も定期的に振り返る予定を立てておけば、使い方の変更や追加の支援が必要になったときに、早めに対応できます。</p><!-- /wp:paragraph -->',
	));
	$postId = wp_insert_post(array(
		'post_type' => 'post', 'post_status' => 'publish', 'post_name' => $slug,
		'post_title' => '使う場面から考える、道具やサービスの選び方',
		'post_excerpt' => '日々の使い方、比較条件、試行の進め方を順に整理するための実例記事です。',
		'post_content' => $content, 'meta_input' => array('_helix_article_probe_owner' => $owner),
	), true);
	if ( is_wp_error($postId) || ! $postId || ! hash_equals($owner, (string) get_post_meta((int) $postId, '_helix_article_probe_owner', true))) {
		fwrite(STDERR, "article creation or ownership metadata failed\n");
		exit(6);
	}
	set_post_thumbnail((int) $postId, $attachmentId);
	if ((int) get_post_thumbnail_id((int) $postId) !== $attachmentId) { fwrite(STDERR, "featured image binding failed\n"); exit(7); }
	$reply(array('action' => 'create', 'postId' => (int) $postId, 'slug' => $slug,
		'ownerVerified' => hash_equals($owner, (string) get_post_meta((int) $postId, '_helix_article_probe_owner', true))
			&& hash_equals($owner, (string) get_post_meta($attachmentId, '_helix_article_probe_owner', true)),
		'featuredImageId' => $attachmentId, 'permalink' => get_permalink((int) $postId),
		'contentBlockCount' => count(parse_blocks($content))));
	exit(0);
}

if ('delete' === $action) {
	$post = $findPost($slug);
	if ($post) {
		if ( ! hash_equals($owner, (string) get_post_meta($post->ID, '_helix_article_probe_owner', true))) {
			fwrite(STDERR, "article ownership guard refused deletion\n");
			exit(8);
		}
		wp_delete_post($post->ID, true);
	}
	$deletedImages = array();
	foreach ($ownedImages($owner) as $attachmentId) {
		if (hash_equals($owner, (string) get_post_meta((int) $attachmentId, '_helix_article_probe_owner', true))) {
			wp_delete_attachment((int) $attachmentId, true);
			$deletedImages[] = (int) $attachmentId;
		}
	}
	$remaining = $findPost($slug);
	$remainingImages = $ownedImages($owner);
	$reply(array('action' => 'delete', 'slugAbsent' => ! $remaining,
		'imagesAbsent' => empty($remainingImages), 'deletedImages' => $deletedImages,
		'foreignPostPreserved' => ! $remaining || ! hash_equals($owner, (string) get_post_meta($remaining->ID, '_helix_article_probe_owner', true))));
	exit(0);
}

fwrite(STDERR, "unknown fixture action\n");
exit(9);
