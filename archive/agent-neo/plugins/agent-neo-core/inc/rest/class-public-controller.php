<?php
/**
 * 公開 API コントローラー（認証不要）。
 *
 * GET /public/pages/{id}/snapshot
 * GET /public/crawl-map
 * GET /public/llmo/answers
 *
 * carry-026 公開レスポンス原則: 内部 ID を opaque 変換し、
 * variant_id / 内部 slug を完全除外。非公開投稿は除外する。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 公開向け AI Snapshot / Crawl Map / LLMO answers endpoint。
 *
 * REQ-NF-025: AI ロジック禁止。WP コンテンツの read と
 * 決定的な構造抽出（section/JSON-LD/canonical/robots）のみ。
 */
final class Agent_Neo_Core_Public_Controller extends Agent_Neo_Core_REST_Controller_Base {

	/**
	 * carry-026: opaque 変換で使用する HMAC の先頭桁数。
	 */
	private const OPAQUE_ID_LENGTH = 16;

	/**
	 * 公開投稿ステータス。パスワード保護・下書き等を除外する。
	 */
	private const ALLOWED_POST_STATUS = 'publish';

	/**
	 * section / block 情報を格納する post meta キー。
	 * SEO controller と同じ meta を参照し、独立した JSON-LD を取得する。
	 */
	private const SEO_META_KEY = '_agent_neo_seo_meta';

	/**
	 * クロールマップのキャッシュ TTL（秒）。
	 */
	private const CRAWL_MAP_CACHE_TTL = 300;

	/**
	 * crawl-map / llmo/answers の 1 リクエストあたり最大返却件数。
	 * OOM / DoS 対策として絶対上限を設ける。
	 */
	private const MAX_POSTS_PER_PAGE = 500;

	/**
	 * rest_api_init に route 登録を接続する。
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * 公開 API の 3 ルートを登録する。
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET /public/pages/{id}/snapshot
		$this->register_agent_route(
			'/public/pages/(?P<id>\d+)/snapshot',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_page_snapshot' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// GET /public/crawl-map
		$this->register_agent_route(
			'/public/crawl-map',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_crawl_map' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'limit' => array(
						'required'          => false,
						'type'              => 'integer',
						'minimum'           => 1,
						'maximum'           => self::MAX_POSTS_PER_PAGE,
						'default'           => self::MAX_POSTS_PER_PAGE,
						'sanitize_callback' => 'absint',
						'description'       => '返却する最大エントリ数（上限 500）。',
					),
					'page'  => array(
						'required'          => false,
						'type'              => 'integer',
						'minimum'           => 1,
						'default'           => 1,
						'sanitize_callback' => 'absint',
						'description'       => 'ページ番号（1 始まり）。',
					),
				),
			)
		);

		// GET /public/llmo/answers
		$this->register_agent_route(
			'/public/llmo/answers',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_llmo_answers' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'page_id' => array(
						'required'          => false,
						'type'              => 'integer',
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
						'description'       => '指定ページのみを対象にする（省略時はサイト全体）。',
					),
					'limit'   => array(
						'required'          => false,
						'type'              => 'integer',
						'minimum'           => 1,
						'maximum'           => self::MAX_POSTS_PER_PAGE,
						'default'           => self::MAX_POSTS_PER_PAGE,
						'sanitize_callback' => 'absint',
						'description'       => '対象投稿数の上限（上限 500）。page_id 指定時は無視される。',
					),
					'page'    => array(
						'required'          => false,
						'type'              => 'integer',
						'minimum'           => 1,
						'default'           => 1,
						'sanitize_callback' => 'absint',
						'description'       => 'ページ番号（1 始まり）。page_id 指定時は無視される。',
					),
				),
			)
		);
	}

	/**
	 * GET /public/pages/{id}/snapshot
	 *
	 * 公開済み（publish / パスワードなし）のページのみ返す。
	 * draft / private / password-protected は 404。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_page_snapshot( WP_REST_Request $request ) {
		$request_id = $this->resolve_request_id( $request );
		$post_id    = (int) $request['id'];

		// 公開投稿ガード。
		$post = $this->get_public_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		// SEO meta（canonical / noindex / JSON-LD）を取得する。
		$seo = $this->seo_meta_for_post( $post_id, $post );

		// セクション一覧を決定的に抽出する。
		$sections = $this->extract_sections( $post );

		// robots directive を決定する。
		$robots = $seo['noindex'] ? 'noindex,nofollow' : 'index,follow';

		$data = array(
			'post_id'      => $post_id,
			'post_type'    => $post->post_type,
			'canonical'    => $seo['canonical'],
			'robots'       => $robots,
			'json_ld'      => $seo['json_ld'],
			'sections'     => $sections,
			'ctas'         => $this->extract_ctas( $post ),
			'updated_at'   => get_the_modified_date( 'c', $post ),
		);

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $data, $request_id )
		);
	}

	/**
	 * GET /public/crawl-map
	 *
	 * 全公開ページの crawl map を返す。
	 * OOM / DoS 対策として posts_per_page を MAX_POSTS_PER_PAGE で上限クランプする。
	 * `?limit`（最大 500）と `?page` でページングできる。
	 * レスポンスには公開ページ総数 `total` と切り捨てフラグ `truncated` を含める。
	 * 結果は transient にキャッシュし、更新負荷を抑制する（キーにページパラメータを含む）。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_crawl_map( WP_REST_Request $request ) {
		$request_id = $this->resolve_request_id( $request );

		// ?limit / ?page を absint でクランプし、上限を MAX_POSTS_PER_PAGE に制限する。
		$limit = min( absint( $request->get_param( 'limit' ) ?: self::MAX_POSTS_PER_PAGE ), self::MAX_POSTS_PER_PAGE );
		$limit = max( 1, $limit );
		$page  = max( 1, absint( $request->get_param( 'page' ) ?: 1 ) );

		// ページパラメータを含めたキャッシュキーを生成する。
		$cache_key   = sprintf( 'agent_neo_crawl_map_v2_%d_%d', $limit, $page );
		$cached_data = get_transient( $cache_key );

		if ( is_array( $cached_data ) ) {
			return rest_ensure_response(
				Agent_Neo_Core_Auth::success_response( $cached_data, $request_id )
			);
		}

		// 公開済み全 post_type を対象にする。
		$post_types = get_post_types( array( 'public' => true ), 'names' );

		// no_found_rows=false にして公開ページ総数（total）を取得する。
		$query = new WP_Query(
			array(
				'post_type'      => array_values( $post_types ),
				'post_status'    => self::ALLOWED_POST_STATUS,
				'posts_per_page' => $limit,
				'paged'          => $page,
				'has_password'   => false,
				'no_found_rows'  => false,
				'fields'         => 'ids',
			)
		);

		// WP_Query が返す found_posts は has_password フィルタ前の数になる場合があるため、
		// パスワード保護を別途除外した上で実際の返却件数を確定する。
		$total_found = (int) $query->found_posts;

		$entries = array();
		foreach ( $query->posts as $post_id ) {
			$post_id = (int) $post_id;
			$post    = get_post( $post_id );
			if ( ! $post instanceof WP_Post ) {
				continue;
			}

			// パスワード保護を二重チェックする（WP_Query の has_password フィルタが
			// バージョンによって挙動が異なるため、確実に除外する）。
			if ( '' !== $post->post_password ) {
				continue;
			}

			$seo          = $this->seo_meta_for_post( $post_id, $post );
			$section_list = $this->extract_sections( $post );
			$robots       = $seo['noindex'] ? 'noindex,nofollow' : 'index,follow';

			$entries[] = array(
				'post_id'       => $post_id,
				'canonical'     => $seo['canonical'],
				'robots'        => $robots,
				'updated_at'    => get_the_modified_date( 'c', $post ),
				'section_count' => count( $section_list ),
				'content_type'  => $post->post_type,
				'sections'      => $section_list,
			);
		}

		// truncated: 公開ページ総数が今回の返却件数（limit × page 分）より多い場合 true。
		$truncated = $total_found > ( $limit * $page );

		$data = array(
			'entries'   => $entries,
			'total'     => $total_found,
			'page'      => $page,
			'limit'     => $limit,
			'truncated' => $truncated,
		);

		set_transient( $cache_key, $data, self::CRAWL_MAP_CACHE_TTL );

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $data, $request_id )
		);
	}

	/**
	 * GET /public/llmo/answers
	 *
	 * answer unit / citation anchor / evidence graph を返す。
	 * 任意 `?page_id=<id>` でページ単位フィルタ（省略時はサイト全体）。
	 * LLM 呼び出し禁止（REQ-NF-025）: 見出し構造・既存メタからの決定的導出のみ。
	 *
	 * OOM / DoS 対策として省略時（サイト全体）は posts_per_page を
	 * MAX_POSTS_PER_PAGE で上限クランプし、truncated フラグを返す。
	 * `?limit`（最大 500）と `?page` でページングできる（page_id 指定時は無視）。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_llmo_answers( WP_REST_Request $request ) {
		$request_id = $this->resolve_request_id( $request );
		$page_id    = isset( $request['page_id'] ) ? (int) $request['page_id'] : 0;

		if ( $page_id > 0 ) {
			// ページ単位フィルタ: 公開済みのみ。軽量（上限クランプ不要）。
			$post = $this->get_public_post( $page_id );
			if ( is_wp_error( $post ) ) {
				return $post;
			}

			$units = $this->build_answer_units_for_post( $page_id, $post );
			$data  = array(
				'page_id'      => $page_id,
				'answer_units' => $units,
				'total'        => count( $units ),
				'truncated'    => false,
			);
		} else {
			// サイト全体: OOM / DoS 対策として posts_per_page を上限クランプする。
			$limit = min( absint( $request->get_param( 'limit' ) ?: self::MAX_POSTS_PER_PAGE ), self::MAX_POSTS_PER_PAGE );
			$limit = max( 1, $limit );
			$page  = max( 1, absint( $request->get_param( 'page' ) ?: 1 ) );

			$post_types = get_post_types( array( 'public' => true ), 'names' );

			// no_found_rows=false にして公開ページ総数を取得し、truncated 判定に使う。
			$query = new WP_Query(
				array(
					'post_type'      => array_values( $post_types ),
					'post_status'    => self::ALLOWED_POST_STATUS,
					'posts_per_page' => $limit,
					'paged'          => $page,
					'has_password'   => false,
					'no_found_rows'  => false,
					'fields'         => 'ids',
				)
			);

			$total_found = (int) $query->found_posts;

			$all_units = array();
			foreach ( $query->posts as $post_id ) {
				$post_id = (int) $post_id;
				$post    = get_post( $post_id );
				if ( ! $post instanceof WP_Post || '' !== $post->post_password ) {
					continue;
				}

				$units = $this->build_answer_units_for_post( $post_id, $post );
				foreach ( $units as $unit ) {
					$all_units[] = $unit;
				}
			}

			// truncated: 公開ページ総数が今回の返却件数（limit × page 分）より多い場合 true。
			$truncated = $total_found > ( $limit * $page );

			$data = array(
				'answer_units' => $all_units,
				'total'        => $total_found,
				'page'         => $page,
				'limit'        => $limit,
				'truncated'    => $truncated,
			);
		}

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $data, $request_id )
		);
	}

	// =========================================================================
	// プライベートヘルパー
	// =========================================================================

	/**
	 * 公開済み（publish / パスワードなし）の投稿を返す。
	 * それ以外は NOT_FOUND エラーを返す（draft/private 存在の情報漏洩防止）。
	 *
	 * @param int $post_id Post id。
	 * @return WP_Post|WP_Error
	 */
	private function get_public_post( int $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return Agent_Neo_Core_Auth::error(
				'NOT_FOUND',
				__( 'Page was not found.', 'agent-neo-core' )
			);
		}

		// post_status が publish 以外（draft / private / future 等）は 404。
		if ( self::ALLOWED_POST_STATUS !== $post->post_status ) {
			return Agent_Neo_Core_Auth::error(
				'NOT_FOUND',
				__( 'Page was not found.', 'agent-neo-core' )
			);
		}

		// パスワード保護は除外する。
		if ( '' !== $post->post_password ) {
			return Agent_Neo_Core_Auth::error(
				'NOT_FOUND',
				__( 'Page was not found.', 'agent-neo-core' )
			);
		}

		return $post;
	}

	/**
	 * SEO meta（canonical / noindex / JSON-LD）を取得する。
	 * class-seo-controller.php と同じ meta key を参照し読み取り専用で利用する。
	 *
	 * @param int     $post_id Post id。
	 * @param WP_Post $post    Post。
	 * @return array<string, mixed>
	 */
	private function seo_meta_for_post( int $post_id, WP_Post $post ): array {
		$raw = get_post_meta( $post_id, self::SEO_META_KEY, true );

		$stored = array();
		if ( is_string( $raw ) && '' !== $raw ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				$stored = $decoded;
			}
		} elseif ( is_array( $raw ) ) {
			$stored = $raw;
		}

		$canonical = isset( $stored['canonical'] ) && is_string( $stored['canonical'] ) && '' !== $stored['canonical']
			? $stored['canonical']
			: (string) get_permalink( $post );

		$noindex = isset( $stored['noindex'] ) ? (bool) $stored['noindex'] : false;
		$json_ld = isset( $stored['json_ld'] ) && is_array( $stored['json_ld'] ) ? $stored['json_ld'] : array();

		return array(
			'canonical' => $canonical,
			'noindex'   => $noindex,
			'json_ld'   => $json_ld,
		);
	}

	/**
	 * 投稿コンテンツから H2/H3 見出しを section として抽出し、
	 * carry-026 に従い section_id_public（opaque）を付与する。
	 * 内部 section_id は露出しない。variant_id も含めない。
	 *
	 * 見出し抽出のみが目的のため、ショートコード完全展開が必要な do_blocks() ではなく
	 * parse_blocks() から heading ブロック属性を直接読み取る軽量実装を採用する。
	 * core/heading ブロック以外（HTML 直書き等）はフォールバックとして
	 * ブロックの innerHTML から正規表現で抽出する。
	 *
	 * @param WP_Post $post Post。
	 * @return array<int, array<string, mixed>>
	 */
	private function extract_sections( WP_Post $post ): array {
		$content = $post->post_content;
		if ( '' === $content ) {
			return array();
		}

		// parse_blocks() でブロック構造を取得する（do_blocks より大幅に軽量）。
		$blocks   = parse_blocks( $content );
		$sections = array();
		$index    = 0;

		foreach ( $blocks as $block ) {
			// core/heading ブロック: attrs['level'] で H2/H3 を判定する。
			if ( 'core/heading' === $block['blockName'] ) {
				$level = isset( $block['attrs']['level'] ) ? (int) $block['attrs']['level'] : 2;
				if ( ! in_array( $level, array( 2, 3 ), true ) ) {
					continue;
				}

				// innerHTML からテキストを取り出す（タグ除去）。
				$raw_text = wp_strip_all_tags( $block['innerHTML'] ?? '' );
				$text     = trim( html_entity_decode( $raw_text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
				if ( '' === $text ) {
					continue;
				}
			} else {
				// core/heading 以外のブロック（カスタムブロック / クラシックブロック等）:
				// innerHTML に含まれる <h2> / <h3> を正規表現でフォールバック抽出する。
				$inner_html = $block['innerHTML'] ?? '';
				if ( '' === $inner_html ) {
					// ネストブロックの innerContent を連結して検索する。
					$inner_html = implode( '', $block['innerContent'] ?? array() );
				}

				if ( '' === $inner_html ) {
					continue;
				}

				// この block に H2/H3 が含まれているか確認し、あれば全件処理する。
				if ( ! preg_match( '/<h[23][^>]*>/i', $inner_html ) ) {
					continue;
				}

				preg_match_all(
					'/<h([23])[^>]*>(.*?)<\/h\1>/is',
					$inner_html,
					$h_matches,
					PREG_SET_ORDER
				);

				foreach ( $h_matches as $h_match ) {
					$level    = (int) $h_match[1];
					$raw_text = wp_strip_all_tags( $h_match[2] );
					$text     = trim( html_entity_decode( $raw_text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
					if ( '' === $text ) {
						continue;
					}

					$internal_seed     = sprintf( 'section::%d::%d::%s', $post->ID, $index, $text );
					$section_id_public = $this->make_opaque_id( $internal_seed );

					$sections[] = array(
						'section_id_public' => $section_id_public,
						'level'             => $level,
						'heading'           => $text,
						'index'             => $index,
					);

					++$index;
				}

				// フォールバック処理は上記ループで完結しているため次のブロックへ。
				continue;
			}

			// 決定的な内部識別子（投稿 ID + インデックス + テキスト）から opaque ID を生成する。
			// 内部 section_id は外部に露出しない。
			$internal_seed     = sprintf( 'section::%d::%d::%s', $post->ID, $index, $text );
			$section_id_public = $this->make_opaque_id( $internal_seed );

			$sections[] = array(
				'section_id_public' => $section_id_public,
				'level'             => $level,
				'heading'           => $text,
				'index'             => $index,
			);

			++$index;
		}

		return $sections;
	}

	/**
	 * 投稿コンテンツから CTA 情報を抽出し、
	 * carry-026 に従い cta_id_public（opaque）を付与する。
	 * 内部 cta_id / variant_id は露出しない。
	 *
	 * wp:agent-neo/cta ブロック属性から決定的に導出する。
	 * LLM 呼び出し禁止（REQ-NF-025）。
	 *
	 * @param WP_Post $post Post。
	 * @return array<int, array<string, mixed>>
	 */
	private function extract_ctas( WP_Post $post ): array {
		$content = $post->post_content;
		if ( '' === $content ) {
			return array();
		}

		// Gutenberg block コメントから wp:agent-neo/cta ブロックを抽出する。
		preg_match_all(
			'/<!--\s*wp:agent-neo\/cta\s+(\{[^}]+\})\s*(?:\/-->|-->)/is',
			$content,
			$matches,
			PREG_SET_ORDER
		);

		$ctas  = array();
		$index = 0;

		foreach ( $matches as $match ) {
			$attrs = json_decode( $match[1], true );
			if ( ! is_array( $attrs ) ) {
				continue;
			}

			// label を安全に取得する（内部 ID は取得するが外部には露出しない）。
			$label       = isset( $attrs['label'] ) && is_string( $attrs['label'] ) ? sanitize_text_field( $attrs['label'] ) : '';
			$button_text = isset( $attrs['buttonText'] ) && is_string( $attrs['buttonText'] ) ? sanitize_text_field( $attrs['buttonText'] ) : '';
			$url         = isset( $attrs['url'] ) && is_string( $attrs['url'] ) ? esc_url_raw( $attrs['url'] ) : '';

			// 内部 cta_id を opaque 変換する。carry-026: 内部 cta_id を直接露出しない。
			$internal_cta_seed = sprintf( 'cta::%d::%d::%s', $post->ID, $index, $label );
			$cta_id_public     = $this->make_opaque_id( $internal_cta_seed );

			// carry-026: variant_id はレスポンスから完全除外する。
			$ctas[] = array(
				'cta_id_public' => $cta_id_public,
				'label'         => $label,
				'button_text'   => $button_text,
				'url'           => $url,
				'index'         => $index,
			);

			++$index;
		}

		return $ctas;
	}

	/**
	 * 投稿の見出し構造から LLMO answer unit を決定的に生成する。
	 * LLM 呼び出し禁止（REQ-NF-025）: 見出しテキスト + 段落テキストの決定的導出のみ。
	 *
	 * @param int     $post_id Post id。
	 * @param WP_Post $post    Post。
	 * @return array<int, array<string, mixed>>
	 */
	private function build_answer_units_for_post( int $post_id, WP_Post $post ): array {
		$content = $post->post_content;
		if ( '' === $content ) {
			return array();
		}

		$rendered = do_blocks( $content );

		// H2 単位で answer unit を構築する。
		// 各 H2 の直後にある段落テキストを evidence body とする。
		$dom = new DOMDocument();
		// HTML5 doc として整形（文字化け防止のため UTF-8 メタを付与する）。
		$wrapped = '<?xml encoding="utf-8" ?><div>' . $rendered . '</div>';
		libxml_use_internal_errors( true );
		$dom->loadHTML( $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		$xpath        = new DOMXPath( $dom );
		$h2_nodes     = $xpath->query( '//h2' );
		$answer_units = array();

		if ( $h2_nodes instanceof DOMNodeList ) {
			$h2_index = 0;
			foreach ( $h2_nodes as $h2 ) {
				if ( ! $h2 instanceof DOMElement ) {
					continue;
				}

				$heading = trim( $h2->textContent );
				if ( '' === $heading ) {
					continue;
				}

				// 次の H2 までの段落テキストを evidence body として収集する。
				$body_parts = array();
				$sibling    = $h2->nextSibling;

				while ( null !== $sibling ) {
					if ( $sibling instanceof DOMElement ) {
						// 次の H2 に到達したら停止する。
						if ( 'h2' === strtolower( $sibling->tagName ) ) {
							break;
						}

						// 段落・リスト・blockquote のテキストを収集する。
						$tag = strtolower( $sibling->tagName );
						if ( in_array( $tag, array( 'p', 'ul', 'ol', 'blockquote', 'figure' ), true ) ) {
							$text = trim( wp_strip_all_tags( $sibling->textContent ) );
							if ( '' !== $text ) {
								$body_parts[] = $text;
							}
						}
					}

					$sibling = $sibling->nextSibling;
				}

				// citation anchor（= 公開 URL の fragment 指定）を生成する。
				$canonical       = (string) get_permalink( $post );
				$anchor_id       = 'an-section-' . $h2_index;
				$citation_anchor = rtrim( $canonical, '/' ) . '#' . $anchor_id;

				// evidence graph は見出し→本文テキストの決定的マッピング。
				$evidence_graph = array(
					'post_id'   => $post_id,
					'post_url'  => $canonical,
					'heading'   => $heading,
					'body'      => implode( ' ', $body_parts ),
					'anchor_id' => $anchor_id,
				);

				// section_id_public を付与する。
				$section_seed      = sprintf( 'section::%d::%d::%s', $post_id, $h2_index, $heading );
				$section_id_public = $this->make_opaque_id( $section_seed );

				$answer_units[] = array(
					'section_id_public' => $section_id_public,
					'question'          => $heading,
					'answer_body'       => implode( ' ', $body_parts ),
					'citation_anchor'   => $citation_anchor,
					'evidence_graph'    => $evidence_graph,
					'post_id'           => $post_id,
					'post_type'         => $post->post_type,
				);

				++$h2_index;
			}
		}

		return $answer_units;
	}

	/**
	 * 内部 seed から carry-026 準拠の opaque 公開 ID を生成する。
	 *
	 * 決定的（同じ seed は常に同じ ID）かつ不可逆。
	 * salt は wp_salt() を使用する（サイト固有・セキュアな WP 組み込み salt）。
	 *
	 * @param string $internal_seed 内部識別子。
	 * @return string 先頭 OPAQUE_ID_LENGTH 桁の hex 文字列。
	 */
	private function make_opaque_id( string $internal_seed ): string {
		$salt = wp_salt( 'auth' );
		$hash = hash_hmac( 'sha256', $internal_seed, $salt );
		return substr( $hash, 0, self::OPAQUE_ID_LENGTH );
	}

	/**
	 * X-Request-Id ヘッダを取得し、なければ UUID v4 を生成する。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return string
	 */
	private function resolve_request_id( WP_REST_Request $request ): string {
		$header = $request->get_header( 'X-Request-Id' );
		if ( is_string( $header ) && '' !== $header ) {
			return $header;
		}

		return wp_generate_uuid4();
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Public_Controller();
		$controller->register();
		$container->register_module( 'rest-public' );
	}
);
