<?php
/**
 * Plugin Name: AGENT NEO Abilities Verify
 * Description: WP 6.9+ / WP 7.0 Abilities API の登録契約を検証する mu-plugin。
 * Version: 0.1.0
 *
 * @package AgentNeoAbilitiesVerify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AGENT NEO 検証用 category を登録する。
 */
add_action(
	'wp_abilities_api_categories_init',
	static function () {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			'agent-neo',
			array(
				'label'       => 'AGENT NEO',
				'description' => 'AGENT-NEO abilities',
			)
		);
	}
);

/**
 * AGENT NEO 検証用 ability を登録する。
 */
add_action(
	'wp_abilities_api_init',
	static function () {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			'agent-neo/diag-ping',
			array(
				'label'               => 'AGENT NEO diagnostic ping',
				'description'         => 'Returns a deterministic pong response for Abilities API verification.',
				'category'            => 'agent-neo',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'echo' => array(
							'type'        => 'string',
							'description' => 'Value echoed in the diagnostic response.',
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'pong' => array(
							'type' => 'boolean',
						),
						'echo' => array(
							'type' => 'string',
						),
					),
					'required'   => array( 'pong', 'echo' ),
				),
				'execute_callback'    => static function ( $input ) {
					$echo = '';

					if ( is_array( $input ) && isset( $input['echo'] ) ) {
						$echo = (string) $input['echo'];
					}

					return array(
						'pong' => true,
						'echo' => $echo,
					);
				},
				'permission_callback' => '__return_true',
			)
		);
	}
);
