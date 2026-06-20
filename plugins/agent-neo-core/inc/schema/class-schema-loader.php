<?php
/**
 * Schema loader.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OpenAPI / JSON Schema を読み込む最小基盤。
 */
final class Agent_Neo_Core_Schema_Loader {
	/**
	 * Schema directory。
	 *
	 * @var string
	 */
	private string $schema_dir;

	/**
	 * JSON Schema cache。
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $schemas = array();

	/**
	 * Validation errors。
	 *
	 * @var array<int, string>
	 */
	private array $errors = array();

	/**
	 * @param string $schema_dir Schema directory。
	 */
	public function __construct( string $schema_dir ) {
		$this->schema_dir = trailingslashit( $schema_dir );
	}

	/**
	 * Schema files を読み込む。
	 *
	 * @return void
	 */
	public function load(): void {
		$this->errors  = array();
		$this->schemas = array();

		$this->validate_openapi_file();
		$this->schemas['status-response'] = $this->read_json_schema( 'status-response.schema.json' );
	}

	/**
	 * 読み込み状態が valid か返す。
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return empty( $this->errors );
	}

	/**
	 * Schema errors を返す。
	 *
	 * @return array<int, string>
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * 簡易 JSON Schema required/type validation。
	 *
	 * @param string               $schema_name Schema name。
	 * @param array<string, mixed> $data Data。
	 * @return true|WP_Error
	 */
	public function validate_schema( string $schema_name, array $data ) {
		$schema = $this->schemas[ $schema_name ] ?? array();

		if ( empty( $schema ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'Schema is not loaded.', 'agent-neo-core' ),
				array( 'schema' => $schema_name )
			);
		}

		$errors = $this->validate_object( $schema, $data, '$' );

		if ( ! empty( $errors ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'Response schema validation failed.', 'agent-neo-core' ),
				array(
					'schema' => $schema_name,
					'errors' => $errors,
				)
			);
		}

		return true;
	}

	/**
	 * openapi.yaml の存在と最小要件を確認する。
	 *
	 * @return void
	 */
	private function validate_openapi_file(): void {
		$path = $this->schema_dir . 'openapi.yaml';

		if ( ! is_readable( $path ) ) {
			$this->errors[] = 'schema file missing: openapi.yaml';
			return;
		}

		$contents = file_get_contents( $path );
		if ( false === $contents ) {
			$this->errors[] = 'schema file unreadable: openapi.yaml';
			return;
		}

		foreach ( array( 'openapi:', '/status:', 'StandardResponse:' ) as $needle ) {
			if ( false === strpos( $contents, $needle ) ) {
				$this->errors[] = 'openapi.yaml missing marker: ' . $needle;
			}
		}
	}

	/**
	 * JSON Schema を配列として読み込む。
	 *
	 * @param string $file File name。
	 * @return array<string, mixed>
	 */
	private function read_json_schema( string $file ): array {
		$path = $this->schema_dir . $file;

		if ( ! is_readable( $path ) ) {
			$this->errors[] = 'schema file missing: ' . $file;
			return array();
		}

		$contents = file_get_contents( $path );
		if ( false === $contents ) {
			$this->errors[] = 'schema file unreadable: ' . $file;
			return array();
		}

		$data = json_decode( $contents, true );
		if ( ! is_array( $data ) ) {
			$this->errors[] = 'schema json invalid: ' . $file;
			return array();
		}

		return $data;
	}

	/**
	 * object schema の required/type を検証する。
	 *
	 * @param array<string, mixed> $schema Schema。
	 * @param array<string, mixed> $data Data。
	 * @param string               $path Path。
	 * @return array<int, string>
	 */
	private function validate_object( array $schema, array $data, string $path ): array {
		$errors   = array();
		$required = $schema['required'] ?? array();

		if ( is_array( $required ) ) {
			foreach ( $required as $required_key ) {
				if ( is_string( $required_key ) && ! array_key_exists( $required_key, $data ) ) {
					$errors[] = $path . '.' . $required_key . ' is required';
				}
			}
		}

		$properties = $schema['properties'] ?? array();
		if ( ! is_array( $properties ) ) {
			return $errors;
		}

		foreach ( $properties as $key => $property_schema ) {
			if ( ! is_string( $key ) || ! is_array( $property_schema ) || ! array_key_exists( $key, $data ) ) {
				continue;
			}

			$errors = array_merge( $errors, $this->validate_value( $property_schema, $data[ $key ], $path . '.' . $key ) );
		}

		return $errors;
	}

	/**
	 * 値の型と nested object を検証する。
	 *
	 * @param array<string, mixed> $schema Schema。
	 * @param mixed                $value Value。
	 * @param string               $path Path。
	 * @return array<int, string>
	 */
	private function validate_value( array $schema, $value, string $path ): array {
		$type     = $schema['type'] ?? '';
		$nullable = ! empty( $schema['nullable'] );

		if ( null === $value ) {
			return $nullable ? array() : array( $path . ' must not be null' );
		}

		if ( 'boolean' === $type && ! is_bool( $value ) ) {
			return array( $path . ' must be boolean' );
		}

		if ( 'string' === $type && ! is_string( $value ) ) {
			return array( $path . ' must be string' );
		}

		if ( 'object' === $type ) {
			if ( ! is_array( $value ) ) {
				return array( $path . ' must be object' );
			}

			return $this->validate_object( $schema, $value, $path );
		}

		return array();
	}
}
