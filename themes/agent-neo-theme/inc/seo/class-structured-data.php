<?php
/**
 * JSON-LD 構造化データ（@graph）出力。
 *
 * REQ-F-018 / SNS連携基盤 — structured data 担当。
 * REQ-NF-025 厳守: 外部 fetch・AI 判定・モデル呼び出し一切なし。
 * WP データから機械生成するのみ。値は事前 sanitize 済み。
 *
 * @graph ノード:
 *   - Organization  (常時)
 *   - WebSite       (常時、SearchAction 付き)
 *   - BlogPosting   (is_singular() 時)
 *   - BreadcrumbList (is_singular() 時、an-breadcrumb と整合)
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * wp_head へ JSON-LD 構造化データを出力するクラス。
 */
final class Agent_Neo_Structured_Data {

	/**
	 * フックを登録する。
	 *
	 * @return void
	 */
	public function register(): void {
		if ( is_admin() ) {
			return;
		}
		// OGP より後（priority 6）に出力。
		add_action( 'wp_head', array( $this, 'output_structured_data' ), 6 );
	}

	/**
	 * JSON-LD @graph を出力する。
	 *
	 * @return void
	 */
	public function output_structured_data(): void {
		if ( is_feed() ) {
			return;
		}

		$graph = $this->build_graph();

		if ( empty( $graph ) ) {
			return;
		}

		$payload = array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);

		$json = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( false === $json ) {
			return;
		}

		echo "\n<script type=\"application/ld+json\">\n";
		// JSON-LD は <script> 内なので esc_html は不要（エスケープすると JSON が壊れる）。
		// wp_json_encode は XSS 対策済み（</ → <\/ に変換）。
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $json;
		echo "\n</script>\n\n";
	}

	/**
	 * @graph ノード配列を組み立てる。
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function build_graph(): array {
		$graph = array();

		$graph[] = $this->build_organization();
		$graph[] = $this->build_website();

		if ( is_singular() ) {
			$blog_posting = $this->build_blog_posting();
			if ( ! empty( $blog_posting ) ) {
				$graph[] = $blog_posting;
			}
			$breadcrumb = $this->build_breadcrumb_list();
			if ( ! empty( $breadcrumb ) ) {
				$graph[] = $breadcrumb;
			}
		}

		return $graph;
	}

	/**
	 * Organization ノードを組み立てる。
	 *
	 * @return array<string, mixed>
	 */
	private function build_organization(): array {
		$site_name = sanitize_text_field( get_bloginfo( 'name' ) );
		$site_url  = esc_url_raw( home_url( '/' ) );

		$org = array(
			'@type' => 'Organization',
			'@id'   => $site_url . '#organization',
			'name'  => $site_name,
			'url'   => $site_url,
		);

		// カスタムロゴをロゴとして設定。
		$logo_url = $this->get_logo_url();
		if ( '' !== $logo_url ) {
			$org['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $logo_url,
			);
		}

		return $org;
	}

	/**
	 * WebSite ノードを組み立てる。
	 * SearchAction（サイト内検索）を potentialAction に含める。
	 *
	 * @return array<string, mixed>
	 */
	private function build_website(): array {
		$site_name = sanitize_text_field( get_bloginfo( 'name' ) );
		$site_url  = esc_url_raw( home_url( '/' ) );

		return array(
			'@type'           => 'WebSite',
			'@id'             => $site_url . '#website',
			'name'            => $site_name,
			'url'             => $site_url,
			'publisher'       => array( '@id' => $site_url . '#organization' ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => esc_url_raw( home_url( '/?s={search_term_string}' ) ),
				),
				'query-input' => 'required name=search_term_string',
			),
		);
	}

	/**
	 * BlogPosting ノードを組み立てる（is_singular() 時のみ呼ぶ）。
	 *
	 * @return array<string, mixed>
	 */
	private function build_blog_posting(): array {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return array();
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return array();
		}

		$site_url    = esc_url_raw( home_url( '/' ) );
		$post_url    = esc_url_raw( (string) get_permalink( $post_id ) );
		$title       = sanitize_text_field( get_the_title( $post_id ) );
		$description = $this->get_post_description( $post );

		// 公開日・更新日を ISO 8601 形式に変換。
		$date_published = mysql2date( 'c', $post->post_date_gmt, false );
		$date_modified  = mysql2date( 'c', $post->post_modified_gmt, false );

		// 著者情報。
		$author_name = sanitize_text_field( get_the_author_meta( 'display_name', (int) $post->post_author ) );

		$node = array(
			'@type'            => 'BlogPosting',
			'@id'              => $post_url . '#article',
			'headline'         => $title,
			'description'      => $description,
			'url'              => $post_url,
			'datePublished'    => (string) $date_published,
			'dateModified'     => (string) $date_modified,
			'author'           => array(
				'@type' => 'Person',
				'name'  => $author_name,
				'url'   => esc_url_raw( get_author_posts_url( (int) $post->post_author ) ),
			),
			'publisher'        => array( '@id' => $site_url . '#organization' ),
			'mainEntityOfPage' => array(
				'@type' => 'WebPage',
				'@id'   => $post_url,
			),
		);

		// アイキャッチ画像。
		if ( has_post_thumbnail( $post_id ) ) {
			$thumbnail = wp_get_attachment_image_src(
				(int) get_post_thumbnail_id( $post_id ),
				'full'
			);
			if ( is_array( $thumbnail ) && isset( $thumbnail[0], $thumbnail[1], $thumbnail[2] ) ) {
				$node['image'] = array(
					'@type'  => 'ImageObject',
					'url'    => esc_url_raw( $thumbnail[0] ),
					'width'  => (int) $thumbnail[1],
					'height' => (int) $thumbnail[2],
				);
			}
		}

		return $node;
	}

	/**
	 * BreadcrumbList ノードを組み立てる（is_singular() 時のみ呼ぶ）。
	 *
	 * single.html の an-breadcrumb と整合:
	 *   Home → カテゴリ（存在する場合） → 投稿
	 *
	 * @return array<string, mixed>
	 */
	private function build_breadcrumb_list(): array {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return array();
		}

		$items = array();

		// 1: Home。
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => __( 'ホーム', 'agent-neo' ),
			'item'     => esc_url_raw( home_url( '/' ) ),
		);

		$position = 1;

		// 2: カテゴリ（投稿タイプが post の場合）。
		if ( is_singular( 'post' ) ) {
			$categories = get_the_category( $post_id );
			if ( ! empty( $categories ) && isset( $categories[0] ) ) {
				$cat      = $categories[0];
				$cat_link = (string) get_category_link( $cat->term_id );
				if ( ! is_wp_error( $cat_link ) ) {
					++$position;
					$items[] = array(
						'@type'    => 'ListItem',
						'position' => $position,
						'name'     => sanitize_text_field( $cat->name ),
						'item'     => esc_url_raw( $cat_link ),
					);
				}
			}
		}

		// 最終: 投稿自身。
		++$position;
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $position,
			'name'     => sanitize_text_field( get_the_title( $post_id ) ),
			'item'     => esc_url_raw( (string) get_permalink( $post_id ) ),
		);

		return array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);
	}

	/**
	 * 投稿の description を取得する。
	 * 抜粋 → 本文先頭 30 語 の順にフォールバック。
	 *
	 * @param WP_Post $post 投稿オブジェクト。
	 * @return string
	 */
	private function get_post_description( WP_Post $post ): string {
		$excerpt = get_the_excerpt( $post );
		if ( '' !== $excerpt ) {
			return sanitize_text_field( $excerpt );
		}

		return sanitize_text_field(
			wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '...' )
		);
	}

	/**
	 * サイトのロゴ URL を返す。
	 *
	 * @return string
	 */
	private function get_logo_url(): string {
		$logo_id = (int) get_theme_mod( 'custom_logo' );
		if ( 0 < $logo_id ) {
			$logo = wp_get_attachment_image_src( $logo_id, 'full' );
			if ( is_array( $logo ) && isset( $logo[0] ) ) {
				return esc_url_raw( $logo[0] );
			}
		}
		return '';
	}
}
