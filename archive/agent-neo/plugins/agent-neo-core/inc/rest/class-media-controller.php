<?php
/**
 * POST /agent-neo/v1/media/upload controller.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 画像アップロード・WebP 変換パイプラインを担う REST controller。
 *
 * AI ロジック禁止（REQ-NF-025）。決定的変換と WP 添付処理のみ実施する。
 */
final class Agent_Neo_Core_Media_Controller extends Agent_Neo_Core_REST_Controller_Base {

	/**
	 * 同期変換を行うファイルサイズ上限（バイト）。
	 */
	private const SYNC_SIZE_LIMIT = 5 * 1024 * 1024; // 5 MB

	/**
	 * Auth helper。
	 *
	 * @var Agent_Neo_Core_Auth
	 */
	private Agent_Neo_Core_Auth $auth;

	/**
	 * @param Agent_Neo_Core_Auth $auth Auth helper。
	 */
	public function __construct( Agent_Neo_Core_Auth $auth ) {
		$this->auth = $auth;
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
	 * POST /media/upload を登録する。
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->register_agent_route(
			'/media/upload',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'upload_media' ),
				'permission_callback' => array( $this, 'check_upload_permission' ),
			)
		);
	}

	/**
	 * upload_files capability を確認する。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return true|WP_Error
	 */
	public function check_upload_permission( WP_REST_Request $request ) {
		return $this->auth->check_write_permission( $request, 'upload_files' );
	}

	/**
	 * POST /media/upload ハンドラ。
	 *
	 * multipart/form-data の画像を受け取り、WP 添付として保存する。
	 * Imagick/GD が WebP 対応であれば WebP へ変換する。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function upload_media( WP_REST_Request $request ) {
		// request_id 確定。
		$request_id = $this->resolve_request_id( $request );

		// WP アップロード用関数を読み込む。
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// $_FILES 経由でのアップロードを確認する。
		$files = $request->get_file_params();
		if ( empty( $files['file'] ) || ! is_array( $files['file'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'file フィールドが multipart/form-data に含まれていません。', 'agent-neo-core' ),
				array( 'field' => 'file' )
			);
		}

		$file = $files['file'];

		// アップロードエラーチェック。
		if ( ! empty( $file['error'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				sprintf(
					/* translators: %d: PHP upload error code */
					__( 'ファイルアップロードに失敗しました（PHP error code: %d）。', 'agent-neo-core' ),
					(int) $file['error']
				),
				array( 'upload_error' => (int) $file['error'] )
			);
		}

		// MIME タイプ検証（画像のみ受け付ける）。第一フィルタで .php 偽装等を弾く。
		$mime_type = $this->detect_mime( $file['tmp_name'], $file['name'] ?? '' );
		if ( ! $this->is_allowed_image_mime( $mime_type ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				sprintf(
					/* translators: %s: detected MIME type */
					__( '対応していない MIME タイプです: %s', 'agent-neo-core' ),
					esc_html( $mime_type )
				),
				array( 'field' => 'file', 'detected_mime' => $mime_type )
			);
		}

		$warnings  = array();
		$converted = false;
		$file_size = (int) $file['size'];

		// 5 MB 超は同期変換せずキュー返却する。
		if ( $file_size > self::SYNC_SIZE_LIMIT ) {
			$warnings[] = array(
				'code'    => 'LARGE_FILE_QUEUED',
				'message' => __( 'ファイルが 5 MB を超えるため WebP 変換はバックグラウンドで行われます。', 'agent-neo-core' ),
			);

			// WP 添付として保存し queued ステータスを返す。
			$attachment_id = media_handle_sideload( $file, 0 );
			if ( is_wp_error( $attachment_id ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					$attachment_id->get_error_message(),
					array( 'wp_error_code' => $attachment_id->get_error_code() )
				);
			}

			$url      = wp_get_attachment_url( $attachment_id );
			$url      = is_string( $url ) ? $url : '';
			$response = Agent_Neo_Core_Auth::success_response(
				array(
					'attachment_id' => $attachment_id,
					'url'           => $url,
					'mime'          => get_post_mime_type( $attachment_id ) ?: $mime_type,
					'converted'     => false,
					'status'        => 'queued',
					'warnings'      => $warnings,
				),
				$request_id
			);

			return rest_ensure_response( $response );
		}

		// GIF アニメーション保持（変換スキップ・warning 追加）。
		if ( 'image/gif' === $mime_type ) {
			$warnings[] = array(
				'code'    => 'GIF_ANIMATION_PRESERVED',
				'message' => __( 'GIF ファイルはアニメーション保持のため WebP 変換をスキップします。', 'agent-neo-core' ),
			);

			$attachment_id = media_handle_sideload( $file, 0 );
			if ( is_wp_error( $attachment_id ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					$attachment_id->get_error_message(),
					array( 'wp_error_code' => $attachment_id->get_error_code() )
				);
			}

			$url      = wp_get_attachment_url( $attachment_id );
			$url      = is_string( $url ) ? $url : '';
			$response = Agent_Neo_Core_Auth::success_response(
				array(
					'attachment_id' => $attachment_id,
					'url'           => $url,
					'mime'          => 'image/gif',
					'converted'     => false,
					'status'        => 'done',
					'warnings'      => $warnings,
				),
				$request_id
			);

			return rest_ensure_response( $response );
		}

		// 既存 WebP はそのまま添付する（変換スキップ）。
		if ( 'image/webp' === $mime_type ) {
			$attachment_id = media_handle_sideload( $file, 0 );
			if ( is_wp_error( $attachment_id ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					$attachment_id->get_error_message(),
					array( 'wp_error_code' => $attachment_id->get_error_code() )
				);
			}

			$url      = wp_get_attachment_url( $attachment_id );
			$url      = is_string( $url ) ? $url : '';
			$response = Agent_Neo_Core_Auth::success_response(
				array(
					'attachment_id' => $attachment_id,
					'url'           => $url,
					'mime'          => 'image/webp',
					'converted'     => false,
					'status'        => 'done',
					'warnings'      => $warnings,
				),
				$request_id
			);

			return rest_ensure_response( $response );
		}

		// WebP 変換を試みる（JPEG / PNG 等）。
		$webp_result = $this->try_convert_to_webp( $file['tmp_name'], $mime_type );
		if ( is_wp_error( $webp_result ) ) {
			// 変換失敗時は warning を追加してオリジナルのまま添付する。
			$warnings[] = array(
				'code'    => 'WEBP_CONVERSION_SKIPPED',
				'message' => $webp_result->get_error_message(),
			);
		} elseif ( is_string( $webp_result ) ) {
			// 変換成功: tmp ファイルを WebP に差し替えて sideload する。
			$file['tmp_name'] = $webp_result;
			$file['name']     = $this->replace_extension( $file['name'], 'webp' );
			$file['type']     = 'image/webp';
			$mime_type        = 'image/webp';
			$converted        = true;
		}

		$attachment_id = media_handle_sideload( $file, 0 );
		if ( is_wp_error( $attachment_id ) ) {
			// 一時変換ファイルがあれば削除する。
			if ( $converted && file_exists( $file['tmp_name'] ) ) {
				@unlink( $file['tmp_name'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}

			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				$attachment_id->get_error_message(),
				array( 'wp_error_code' => $attachment_id->get_error_code() )
			);
		}

		$url      = wp_get_attachment_url( $attachment_id );
		$url      = is_string( $url ) ? $url : '';
		$response = Agent_Neo_Core_Auth::success_response(
			array(
				'attachment_id' => $attachment_id,
				'url'           => $url,
				'mime'          => get_post_mime_type( $attachment_id ) ?: $mime_type,
				'converted'     => $converted,
				'status'        => 'done',
				'warnings'      => $warnings,
			),
			$request_id
		);

		return rest_ensure_response( $response );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * X-Request-Id ヘッダーを取得するか UUID v4 を生成する。
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

	/**
	 * ファイルの実際の MIME タイプを取得する。
	 *
	 * wp_check_filetype_and_ext() でバイナリ内容ベースの一次検証を行い、
	 * finfo / getimagesize をクロスチェックとして重ねる。
	 * いずれかが画像許可リスト外を返した場合は 'application/octet-stream' を返し
	 * 呼び出し元の is_allowed_image_mime() で弾く設計とする。
	 *
	 * @param string $tmp_name アップロードされた一時ファイルのパス。
	 * @param string $original_name クライアントが送信したファイル名（拡張子判定に使用）。
	 * @return string 確定 MIME タイプ。不明または不一致時は 'application/octet-stream'。
	 */
	private function detect_mime( string $tmp_name, string $original_name ): string {
		// wp-admin/includes/file.php を確実に読み込む（upload_media で読込済みだが念のため）。
		require_once ABSPATH . 'wp-admin/includes/file.php';

		// 第一フィルタ: バイナリ内容 + 拡張子の両方を検証する（パス名のみの弱い判定を排除）。
		$wp_check = wp_check_filetype_and_ext( $tmp_name, $original_name );
		$wp_mime  = ! empty( $wp_check['type'] ) && is_string( $wp_check['type'] ) ? $wp_check['type'] : '';

		// wp_check_filetype_and_ext が画像許可リスト外を返した場合は即座に拒否する。
		if ( '' !== $wp_mime && ! $this->is_allowed_image_mime( $wp_mime ) ) {
			return $wp_mime; // 呼び出し元で VALIDATION_ERROR に変換する。
		}

		// クロスチェック 1: finfo によるバイナリ署名検証。
		$finfo_mime = '';
		if ( function_exists( 'finfo_open' ) ) {
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			if ( false !== $finfo ) {
				$detected = finfo_file( $finfo, $tmp_name );
				finfo_close( $finfo );
				if ( is_string( $detected ) && '' !== $detected ) {
					$finfo_mime = $detected;
				}
			}
		}

		// クロスチェック 2: getimagesize による画像ヘッダ検証。
		$imgsize_mime = '';
		$image_info   = @getimagesize( $tmp_name ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( is_array( $image_info ) && isset( $image_info['mime'] ) ) {
			$imgsize_mime = (string) $image_info['mime'];
		}

		// wp_check と finfo の不一致はバイナリ偽装の可能性がある。
		if ( '' !== $wp_mime && '' !== $finfo_mime && $wp_mime !== $finfo_mime ) {
			return 'application/octet-stream'; // 不一致はエラー扱い。
		}

		// finfo と getimagesize 両方が取得できた場合に不一致を検出する。
		if ( '' !== $finfo_mime && '' !== $imgsize_mime && $finfo_mime !== $imgsize_mime ) {
			return 'application/octet-stream'; // 不一致はエラー扱い。
		}

		// 信頼度の高い順: wp_check > finfo > getimagesize の優先順で返す。
		if ( '' !== $wp_mime ) {
			return $wp_mime;
		}
		if ( '' !== $finfo_mime ) {
			return $finfo_mime;
		}
		if ( '' !== $imgsize_mime ) {
			return $imgsize_mime;
		}

		return 'application/octet-stream';
	}

	/**
	 * 許可 MIME タイプか確認する。
	 *
	 * @param string $mime MIME タイプ。
	 * @return bool
	 */
	private function is_allowed_image_mime( string $mime ): bool {
		$allowed = array(
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/webp',
			'image/avif',
			'image/bmp',
		);

		return in_array( $mime, $allowed, true );
	}

	/**
	 * Imagick または GD を使い WebP へ変換する。
	 *
	 * @param string $source_path 変換元パス。
	 * @param string $source_mime 変換元 MIME。
	 * @return string|WP_Error 変換後一時ファイルパスまたは WP_Error。
	 */
	private function try_convert_to_webp( string $source_path, string $source_mime ) {
		$dest_path = $source_path . '_webp_' . wp_generate_password( 8, false ) . '.webp';

		// Imagick 優先。
		if ( class_exists( 'Imagick' ) ) {
			try {
				$imagick = new Imagick();
				$imagick->readImage( $source_path );
				$imagick->setImageFormat( 'webp' );
				$imagick->setImageCompressionQuality( 82 );
				$imagick->writeImage( $dest_path );
				$imagick->destroy();

				if ( file_exists( $dest_path ) && filesize( $dest_path ) > 0 ) {
					return $dest_path;
				}
			} catch ( Exception $e ) {
				// Imagick 失敗時は GD へフォールバック。
			}
		}

		// GD フォールバック。
		if ( function_exists( 'imagewebp' ) ) {
			$image = $this->gd_create_image( $source_path, $source_mime );
			if ( false !== $image && null !== $image ) {
				$result = imagewebp( $image, $dest_path, 82 );
				imagedestroy( $image );

				if ( $result && file_exists( $dest_path ) && filesize( $dest_path ) > 0 ) {
					return $dest_path;
				}
			}
		}

		return new WP_Error(
			'WEBP_UNSUPPORTED',
			__( 'Imagick も GD も WebP 変換に対応していません。オリジナル形式で保存します。', 'agent-neo-core' )
		);
	}

	/**
	 * GD を使いソース画像リソースを生成する。
	 *
	 * @param string $path ファイルパス。
	 * @param string $mime MIME タイプ。
	 * @return \GdImage|false|null
	 */
	private function gd_create_image( string $path, string $mime ) {
		switch ( $mime ) {
			case 'image/jpeg':
				return function_exists( 'imagecreatefromjpeg' ) ? imagecreatefromjpeg( $path ) : null;
			case 'image/png':
				return function_exists( 'imagecreatefrompng' ) ? imagecreatefrompng( $path ) : null;
			case 'image/bmp':
				return function_exists( 'imagecreatefrombmp' ) ? imagecreatefrombmp( $path ) : null;
			default:
				return null;
		}
	}

	/**
	 * ファイル名の拡張子を置換する。
	 *
	 * @param string $filename ファイル名。
	 * @param string $new_ext 新しい拡張子（ドットなし）。
	 * @return string
	 */
	private function replace_extension( string $filename, string $new_ext ): string {
		$info = pathinfo( $filename );
		$base = $info['filename'] ?? sanitize_file_name( $filename );

		return sanitize_file_name( $base ) . '.' . $new_ext;
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Media_Controller(
			$container->auth()
		);
		$controller->register();
		$container->register_module( 'rest-media' );
	}
);
