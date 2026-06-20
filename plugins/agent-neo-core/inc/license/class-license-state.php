<?php
/**
 * License state skeleton.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Automation SEO entitlement の読み取り骨組み。
 */
final class Agent_Neo_Core_License_State {
	/**
	 * Option name。
	 */
	private const OPTION_NAME = 'agent_neo_license_state';

	/**
	 * Hook 登録の拡張点。
	 *
	 * @return void
	 */
	public function register(): void {
		/**
		 * T-013 で /license/validate と Automation SEO entitlement 同期を追加する。
		 */
	}

	/**
	 * license mode を返す。
	 *
	 * @return string
	 */
	public function license_mode(): string {
		$state = $this->state();
		return isset( $state['license_mode'] ) && is_string( $state['license_mode'] ) ? $state['license_mode'] : 'readonly';
	}

	/**
	 * package を返す。
	 *
	 * @return string
	 */
	public function package(): string {
		$state = $this->state();
		return isset( $state['package'] ) && is_string( $state['package'] ) ? $state['package'] : 'personal';
	}

	/**
	 * 連携状態を返す。
	 *
	 * @return string
	 */
	public function integration_status(): string {
		$state = $this->state();
		return isset( $state['integration_status'] ) && is_string( $state['integration_status'] ) ? $state['integration_status'] : 'not_configured';
	}

	/**
	 * License summary を返す。
	 *
	 * @return array<string, mixed>
	 */
	public function summary(): array {
		return array(
			'license_mode'       => $this->license_mode(),
			'package'            => $this->package(),
			'integration_status' => $this->integration_status(),
		);
	}

	/**
	 * Option state を返す。
	 *
	 * @return array<string, mixed>
	 */
	private function state(): array {
		$state = get_option( self::OPTION_NAME, array() );
		return is_array( $state ) ? $state : array();
	}
}
