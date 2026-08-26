<?php
/**
 * Design tokens presenter — 保存済みデザイントークンを theme.json へ投影する。
 *
 * REQ-F-009 の書込み側（/design-tokens/apply → option 保存）に対する表示側の対。
 * option `agent_neo_core_design_tokens` を wp_theme_json_data_theme で
 * settings（palette / fontFamilies / spacingSizes）へ決定的にマージし、
 * 「API で適用したトークンが実際の描画に反映される」までを製品の責務にする。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * デザイントークンの表示投影。
 */
final class Agent_Neo_Core_Design_Tokens_Presenter {

	/**
	 * トークン格納 option（Design_Tokens_Controller と共有）。
	 */
	private const OPTION_KEY = 'agent_neo_core_design_tokens';

	/**
	 * Hooks を登録する。
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'wp_theme_json_data_theme', array( $this, 'project_tokens' ) );
	}

	/**
	 * option のトークンを theme.json data へマージする。
	 *
	 * @param WP_Theme_JSON_Data $theme_json Theme JSON data。
	 * @return WP_Theme_JSON_Data
	 */
	public function project_tokens( $theme_json ) {
		$tokens = $this->load_tokens();
		if ( empty( $tokens ) ) {
			return $theme_json;
		}

		$settings = array();

		if ( ! empty( $tokens['color'] ) && is_array( $tokens['color'] ) ) {
			$palette = array();
			foreach ( $tokens['color'] as $slug => $hex ) {
				if ( ! is_string( $hex ) || '' === $hex ) {
					continue;
				}
				$palette[] = array(
					'slug'  => (string) $slug,
					'name'  => ucfirst( (string) $slug ),
					'color' => $hex,
				);
			}
			if ( array() !== $palette ) {
				$settings['color'] = array( 'palette' => $palette );
			}
		}

		if ( ! empty( $tokens['font'] ) && is_array( $tokens['font'] ) ) {
			$families = array();
			foreach ( $tokens['font'] as $slug => $family ) {
				if ( ! is_string( $family ) || '' === $family ) {
					continue;
				}
				$families[] = array(
					'slug'       => (string) $slug,
					'name'       => ucfirst( (string) $slug ),
					'fontFamily' => $family,
				);
			}
			if ( array() !== $families ) {
				$settings['typography'] = array( 'fontFamilies' => $families );
			}
		}

		if ( ! empty( $tokens['spacing'] ) && is_array( $tokens['spacing'] ) ) {
			$sizes = array();
			foreach ( $tokens['spacing'] as $slug => $size ) {
				if ( ! is_string( $size ) || '' === $size ) {
					continue;
				}
				$sizes[] = array(
					'slug' => (string) $slug,
					'name' => (string) $slug,
					'size' => $size,
				);
			}
			if ( array() !== $sizes ) {
				$settings['spacing'] = array( 'spacingSizes' => $sizes );
			}
		}

		if ( array() === $settings ) {
			return $theme_json;
		}

		return $theme_json->update_with(
			array(
				'version'  => 3,
				'settings' => $settings,
			)
		);
	}

	/**
	 * option からトークンを読む（Controller の格納形式: JSON 文字列）。
	 *
	 * @return array<string, mixed>
	 */
	private function load_tokens(): array {
		$raw = get_option( self::OPTION_KEY );
		if ( is_string( $raw ) && '' !== $raw ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}
		if ( is_array( $raw ) ) {
			return $raw;
		}
		return array();
	}
}
