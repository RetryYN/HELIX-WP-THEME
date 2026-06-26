<?php
/**
 * JSON-LD 構造化データ（@graph）出力。
 *
 * REQ-F-018 / SNS連携基盤 — structured data 担当。
 * REQ-NF-025 厳守: 外部 fetch・AI 判定・モデル呼び出し一切なし。
 * WP データから機械生成するのみ。値は事前 sanitize 済み。
 *
 * @graph ノード:
 *   - Organization  (常時): logo（カスタムロゴ）+ sameAs（フィルタ拡張）
 *   - WebSite       (常時)
 *   - BlogPosting   (is_singular('post') 時のみ): author.name フォールバック付き
 *   - WebPage       (is_singular('page') 時): BlogPosting 誤適用を防ぐ
 *   - BreadcrumbList (is_singular() 時、an-breadcrumb と整合)
 *   - Person        (@graph 内 author ノード: is_singular('post') 時)
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

		// WP バージョン露出を除去（m-3）。
		remove_action( 'wp_head', 'wp_generator' );
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

		if ( is_singular( 'post' ) ) {
			// 投稿（post）: BlogPosting + Person（著者）+ BreadcrumbList。
			$person = $this->build_author_person();
			if ( ! empty( $person ) ) {
				$graph[] = $person;
			}
			$blog_posting = $this->build_blog_posting();
			if ( ! empty( $blog_posting ) ) {
				$graph[] = $blog_posting;
			}
			$breadcrumb = $this->build_breadcrumb_list();
			if ( ! empty( $breadcrumb ) ) {
				$graph[] = $breadcrumb;
			}
		} elseif ( is_singular( 'page' ) ) {
			// 固定ページ: WebPage ノード（BlogPosting 誤適用を防ぐ）。
			$web_page = $this->build_web_page();
			if ( ! empty( $web_page ) ) {
				$graph[] = $web_page;
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
	 * logo（カスタムロゴ / ImageObject）と sameAs（フィルタ拡張）を含む。
	 *
	 * sameAs は `apply_filters('agent_neo_organization_same_as', [])` 経由で
	 * 外部から SNS URL 等を注入可能（データソースがない場合は空配列フォールバック）。
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

		// カスタムロゴを ImageObject として設定（IM-4）。
		$logo_url = $this->get_logo_url();
		if ( '' !== $logo_url ) {
			$org['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $logo_url,
			);
		} else {
			// サイトアイコン（favicon）をフォールバックとして試みる。
			$site_icon_id = (int) get_option( 'site_icon' );
			if ( 0 < $site_icon_id ) {
				$icon = wp_get_attachment_image_src( $site_icon_id, 'full' );
				if ( is_array( $icon ) && isset( $icon[0] ) ) {
					$org['logo'] = array(
						'@type' => 'ImageObject',
						'url'   => esc_url_raw( $icon[0] ),
					);
				}
			}
		}

		/**
		 * Organization の sameAs 配列（SNS URL 等）を注入するフィルタ（IM-4）。
		 *
		 * 使い方（子テーマ / プラグイン）:
		 *   add_filter( 'agent_neo_organization_same_as', function( $urls ) {
		 *       $urls[] = 'https://twitter.com/example';
		 *       return $urls;
		 *   } );
		 *
		 * @param string[] $same_as SNS / 公式サイト URL 配列。
		 */
		$same_as = apply_filters( 'agent_neo_organization_same_as', array() );
		if ( is_array( $same_as ) && ! empty( $same_as ) ) {
			$org['sameAs'] = array_values( array_filter( array_map( 'esc_url_raw', $same_as ) ) );
		}

		return $org;
	}

	/**
	 * WebSite ノードを組み立てる。
	 *
	 * SearchAction（サイト内検索）は 2024-11-29 廃止のため除去。
	 *
	 * @return array<string, mixed>
	 */
	private function build_website(): array {
		$site_name = sanitize_text_field( get_bloginfo( 'name' ) );
		$site_url  = esc_url_raw( home_url( '/' ) );

		return array(
			'@type'     => 'WebSite',
			'@id'       => $site_url . '#website',
			'name'      => $site_name,
			'url'       => $site_url,
			'publisher' => array( '@id' => $site_url . '#organization' ),
		);
	}

	/**
	 * 著者 Person ノードを @graph に追加する（IM-5）。
	 *
	 * BlogPosting.author は @id で本ノードを参照する。
	 * jobTitle は WP 標準フィールドなし → user meta `agent_neo_job_title` を参照（拡張点）。
	 *
	 * @return array<string, mixed>
	 */
	private function build_author_person(): array {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return array();
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return array();
		}

		$author_id   = $this->resolve_author_id( (int) $post->post_author );
		$author_name = $this->get_author_name( $author_id );
		if ( '' === $author_name ) {
			return array();
		}

		$author_url = esc_url_raw( get_author_posts_url( $author_id ) );

		$person = array(
			'@type' => 'Person',
			'@id'   => $author_url . '#author',
			'name'  => $author_name,
			'url'   => $author_url,
		);

		// 著者 bio（紹介文）。
		$bio = sanitize_text_field( get_the_author_meta( 'description', $author_id ) );
		if ( '' !== $bio ) {
			$person['description'] = $bio;
		}

		// jobTitle 拡張点: `agent_neo_job_title` ユーザーメタ（IM-5）。
		$job_title = sanitize_text_field( (string) get_user_meta( $author_id, 'agent_neo_job_title', true ) );
		if ( '' !== $job_title ) {
			$person['jobTitle'] = $job_title;
		}

		// sameAs: ユーザープロフィール URL（user_url）があれば追加（IM-5）。
		$same_as    = array();
		$author_website = esc_url_raw( get_the_author_meta( 'user_url', $author_id ) );
		if ( '' !== $author_website ) {
			$same_as[] = $author_website;
		}
		if ( ! empty( $same_as ) ) {
			$person['sameAs'] = $same_as;
		}

		return $person;
	}

	/**
	 * BlogPosting ノードを組み立てる（is_singular('post') 時のみ呼ぶ）。
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

		// 著者情報: @id で Person ノードを参照（IM-5）。
		$author_id   = $this->resolve_author_id( (int) $post->post_author );
		$author_url  = esc_url_raw( get_author_posts_url( $author_id ) );
		$author_name = $this->get_author_name( $author_id );

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
				'@id'   => $author_url . '#author',
				'name'  => $author_name,
				'url'   => $author_url,
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
	 * WebPage ノードを組み立てる（is_singular('page') 時のみ呼ぶ）。
	 *
	 * 固定ページには BlogPosting を出力しない（CR-2）。
	 *
	 * @return array<string, mixed>
	 */
	private function build_web_page(): array {
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

		return array(
			'@type'       => 'WebPage',
			'@id'         => $post_url . '#webpage',
			'name'        => $title,
			'url'         => $post_url,
			'description' => $description,
			'isPartOf'    => array( '@id' => $site_url . '#website' ),
		);
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
	 * エンティティ（[&hellip;] 等）を除去し、プレーンテキストに変換する（IM-7）。
	 * 抜粋 → 本文先頭 30 語 の順にフォールバック。
	 *
	 * @param WP_Post $post 投稿オブジェクト。
	 * @return string
	 */
	private function get_post_description( WP_Post $post ): string {
		$excerpt = get_the_excerpt( $post );
		if ( '' !== $excerpt ) {
			return $this->sanitize_description( $excerpt );
		}

		$trimmed = wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '...' );
		return $this->sanitize_description( $trimmed );
	}

	/**
	 * description 文字列を JSON-LD / OGP 向けにサニタイズする（IM-7）。
	 * HTML タグ除去 → エンティティデコード → 空白正規化。
	 *
	 * @param string $text 生テキスト（抜粋・本文先頭等）。
	 * @return string
	 */
	private function sanitize_description( string $text ): string {
		$text = wp_strip_all_tags( $text );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		// [...] 等の読み取り省略マーカー除去。
		$text = preg_replace( '/\[[\w&;… .]+\]/', '', (string) $text );
		$text = sanitize_text_field( $text );
		return (string) $text;
	}

	/**
	 * 著者名を取得する。display_name が空なら nicename → login にフォールバック（CR-1）。
	 *
	 * @param int $author_id 著者ユーザー ID。
	 * @return string 必ず非空の著者名（ユーザーが存在する場合）。
	 */
	/**
	 * 著者 ID を解決する。post_author=0 や存在しないユーザーの場合は
	 * 最初の管理者にフォールバックする（CR-1: Article author.name 必須対策）。
	 *
	 * @param int $author_id 投稿の post_author 値。
	 * @return int 有効な著者ユーザー ID（解決不能時は元の値）。
	 */
	private function resolve_author_id( int $author_id ): int {
		if ( 0 < $author_id && false !== get_userdata( $author_id ) ) {
			return $author_id;
		}

		$admins = get_users(
			array(
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ID',
			)
		);
		if ( ! empty( $admins ) ) {
			return (int) $admins[0];
		}

		return $author_id;
	}

	/**
	 * 著者名を取得する。display_name が空なら nicename → login にフォールバック（CR-1）。
	 *
	 * @param int $author_id 著者ユーザー ID。
	 * @return string 必ず非空の著者名（ユーザーが存在する場合）。
	 */
	private function get_author_name( int $author_id ): string {
		$name = sanitize_text_field( get_the_author_meta( 'display_name', $author_id ) );
		if ( '' !== $name ) {
			return $name;
		}

		// nicename フォールバック。
		$name = sanitize_text_field( get_the_author_meta( 'user_nicename', $author_id ) );
		if ( '' !== $name ) {
			return $name;
		}

		// login フォールバック（最終手段）。
		return sanitize_text_field( get_the_author_meta( 'login', $author_id ) );
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
