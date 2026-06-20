<?php
/**
 * catalog-update producer skeleton.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * D-PLUGIN-CONTRACT §17 を参照する producer 配置のみを提供する。
 */
final class Agent_Neo_Core_Catalog_Update_Producer {
	/**
	 * Hook 登録の拡張点。
	 *
	 * @return void
	 */
	public function register(): void {
		/**
		 * T-014/T-015 で送信、outbox、retry、DLQ を追加する。
		 * この scaffold では外部送信を一切行わない。
		 */
	}

	/**
	 * 契約参照サマリを返す。
	 *
	 * @return array<string, mixed>
	 */
	public function contract_summary(): array {
		return array(
			'producer'          => 'agent-neo-core',
			'consumer'          => 'automation-seo',
			'contract'          => 'D-PLUGIN-CONTRACT §17 / §17.11',
			'implemented'       => false,
			'implementation_in' => 'T-014/T-015',
			'retry'             => array(
				'initial_backoff_seconds' => 1,
				'multiplier'              => 2,
				'max_attempts'            => 5,
				'jitter'                  => '+/-10%',
			),
		);
	}
}
