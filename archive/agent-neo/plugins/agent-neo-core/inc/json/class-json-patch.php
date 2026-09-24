<?php
/**
 * JSON Patch helper for AGENT NEO content documents.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RFC6902 JSON Patch の適用と diff hash を提供する。
 */
final class Agent_Neo_Core_JSON_Patch {
	/**
	 * JSON Patch operations を適用する。
	 *
	 * @param array<string|int, mixed> $document Target document。
	 * @param array<int, mixed>        $operations JSON Patch operations。
	 * @return array<string|int, mixed>|WP_Error
	 */
	public function apply( array $document, array $operations ) {
		$target = $document;

		foreach ( $operations as $index => $operation ) {
			if ( ! is_array( $operation ) ) {
				return $this->validation_error( 'operation must be an object', $index );
			}

			$op   = isset( $operation['op'] ) && is_string( $operation['op'] ) ? $operation['op'] : '';
			$path = isset( $operation['path'] ) && is_string( $operation['path'] ) ? $operation['path'] : null;

			if ( null === $path || '' === $op ) {
				return $this->validation_error( 'operation requires op and path', $index );
			}

			$result = $this->apply_operation( $target, $op, $path, $operation );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$target = $result;
		}

		return $target;
	}

	/**
	 * 正規化 diff を生成する。
	 *
	 * @param mixed  $before Before。
	 * @param mixed  $after After。
	 * @param string $path JSON pointer。
	 * @return array<int, array<string, mixed>>
	 */
	public function diff( $before, $after, string $path = '' ): array {
		if ( $before === $after ) {
			return array();
		}

		if ( is_array( $before ) && is_array( $after ) && $this->is_assoc( $before ) && $this->is_assoc( $after ) ) {
			$diff = array();
			$keys = array_unique( array_merge( array_keys( $before ), array_keys( $after ) ) );
			sort( $keys );

			foreach ( $keys as $key ) {
				$child_path = $path . '/' . $this->escape_pointer_token( (string) $key );
				if ( ! array_key_exists( $key, $before ) ) {
					$diff[] = array(
						'op'    => 'add',
						'path'  => $child_path,
						'value' => $after[ $key ],
					);
					continue;
				}

				if ( ! array_key_exists( $key, $after ) ) {
					$diff[] = array(
						'op'   => 'remove',
						'path' => $child_path,
					);
					continue;
				}

				$diff = array_merge( $diff, $this->diff( $before[ $key ], $after[ $key ], $child_path ) );
			}

			return $diff;
		}

		return array(
			array(
				'op'    => 'replace',
				'path'  => '' === $path ? '/' : $path,
				'value' => $after,
			),
		);
	}

	/**
	 * diff の安定 hash を返す。
	 *
	 * @param array<int, array<string, mixed>> $diff Diff。
	 * @return string
	 */
	public function diff_hash( array $diff ): string {
		return hash( 'sha256', $this->canonical_json( $diff ) );
	}

	/**
	 * 投稿本文を block document に変換する。
	 *
	 * @param string $post_content Post content。
	 * @return array<string, mixed>
	 */
	public function document_from_post_content( string $post_content ): array {
		return array(
			'post_content' => $post_content,
			'blocks'       => function_exists( 'parse_blocks' ) ? parse_blocks( $post_content ) : array(),
		);
	}

	/**
	 * block document から投稿本文を生成する。
	 *
	 * @param array<string, mixed> $document Document。
	 * @return string
	 */
	public function post_content_from_document( array $document ): string {
		if ( isset( $document['post_content'] ) && is_string( $document['post_content'] ) && ! isset( $document['blocks'] ) ) {
			return $document['post_content'];
		}

		if ( isset( $document['blocks'] ) && is_array( $document['blocks'] ) && function_exists( 'serialize_blocks' ) ) {
			return serialize_blocks( $document['blocks'] );
		}

		return isset( $document['post_content'] ) && is_string( $document['post_content'] ) ? $document['post_content'] : '';
	}

	/**
	 * block_id に一致する block を更新する。
	 *
	 * @param array<int, array<string, mixed>> $blocks Blocks。
	 * @param string                          $block_id Block id。
	 * @param array<string, mixed>            $new_block New block。
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	public function replace_block_by_id( array $blocks, string $block_id, array $new_block ) {
		$updated = false;
		$blocks  = $this->replace_block_in_tree( $blocks, $block_id, $new_block, $updated );

		if ( ! $updated ) {
			return Agent_Neo_Core_Auth::error(
				'NOT_FOUND',
				__( 'Block was not found.', 'agent-neo-core' ),
				array( 'block_id' => $block_id )
			);
		}

		return $blocks;
	}

	/**
	 * block_id に一致する block を返す。
	 *
	 * @param array<int, array<string, mixed>> $blocks Blocks。
	 * @param string                          $block_id Block id。
	 * @return array<string, mixed>|null
	 */
	public function find_block_by_id( array $blocks, string $block_id ): ?array {
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			if ( $block_id === $this->block_id( $block ) ) {
				return $block;
			}

			$inner_blocks = isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ? $block['innerBlocks'] : array();
			$found        = $this->find_block_by_id( $inner_blocks, $block_id );
			if ( null !== $found ) {
				return $found;
			}
		}

		return null;
	}

	/**
	 * block から安定 block_id を読む。
	 *
	 * @param array<string, mixed> $block Block。
	 * @return string
	 */
	public function block_id( array $block ): string {
		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

		foreach ( array( 'block_id', 'blockId', 'data-block-id' ) as $key ) {
			if ( isset( $attrs[ $key ] ) && is_string( $attrs[ $key ] ) ) {
				return $attrs[ $key ];
			}
		}

		if ( isset( $attrs['agentNeo'] ) && is_array( $attrs['agentNeo'] ) && isset( $attrs['agentNeo']['block_id'] ) && is_string( $attrs['agentNeo']['block_id'] ) ) {
			return $attrs['agentNeo']['block_id'];
		}

		if ( isset( $attrs['metadata'] ) && is_array( $attrs['metadata'] ) && isset( $attrs['metadata']['agentNeoBlockId'] ) && is_string( $attrs['metadata']['agentNeoBlockId'] ) ) {
			return $attrs['metadata']['agentNeoBlockId'];
		}

		return '';
	}

	/**
	 * section_id に一致する top-level block range を返す。
	 *
	 * @param array<int, array<string, mixed>> $blocks Blocks。
	 * @param string                          $section_id Section id。
	 * @return array<string, int>|WP_Error
	 */
	public function find_section_range( array $blocks, string $section_id ) {
		$start = null;
		$count = count( $blocks );

		for ( $index = 0; $index < $count; $index++ ) {
			$block = $blocks[ $index ];
			if ( ! is_array( $block ) || $section_id !== $this->section_id( $block ) ) {
				continue;
			}

			$start = $index;
			break;
		}

		if ( null === $start ) {
			return Agent_Neo_Core_Auth::error(
				'NOT_FOUND',
				__( 'Section was not found.', 'agent-neo-core' ),
				array( 'section_id' => $section_id )
			);
		}

		$end = $start + 1;
		for ( $index = $start + 1; $index < $count; $index++ ) {
			$block = $blocks[ $index ];
			if ( is_array( $block ) && $this->is_h2_heading( $block ) ) {
				break;
			}
			$end++;
		}

		return array(
			'start'  => $start,
			'length' => $end - $start,
		);
	}

	/**
	 * section_id に一致する section を置換する。
	 *
	 * @param array<int, array<string, mixed>> $blocks Blocks。
	 * @param string                          $section_id Section id。
	 * @param array<int, array<string, mixed>> $section_blocks Section blocks。
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	public function replace_section_by_id( array $blocks, string $section_id, array $section_blocks ) {
		$range = $this->find_section_range( $blocks, $section_id );
		if ( is_wp_error( $range ) ) {
			return $range;
		}

		array_splice( $blocks, $range['start'], $range['length'], $section_blocks );
		return $blocks;
	}

	/**
	 * section block から section_id を読む。
	 *
	 * @param array<string, mixed> $block Block。
	 * @return string
	 */
	public function section_id( array $block ): string {
		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

		foreach ( array( 'section_id', 'sectionId', 'data-agent-section-id' ) as $key ) {
			if ( isset( $attrs[ $key ] ) && is_string( $attrs[ $key ] ) ) {
				return $attrs[ $key ];
			}
		}

		if ( isset( $attrs['agentNeo'] ) && is_array( $attrs['agentNeo'] ) && isset( $attrs['agentNeo']['section_id'] ) && is_string( $attrs['agentNeo']['section_id'] ) ) {
			return $attrs['agentNeo']['section_id'];
		}

		return '';
	}

	/**
	 * canonical JSON を返す。
	 *
	 * @param mixed $value Value。
	 * @return string
	 */
	public function canonical_json( $value ): string {
		$normalized = $this->sort_recursive( $value );
		$json       = wp_json_encode( $normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		return is_string( $json ) ? $json : '';
	}

	/**
	 * 単一 operation を適用する。
	 *
	 * @param mixed                $target Target。
	 * @param string               $op Operation。
	 * @param string               $path Path。
	 * @param array<string, mixed> $operation Operation data。
	 * @return mixed|WP_Error
	 */
	private function apply_operation( $target, string $op, string $path, array $operation ) {
		switch ( $op ) {
			case 'add':
				if ( ! array_key_exists( 'value', $operation ) ) {
					return $this->validation_error( 'add requires value' );
				}
				return $this->set_value( $target, $path, $operation['value'], true );
			case 'replace':
				if ( ! array_key_exists( 'value', $operation ) ) {
					return $this->validation_error( 'replace requires value' );
				}
				if ( is_wp_error( $this->get_value( $target, $path ) ) ) {
					return $this->validation_error( 'replace path does not exist' );
				}
				return $this->set_value( $target, $path, $operation['value'], false );
			case 'remove':
				return $this->remove_value( $target, $path );
			case 'test':
				if ( ! array_key_exists( 'value', $operation ) ) {
					return $this->validation_error( 'test requires value' );
				}
				$current = $this->get_value( $target, $path );
				if ( is_wp_error( $current ) || $current !== $operation['value'] ) {
					return $this->validation_error( 'test operation failed' );
				}
				return $target;
			case 'copy':
				$from = isset( $operation['from'] ) && is_string( $operation['from'] ) ? $operation['from'] : '';
				if ( '' === $from ) {
					return $this->validation_error( 'copy requires from' );
				}
				$value = $this->get_value( $target, $from );
				return is_wp_error( $value ) ? $value : $this->set_value( $target, $path, $value, true );
			case 'move':
				$from = isset( $operation['from'] ) && is_string( $operation['from'] ) ? $operation['from'] : '';
				if ( '' === $from ) {
					return $this->validation_error( 'move requires from' );
				}
				$value = $this->get_value( $target, $from );
				if ( is_wp_error( $value ) ) {
					return $value;
				}
				$target = $this->remove_value( $target, $from );
				return is_wp_error( $target ) ? $target : $this->set_value( $target, $path, $value, true );
			default:
				return $this->validation_error( 'unsupported operation: ' . $op );
		}
	}

	/**
	 * JSON pointer の値を読む。
	 *
	 * @param mixed  $target Target。
	 * @param string $path Path。
	 * @return mixed|WP_Error
	 */
	private function get_value( $target, string $path ) {
		if ( '' === $path || '/' === $path ) {
			return $target;
		}

		$current = $target;
		foreach ( $this->path_segments( $path ) as $segment ) {
			if ( is_array( $current ) && array_key_exists( $segment, $current ) ) {
				$current = $current[ $segment ];
				continue;
			}

			if ( is_array( $current ) && ctype_digit( $segment ) ) {
				$index = (int) $segment;
				if ( array_key_exists( $index, $current ) ) {
					$current = $current[ $index ];
					continue;
				}
			}

			return $this->validation_error( 'path does not exist: ' . $path );
		}

		return $current;
	}

	/**
	 * JSON pointer の値を設定する。
	 *
	 * @param mixed  $target Target。
	 * @param string $path Path。
	 * @param mixed  $value Value。
	 * @param bool   $allow_append Allow add semantics。
	 * @return mixed|WP_Error
	 */
	private function set_value( $target, string $path, $value, bool $allow_append ) {
		if ( '' === $path || '/' === $path ) {
			return $value;
		}

		$segments = $this->path_segments( $path );
		$last     = array_pop( $segments );
		$cursor   = &$target;

		foreach ( $segments as $segment ) {
			if ( is_array( $cursor ) && array_key_exists( $segment, $cursor ) ) {
				$cursor = &$cursor[ $segment ];
				continue;
			}

			if ( is_array( $cursor ) && ctype_digit( $segment ) && array_key_exists( (int) $segment, $cursor ) ) {
				$index  = (int) $segment;
				$cursor = &$cursor[ $index ];
				continue;
			}

			return $this->validation_error( 'parent path does not exist: ' . $path );
		}

		if ( ! is_array( $cursor ) || null === $last ) {
			return $this->validation_error( 'target is not an object or array: ' . $path );
		}

		if ( '-' === $last && $allow_append && ! $this->is_assoc( $cursor ) ) {
			$cursor[] = $value;
			return $target;
		}

		if ( ctype_digit( $last ) && ! $this->is_assoc( $cursor ) ) {
			$index = (int) $last;
			if ( $allow_append && $index <= count( $cursor ) ) {
				array_splice( $cursor, $index, 0, array( $value ) );
				return $target;
			}
			if ( array_key_exists( $index, $cursor ) ) {
				$cursor[ $index ] = $value;
				return $target;
			}
		}

		if ( $allow_append || array_key_exists( $last, $cursor ) ) {
			$cursor[ $last ] = $value;
			return $target;
		}

		return $this->validation_error( 'target path does not exist: ' . $path );
	}

	/**
	 * JSON pointer の値を削除する。
	 *
	 * @param mixed  $target Target。
	 * @param string $path Path。
	 * @return mixed|WP_Error
	 */
	private function remove_value( $target, string $path ) {
		if ( '' === $path || '/' === $path ) {
			return $this->validation_error( 'cannot remove root document' );
		}

		$segments = $this->path_segments( $path );
		$last     = array_pop( $segments );
		$cursor   = &$target;

		foreach ( $segments as $segment ) {
			if ( is_array( $cursor ) && array_key_exists( $segment, $cursor ) ) {
				$cursor = &$cursor[ $segment ];
				continue;
			}

			if ( is_array( $cursor ) && ctype_digit( $segment ) && array_key_exists( (int) $segment, $cursor ) ) {
				$index  = (int) $segment;
				$cursor = &$cursor[ $index ];
				continue;
			}

			return $this->validation_error( 'parent path does not exist: ' . $path );
		}

		if ( ! is_array( $cursor ) || null === $last ) {
			return $this->validation_error( 'target is not an object or array: ' . $path );
		}

		if ( ctype_digit( $last ) && ! $this->is_assoc( $cursor ) && array_key_exists( (int) $last, $cursor ) ) {
			array_splice( $cursor, (int) $last, 1 );
			return $target;
		}

		if ( array_key_exists( $last, $cursor ) ) {
			unset( $cursor[ $last ] );
			return $target;
		}

		return $this->validation_error( 'target path does not exist: ' . $path );
	}

	/**
	 * block tree 内の block を置換する。
	 *
	 * @param array<int, array<string, mixed>> $blocks Blocks。
	 * @param string                          $block_id Block id。
	 * @param array<string, mixed>            $new_block New block。
	 * @param bool                            $updated Updated flag。
	 * @return array<int, array<string, mixed>>
	 */
	private function replace_block_in_tree( array $blocks, string $block_id, array $new_block, bool &$updated ): array {
		foreach ( $blocks as $index => $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			if ( $block_id === $this->block_id( $block ) ) {
				$blocks[ $index ] = $new_block;
				$updated          = true;
				return $blocks;
			}

			if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = $this->replace_block_in_tree( $block['innerBlocks'], $block_id, $new_block, $updated );
				$blocks[ $index ]    = $block;
				if ( $updated ) {
					return $blocks;
				}
			}
		}

		return $blocks;
	}

	/**
	 * H2 heading block か判定する。
	 *
	 * @param array<string, mixed> $block Block。
	 * @return bool
	 */
	private function is_h2_heading( array $block ): bool {
		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		return isset( $block['blockName'] ) && 'core/heading' === $block['blockName'] && isset( $attrs['level'] ) && 2 === (int) $attrs['level'];
	}

	/**
	 * JSON pointer segments を返す。
	 *
	 * @param string $path Path。
	 * @return array<int, string>
	 */
	private function path_segments( string $path ): array {
		if ( '' === $path || '/' === $path ) {
			return array();
		}

		$trimmed = ltrim( $path, '/' );
		return array_map( array( $this, 'unescape_pointer_token' ), explode( '/', $trimmed ) );
	}

	/**
	 * Pointer token を unescape する。
	 *
	 * @param string $token Token。
	 * @return string
	 */
	private function unescape_pointer_token( string $token ): string {
		return str_replace( array( '~1', '~0' ), array( '/', '~' ), $token );
	}

	/**
	 * Pointer token を escape する。
	 *
	 * @param string $token Token。
	 * @return string
	 */
	private function escape_pointer_token( string $token ): string {
		return str_replace( array( '~', '/' ), array( '~0', '~1' ), $token );
	}

	/**
	 * 連想配列か判定する。
	 *
	 * @param array<int|string, mixed> $array Array。
	 * @return bool
	 */
	private function is_assoc( array $array ): bool {
		if ( array() === $array ) {
			return false;
		}

		return array_keys( $array ) !== range( 0, count( $array ) - 1 );
	}

	/**
	 * 再帰的に key sort する。
	 *
	 * @param mixed $value Value。
	 * @return mixed
	 */
	private function sort_recursive( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		foreach ( $value as $key => $child ) {
			$value[ $key ] = $this->sort_recursive( $child );
		}

		if ( $this->is_assoc( $value ) ) {
			ksort( $value );
		}

		return $value;
	}

	/**
	 * Validation error を返す。
	 *
	 * @param string $message Message。
	 * @param int    $index Operation index。
	 * @return WP_Error
	 */
	private function validation_error( string $message, int $index = -1 ): WP_Error {
		return Agent_Neo_Core_Auth::error(
			'VALIDATION_ERROR',
			__( 'JSON Patch validation failed.', 'agent-neo-core' ),
			array(
				'reason'          => $message,
				'operation_index' => $index,
			)
		);
	}
}
