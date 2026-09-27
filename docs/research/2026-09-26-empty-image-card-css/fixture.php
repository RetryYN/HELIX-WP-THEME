<?php
/** Add image, tag and author controls to the isolated article-purpose fixture. */
declare(strict_types=1);
require '/var/www/html/wp-load.php';
if (get_option('blogname') !== 'HELIX Article Purpose PoC') {
    throw new RuntimeException('Dedicated article-purpose lab required');
}
$posts = get_posts(['numberposts' => -1, 'post_type' => 'post']);
foreach ($posts as $post) {
    wp_update_post(['ID' => $post->ID, 'post_author' => 1]);
    wp_set_post_tags($post->ID, 'Card fixture');
}
$post = get_page_by_path('product-launch-sample-2026', OBJECT, 'post');
if (!$post) {
    throw new RuntimeException('Run article-purpose seed first');
}
if (!has_post_thumbnail($post->ID)) {
    $upload = wp_upload_bits('card-fixture.png', null, file_get_contents(get_theme_file_path('assets/img/avatar.png')));
    if ($upload['error']) {
        throw new RuntimeException($upload['error']);
    }
    $id = wp_insert_attachment(['post_mime_type' => 'image/png', 'post_title' => 'Synthetic image fixture', 'post_status' => 'inherit'], $upload['file']);
    require_once ABSPATH . 'wp-admin/includes/image.php';
    wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $upload['file']));
    set_post_thumbnail($post->ID, $id);
}
echo "Image, tag and author fixture ready\n";
