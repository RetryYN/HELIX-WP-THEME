<?php
/**
 * Title: 著者プロフィール
 * Slug: agent-neo/author-profile
 * Categories: agent-neo
 * Description: 記事末尾に表示する著者プロフィールボックス（アバター・表示名・自己紹介・アーカイブリンク）。
 * Keywords: author, profile, bio, writer
 * Viewport Width: 1280
 * Block Types: core/group
 * Post Types: wp_template, post
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 著者情報を取得する。
// ブロックパターンは WP のループコンテキスト内で展開されるため
// get_the_author_meta() / get_avatar() 等の WP グローバルを使用する。
$author_id      = (int) get_the_author_meta( 'ID' );
$author_name    = sanitize_text_field( (string) get_the_author_meta( 'display_name' ) );
$author_bio     = sanitize_textarea_field( (string) get_the_author_meta( 'description' ) );
$author_url     = esc_url( get_author_posts_url( $author_id ) );
$author_avatar  = get_avatar( $author_id, 80, '', $author_name, array( 'class' => 'an-author-profile__avatar' ) );
?>

<!-- wp:html -->
<div class="an-author-profile"
     style="display:flex;flex-wrap:nowrap;align-items:flex-start;gap:1.25rem;border:1px solid var(--wp--preset--color--secondary,#e8e8e8);border-radius:10px;padding:1.5rem;margin-top:var(--wp--preset--spacing--40,2rem)">

	<!-- アバター -->
	<div class="an-author-profile__avatar-wrap" style="flex-shrink:0">
		<?php echo $author_avatar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_avatar() は WP コアの安全なエスケープ済み出力。 ?>
	</div>

	<!-- テキスト情報 -->
	<div class="an-author-profile__body" style="flex:1 1 0;min-width:0">

		<p class="an-author-profile__name"
		   style="font-size:1.0625rem;font-weight:700;color:var(--wp--preset--color--foreground,#1a1a1a);margin:0 0 0.5rem">
			<?php echo esc_html( $author_name ); ?>
		</p>

		<?php if ( '' !== $author_bio ) : ?>
		<p class="an-author-profile__bio"
		   style="font-size:0.875rem;color:var(--wp--preset--color--muted,#6b6b6b);margin:0 0 0.75rem;line-height:1.7">
			<?php echo nl2br( esc_html( $author_bio ) ); ?>
		</p>
		<?php endif; ?>

		<a href="<?php echo $author_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_url() 済み。 ?>"
		   class="an-author-profile__link"
		   style="font-size:0.8125rem;font-weight:600;color:var(--wp--preset--color--accent-aa,#cc4400);text-decoration:none"
		   aria-label="<?php echo esc_attr( sprintf( /* translators: %s: 著者名 */ __( '%s の記事一覧へ', 'agent-neo' ), $author_name ) ); ?>">
			<?php esc_html_e( '記事一覧を見る →', 'agent-neo' ); ?>
		</a>

	</div>

</div>
<!-- /wp:html -->
