<?php
/**
 * Theme/Core Plugin 境界 guard。
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme が所有してはいけない責務を manifest から検査する。
 */
final class Agent_Neo_Boundary_Guard {
	/**
	 * Boundary エラー。
	 *
	 * @var array<int, string>
	 */
	private array $errors = array();

	/**
	 * Manifest boundary を検証する。
	 *
	 * @param array<string, mixed> $manifest Theme manifest。
	 * @return void
	 */
	public function validate( array $manifest ): void {
		$boundary = $manifest['boundary'] ?? array();

		if ( ! is_array( $boundary ) ) {
			$this->errors[] = 'boundary must be an object';
			return;
		}

		$this->require_owner( $boundary, 'fse_templates_patterns_styles', 'theme' );
		$this->require_owner( $boundary, 'visual_only_block', 'theme' );
		$this->require_owner( $boundary, 'section_id_cta_id_attributes', 'theme' );
		$this->require_owner( $boundary, 'seo_head_render', 'theme_adapter' );
		$this->require_owner( $boundary, 'json_operation_api', 'core_plugin' );
		$this->require_owner( $boundary, 'cpt_ab_tracking_storage', 'core_plugin' );
		$this->require_owner( $boundary, 'catalog_update_trigger', 'core_plugin' );
	}

	/**
	 * Boundary が valid か返す。
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return empty( $this->errors );
	}

	/**
	 * エラー一覧を返す。
	 *
	 * @return array<int, string>
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * owner 宣言を検証する。
	 *
	 * @param array<string, mixed> $boundary Boundary section。
	 * @param string               $key Boundary key。
	 * @param string               $expected 期待 owner。
	 * @return void
	 */
	private function require_owner( array $boundary, string $key, string $expected ): void {
		$item = $boundary[ $key ] ?? null;

		if ( ! is_array( $item ) || ! isset( $item['owner'] ) || $expected !== $item['owner'] ) {
			$this->errors[] = 'boundary.' . $key . '.owner must be ' . $expected;
		}

		if ( isset( $item['theme_allowed'] ) && false === $item['theme_allowed'] && 'theme' === $expected ) {
			$this->errors[] = 'boundary.' . $key . ' conflicts with theme ownership';
		}

		if (
			'core_plugin' === $expected
			&& isset( $item['theme_allowed'] )
			&& true === $item['theme_allowed']
		) {
			$this->errors[] = 'boundary.' . $key
				. ' (core-plugin-owned) must not grant theme ownership (theme_allowed=true)';
		}
	}
}
