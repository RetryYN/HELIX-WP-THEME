<?php
/**
 * Rollback snapshot store.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * apply 前 snapshot と resource_version を管理する。
 */
final class Agent_Neo_Core_Rollback_Store {
	private const SNAPSHOT_META = '_agent_neo_rollback_points';
	private const VERSION_META  = '_agent_neo_resource_version';
	private const HISTORY_META  = '_agent_neo_block_history';
	private const MAX_POINTS    = 30;
	private const HISTORY_LIMIT = 5;

	/**
	 * apply 前 snapshot を保存する。
	 *
	 * @param int    $post_id Post id。
	 * @param string $content Current content。
	 * @param string $reason Reason。
	 * @return string rollback_point_id。
	 */
	public function snapshot( int $post_id, string $content, string $reason = '' ): string {
		$rollback_point_id = 'rb_' . wp_generate_uuid4();
		$points            = get_post_meta( $post_id, self::SNAPSHOT_META, true );
		$points            = is_array( $points ) ? $points : array();

		$points[] = array(
			'rollback_point_id' => $rollback_point_id,
			'post_id'           => $post_id,
			'content'           => $content,
			'reason'            => $reason,
			'created_at'        => gmdate( 'c' ),
		);

		if ( count( $points ) > self::MAX_POINTS ) {
			$points = array_slice( $points, -1 * self::MAX_POINTS );
		}

		update_post_meta( $post_id, self::SNAPSHOT_META, $points );
		return $rollback_point_id;
	}

	/**
	 * resource version をインクリメントする。
	 *
	 * @param int $post_id Post id。
	 * @return int
	 */
	public function increment_resource_version( int $post_id ): int {
		$current = (int) get_post_meta( $post_id, self::VERSION_META, true );
		$next    = $current + 1;
		update_post_meta( $post_id, self::VERSION_META, $next );
		return $next;
	}

	/**
	 * 現在の resource version を返す。
	 *
	 * @param int $post_id Post id。
	 * @return int
	 */
	public function resource_version( int $post_id ): int {
		return (int) get_post_meta( $post_id, self::VERSION_META, true );
	}

	/**
	 * block 履歴に旧版を追加する。
	 *
	 * @param int                  $post_id Post id。
	 * @param string               $block_id Block id。
	 * @param array<string, mixed> $block Block。
	 * @param int                  $version Version。
	 * @return array<int, array<string, mixed>>
	 */
	public function append_block_history( int $post_id, string $block_id, array $block, int $version ): array {
		$history = get_post_meta( $post_id, self::HISTORY_META, true );
		$history = is_array( $history ) ? $history : array();

		if ( ! isset( $history[ $block_id ] ) || ! is_array( $history[ $block_id ] ) ) {
			$history[ $block_id ] = array();
		}

		$history[ $block_id ][] = array(
			'version'    => $version,
			'saved_at'   => gmdate( 'c' ),
			'block_name' => isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : '',
			'block'      => $block,
		);

		$history[ $block_id ] = array_slice( $history[ $block_id ], -1 * self::HISTORY_LIMIT );
		update_post_meta( $post_id, self::HISTORY_META, $history );

		return $this->history_summary( $history[ $block_id ] );
	}

	/**
	 * block 履歴サマリを返す。
	 *
	 * @param int    $post_id Post id。
	 * @param string $block_id Block id。
	 * @return array<int, array<string, mixed>>
	 */
	public function block_history( int $post_id, string $block_id ): array {
		$history = get_post_meta( $post_id, self::HISTORY_META, true );
		$history = is_array( $history ) ? $history : array();
		$items   = isset( $history[ $block_id ] ) && is_array( $history[ $block_id ] ) ? $history[ $block_id ] : array();
		return $this->history_summary( $items );
	}

	/**
	 * history summary に整形する。
	 *
	 * @param array<int, array<string, mixed>> $items Items。
	 * @return array<int, array<string, mixed>>
	 */
	private function history_summary( array $items ): array {
		return array_map(
			static function ( array $item ): array {
				return array(
					'version'    => isset( $item['version'] ) ? (int) $item['version'] : 0,
					'saved_at'   => isset( $item['saved_at'] ) ? (string) $item['saved_at'] : '',
					'block_name' => isset( $item['block_name'] ) ? (string) $item['block_name'] : '',
				);
			},
			$items
		);
	}
}
