<?php
/**
 * OGP・Twitter Card・meta description・noindex・canonical 出力。
 *
 * REQ-F-018 / SNS連携基盤 — head meta 担当。
 * REQ-NF-025 厳守: 外部 fetch・AI 判定・モデル呼び出し一切なし。
 * WP 投稿データ・サイト設定・投稿メタから静的にメタを出力するのみ。
 *
 * automation SEO 由来の上書きメタを優先し、ない場合は WP ネイティブにフォールバックする。
 *
 * 出力するメタ:
 *   - <meta name="description">       (IM-1: 全ページ)
 *   - <meta name="robots" noindex>    (IM-2: 著者/日付/404)
 *   - <link rel="canonical">          (IM-3: 非 singular)
 *   - OGP (og:title/description/image/url/type/site_name/locale)
 *   - Twitter Card
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * wp_head へ OGP / Twitter Card / meta description / canonical を出力するクラス。
 */
final class Agent_Neo_Head_Meta {

	/**
	 * フックを登録する。
	 *
	 * @return void
	 */
	public function register(): void {
		// 管理画面・フィードでは出力しない。
		if ( is_admin() ) {
			return;
		}
		// wp_head priority 5 で出力（consent default の priority 1 より後）。
		add_action( 'wp_head', array( $this, 'output_meta_tags' ), 5 );

		// IM-2: 著者・日付アーカイブ・404 に noindex + follow を付与。
		add_filter( 'wp_robots', array( $this, 'add_noindex_robots' ) );

		// IM-3: 非 singular（ホーム・アーカイブ）に canonical を出力。
		// singular は WP コアが出力するため skip（priority 4 は wp_head より先）。
		add_action( 'wp_head', array( $this, 'output_canonical' ), 4 );
	}

	/**
	 * OGP / Twitter Card / meta description を出力する。
	 * フィード・管理画面時はスキップ。
	 *
	 * @return void
	 */
	public function output_meta_tags(): void {
		// フィードでは出力しない。
		if ( is_feed() ) {
			return;
		}

		$meta = $this->build_meta();

		if ( empty( $meta ) ) {
			return;
		}

		echo "\n<!-- AGENT NEO SEO: meta description / OGP / Twitter Card -->\n";

		// meta description（IM-1）。
		$description = $meta['description'] ?? '';
		if ( '' !== $description ) {
			printf(
				'<meta name="description" content="%s" />' . "\n",
				esc_attr( $description )
			);
		}

		// OGP。
		foreach ( $meta['og'] as $property => $content ) {
			if ( '' !== $content ) {
				printf(
					'<meta property="%s" content="%s" />' . "\n",
					esc_attr( $property ),
					esc_attr( $content )
				);
			}
		}

		// Twitter Card。
		foreach ( $meta['twitter'] as $name => $content ) {
			if ( '' !== $content ) {
				printf(
					'<meta name="%s" content="%s" />' . "\n",
					esc_attr( $name ),
					esc_attr( $content )
				);
			}
		}

		echo "<!-- /AGENT NEO SEO -->\n\n";
	}

	/**
	 * IM-2: 著者・日付アーカイブ・404 に noindex + follow を付与するフィルタ。
	 *
	 * @param array<string, bool|string> $robots wp_robots フィルタから渡されるロボット設定。
	 * @return array<string, bool|string>
	 */
	public function add_noindex_robots( array $robots ): array {
		if ( is_author() || is_date() || is_404() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			// noindex 時は max-snippet 等を取り下げる（任意）。
			unset( $robots['max-image-preview'] );
		}
		return $robots;
	}

	/**
	 * IM-3: 非 singular のページに canonical を出力する。
	 * singular は WP コアが出力するため本メソッドではスキップ。
	 * is_wp_error / 空 URL はスキップ。
	 *
	 * @return void
	 */
	public function output_canonical(): void {
		if ( is_feed() || is_singular() ) {
			// singular は wp-includes/general-template.php が出力する。
			return;
		}

		$canonical = '';

		if ( is_front_page() || is_home() ) {
			$canonical = esc_url_raw( home_url( '/' ) );
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term_link = get_term_link( (int) get_queried_object_id() );
			if ( ! is_wp_error( $term_link ) ) {
				$canonical = esc_url_raw( (string) $term_link );
			}
		} elseif ( is_author() ) {
			$canonical = esc_url_raw( get_author_posts_url( (int) get_queried_object_id() ) );
		} elseif ( is_date() ) {
			// 日付アーカイブ URL。
			global $wp;
			$canonical = esc_url_raw( home_url( add_query_arg( array(), $wp->request ) ) );
		} elseif ( is_search() ) {
			$canonical = esc_url_raw( get_search_link() );
		}

		if ( '' !== $canonical ) {
			printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $canonical ) );
		}
	}

	/**
	 * OGP / Twitter Card / meta description メタを組み立てる。
	 *
	 * @return array{description: string, og: array<string, string>, twitter: array<string, string>}
	 */
	private function build_meta(): array {
		if ( is_singular() ) {
			return $this->build_singular_meta();
		}

		return $this->build_non_singular_meta();
	}

	/**
	 * 単一投稿ページのメタを組み立てる。
	 *
	 * automation SEO 由来のメタが存在する場合はそれを優先し、
	 * ない場合は WP ネイティブにフォールバックする。
	 *
	 * @return array{description: string, og: array<string, string>, twitter: array<string, string>}
	 */
	private function build_singular_meta(): array {
		$post_id = get_the_ID();

		// タイトル: automation SEO 上書き → 投稿タイトル。
		$title = '';
		if ( $post_id ) {
			$override_title = get_post_meta( $post_id, '_agent_neo_og_title', true );
			$title = ( '' !== (string) $override_title )
				? (string) $override_title
				: (string) get_the_title( $post_id );
		}

		// 説明文: automation SEO 上書き → 抜粋 → 本文先頭。
		$description = '';
		if ( $post_id ) {
			$override_desc = get_post_meta( $post_id, '_agent_neo_og_description', true );
			if ( '' !== (string) $override_desc ) {
				$description = $this->sanitize_description( (string) $override_desc );
			} else {
				$post = get_post( $post_id );
				if ( $post instanceof WP_Post ) {
					$excerpt = get_the_excerpt( $post );
					if ( '' !== $excerpt ) {
						$description = $this->sanitize_description( $excerpt );
					} else {
						// 本文先頭 160 文字（タグ除去 + エンティティデコード）。
						$description = $this->sanitize_description(
							wp_trim_words(
								wp_strip_all_tags( $post->post_content ),
								30,
								'...'
							)
						);
					}
				}
			}
		}

		// OG 画像: automation SEO 上書き → アイキャッチ → サイト既定（IM-6）。
		$image = '';
		if ( $post_id ) {
			$override_image = get_post_meta( $post_id, '_agent_neo_og_image', true );
			if ( '' !== (string) $override_image ) {
				$image = esc_url_raw( (string) $override_image );
			} elseif ( has_post_thumbnail( $post_id ) ) {
				$thumbnail = wp_get_attachment_image_src(
					(int) get_post_thumbnail_id( $post_id ),
					'full'
				);
				if ( is_array( $thumbnail ) && isset( $thumbnail[0] ) ) {
					$image = esc_url_raw( $thumbnail[0] );
				}
			}
		}

		// サイト既定 OG 画像（アイキャッチがない場合）。
		if ( '' === $image ) {
			$image = $this->get_default_og_image();
		}

		// og:type: post タイプが 'post' なら 'article'、固定ページ・その他は 'website'（CR-2）。
		$og_type = is_singular( 'post' ) ? 'article' : 'website';

		// パーマリンク。
		$url = $post_id ? (string) get_permalink( $post_id ) : '';

		return $this->assemble_meta( $title, $description, $image, $url, $og_type );
	}

	/**
	 * 非単一ページ（フロント・アーカイブ等）のメタを組み立てる。
	 *
	 * @return array{description: string, og: array<string, string>, twitter: array<string, string>}
	 */
	private function build_non_singular_meta(): array {
		$title       = get_bloginfo( 'name' );
		$description = $this->sanitize_description( (string) get_bloginfo( 'description' ) );
		$image       = $this->get_default_og_image();
		$url         = home_url( '/' );
		$og_type     = 'website';

		// アーカイブページはアーカイブタイトル・term description を使用（IM-1）。
		if ( is_archive() ) {
			$archive_title = get_the_archive_title();
			if ( '' !== (string) $archive_title ) {
				$title = wp_strip_all_tags( (string) $archive_title ) . ' | ' . get_bloginfo( 'name' );
			}

			// term description を meta description に使用（IM-1）。
			$term = get_queried_object();
			if ( $term instanceof WP_Term && '' !== $term->description ) {
				$description = $this->sanitize_description( $term->description );
			}

			$term_link = get_term_link( (int) get_queried_object_id() );
			if ( ! is_wp_error( $term_link ) ) {
				$url = (string) $term_link;
			}
		}

		// 検索ページ。
		if ( is_search() ) {
			$title = sprintf(
				/* translators: %s: 検索キーワード */
				__( '「%s」の検索結果', 'agent-neo' ),
				get_search_query()
			) . ' | ' . get_bloginfo( 'name' );
		}

		return $this->assemble_meta( $title, $description, $image, $url, $og_type );
	}

	/**
	 * メタ配列を組み立てる。
	 *
	 * @param string $title       タイトル。
	 * @param string $description 説明文。
	 * @param string $image       OG 画像 URL。
	 * @param string $url         正規 URL。
	 * @param string $og_type     og:type 値。
	 * @return array{description: string, og: array<string, string>, twitter: array<string, string>}
	 */
	private function assemble_meta(
		string $title,
		string $description,
		string $image,
		string $url,
		string $og_type
	): array {
		$site_name = get_bloginfo( 'name' );
		$locale    = $this->get_og_locale();

		// twitter:card は og:image がある場合のみ summary_large_image（IM-6）。
		$twitter_card = ( '' !== $image ) ? 'summary_large_image' : 'summary';

		return array(
			'description' => $description,
			'og'          => array(
				'og:title'       => $title,
				'og:description' => $description,
				'og:image'       => $image,
				'og:url'         => esc_url_raw( $url ),
				'og:type'        => $og_type,
				'og:site_name'   => $site_name,
				'og:locale'      => $locale,
			),
			'twitter'     => array(
				'twitter:card'        => $twitter_card,
				'twitter:title'       => $title,
				'twitter:description' => $description,
				'twitter:image'       => $image,
			),
		);
	}

	/**
	 * description 文字列を OGP / meta description 向けにサニタイズする（IM-7）。
	 * HTML タグ除去 → エンティティデコード → 省略マーカー除去 → 空白正規化。
	 *
	 * @param string $text 生テキスト。
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
	 * サイト既定の OG 画像 URL を返す（IM-6）。
	 * カスタムロゴ → サイトアイコン → カスタムヘッダー → 空文字の順にフォールバック。
	 * 完全な無画像時は空文字（誤った空 og:image を出さない）。
	 *
	 * @return string
	 */
	private function get_default_og_image(): string {
		// カスタムロゴを OG 画像として使用。
		$logo_id = (int) get_theme_mod( 'custom_logo' );
		if ( 0 < $logo_id ) {
			$logo = wp_get_attachment_image_src( $logo_id, 'full' );
			if ( is_array( $logo ) && isset( $logo[0] ) ) {
				return esc_url_raw( $logo[0] );
			}
		}

		// サイトアイコン（favicon）を OG 画像フォールバックとして使用（IM-6）。
		$site_icon_id = (int) get_option( 'site_icon' );
		if ( 0 < $site_icon_id ) {
			$icon = wp_get_attachment_image_src( $site_icon_id, 'full' );
			if ( is_array( $icon ) && isset( $icon[0] ) ) {
				return esc_url_raw( $icon[0] );
			}
		}

		// カスタムヘッダー画像。
		$header_image = get_header_image();
		if ( '' !== (string) $header_image ) {
			return esc_url_raw( (string) $header_image );
		}

		// 完全な無画像時は空文字（誤った空 og:image を出さない）。
		return '';
	}

	/**
	 * WP ロケールを OGP locale 形式（ja_JP 等）に変換して返す。
	 *
	 * @return string
	 */
	private function get_og_locale(): string {
		$locale = get_locale();

		// WP ロケールはすでに ja_JP 形式なのでそのまま返す。
		// 不正な文字があれば ja_JP にフォールバック。
		if ( preg_match( '/\A[a-zA-Z]{2,3}(_[a-zA-Z]{2,3})?\z/', $locale ) ) {
			return $locale;
		}

		return 'ja_JP';
	}
}
