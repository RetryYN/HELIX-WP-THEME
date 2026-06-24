<?php
/**
 * WP クラス・定数スタブ（Unit / Security スイート用）。
 *
 * Brain Monkey は WP 関数はスタブするが WP クラスは提供しない。
 * 本ファイルで最小限の WP クラス・定数を定義し、
 * Controller のロードと PHPUnit createMock() を可能にする。
 *
 * @package AgentNeoCore\Tests
 */

declare( strict_types=1 );

// ------------------------------------------------------------------
// WP 時間定数
// ------------------------------------------------------------------

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
    define( 'MINUTE_IN_SECONDS', 60 );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
    define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
    define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
    define( 'WEEK_IN_SECONDS', 604800 );
}

if ( ! defined( 'DATE_ATOM' ) ) {
    define( 'DATE_ATOM', 'Y-m-d\TH:i:sP' );
}

// ------------------------------------------------------------------
// WP_Error スタブ
// ------------------------------------------------------------------

if ( ! class_exists( 'WP_Error' ) ) {
    /**
     * WP_Error 最小スタブ。
     */
    class WP_Error {
        private string $code;
        private string $message;
        /** @var array<string,mixed> */
        private array $data;

        /**
         * @param string               $code    エラーコード。
         * @param string               $message エラーメッセージ。
         * @param array<string,mixed>  $data    追加データ。
         */
        public function __construct( string $code = '', string $message = '', $data = array() ) {
            $this->code    = $code;
            $this->message = $message;
            $this->data    = is_array( $data ) ? $data : array( 'data' => $data );
        }

        public function get_error_code(): string {
            return $this->code;
        }

        public function get_error_message(): string {
            return $this->message;
        }

        /** @return array<string,mixed> */
        public function get_error_data(): array {
            return $this->data;
        }
    }
}

// ------------------------------------------------------------------
// WP_REST_Request スタブ（createMock 対象なので最小インタフェースのみ）
// ------------------------------------------------------------------

if ( ! class_exists( 'WP_REST_Request' ) ) {
    /**
     * WP_REST_Request 最小スタブ。
     */
    class WP_REST_Request {
        private string $method;
        /** @var array<string,mixed> */
        private array $json_params;
        /** @var array<string,string> */
        private array $headers;

        /**
         * @param string              $method      HTTP メソッド。
         * @param array<string,mixed> $json_params JSON パラメータ。
         * @param array<string,string> $headers    ヘッダ。
         */
        public function __construct(
            string $method = 'POST',
            array $json_params = array(),
            array $headers = array()
        ) {
            $this->method      = $method;
            $this->json_params = $json_params;
            $this->headers     = $headers;
        }

        /** @return array<string,mixed> */
        public function get_json_params(): array {
            return $this->json_params;
        }

        public function get_method(): string {
            return $this->method;
        }

        public function get_header( string $name ): string {
            return $this->headers[ strtolower( $name ) ] ?? '';
        }

        /** @return mixed */
        public function get_param( string $key ) {
            return $this->json_params[ $key ] ?? null;
        }
    }
}

// ------------------------------------------------------------------
// WP_REST_Server 定数スタブ
// ------------------------------------------------------------------

if ( ! class_exists( 'WP_REST_Server' ) ) {
    /**
     * WP_REST_Server 最小スタブ。
     */
    class WP_REST_Server {
        public const READABLE  = 'GET';
        public const CREATABLE = 'POST';
        public const EDITABLE  = 'POST, PUT, PATCH';
        public const DELETABLE = 'DELETE';
        public const ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE';
    }
}

// ------------------------------------------------------------------
// WP_Post スタブ
// ------------------------------------------------------------------

if ( ! class_exists( 'WP_Post' ) ) {
    /**
     * WP_Post 最小スタブ。
     */
    class WP_Post {
        public int    $ID           = 0;
        public string $post_content = '';
        public string $post_status  = 'publish';
        public string $post_password = '';
        public string $post_type    = 'post';
        public string $post_name    = '';
        public string $post_title   = '';
    }
}

// ------------------------------------------------------------------
// WP_REST_Response スタブ
// ------------------------------------------------------------------

if ( ! class_exists( 'WP_REST_Response' ) ) {
    /**
     * WP_REST_Response 最小スタブ。
     */
    class WP_REST_Response {
        /** @var mixed */
        private $data;
        private int $status;

        /**
         * @param mixed $data   レスポンスデータ。
         * @param int   $status HTTP ステータスコード。
         */
        public function __construct( $data = null, int $status = 200 ) {
            $this->data   = $data;
            $this->status = $status;
        }

        /** @return mixed */
        public function get_data() {
            return $this->data;
        }

        public function get_status(): int {
            return $this->status;
        }

        /** @param mixed $data */
        public function set_data( $data ): void {
            $this->data = $data;
        }
    }
}
