<?php
/**
 * AGENT NEO Core Plugin の軽量 DI コンテナ。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 共有依存を lazy に生成し、REST controller の自己登録へ渡す。
 */
final class Agent_Neo_Core_Container {
	/**
	 * Schema directory。
	 *
	 * @var string
	 */
	private string $schema_dir;

	/**
	 * 読み込み済み module。
	 *
	 * @var array<int, string>
	 */
	private array $loaded_modules = array();

	private ?Agent_Neo_Core_Schema_Loader $schema_loader = null;
	private ?Agent_Neo_Core_Auth $auth = null;
	private ?Agent_Neo_Core_License_State $license_state = null;
	private ?Agent_Neo_Core_Agent_Action_CPT $agent_action_cpt = null;
	private ?Agent_Neo_Core_JSON_Patch $json_patch = null;
	private ?Agent_Neo_Core_Dry_Run_Store $dry_run_store = null;
	private ?Agent_Neo_Core_Idempotency_Store $idempotency_store = null;
	private ?Agent_Neo_Core_Rollback_Store $rollback_store = null;
	private ?Agent_Neo_Core_Audit_Log $audit_log = null;

	/**
	 * @param string $schema_dir Schema directory。
	 */
	public function __construct( string $schema_dir ) {
		$this->schema_dir = trailingslashit( $schema_dir );
	}

	/**
	 * @return Agent_Neo_Core_Schema_Loader
	 */
	public function schema_loader(): Agent_Neo_Core_Schema_Loader {
		if ( null === $this->schema_loader ) {
			$this->schema_loader = new Agent_Neo_Core_Schema_Loader( $this->schema_dir );
		}

		return $this->schema_loader;
	}

	/**
	 * @return Agent_Neo_Core_Auth
	 */
	public function auth(): Agent_Neo_Core_Auth {
		if ( null === $this->auth ) {
			$this->auth = new Agent_Neo_Core_Auth();
		}

		return $this->auth;
	}

	/**
	 * @return Agent_Neo_Core_License_State
	 */
	public function license_state(): Agent_Neo_Core_License_State {
		if ( null === $this->license_state ) {
			$this->license_state = new Agent_Neo_Core_License_State();
		}

		return $this->license_state;
	}

	/**
	 * @return Agent_Neo_Core_Agent_Action_CPT
	 */
	public function agent_action_cpt(): Agent_Neo_Core_Agent_Action_CPT {
		if ( null === $this->agent_action_cpt ) {
			$this->agent_action_cpt = new Agent_Neo_Core_Agent_Action_CPT();
		}

		return $this->agent_action_cpt;
	}

	/**
	 * @return Agent_Neo_Core_JSON_Patch
	 */
	public function json_patch(): Agent_Neo_Core_JSON_Patch {
		if ( null === $this->json_patch ) {
			$this->json_patch = new Agent_Neo_Core_JSON_Patch();
		}

		return $this->json_patch;
	}

	/**
	 * @return Agent_Neo_Core_Dry_Run_Store
	 */
	public function dry_run_store(): Agent_Neo_Core_Dry_Run_Store {
		if ( null === $this->dry_run_store ) {
			$this->dry_run_store = new Agent_Neo_Core_Dry_Run_Store();
		}

		return $this->dry_run_store;
	}

	/**
	 * @return Agent_Neo_Core_Idempotency_Store
	 */
	public function idempotency_store(): Agent_Neo_Core_Idempotency_Store {
		if ( null === $this->idempotency_store ) {
			$this->idempotency_store = new Agent_Neo_Core_Idempotency_Store();
		}

		return $this->idempotency_store;
	}

	/**
	 * @return Agent_Neo_Core_Rollback_Store
	 */
	public function rollback_store(): Agent_Neo_Core_Rollback_Store {
		if ( null === $this->rollback_store ) {
			$this->rollback_store = new Agent_Neo_Core_Rollback_Store();
		}

		return $this->rollback_store;
	}

	/**
	 * @return Agent_Neo_Core_Audit_Log
	 */
	public function audit_log(): Agent_Neo_Core_Audit_Log {
		if ( null === $this->audit_log ) {
			$this->audit_log = new Agent_Neo_Core_Audit_Log();
		}

		return $this->audit_log;
	}

	/**
	 * health 用に module 登録を記録する。
	 *
	 * @param string $name Module name。
	 * @return void
	 */
	public function register_module( string $name ): void {
		if ( in_array( $name, $this->loaded_modules, true ) ) {
			return;
		}

		$this->loaded_modules[] = $name;
	}

	/**
	 * @return array<int, string>
	 */
	public function loaded_modules(): array {
		return $this->loaded_modules;
	}
}
