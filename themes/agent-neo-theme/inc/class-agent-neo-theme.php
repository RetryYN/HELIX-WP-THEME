<?php
/**
 * AGENT NEO テーマの kernel。
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 起動順序、設定検証、module 登録を管理する。
 */
final class Agent_Neo_Theme {
	/**
	 * 起動 step の記録。
	 *
	 * @var array<string, bool>
	 */
	private array $steps = array();

	/**
	 * 読み込み済み module。
	 *
	 * @var array<int, string>
	 */
	private array $loaded_modules = array();

	/**
	 * 設定 loader。
	 *
	 * @var Agent_Neo_Config_Loader
	 */
	private Agent_Neo_Config_Loader $config_loader;

	/**
	 * 境界 guard。
	 *
	 * @var Agent_Neo_Boundary_Guard
	 */
	private Agent_Neo_Boundary_Guard $boundary_guard;

	/**
	 * Theme setup module。
	 *
	 * @var Agent_Neo_Theme_Setup
	 */
	private Agent_Neo_Theme_Setup $theme_setup;

	/**
	 * サードパーティタグ管理 module。
	 *
	 * @var Agent_Neo_Third_Party_Manager
	 */
	private Agent_Neo_Third_Party_Manager $third_party_manager;

	/**
	 * module を生成する。
	 */
	public function __construct() {
		$this->trace_step( 'construct' );
		$this->config_loader       = new Agent_Neo_Config_Loader( AGENT_NEO_DIR . 'config/' );
		$this->boundary_guard      = new Agent_Neo_Boundary_Guard();
		$this->theme_setup         = new Agent_Neo_Theme_Setup();
		// third-party-tags.json はロード前に空配列でインスタンス化し、register() 後に設定を渡す。
		$this->third_party_manager = new Agent_Neo_Third_Party_Manager( array() );
	}

	/**
	 * 設定を検証し、module を登録する。
	 *
	 * @return void
	 */
	public function register(): void {
		$this->trace_step( 'register_start' );

		$this->config_loader->load();
		$this->loaded_modules[] = 'config-loader';
		$this->trace_step( 'config_loaded' );

		$this->boundary_guard->validate( $this->config_loader->get( 'theme-manifest' ) );
		$this->loaded_modules[] = 'boundary-guard';
		$this->trace_step( 'boundary_validated' );

		$this->theme_setup->register();
		$this->loaded_modules[] = 'theme-setup';
		$this->trace_step( 'modules_registered' );

		if ( ! $this->config_loader->is_valid() || ! $this->boundary_guard->is_valid() ) {
			add_action( 'admin_notices', array( $this, 'render_admin_notice' ) );
			$this->trace_step( 'fail_fast_degraded' );
			return;
		}

		// サードパーティタグ管理を初期化し、フックを登録する。
		// config-loader の is_valid() 確認後に配線することで、不正設定時は graceful skip。
		$third_party_config = $this->load_third_party_config();
		$this->third_party_manager = new Agent_Neo_Third_Party_Manager( $third_party_config );
		$this->third_party_manager->register();
		$this->loaded_modules[] = 'third-party-manager';
		$this->trace_step( 'third_party_registered' );

		$this->trace_step( 'register_complete' );
	}

	/**
	 * third-party-tags.json を読み込む。
	 * ファイルが存在しない場合は空配列を返す（graceful degradation）。
	 *
	 * @return array<string, mixed>
	 */
	private function load_third_party_config(): array {
		$path = AGENT_NEO_DIR . 'config/third-party-tags.json';

		if ( ! is_readable( $path ) ) {
			return array();
		}

		$contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $contents ) {
			return array();
		}

		$data = json_decode( $contents, true );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * 管理画面へ fail-fast の明示エラーを出す。
	 *
	 * @return void
	 */
	public function render_admin_notice(): void {
		$errors = array_merge(
			$this->config_loader->get_errors(),
			$this->boundary_guard->get_errors()
		);

		if ( empty( $errors ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'AGENT NEO theme config validation failed.', 'agent-neo' );
		echo '</p><ul>';

		foreach ( $errors as $error ) {
			echo '<li>' . esc_html( $error ) . '</li>';
		}

		echo '</ul></div>';
	}

	/**
	 * health サマリを返す。
	 *
	 * @return array<string, mixed>
	 */
	public function health(): array {
		return array(
			'loaded'               => true,
			'version'              => AGENT_NEO_VERSION,
			'loaded_modules'       => $this->loaded_modules,
			'config_valid'         => $this->config_loader->is_valid(),
			'boundary_valid'       => $this->boundary_guard->is_valid(),
			'steps'                => $this->steps,
			'config_errors'        => $this->config_loader->get_errors(),
			'boundary_errors'      => $this->boundary_guard->get_errors(),
			'registered_files'     => $this->config_loader->registered_files(),
			'third_party_manager'  => in_array( 'third-party-manager', $this->loaded_modules, true ),
		);
	}

	/**
	 * WP_DEBUG 時に起動 step を error_log へ流す。
	 *
	 * @param string $step Step 名。
	 * @return void
	 */
	private function trace_step( string $step ): void {
		$this->steps[ $step ] = true;

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[agent_neo_theme] bootstrap step: ' . $step ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
