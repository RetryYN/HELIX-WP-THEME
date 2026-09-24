<?php
/**
 * POST /affiliate/block controller.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 収益化ブロック生成 endpoint。
 *
 * api-catalog A-005: Amazon Product API 等の提供データから静的にブロック構造を組み立てる。
 * AI による内容判断・文章生成は行わない（REQ-NF-025 厳守）。
 * payload で受け取った構造化データを決定的に静的ブロック構造へ整形して返すのみ。
 * 永続化（DB write）は本 endpoint の責務外。
 */
final class Agent_Neo_Core_Affiliate_Controller extends Agent_Neo_Core_REST_Controller_Base {

	/**
	 * block_type の許容値。
	 *
	 * @var string[]
	 */
	private const ALLOWED_BLOCK_TYPES = array(
		'review',
		'ranking',
		'comparison',
		'affiliate_cta',
		'product_card',
	);

	/**
	 * rest_api_init に route 登録を接続する。
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Routes を登録する。
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->register_agent_route(
			'/affiliate/block',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'generate_block' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);
	}

	/**
	 * Write permission を確認する。
	 *
	 * ログイン済みかつ edit_posts 権限を要求する。
	 * 未ログイン → 401 UNAUTHORIZED、権限不足 → 403 FORBIDDEN。
	 * personal-tier 機能のため package による corporate-only ゲートは不要。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return true|WP_Error
	 */
	public function check_write_permission( WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return Agent_Neo_Core_Auth::error(
				'UNAUTHORIZED',
				__( 'Authentication required for affiliate block generation.', 'agent-neo-core' )
			);
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Insufficient capability for affiliate block generation.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * POST /affiliate/block ハンドラ。
	 *
	 * block_type と payload から決定的にブロック構造を組み立てて返す。
	 * AI 処理・LLM 呼び出し・文章生成は一切行わない（REQ-NF-025）。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function generate_block( WP_REST_Request $request ) {
		$request_id = $request->get_header( 'X-Request-Id' );
		if ( ! is_string( $request_id ) || '' === $request_id ) {
			$request_id = wp_generate_uuid4();
		}

		$params = $request->get_json_params();

		// JSON body 検証。
		if ( ! is_array( $params ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'JSON body is required.', 'agent-neo-core' )
			);
		}

		// block_type バリデーション。
		if ( ! isset( $params['block_type'] ) || ! is_string( $params['block_type'] ) || '' === $params['block_type'] ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'block_type is required.', 'agent-neo-core' ),
				array( 'field' => 'block_type' )
			);
		}

		$block_type = $params['block_type'];

		if ( ! in_array( $block_type, self::ALLOWED_BLOCK_TYPES, true ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'block_type must be one of: review, ranking, comparison, affiliate_cta, product_card.', 'agent-neo-core' ),
				array(
					'field'   => 'block_type',
					'allowed' => self::ALLOWED_BLOCK_TYPES,
				)
			);
		}

		// payload バリデーション。
		if ( ! isset( $params['payload'] ) || ! is_array( $params['payload'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'payload is required and must be an object.', 'agent-neo-core' ),
				array( 'field' => 'payload' )
			);
		}

		$payload = $params['payload'];

		// block_type ごとの最小必須フィールドチェックおよびブロック構造組立。
		$validation = $this->validate_payload( $block_type, $payload );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$block_structure = $this->build_block( $block_type, $payload );

		$data = array(
			'block_type' => $block_type,
			'block'      => $block_structure,
		);

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $data, $request_id )
		);
	}

	/**
	 * block_type ごとに payload の最小必須フィールドを検証する。
	 *
	 * @param string               $block_type Block type。
	 * @param array<string, mixed> $payload Payload。
	 * @return true|WP_Error
	 */
	private function validate_payload( string $block_type, array $payload ) {
		switch ( $block_type ) {
			case 'review':
				// 最小: title（文字列・必須）, rating（数値 0-5・必須）。
				if ( ! isset( $payload['title'] ) || ! is_string( $payload['title'] ) || '' === trim( $payload['title'] ) ) {
					return Agent_Neo_Core_Auth::error(
						'VALIDATION_ERROR',
						__( 'payload.title is required for review block.', 'agent-neo-core' ),
						array( 'field' => 'payload.title' )
					);
				}
				// rating は必須。欠落時は 400 を返す。
				if ( ! isset( $payload['rating'] ) ) {
					return Agent_Neo_Core_Auth::error(
						'VALIDATION_ERROR',
						__( 'payload.rating is required for review block.', 'agent-neo-core' ),
						array( 'field' => 'payload.rating' )
					);
				}
				// rating は数値かつ 0〜5 の範囲でなければならない。
				if ( ! is_numeric( $payload['rating'] ) || $payload['rating'] < 0 || $payload['rating'] > 5 ) {
					return Agent_Neo_Core_Auth::error(
						'VALIDATION_ERROR',
						__( 'payload.rating must be a number between 0 and 5.', 'agent-neo-core' ),
						array( 'field' => 'payload.rating' )
					);
				}
				break;

			case 'ranking':
				// 最小: items（配列）。
				if ( ! isset( $payload['items'] ) || ! is_array( $payload['items'] ) || count( $payload['items'] ) === 0 ) {
					return Agent_Neo_Core_Auth::error(
						'VALIDATION_ERROR',
						__( 'payload.items is required and must be a non-empty array for ranking block.', 'agent-neo-core' ),
						array( 'field' => 'payload.items' )
					);
				}
				break;

			case 'comparison':
				// 最小: items（配列、最低 2 件）。
				if ( ! isset( $payload['items'] ) || ! is_array( $payload['items'] ) || count( $payload['items'] ) < 2 ) {
					return Agent_Neo_Core_Auth::error(
						'VALIDATION_ERROR',
						__( 'payload.items must have at least 2 entries for comparison block.', 'agent-neo-core' ),
						array( 'field' => 'payload.items' )
					);
				}
				break;

			case 'affiliate_cta':
				// 最小: label（文字列）, url（文字列）。
				if ( ! isset( $payload['label'] ) || ! is_string( $payload['label'] ) || '' === trim( $payload['label'] ) ) {
					return Agent_Neo_Core_Auth::error(
						'VALIDATION_ERROR',
						__( 'payload.label is required for affiliate_cta block.', 'agent-neo-core' ),
						array( 'field' => 'payload.label' )
					);
				}
				if ( ! isset( $payload['url'] ) || ! is_string( $payload['url'] ) || '' === trim( $payload['url'] ) ) {
					return Agent_Neo_Core_Auth::error(
						'VALIDATION_ERROR',
						__( 'payload.url is required for affiliate_cta block.', 'agent-neo-core' ),
						array( 'field' => 'payload.url' )
					);
				}
				break;

			case 'product_card':
				// 最小: items（配列）。
				if ( ! isset( $payload['items'] ) || ! is_array( $payload['items'] ) || count( $payload['items'] ) === 0 ) {
					return Agent_Neo_Core_Auth::error(
						'VALIDATION_ERROR',
						__( 'payload.items is required and must be a non-empty array for product_card block.', 'agent-neo-core' ),
						array( 'field' => 'payload.items' )
					);
				}
				break;
		}

		return true;
	}

	/**
	 * block_type と payload から Gutenberg ブロック構造を決定的に組み立てる。
	 *
	 * 外部入力は esc_html / esc_url で必ずサニタイズする（XSS 防止）。
	 * AI・LLM・文章生成は一切行わない（REQ-NF-025）。
	 *
	 * @param string               $block_type Block type。
	 * @param array<string, mixed> $payload Payload。
	 * @return array<string, mixed> Gutenberg ブロック構造。
	 */
	private function build_block( string $block_type, array $payload ): array {
		switch ( $block_type ) {
			case 'review':
				return $this->build_review_block( $payload );
			case 'ranking':
				return $this->build_ranking_block( $payload );
			case 'comparison':
				return $this->build_comparison_block( $payload );
			case 'affiliate_cta':
				return $this->build_affiliate_cta_block( $payload );
			case 'product_card':
				return $this->build_product_card_block( $payload );
		}

		// validate_payload で事前に弾くため、ここには到達しない。
		return array();
	}

	/**
	 * review ブロック構造を組み立てる。
	 *
	 * payload: title（商品名・必須）, rating（0-5 数値・必須）, pros（文字列配列）, cons（文字列配列）, summary（文字列）。
	 * validate_payload() で title・rating の必須チェックと rating の範囲チェックを通過済みであることを前提とする。
	 *
	 * @param array<string, mixed> $payload Payload。
	 * @return array<string, mixed>
	 */
	private function build_review_block( array $payload ): array {
		$title   = esc_html( (string) ( $payload['title'] ?? '' ) );
		// validate_payload() で rating の存在と範囲（0-5）を検証済みのため、ここへの到達時は必ず有効な値が存在する。
		$rating  = isset( $payload['rating'] ) ? (float) $payload['rating'] : 0.0;
		$summary = esc_html( (string) ( $payload['summary'] ?? '' ) );

		// pros / cons は文字列配列としてサニタイズ。
		$pros = array();
		if ( isset( $payload['pros'] ) && is_array( $payload['pros'] ) ) {
			foreach ( $payload['pros'] as $pro ) {
				if ( is_string( $pro ) && '' !== trim( $pro ) ) {
					$pros[] = esc_html( $pro );
				}
			}
		}

		$cons = array();
		if ( isset( $payload['cons'] ) && is_array( $payload['cons'] ) ) {
			foreach ( $payload['cons'] as $con ) {
				if ( is_string( $con ) && '' !== trim( $con ) ) {
					$cons[] = esc_html( $con );
				}
			}
		}

		// HTML 組立。
		$pros_html = '';
		foreach ( $pros as $pro ) {
			$pros_html .= '<li>' . $pro . '</li>';
		}

		$cons_html = '';
		foreach ( $cons as $con ) {
			$cons_html .= '<li>' . $con . '</li>';
		}

		$html = sprintf(
			'<div class="agent-neo-review"><h3>%s</h3><div class="agent-neo-review__rating" data-rating="%s">評価: %s / 5</div>',
			$title,
			esc_attr( (string) $rating ),
			esc_html( (string) $rating )
		);

		if ( ! empty( $pros ) ) {
			$html .= '<ul class="agent-neo-review__pros">' . $pros_html . '</ul>';
		}

		if ( ! empty( $cons ) ) {
			$html .= '<ul class="agent-neo-review__cons">' . $cons_html . '</ul>';
		}

		if ( '' !== $summary ) {
			$html .= '<p class="agent-neo-review__summary">' . $summary . '</p>';
		}

		$html .= '</div>';

		return array(
			'blockName'    => 'agent-neo/review',
			'attrs'        => array(
				'blockType' => 'review',
				'title'     => $title,
				'rating'    => $rating,
			),
			'innerBlocks'  => array(),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
		);
	}

	/**
	 * ranking ブロック構造を組み立てる。
	 *
	 * payload: items（配列）。各 item: rank（任意/数値）, name（文字列）, image_url（任意）, affiliate_url（任意）, description（任意）。
	 *
	 * @param array<string, mixed> $payload Payload。
	 * @return array<string, mixed>
	 */
	private function build_ranking_block( array $payload ): array {
		$items    = (array) $payload['items'];
		$rows_html = '';
		$rank      = 1;

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			// 順位は payload 指定 or 連番。
			$display_rank  = isset( $item['rank'] ) && is_numeric( $item['rank'] ) ? (int) $item['rank'] : $rank;
			$name          = esc_html( (string) ( $item['name'] ?? '' ) );
			$description   = esc_html( (string) ( $item['description'] ?? '' ) );
			$image_url     = isset( $item['image_url'] ) && is_string( $item['image_url'] ) ? esc_url( $item['image_url'] ) : '';
			$affiliate_url = isset( $item['affiliate_url'] ) && is_string( $item['affiliate_url'] ) ? esc_url( $item['affiliate_url'] ) : '';

			$img_html  = '' !== $image_url ? '<img src="' . $image_url . '" alt="' . $name . '" class="agent-neo-ranking__img" />' : '';
			$link_html = '' !== $affiliate_url
				? '<a href="' . $affiliate_url . '" class="agent-neo-ranking__link" rel="nofollow noopener" target="_blank">' . $name . '</a>'
				: '<span class="agent-neo-ranking__name">' . $name . '</span>';

			$rows_html .= sprintf(
				'<tr class="agent-neo-ranking__row"><td class="agent-neo-ranking__rank">%d</td><td class="agent-neo-ranking__product">%s%s</td><td class="agent-neo-ranking__desc">%s</td></tr>',
				$display_rank,
				$img_html,
				$link_html,
				$description
			);

			++$rank;
		}

		$html = '<div class="agent-neo-ranking"><table class="agent-neo-ranking__table"><thead><tr><th>順位</th><th>商品</th><th>説明</th></tr></thead><tbody>' . $rows_html . '</tbody></table></div>';

		return array(
			'blockName'    => 'agent-neo/ranking',
			'attrs'        => array(
				'blockType'  => 'ranking',
				'item_count' => count( $items ),
			),
			'innerBlocks'  => array(),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
		);
	}

	/**
	 * comparison ブロック構造を組み立てる。
	 *
	 * payload: items（配列、最低 2 件）。各 item: name（文字列）, specs（連想配列）, affiliate_url（任意）, image_url（任意）。
	 * specs キーを列ヘッダとして横比較テーブルを組み立てる。
	 *
	 * @param array<string, mixed> $payload Payload。
	 * @return array<string, mixed>
	 */
	private function build_comparison_block( array $payload ): array {
		$items = (array) $payload['items'];

		// 全 item の specs キーを収集して列ヘッダを確定する。
		$spec_keys = array();
		foreach ( $items as $item ) {
			if ( is_array( $item ) && isset( $item['specs'] ) && is_array( $item['specs'] ) ) {
				foreach ( array_keys( $item['specs'] ) as $key ) {
					if ( ! in_array( $key, $spec_keys, true ) ) {
						$spec_keys[] = $key;
					}
				}
			}
		}

		// ヘッダ行。
		$header_html = '<th></th>';
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$name         = esc_html( (string) ( $item['name'] ?? '' ) );
			$image_url    = isset( $item['image_url'] ) && is_string( $item['image_url'] ) ? esc_url( $item['image_url'] ) : '';
			$affl_url     = isset( $item['affiliate_url'] ) && is_string( $item['affiliate_url'] ) ? esc_url( $item['affiliate_url'] ) : '';
			$img_html     = '' !== $image_url ? '<img src="' . $image_url . '" alt="' . $name . '" class="agent-neo-comparison__img" />' : '';
			$name_html    = '' !== $affl_url
				? '<a href="' . $affl_url . '" rel="nofollow noopener" target="_blank">' . $name . '</a>'
				: $name;
			$header_html .= '<th>' . $img_html . $name_html . '</th>';
		}

		// spec 行。
		$rows_html = '';
		foreach ( $spec_keys as $key ) {
			$row_html  = '<td class="agent-neo-comparison__spec-label">' . esc_html( $key ) . '</td>';
			foreach ( $items as $item ) {
				if ( ! is_array( $item ) ) {
					$row_html .= '<td>-</td>';
					continue;
				}
				$val       = isset( $item['specs'][ $key ] ) ? esc_html( (string) $item['specs'][ $key ] ) : '-';
				$row_html .= '<td>' . $val . '</td>';
			}
			$rows_html .= '<tr>' . $row_html . '</tr>';
		}

		$html = '<div class="agent-neo-comparison"><table class="agent-neo-comparison__table"><thead><tr>' . $header_html . '</tr></thead><tbody>' . $rows_html . '</tbody></table></div>';

		return array(
			'blockName'    => 'agent-neo/comparison',
			'attrs'        => array(
				'blockType'  => 'comparison',
				'item_count' => count( $items ),
				'spec_keys'  => $spec_keys,
			),
			'innerBlocks'  => array(),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
		);
	}

	/**
	 * affiliate_cta ブロック構造を組み立てる。
	 *
	 * payload: label（文字列）, url（文字列）, description（任意）, image_url（任意）。
	 *
	 * @param array<string, mixed> $payload Payload。
	 * @return array<string, mixed>
	 */
	private function build_affiliate_cta_block( array $payload ): array {
		$label       = esc_html( (string) $payload['label'] );
		$url         = esc_url( (string) $payload['url'] );
		$description = esc_html( (string) ( $payload['description'] ?? '' ) );
		$image_url   = isset( $payload['image_url'] ) && is_string( $payload['image_url'] ) ? esc_url( $payload['image_url'] ) : '';

		$img_html  = '' !== $image_url ? '<img src="' . $image_url . '" alt="' . $label . '" class="agent-neo-cta__img" />' : '';
		$desc_html = '' !== $description ? '<p class="agent-neo-cta__description">' . $description . '</p>' : '';

		$html = sprintf(
			'<div class="agent-neo-cta">%s%s<a href="%s" class="agent-neo-cta__button" rel="nofollow noopener" target="_blank">%s</a></div>',
			$img_html,
			$desc_html,
			$url,
			$label
		);

		return array(
			'blockName'    => 'agent-neo/affiliate-cta',
			'attrs'        => array(
				'blockType' => 'affiliate_cta',
				'label'     => $label,
				'url'       => $url,
			),
			'innerBlocks'  => array(),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
		);
	}

	/**
	 * product_card ブロック構造を組み立てる。
	 *
	 * payload: items（配列）。各 item: name（文字列）, price（任意/文字列）, image_url（任意）, affiliate_url（任意）, description（任意）。
	 *
	 * @param array<string, mixed> $payload Payload。
	 * @return array<string, mixed>
	 */
	private function build_product_card_block( array $payload ): array {
		$items      = (array) $payload['items'];
		$cards_html = '';

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$name          = esc_html( (string) ( $item['name'] ?? '' ) );
			$price         = esc_html( (string) ( $item['price'] ?? '' ) );
			$description   = esc_html( (string) ( $item['description'] ?? '' ) );
			$image_url     = isset( $item['image_url'] ) && is_string( $item['image_url'] ) ? esc_url( $item['image_url'] ) : '';
			$affiliate_url = isset( $item['affiliate_url'] ) && is_string( $item['affiliate_url'] ) ? esc_url( $item['affiliate_url'] ) : '';

			$img_html   = '' !== $image_url ? '<img src="' . $image_url . '" alt="' . $name . '" class="agent-neo-product-card__img" />' : '';
			$price_html = '' !== $price ? '<p class="agent-neo-product-card__price">' . $price . '</p>' : '';
			$desc_html  = '' !== $description ? '<p class="agent-neo-product-card__description">' . $description . '</p>' : '';
			$link_html  = '' !== $affiliate_url
				? '<a href="' . $affiliate_url . '" class="agent-neo-product-card__link" rel="nofollow noopener" target="_blank">詳細・購入はこちら</a>'
				: '';

			$cards_html .= sprintf(
				'<div class="agent-neo-product-card">%s<h4 class="agent-neo-product-card__name">%s</h4>%s%s%s</div>',
				$img_html,
				$name,
				$price_html,
				$desc_html,
				$link_html
			);
		}

		$html = '<div class="agent-neo-product-card-list">' . $cards_html . '</div>';

		return array(
			'blockName'    => 'agent-neo/product-card',
			'attrs'        => array(
				'blockType'  => 'product_card',
				'item_count' => count( $items ),
			),
			'innerBlocks'  => array(),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
		);
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Affiliate_Controller();
		$controller->register();
		$container->register_module( 'rest-affiliate' );
	}
);
