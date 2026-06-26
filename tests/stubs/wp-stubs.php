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
     * 実WP の WP_HTML_Tag_Processor（WP6.2+）の挙動に忠実な最小実装。
     * - next_tag(null)   : 任意タグを線形スキャン（開き/閉じ両方）
     * - next_tag('DIV')  : 指定タグ名のみスキャン（実WP の query 形式に準拠）
     * - is_tag_closer()  : 現在のトークンが閉じタグか判定
     * - get_tag()        : 現在のタグ名を大文字で返す
     * - get_attribute()  : 属性値を返す（存在しなければ null）
     * - set_attribute()  : 属性をセットする
     * - get_updated_html(): 変更済み HTML を返す
     *
     * 内部実装: DOMDocument でパースし、DOMElement を文書順フラットリストとして保持。
     * next_tag() 引数なし = 全要素を順に走査、引数あり = 指定タグのみ。
     * is_tag_closer() は DOM ベースのスタブでは常に false（開き要素のみリスト化）。
     * 実WP との差異: 閉じタグを明示的に走査するケースは本プロジェクトでは不要。
     */
    class WP_HTML_Tag_Processor {
        /** @var string 入力 HTML */
        private string $html;

        /** @var \DOMDocument */
        private \DOMDocument $dom;

        /**
         * 文書順の全 DOMElement フラットリスト（開き要素のみ）。
         *
         * @var list<\DOMElement>
         */
        private array $all_elements = array();

        /**
         * next_tag() が返す要素のリスト（フィルタ後）。
         *
         * @var list<\DOMElement>
         */
        private array $filtered_list = array();

        /** @var int 現在のカーソル位置（filtered_list インデックス） */
        private int $cursor = -1;

        /**
         * 前回の next_tag() のフィルタ条件。
         *
         * null  = 全タグ（引数なし呼び出し）
         * ''    = 未初期化（コンストラクタ後・まだ next_tag() 未呼び出し）
         *
         * @var string|null
         */
        private ?string $last_filter = ''; // '' = sentinel（未初期化、nullと区別）

        /** @var bool next_tag() が一度でも呼ばれたか */
        private bool $initialized = false;

        /**
         * @param string $html 入力 HTML。
         */
        public function __construct( string $html ) {
            $this->html = $html;
            $this->dom  = new \DOMDocument();
            $wrapped    = '<?xml encoding="UTF-8"><root>' . $html . '</root>';
            @$this->dom->loadHTML( $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING );

            // 文書順に全 DOMElement を収集する（再帰 DOMTreeWalker 相当）。
            $root = $this->dom->getElementsByTagName( 'root' )->item( 0 );
            if ( $root instanceof \DOMElement ) {
                $this->collect_elements( $root );
            }
        }

        /**
         * DOMElement を文書順でフラットリストに収集する。
         *
         * @param \DOMElement $node 起点ノード（root 要素）。
         * @return void
         */
        private function collect_elements( \DOMElement $node ): void {
            foreach ( $node->childNodes as $child ) {
                if ( $child instanceof \DOMElement ) {
                    $this->all_elements[] = $child;
                    $this->collect_elements( $child );
                }
            }
        }

        /**
         * 次のタグへ進む。
         *
         * - 引数なし（null）: 全タグを文書順で線形スキャン。
         * - 文字列指定: 指定タグ名のみスキャン。
         * - フィルタが変わった場合はリストを再構築しカーソルをリセット。
         *
         * @param string|array|null $query タグ名文字列、クエリ配列、または null（任意タグ）。
         * @return bool 次のノードが存在すれば true。
         */
        public function next_tag( $query = null ): bool {
            // フィルタ条件を正規化する（null = 全タグ、文字列 = 指定タグ）。
            $filter = null;
            if ( is_string( $query ) && '' !== $query ) {
                $filter = strtoupper( $query );
            } elseif ( is_array( $query ) && isset( $query['tag_name'] ) && '' !== $query['tag_name'] ) {
                $filter = strtoupper( (string) $query['tag_name'] );
            }

            // フィルタ条件が変わった場合（または初回呼び出し）はリストを再構築してカーソルをリセットする。
            // last_filter の初期値は '' (sentinel)。null（全タグ）とも '' とも区別する。
            if ( ! $this->initialized || $filter !== $this->last_filter ) {
                if ( null === $filter ) {
                    $this->filtered_list = $this->all_elements;
                } else {
                    $this->filtered_list = array_values(
                        array_filter(
                            $this->all_elements,
                            static function ( \DOMElement $el ) use ( $filter ): bool {
                                return strtoupper( $el->tagName ) === $filter;
                            }
                        )
                    );
                }
                $this->cursor      = -1;
                $this->last_filter = $filter;
                $this->initialized = true;
            }

            ++$this->cursor;
            return $this->cursor < count( $this->filtered_list );
        }

        /**
         * 現在のトークンが閉じタグかを返す。
         *
         * DOM ベースのスタブは開き要素のみリスト化するため常に false を返す。
         * 実WP では next_tag() 引数なしで閉じタグも走査できるが、
         * 本プロジェクトの instrument_affiliate_links() は is_tag_closer() で
         * スキップするだけなので false 固定で正しい動作になる。
         *
         * @return bool
         */
        public function is_tag_closer(): bool {
            return false;
        }

        /**
         * 現在のタグ名を大文字で返す。
         *
         * @return string|null タグ名（大文字）、マッチなしは null。
         */
        public function get_tag(): ?string {
            if ( $this->cursor < 0 || $this->cursor >= count( $this->filtered_list ) ) {
                return null;
            }
            return strtoupper( $this->filtered_list[ $this->cursor ]->tagName );
        }

        /**
         * 現在のノードの属性値を返す。
         *
         * @param string $name 属性名。
         * @return string|null 属性値。属性が存在しない場合は null。
         */
        public function get_attribute( string $name ): ?string {
            $node = $this->current_node();
            if ( null === $node ) {
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
         * @return bool
         */
        public function set_attribute( string $name, string $value ): bool {
            $node = $this->current_node();
            if ( null === $node ) {
                return false;
            }
            $node->setAttribute( $name, $value );
            return true;
        }

        /**
         * 変更済み HTML を返す。
         *
         * @return string 更新後 HTML。
         */
        public function get_updated_html(): string {
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

        /**
         * 現在カーソルが指す DOMElement を返す。
         *
         * @return \DOMElement|null
         */
        private function current_node(): ?\DOMElement {
            if ( $this->cursor < 0 || $this->cursor >= count( $this->filtered_list ) ) {
                return null;
            }
            return $this->filtered_list[ $this->cursor ];
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
