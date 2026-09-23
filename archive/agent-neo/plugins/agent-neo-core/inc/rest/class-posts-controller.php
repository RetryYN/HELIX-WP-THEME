<?php
/**
 * GET /agent-neo/v1/posts, /posts/{id}, /posts/{id}/diff, /posts/{id}/markdown コントローラー。
 *
 * REQ-NF-025 準拠: AI 判断ロジック・LLM 呼び出し一切なし。
 * WP からの read と決定的変換のみを実装する。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 投稿 bulk read / ブロック分解 / JSON Patch diff / Markdown 変換 endpoint。
 */
final class Agent_Neo_Core_Posts_Controller extends Agent_Neo_Core_REST_Controller_Base {

	/**
	 * JSON Patch helper。
	 *
	 * @var Agent_Neo_Core_JSON_Patch
	 */
	private Agent_Neo_Core_JSON_Patch $json_patch;

	/**
	 * @param Agent_Neo_Core_JSON_Patch $json_patch JSON diff helper。
	 */
	public function __construct( Agent_Neo_Core_JSON_Patch $json_patch ) {
		$this->json_patch = $json_patch;
	}

	/**
	 * rest_api_init に route 登録を接続する。
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * 全 route を登録する。
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET /posts — 投稿一覧 bulk read。
		$this->register_agent_route(
			'/posts',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_posts' ),
				'permission_callback' => array( $this, 'check_list_permission' ),
			)
		);

		// GET /posts/{id} — 投稿詳細 + ブロック分解。
		$this->register_agent_route(
			'/posts/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_post_detail' ),
				'permission_callback' => array( $this, 'check_post_permission' ),
			)
		);

		// GET /posts/{id}/diff — JSON Patch 差分エクスポート（RFC 6902）。
		$this->register_agent_route(
			'/posts/(?P<id>\d+)/diff',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_post_diff' ),
				'permission_callback' => array( $this, 'check_post_permission' ),
			)
		);

		// GET /posts/{id}/markdown — ブロック JSON → plain markdown 変換。
		$this->register_agent_route(
			'/posts/(?P<id>\d+)/markdown',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_post_markdown' ),
				'permission_callback' => array( $this, 'check_post_permission' ),
			)
		);
	}

	// -------------------------------------------------------------------------
	// パーミッションコールバック
	// -------------------------------------------------------------------------

	/**
	 * 一覧用 read permission を確認する。
	 *
	 * @return true|WP_Error
	 */
	public function check_list_permission() {
		if ( ! is_user_logged_in() ) {
			return Agent_Neo_Core_Auth::error(
				'UNAUTHORIZED',
				__( 'Authentication required to list posts.', 'agent-neo-core' )
			);
		}

		if ( ! current_user_can( 'read' ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Current user cannot read posts.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * 個別投稿の read permission を確認する。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return true|WP_Error
	 */
	public function check_post_permission( WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return Agent_Neo_Core_Auth::error(
				'UNAUTHORIZED',
				__( 'Authentication required to read this post.', 'agent-neo-core' )
			);
		}

		$post_id = (int) $request['id'];
		if ( $post_id < 1 ) {
			// id が不正な場合は後続のハンドラーで NOT_FOUND を返す。
			return true;
		}

		if ( ! current_user_can( 'read_post', $post_id ) ) {
			// 存在しない post_id の場合も FORBIDDEN ではなく NOT_FOUND を優先する。
			$post = get_post( $post_id );
			if ( ! $post instanceof WP_Post ) {
				return true; // ハンドラーで NOT_FOUND を返す。
			}

			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Current user cannot read this post.', 'agent-neo-core' ),
				array( 'post_id' => $post_id )
			);
		}

		return true;
	}

	// -------------------------------------------------------------------------
	// ハンドラー
	// -------------------------------------------------------------------------

	/**
	 * GET /posts — 投稿一覧を返す。
	 *
	 * クエリパラメータ:
	 *   - since  : ISO 8601 日時。この時刻以降に更新された投稿のみ返す。
	 *   - fields : カンマ区切りフィールド名で sparse fieldset 絞り込み。
	 *              有効フィールド: id, title, status, modified, link
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_posts( WP_REST_Request $request ) {
		$request_id = $this->resolve_request_id( $request );

		// ?since パラメータの検証。
		$since_raw = $request->get_param( 'since' );
		$since     = '';
		if ( is_string( $since_raw ) && '' !== $since_raw ) {
			$since = sanitize_text_field( wp_unslash( $since_raw ) );
			if ( false === strtotime( $since ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'since parameter must be a valid ISO 8601 date.', 'agent-neo-core' ),
					array( 'field' => 'since', 'value' => $since )
				);
			}
		}

		// ?fields パラメータの解析。
		$allowed_fields = array( 'id', 'title', 'status', 'modified', 'link' );
		$fields_raw     = $request->get_param( 'fields' );
		$fields         = $allowed_fields; // デフォルトは全フィールド。
		if ( is_string( $fields_raw ) && '' !== $fields_raw ) {
			$requested = array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $fields_raw ) ) ) );
			$fields    = array_values( array_intersect( $allowed_fields, $requested ) );
			if ( empty( $fields ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'fields parameter contains no valid field names.', 'agent-neo-core' ),
					array( 'field' => 'fields', 'allowed' => $allowed_fields )
				);
			}
		}

		// WP_Query で投稿を取得する。
		$query_args = array(
			'post_type'      => 'post',
			'post_status'    => array( 'publish', 'draft', 'private', 'pending', 'future' ),
			'posts_per_page' => 100,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		);

		if ( '' !== $since ) {
			$query_args['date_query'] = array(
				array(
					'column' => 'post_modified_gmt',
					'after'  => $since,
					'inclusive' => true,
				),
			);
		}

		$query = new WP_Query( $query_args );
		$posts = $query->posts;

		$items = array();
		foreach ( $posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}
			// 読み取り権限のない投稿はスキップする。
			if ( ! current_user_can( 'read_post', $post->ID ) ) {
				continue;
			}

			$item = $this->format_post_item( $post, $fields );
			$items[] = $item;
		}

		// ETag の算出と 304 判定。
		$etag          = '"' . md5( wp_json_encode( $items ) ?: '' ) . '"';
		$if_none_match = $request->get_header( 'If-None-Match' );
		if ( is_string( $if_none_match ) && '' !== $if_none_match && trim( $if_none_match ) === $etag ) {
			$response = new WP_REST_Response( null, 304 );
			return $response;
		}

		$data = array(
			'posts' => $items,
			'total' => count( $items ),
		);

		$response = rest_ensure_response( Agent_Neo_Core_Auth::success_response( $data, $request_id ) );
		$response->header( 'ETag', $etag );

		return $response;
	}

	/**
	 * GET /posts/{id} — 投稿詳細とブロック分解を返す。
	 *
	 * 各ブロックに以下を付与する:
	 *   - block_id  : attrs.metadata.id があれば採用、なければ index ベースの決定的 ID。
	 *   - section_id: H2 見出しで区切った連番（0 始まり）。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_post_detail( WP_REST_Request $request ) {
		$request_id = $this->resolve_request_id( $request );
		$post_id    = (int) $request['id'];

		$post = $this->post_for_read( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$blocks       = parse_blocks( $post->post_content );
		$annotated    = $this->annotate_blocks( $blocks );

		$data = array(
			'post_id'  => $post_id,
			'title'    => get_the_title( $post ),
			'status'   => $post->post_status,
			'modified' => get_post_modified_time( 'c', true, $post ),
			'link'     => get_permalink( $post ),
			'blocks'   => $annotated,
		);

		return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $data, $request_id ) );
	}

	/**
	 * GET /posts/{id}/diff — JSON Patch 差分エクスポート（RFC 6902）。
	 *
	 * クエリパラメータ:
	 *   - from : ISO 8601 日時。この時刻に最も近い revision を baseline とする。
	 *            省略時は空配列を baseline とする（全差分）。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_post_diff( WP_REST_Request $request ) {
		$request_id = $this->resolve_request_id( $request );
		$post_id    = (int) $request['id'];

		$post = $this->post_for_read( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		// ?from パラメータの検証。
		$from_raw  = $request->get_param( 'from' );
		$from      = '';
		if ( is_string( $from_raw ) && '' !== $from_raw ) {
			$from = sanitize_text_field( wp_unslash( $from_raw ) );
			if ( false === strtotime( $from ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'from parameter must be a valid ISO 8601 date.', 'agent-neo-core' ),
					array( 'field' => 'from' )
				);
			}
		}

		// 現在のブロック構造（配列表現）を取得する。
		$current_blocks = $this->blocks_as_array( $post->post_content );

		// baseline の決定: from 指定ありの場合は最近傍 revision を探す。
		$baseline        = array();
		$baseline_source = 'empty';

		if ( '' !== $from ) {
			$revision_result = $this->find_nearest_revision_blocks( $post_id, $from );
			if ( is_wp_error( $revision_result ) ) {
				return $revision_result;
			}
			if ( null !== $revision_result ) {
				$baseline        = $revision_result['blocks'];
				$baseline_source = $revision_result['source'];
			}
		}

		// RFC 6902 diff を算出する。
		$patch = $this->json_patch->diff( $baseline, $current_blocks );

		$data = array(
			'post_id'         => $post_id,
			'from'            => $from,
			'baseline_source' => $baseline_source,
			'patch'           => $patch,
			'patch_count'     => count( $patch ),
		);

		return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $data, $request_id ) );
	}

	/**
	 * GET /posts/{id}/markdown — Gutenberg ブロック JSON → plain markdown 変換。
	 *
	 * vector-friendly な決定的変換のみ実施する。AI 処理は一切含まない。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_post_markdown( WP_REST_Request $request ) {
		$request_id = $this->resolve_request_id( $request );
		$post_id    = (int) $request['id'];

		$post = $this->post_for_read( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$blocks   = parse_blocks( $post->post_content );
		$markdown = $this->blocks_to_markdown( $blocks );

		$data = array(
			'post_id'  => $post_id,
			'title'    => get_the_title( $post ),
			'markdown' => $markdown,
			'length'   => strlen( $markdown ),
		);

		return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $data, $request_id ) );
	}

	// -------------------------------------------------------------------------
	// 内部ユーティリティ: 投稿取得
	// -------------------------------------------------------------------------

	/**
	 * 読み取り対象 WP_Post を取得する。
	 * 存在しない場合は NOT_FOUND、権限不足は FORBIDDEN を返す。
	 *
	 * @param int $post_id Post id。
	 * @return WP_Post|WP_Error
	 */
	private function post_for_read( int $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return Agent_Neo_Core_Auth::error(
				'NOT_FOUND',
				__( 'Post was not found.', 'agent-neo-core' ),
				array( 'post_id' => $post_id )
			);
		}

		if ( ! current_user_can( 'read_post', $post_id ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Current user cannot read this post.', 'agent-neo-core' ),
				array( 'post_id' => $post_id )
			);
		}

		return $post;
	}

	// -------------------------------------------------------------------------
	// 内部ユーティリティ: フォーマット
	// -------------------------------------------------------------------------

	/**
	 * 一覧用投稿アイテムを指定フィールドで整形する。
	 *
	 * @param WP_Post            $post Post。
	 * @param array<int, string> $fields 返すフィールド名リスト。
	 * @return array<string, mixed>
	 */
	private function format_post_item( WP_Post $post, array $fields ): array {
		$all = array(
			'id'       => $post->ID,
			'title'    => get_the_title( $post ),
			'status'   => $post->post_status,
			'modified' => get_post_modified_time( 'c', true, $post ),
			'link'     => get_permalink( $post ),
		);

		$item = array();
		foreach ( $fields as $field ) {
			if ( array_key_exists( $field, $all ) ) {
				$item[ $field ] = $all[ $field ];
			}
		}

		return $item;
	}

	// -------------------------------------------------------------------------
	// 内部ユーティリティ: ブロックアノテーション
	// -------------------------------------------------------------------------

	/**
	 * ブロック配列に block_id / section_id を付与して返す。
	 *
	 * section_id は H2 見出しブロック（core/heading level=2）を境界として
	 * 0 から始まる連番を各ブロックに割り当てる。
	 *
	 * @param array<int, array<string, mixed>> $blocks parse_blocks() の戻り値。
	 * @return array<int, array<string, mixed>>
	 */
	private function annotate_blocks( array $blocks ): array {
		$section_id = 0;
		$annotated  = array();

		foreach ( $blocks as $index => $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			// H2 見出しで section_id をインクリメントする。
			if ( 'core/heading' === ( $block['blockName'] ?? '' ) ) {
				$level = isset( $block['attrs']['level'] ) ? (int) $block['attrs']['level'] : 2;
				if ( 2 === $level ) {
					$section_id++;
				}
			}

			// block_id の決定: attrs.metadata.id 優先、なければ index ベースの決定的 ID。
			$metadata_id = $block['attrs']['metadata']['id'] ?? '';
			$block_id    = ( is_string( $metadata_id ) && '' !== $metadata_id )
				? $metadata_id
				: 'block-' . $index;

			$annotated[] = array(
				'block_id'   => $block_id,
				'section_id' => $section_id,
				'blockName'  => $block['blockName'] ?? '',
				'attrs'      => $block['attrs'] ?? array(),
				'innerHTML'  => $block['innerHTML'] ?? '',
				'innerBlocks' => isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] )
					? $this->annotate_blocks( $block['innerBlocks'] )
					: array(),
			);
		}

		return $annotated;
	}

	// -------------------------------------------------------------------------
	// 内部ユーティリティ: diff 用ブロック配列化
	// -------------------------------------------------------------------------

	/**
	 * post_content をブロック構造の配列表現に変換する（diff 用）。
	 *
	 * @param string $post_content Post content。
	 * @return array<int, array<string, mixed>>
	 */
	private function blocks_as_array( string $post_content ): array {
		$blocks = parse_blocks( $post_content );
		$result = array();

		foreach ( $blocks as $index => $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$result[] = $this->block_to_serializable( $block, $index );
		}

		return $result;
	}

	/**
	 * ブロックを diff 用シリアライズ可能な配列に変換する（再帰）。
	 *
	 * @param array<string, mixed> $block ブロック配列。
	 * @param int                  $index インデックス。
	 * @return array<string, mixed>
	 */
	private function block_to_serializable( array $block, int $index ): array {
		$metadata_id = $block['attrs']['metadata']['id'] ?? '';
		$block_id    = ( is_string( $metadata_id ) && '' !== $metadata_id )
			? $metadata_id
			: 'block-' . $index;

		$inner = array();
		if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			foreach ( $block['innerBlocks'] as $child_index => $child ) {
				if ( is_array( $child ) ) {
					$inner[] = $this->block_to_serializable( $child, $child_index );
				}
			}
		}

		return array(
			'block_id'    => $block_id,
			'blockName'   => $block['blockName'] ?? '',
			'attrs'       => $block['attrs'] ?? array(),
			'innerHTML'   => $block['innerHTML'] ?? '',
			'innerBlocks' => $inner,
		);
	}

	/**
	 * 指定日時に最も近い post revision のブロック配列を返す。
	 *
	 * revision が存在しない場合は null を返す。
	 *
	 * @param int    $post_id Post id。
	 * @param string $from ISO 8601 日時文字列。
	 * @return array{blocks: array<int, array<string, mixed>>, source: string}|null|WP_Error
	 */
	private function find_nearest_revision_blocks( int $post_id, string $from ) {
		// wp_get_post_revisions は WP コア関数（revision 機能が無効ならば空配列）。
		$revisions = wp_get_post_revisions( $post_id, array( 'order' => 'DESC' ) );
		if ( empty( $revisions ) ) {
			return null;
		}

		$from_ts = strtotime( $from );
		if ( false === $from_ts ) {
			return null;
		}

		$nearest        = null;
		$nearest_diff   = PHP_INT_MAX;
		$nearest_source = '';

		foreach ( $revisions as $revision ) {
			if ( ! $revision instanceof WP_Post ) {
				continue;
			}

			$rev_ts = strtotime( $revision->post_modified_gmt );
			if ( false === $rev_ts ) {
				continue;
			}

			$diff = abs( $rev_ts - $from_ts );
			if ( $diff < $nearest_diff ) {
				$nearest_diff   = $diff;
				$nearest        = $revision;
				$nearest_source = 'revision:' . $revision->ID . ':' . $revision->post_modified_gmt;
			}
		}

		if ( null === $nearest ) {
			return null;
		}

		return array(
			'blocks' => $this->blocks_as_array( $nearest->post_content ),
			'source' => $nearest_source,
		);
	}

	// -------------------------------------------------------------------------
	// 内部ユーティリティ: Markdown 変換（決定的・AI なし）
	// -------------------------------------------------------------------------

	/**
	 * ブロック配列を plain markdown に変換する（再帰）。
	 *
	 * 変換ルール（決定的）:
	 *   core/heading       → # 〜 ###### （level に対応）
	 *   core/paragraph     → テキストそのまま（段落間に空行）
	 *   core/list          → - 項目（unordered） / 1. 項目（ordered）
	 *   core/list-item     → - または 1. に続くテキスト
	 *   core/image         → ![alt](src)
	 *   core/quote         → > テキスト
	 *   core/code          → ``` コードブロック
	 *   core/preformatted  → ``` プリフォーマット
	 *   core/table         → Markdown テーブル（GFM 準拠）
	 *   core/separator     → ---
	 *   その他             → innerHTML の strip_tags テキスト
	 *
	 * @param array<int, array<string, mixed>> $blocks ブロック配列。
	 * @param int                              $depth 再帰深度（インデント用）。
	 * @return string
	 */
	private function blocks_to_markdown( array $blocks, int $depth = 0 ): string {
		$lines = array();

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$name      = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : '';
			$attrs     = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
			$innerHTML = isset( $block['innerHTML'] ) && is_string( $block['innerHTML'] ) ? $block['innerHTML'] : '';
			$inner_blocks = isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ? $block['innerBlocks'] : array();

			$md = $this->block_to_markdown_line( $name, $attrs, $innerHTML, $inner_blocks, $depth );
			if ( '' !== $md ) {
				$lines[] = $md;
			}
		}

		return implode( "\n\n", $lines );
	}

	/**
	 * 単一ブロックを markdown テキストに変換する。
	 *
	 * @param string               $name ブロック名。
	 * @param array<string, mixed> $attrs ブロック属性。
	 * @param string               $innerHTML innerHTML。
	 * @param array<int, mixed>    $inner_blocks innerBlocks。
	 * @param int                  $depth 再帰深度。
	 * @return string
	 */
	private function block_to_markdown_line( string $name, array $attrs, string $innerHTML, array $inner_blocks, int $depth ): string {
		switch ( $name ) {
			case 'core/heading':
				$level  = isset( $attrs['level'] ) ? max( 1, min( 6, (int) $attrs['level'] ) ) : 2;
				$prefix = str_repeat( '#', $level );
				$text   = $this->strip_to_text( $innerHTML );
				return $prefix . ' ' . $text;

			case 'core/paragraph':
				return $this->strip_to_text( $innerHTML );

			case 'core/list':
				$ordered = isset( $attrs['ordered'] ) && $attrs['ordered'];
				$items   = array();
				$counter = 1;
				foreach ( $inner_blocks as $item_block ) {
					if ( ! is_array( $item_block ) ) {
						continue;
					}
					$item_html = isset( $item_block['innerHTML'] ) && is_string( $item_block['innerHTML'] ) ? $item_block['innerHTML'] : '';
					$item_text = $this->strip_to_text( $item_html );
					if ( $ordered ) {
						$items[] = $counter . '. ' . $item_text;
						$counter++;
					} else {
						$items[] = '- ' . $item_text;
					}
				}
				return implode( "\n", $items );

			case 'core/list-item':
				// 単体で来た場合（通常は core/list の innerBlocks として来る）。
				return '- ' . $this->strip_to_text( $innerHTML );

			case 'core/image':
				$src = isset( $attrs['url'] ) && is_string( $attrs['url'] ) ? $attrs['url'] : '';
				$alt = isset( $attrs['alt'] ) && is_string( $attrs['alt'] ) ? $attrs['alt'] : '';
				if ( '' === $src ) {
					// src が attrs にない場合は innerHTML から img src を抽出する。
					$src = $this->extract_img_src( $innerHTML );
				}
				return '![' . $alt . '](' . $src . ')';

			case 'core/quote':
				$inner_md = $this->blocks_to_markdown( $inner_blocks, $depth + 1 );
				if ( '' === $inner_md ) {
					$inner_md = $this->strip_to_text( $innerHTML );
				}
				$quoted = implode( "\n", array_map(
					static function ( string $line ): string {
						return '> ' . $line;
					},
					explode( "\n", $inner_md )
				) );
				return $quoted;

			case 'core/code':
			case 'core/preformatted':
				$code = $this->strip_to_text( $innerHTML );
				$lang = isset( $attrs['language'] ) && is_string( $attrs['language'] ) ? $attrs['language'] : '';
				return '```' . $lang . "\n" . $code . "\n" . '```';

			case 'core/table':
				return $this->table_to_markdown( $innerHTML );

			case 'core/separator':
				return '---';

			case 'core/group':
			case 'core/column':
			case 'core/columns':
				// グループ系は innerBlocks を再帰的に変換する。
				if ( ! empty( $inner_blocks ) ) {
					return $this->blocks_to_markdown( $inner_blocks, $depth );
				}
				return '';

			default:
				// 未知ブロックは innerHTML の plain text を返す。
				$text = $this->strip_to_text( $innerHTML );
				return $text;
		}
	}

	/**
	 * HTML タグを除去してプレーンテキストを返す。
	 *
	 * @param string $html HTML 文字列。
	 * @return string
	 */
	private function strip_to_text( string $html ): string {
		// br タグを改行に変換してからタグを除去する。
		$text = preg_replace( '/<br\s*\/?>/i', "\n", $html );
		$text = is_string( $text ) ? $text : $html;
		$text = wp_strip_all_tags( $text );
		return trim( $text );
	}

	/**
	 * innerHTML から img src を抽出する。
	 *
	 * @param string $html HTML 文字列。
	 * @return string
	 */
	private function extract_img_src( string $html ): string {
		if ( 1 === preg_match( '/src=["\']([^"\']+)["\']/', $html, $matches ) ) {
			return $matches[1];
		}
		return '';
	}

	/**
	 * table HTML を GFM Markdown テーブルに変換する（決定的）。
	 *
	 * @param string $html テーブル HTML。
	 * @return string
	 */
	private function table_to_markdown( string $html ): string {
		// thead の行をヘッダ行として扱う。
		$header_cells = array();
		if ( 1 === preg_match( '/<thead[^>]*>(.*?)<\/thead>/is', $html, $thead_match ) ) {
			$header_cells = $this->extract_cells( $thead_match[1], 'th' );
			if ( empty( $header_cells ) ) {
				$header_cells = $this->extract_cells( $thead_match[1], 'td' );
			}
		}

		// tbody の行を取得する。
		$body_rows = array();
		if ( 1 === preg_match( '/<tbody[^>]*>(.*?)<\/tbody>/is', $html, $tbody_match ) ) {
			preg_match_all( '/<tr[^>]*>(.*?)<\/tr>/is', $tbody_match[1], $row_matches );
			foreach ( $row_matches[1] as $row_html ) {
				$cells = $this->extract_cells( $row_html, 'td' );
				if ( ! empty( $cells ) ) {
					$body_rows[] = $cells;
				}
			}
		}

		// ヘッダが空の場合は body の最初の行をヘッダとして使う。
		if ( empty( $header_cells ) && ! empty( $body_rows ) ) {
			$header_cells = array_shift( $body_rows );
		}

		if ( empty( $header_cells ) ) {
			return $this->strip_to_text( $html );
		}

		$col_count  = count( $header_cells );
		$header_row = '| ' . implode( ' | ', $header_cells ) . ' |';
		$sep_row    = '| ' . implode( ' | ', array_fill( 0, $col_count, '---' ) ) . ' |';

		$md_rows = array( $header_row, $sep_row );
		foreach ( $body_rows as $row ) {
			// 列数を揃える。
			while ( count( $row ) < $col_count ) {
				$row[] = '';
			}
			$row      = array_slice( $row, 0, $col_count );
			$md_rows[] = '| ' . implode( ' | ', $row ) . ' |';
		}

		return implode( "\n", $md_rows );
	}

	/**
	 * HTML 断片からセルテキストを抽出する。
	 *
	 * @param string $html HTML 断片。
	 * @param string $tag タグ名（'th' または 'td'）。
	 * @return array<int, string>
	 */
	private function extract_cells( string $html, string $tag ): array {
		$cells = array();
		preg_match_all( '/<' . $tag . '[^>]*>(.*?)<\/' . $tag . '>/is', $html, $matches );
		foreach ( $matches[1] as $cell_html ) {
			$cells[] = $this->strip_to_text( $cell_html );
		}
		return $cells;
	}

	// -------------------------------------------------------------------------
	// 内部ユーティリティ: 共通
	// -------------------------------------------------------------------------

	/**
	 * X-Request-Id ヘッダを取得する。なければ UUID v4 を生成する。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return string
	 */
	private function resolve_request_id( WP_REST_Request $request ): string {
		$request_id = $request->get_header( 'X-Request-Id' );
		if ( is_string( $request_id ) && '' !== $request_id ) {
			return $request_id;
		}

		return wp_generate_uuid4();
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Posts_Controller( $container->json_patch() );
		$controller->register();
		$container->register_module( 'rest-posts' );
	}
);
