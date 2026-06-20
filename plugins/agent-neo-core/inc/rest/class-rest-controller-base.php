<?php
/**
 * Base REST controller.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * agent-neo/v1 namespace を強制する REST controller 基底。
 */
abstract class Agent_Neo_Core_REST_Controller_Base {
	/**
	 * Core Plugin REST namespace。
	 */
	protected const NAMESPACE = 'agent-neo/v1';

	/**
	 * REST route を登録する。
	 *
	 * @return void
	 */
	abstract public function register_routes(): void;

	/**
	 * route path を namespace に登録する。
	 *
	 * @param string               $route Route path。
	 * @param array<string, mixed> $args Route args。
	 * @return bool
	 */
	protected function register_agent_route( string $route, array $args ): bool {
		return register_rest_route( self::NAMESPACE, $route, $args );
	}

	/**
	 * namespace を返す。
	 *
	 * @return string
	 */
	public function namespace(): string {
		return self::NAMESPACE;
	}
}
