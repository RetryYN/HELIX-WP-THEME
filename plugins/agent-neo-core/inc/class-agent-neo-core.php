<?php
/**
 * AGENT NEO Core Plugin の kernel。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * module 登録と health summary を管理する。
 */
final class Agent_Neo_Core {
	/**
	 * 読み込み済み module。
	 *
	 * @var array<int, string>
	 */
	private array $loaded_modules = array();

	/**
	 * 起動 step の記録。
	 *
	 * @var array<string, bool>
	 */
	private array $steps = array();

	/**
	 * Schema loader。
	 *
	 * @var Agent_Neo_Core_Schema_Loader
	 */
	private Agent_Neo_Core_Schema_Loader $schema_loader;

	/**
	 * Auth helper。
	 *
	 * @var Agent_Neo_Core_Auth
	 */
	private Agent_Neo_Core_Auth $auth;

	/**
	 * License state helper。
	 *
	 * @var Agent_Neo_Core_License_State
	 */
	private Agent_Neo_Core_License_State $license_state;

	/**
	 * AgentAction CPT。
	 *
	 * @var Agent_Neo_Core_Agent_Action_CPT
	 */
	private Agent_Neo_Core_Agent_Action_CPT $agent_action_cpt;

	/**
	 * Status REST controller。
	 *
	 * @var Agent_Neo_Core_Status_Controller
	 */
	private Agent_Neo_Core_Status_Controller $status_controller;

	/**
	 * catalog-update producer skeleton。
	 *
	 * @var Agent_Neo_Core_Catalog_Update_Producer
	 */
	private Agent_Neo_Core_Catalog_Update_Producer $catalog_update_producer;

	/**
	 * module を生成する。
	 */
	public function __construct() {
		$this->trace_step( 'construct' );

		$this->schema_loader           = new Agent_Neo_Core_Schema_Loader( AGENT_NEO_CORE_DIR . 'schema/' );
		$this->auth                    = new Agent_Neo_Core_Auth();
		$this->license_state           = new Agent_Neo_Core_License_State();
		$this->agent_action_cpt        = new Agent_Neo_Core_Agent_Action_CPT();
		$this->status_controller       = new Agent_Neo_Core_Status_Controller( $this->schema_loader, $this->license_state );
		$this->catalog_update_producer = new Agent_Neo_Core_Catalog_Update_Producer();
	}

	/**
	 * module を WordPress に登録する。
	 *
	 * @return void
	 */
	public function register(): void {
		$this->trace_step( 'register_start' );

		$this->schema_loader->load();
		$this->loaded_modules[] = 'schema-loader';

		$this->auth->register();
		$this->loaded_modules[] = 'auth';

		$this->license_state->register();
		$this->loaded_modules[] = 'license';

		$this->agent_action_cpt->register();
		$this->loaded_modules[] = 'agent-action-cpt';

		$this->status_controller->register();
		$this->loaded_modules[] = 'rest-status';

		$this->catalog_update_producer->register();
		$this->loaded_modules[] = 'catalog-update-producer';

		$this->trace_step( 'register_complete' );
	}

	/**
	 * health サマリを返す。
	 *
	 * @return array<string, mixed>
	 */
	public function health(): array {
		return array(
			'loaded'                  => true,
			'version'                 => AGENT_NEO_CORE_VERSION,
			'loaded_modules'          => $this->loaded_modules,
			'schema_valid'            => $this->schema_loader->is_valid(),
			'schema_errors'           => $this->schema_loader->get_errors(),
			'agent_action_registered' => post_type_exists( Agent_Neo_Core_Agent_Action_CPT::POST_TYPE ),
			'license'                 => $this->license_state->summary(),
			'catalog_update'          => $this->catalog_update_producer->contract_summary(),
			'steps'                   => $this->steps,
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
			error_log( '[agent_neo_core] bootstrap step: ' . $step ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
