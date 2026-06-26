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
// WP_HTML_Tag_Processor スタブ（WP6.2+ / unit テスト用最小実装）
//
// instrument_block() が class_exists('WP_HTML_Tag_Processor') でガードしているため、
// このスタブを定義することで本経路（79行目・122行目の agent_neo_core_extract_cta_id_from_class
// 呼び出し経路）をユニットテスト上で通せるようにする。
// PHP の DOMDocument を使って <a> / <div> の属性操作を最小実装する。
// ------------------------------------------------------------------

if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
    /**
     * WP_HTML_Tag_Processor 最小スタブ（unit テスト専用）。
     *
     * next_tag() / get_attribute() / set_attribute() / get_updated_html() のみ実装。
     * 実際の WP_HTML_Tag_Processor と完全互換ではない。
     * テストが必要とする属性付与動作を再現する簡易実装。
     */
    class WP_HTML_Tag_Processor {
        /** @var string 入力 HTML */
        private string $html;

        /** @var \DOMDocument */
        private \DOMDocument $dom;

        /** @var \DOMNodeList<\DOMElement>|null 現在対象のタグリスト */
        private ?\DOMNodeList $node_list = null;

        /** @var int 現在のカーソル位置 */
        private int $cursor = -1;

        /** @var string 現在対象のタグ名（大文字） */
        private string $current_tag = '';

        /**
         * @param string $html 入力 HTML。
         */
        public function __construct( string $html ) {
            $this->html = $html;
            $this->dom  = new \DOMDocument();
            // エラー抑制しつつロード。文字コード保持のため meta charset を付与。
            $wrapped = '<?xml encoding="UTF-8"><root>' . $html . '</root>';
            @$this->dom->loadHTML( $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING );
        }

        /**
         * 指定タグ名の次のノードへ進む。
         *
         * @param string $tag_name タグ名（大文字・小文字不問）。
         * @return bool 次のノードが存在すれば true。
         */
        public function next_tag( string $tag_name = '' ): bool {
            $tag_upper = strtoupper( $tag_name );

            if ( $this->current_tag !== $tag_upper || null === $this->node_list ) {
                // タグが切り替わった場合はリストを再取得。
                $this->node_list   = $this->dom->getElementsByTagName( strtolower( $tag_name ) );
                $this->cursor      = -1;
                $this->current_tag = $tag_upper;
            }

            ++$this->cursor;
            return $this->cursor < $this->node_list->length;
        }

        /**
         * 現在のノードの属性値を返す。
         *
         * @param string $name 属性名。
         * @return string|null 属性値。属性が存在しない場合は null。
         */
        public function get_attribute( string $name ): ?string {
            if ( null === $this->node_list || $this->cursor < 0 || $this->cursor >= $this->node_list->length ) {
                return null;
            }
            $node = $this->node_list->item( $this->cursor );
            if ( ! ( $node instanceof \DOMElement ) ) {
                return null;
            }
            if ( ! $node->hasAttribute( $name ) ) {
                return null;
            }
            return $node->getAttribute( $name );
        }

        /**
         * 現在のノードに属性をセットする。
         *
         * @param string $name  属性名。
         * @param string $value 属性値。
         * @return void
         */
        public function set_attribute( string $name, string $value ): void {
            if ( null === $this->node_list || $this->cursor < 0 || $this->cursor >= $this->node_list->length ) {
                return;
            }
            $node = $this->node_list->item( $this->cursor );
            if ( $node instanceof \DOMElement ) {
                $node->setAttribute( $name, $value );
            }
        }

        /**
         * 変更済み HTML を返す。
         *
         * @return string 更新後 HTML。
         */
        public function get_updated_html(): string {
            // <root>...</root> を取り出して元の形に戻す。
            $root = $this->dom->getElementsByTagName( 'root' )->item( 0 );
            if ( ! ( $root instanceof \DOMElement ) ) {
                return $this->html;
            }
            $inner = '';
            foreach ( $root->childNodes as $child ) {
                $inner .= $this->dom->saveHTML( $child );
            }
            return $inner;
        }
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
