<?php
/**
 * 関連記事クエリフィルタ。
 *
 * Query Loop ブロックに namespace "agent-neo/related" を指定したとき、
 * 同カテゴリ・現記事除外・3件に絞り込む。
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'query_loop_block_query_vars',
	function ( array $query, WP_Block $block ): array {
		// 関連記事 Query Loop は単一投稿ページにのみ存在する。
		if ( ! is_singular( 'post' ) ) {
			return $query;
		}

		// core/query は namespace を context に渡さないため取得できないことが多い。
		// パース済みブロック属性（parsed_block['attrs']['namespace']）も合わせて参照し、
		// 取得できた場合のみ別 namespace の Query Loop を除外する。
		// 取得できない場合は、単一投稿の唯一の Query Loop = 関連記事として続行する。
		$namespace = $block->context['query']['namespace']
			?? ( $block->parsed_block['attrs']['namespace'] ?? '' );
		if ( '' !== $namespace && 'agent-neo/related' !== $namespace ) {
			return $query;
		}

		// query_loop_block_query_vars は Query Loop がクエリを構築する時点で実行される。
		// この時点では the_loop が開始していないため get_the_ID() は 0 を返す。
		// get_queried_object_id() で表示中の投稿 ID を確実に取得する。
		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return $query;
		}

		$cats = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );
		if ( ! empty( $cats ) ) {
			$query['category__in'] = $cats;
		}

		$existing                     = $query['post__not_in'] ?? array();
		$query['post__not_in']        = array_merge( $existing, array( $post_id ) );
		$query['posts_per_page']      = 3;
		$query['ignore_sticky_posts'] = true;

		return $query;
	},
	10,
	2
);
