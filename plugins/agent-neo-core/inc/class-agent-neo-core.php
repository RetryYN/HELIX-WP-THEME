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
	 * 起動 step の記録。
	 *
	 * @var array<string, bool>
	 */
	private array $steps = array();

	/**
	 * DI container。
	 *
	 * @var Agent_Neo_Core_Container
	 */
	private Agent_Neo_Core_Container $container;

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

		$this->container               = new Agent_Neo_Core_Container( AGENT_NEO_CORE_DIR . 'schema/' );
		$this->catalog_update_producer = new Agent_Neo_Core_Catalog_Update_Producer();
	}

	/**
	 * module を WordPress に登録する。
	 *
	 * @return void
	 */
	public function register(): void {
		$this->trace_step( 'register_start' );

		$container = $this->container;

		$container->schema_loader()->load();
		$container->register_module( 'schema-loader' );

		$container->auth()->register();
		$container->register_module( 'auth' );

		$container->license_state()->register();
		$container->register_module( 'license' );

		$container->agent_action_cpt()->register();
		$container->register_module( 'agent-action-cpt' );

		$design_tokens_presenter = new Agent_Neo_Core_Design_Tokens_Presenter();
		$design_tokens_presenter->register();
		$container->register_module( 'design-tokens-presenter' );

		do_action( 'agent_neo_core_register_rest', $container );

		$this->catalog_update_producer->register();
		$container->register_module( 'catalog-update-producer' );

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
			'loaded_modules'          => $this->container->loaded_modules(),
			'schema_valid'            => $this->container->schema_loader()->is_valid(),
			'schema_errors'           => $this->container->schema_loader()->get_errors(),
			'agent_action_registered' => post_type_exists( Agent_Neo_Core_Agent_Action_CPT::POST_TYPE ),
			'license'                 => $this->container->license_state()->summary(),
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
