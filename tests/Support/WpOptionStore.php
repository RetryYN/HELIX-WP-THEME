<?php
/**
 * WP option API のインメモリスタブ実装。
 *
 * Brain Monkey の Functions\expect() は同一関数を複数回 expect() した場合、
 * with() 引数によるマッチングが Mockery の登録順に依存し、2回目以降の呼び出しに
 * 正しい andReturn() が適用されないことがある（実証済み: get_option 2回登録で
 * 2回目も1回目の andReturn 値を返す）。
 *
 * 本 Trait はその問題を回避するため、Brain Monkey の Functions\stubs() に
 * クロージャを渡す形でインメモリ配列によるステートフルな WP option 実装を提供する。
 *
 * 使い方:
 *   use AgentNeo\Tests\Support\WpOptionStore;
 *
 *   protected function set_up(): void {
 *       parent::set_up();
 *       Monkey\setUp();
 *       $this->stub_wp_option_api();  // Brain Monkey setUp() 後に呼ぶ
 *   }
 *
 *   // テストデータの事前投入:
 *   $this->option_store['_transient_key'] = ['signature' => '...', ...];
 *
 * @package AgentNeo\Tests\Support
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Support;

use Brain\Monkey\Functions;

/**
 * WP option API インメモリスタブ Trait。
 *
 * Security TestCase でこの Trait を use することで、
 * get_option / add_option / update_option / delete_option を
 * 単一のインメモリ配列 ($option_store) に束ねる。
 *
 * replay 検出テストで「1回目=受理・2回目=拒否」が成立するよう、
 * テスト間でリセットされる隔離された状態として管理する。
 */
trait WpOptionStore {

	/**
	 * インメモリ option ストア。
	 *
	 * set_up() で [] 初期化、tear_down() の前にクリアされる。
	 * テストケース内で事前データを投入する場合は直接代入する:
	 *   $this->option_store['key'] = 'value';
	 *
	 * @var array<string, mixed>
	 */
	protected array $option_store = array();

	/**
	 * WP option 関数を Brain Monkey stubs として登録する。
	 *
	 * Monkey\setUp() の呼び出し後に本メソッドを呼ぶこと。
	 *
	 * 登録する関数:
	 *   - get_option( $option, $default = false )
	 *   - add_option( $option, $value, $deprecated = '', $autoload = 'yes' )
	 *   - update_option( $option, $value, $autoload = null )
	 *   - delete_option( $option )
	 *
	 * @return void
	 */
	protected function stub_wp_option_api(): void {
		// $this を Closure 内から参照するため変数に束縛する。
		$store = &$this->option_store;

		Functions\stubs( array(
			// get_option: ストアにキーが存在すればその値、なければ $default を返す。
			'get_option' => function ( string $option, $default = false ) use ( &$store ) {
				return array_key_exists( $option, $store ) ? $store[ $option ] : $default;
			},

			// add_option: キーが未存在なら追加して true、既存なら false。
			// $deprecated / $autoload は WP 互換のため受け取るが無視する。
			'add_option' => function ( string $option, $value, $deprecated = '', $autoload = 'yes' ) use ( &$store ) {
				if ( array_key_exists( $option, $store ) ) {
					return false;
				}
				$store[ $option ] = $value;
				return true;
			},

			// update_option: 値を上書きして true を返す（削除→更新でもキー存在で ok）。
			'update_option' => function ( string $option, $value, $autoload = null ) use ( &$store ) {
				$store[ $option ] = $value;
				return true;
			},

			// delete_option: キーを削除して true（存在しなければ false）。
			'delete_option' => function ( string $option ) use ( &$store ) {
				if ( ! array_key_exists( $option, $store ) ) {
					return false;
				}
				unset( $store[ $option ] );
				return true;
			},
		) );
	}

	/**
	 * option ストアを空にする。
	 *
	 * tear_down() またはテスト間で明示的にリセットしたい場合に呼ぶ。
	 *
	 * @return void
	 */
	protected function reset_option_store(): void {
		$this->option_store = array();
	}
}
