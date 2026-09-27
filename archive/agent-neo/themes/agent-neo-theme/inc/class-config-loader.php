<?php
/**
 * テーマ config JSON loader。
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * config/*.json を読み込み、最小 schema を fail-fast 検証する。
 */
final class Agent_Neo_Config_Loader {
	/**
	 * Config ディレクトリ。
	 *
	 * @var string
	 */
	private string $config_dir;

	/**
	 * 読み込み済み config。
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $configs = array();

	/**
	 * 検証エラー。
	 *
	 * @var array<int, string>
	 */
	private array $errors = array();

	/**
	 * @param string $config_dir Config ディレクトリ。
	 */
	public function __construct( string $config_dir ) {
		$this->config_dir = trailingslashit( $config_dir );
	}

	/**
	 * Config を読み込んで検証する。
	 *
	 * @return void
	 */
	public function load(): void {
		$this->configs = array(
			'theme-manifest'  => $this->read_json_file( 'theme-manifest.json' ),
			'section-registry' => $this->read_json_file( 'section-registry.json' ),
			'asset-policy'    => $this->read_json_file( 'asset-policy.json' ),
			'schema-reference' => $this->read_json_file( 'schema-reference.json' ),
		);

		$this->validate_manifest();
		$this->validate_section_registry();
		$this->validate_asset_policy();
		$this->validate_schema_reference();
	}

	/**
	 * Config data を返す。
	 *
	 * @param string $name Config 名。
	 * @return array<string, mixed>
	 */
	public function get( string $name ): array {
		return $this->configs[ $name ] ?? array();
	}

	/**
	 * Config が valid か返す。
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
	 * 読み込み対象ファイル名を返す。
	 *
	 * @return array<int, string>
	 */
	public function registered_files(): array {
		return array(
			'theme-manifest.json',
			'section-registry.json',
			'asset-policy.json',
			'schema-reference.json',
		);
	}

	/**
	 * JSON ファイルを配列として読み込む。
	 *
	 * @param string $file ファイル名。
	 * @return array<string, mixed>
	 */
	private function read_json_file( string $file ): array {
		$path = $this->config_dir . $file;

		if ( ! is_readable( $path ) ) {
			$this->errors[] = 'config file missing: ' . $file;
			return array();
		}

		$contents = file_get_contents( $path );
		if ( false === $contents ) {
			$this->errors[] = 'config file unreadable: ' . $file;
			return array();
		}

		$data = json_decode( $contents, true );
		if ( ! is_array( $data ) ) {
			$this->errors[] = 'config json invalid: ' . $file;
			return array();
		}

		return $data;
	}

	/**
	 * theme-manifest.json を検証する。
	 *
	 * @return void
	 */
	private function validate_manifest(): void {
		$manifest = $this->get( 'theme-manifest' );

		$this->require_string( $manifest, 'name', 'theme-manifest' );
		$this->require_string( $manifest, 'version', 'theme-manifest' );
		$this->require_exact_string( $manifest, 'text_domain', 'agent-neo', 'theme-manifest' );
		$this->require_string_list( $manifest, 'modules', 'theme-manifest' );

		if ( empty( $manifest['boundary'] ) || ! is_array( $manifest['boundary'] ) ) {
			$this->errors[] = 'theme-manifest.boundary must be an object';
		}
	}

	/**
	 * section-registry.json を検証する。
	 *
	 * @return void
	 */
	private function validate_section_registry(): void {
		$registry = $this->get( 'section-registry' );

		if ( empty( $registry['sections'] ) || ! is_array( $registry['sections'] ) ) {
			$this->errors[] = 'section-registry.sections must be an array';
			return;
		}

		$section_ids = array();
		foreach ( $registry['sections'] as $index => $section ) {
			if ( ! is_array( $section ) ) {
				$this->errors[] = 'section-registry.sections[' . $index . '] must be an object';
				continue;
			}

			$this->require_string( $section, 'section_id', 'section-registry.sections[' . $index . ']' );
			$this->require_string( $section, 'type', 'section-registry.sections[' . $index . ']' );
			$this->require_string( $section, 'template', 'section-registry.sections[' . $index . ']' );
			$this->require_string( $section, 'pattern', 'section-registry.sections[' . $index . ']' );

			if ( isset( $section['section_id'] ) && is_string( $section['section_id'] ) ) {
				$section_ids[] = $section['section_id'];
			}
		}

		$required = $registry['required_sections'] ?? array();
		if ( ! is_array( $required ) ) {
			$this->errors[] = 'section-registry.required_sections must be an array';
			return;
		}

		foreach ( $required as $section_id ) {
			if ( ! is_string( $section_id ) || ! in_array( $section_id, $section_ids, true ) ) {
				$this->errors[] = 'section-registry missing required section_id: ' . (string) $section_id;
			}
		}
	}

	/**
	 * asset-policy.json を検証する。
	 *
	 * @return void
	 */
	private function validate_asset_policy(): void {
		$policy = $this->get( 'asset-policy' );

		foreach ( array( 'routes', 'blocks', 'parts' ) as $key ) {
			if ( empty( $policy[ $key ] ) || ! is_array( $policy[ $key ] ) ) {
				$this->errors[] = 'asset-policy.' . $key . ' must be an object';
			}
		}
	}

	/**
	 * schema-reference.json を検証する。
	 *
	 * @return void
	 */
	private function validate_schema_reference(): void {
		$reference = $this->get( 'schema-reference' );

		$this->require_exact_string( $reference, 'json_prefix', 'agent_neo', 'schema-reference' );
		$this->require_exact_string( $reference, 'naming', 'snake_case', 'schema-reference' );

		if ( empty( $reference['schemas'] ) || ! is_array( $reference['schemas'] ) ) {
			$this->errors[] = 'schema-reference.schemas must be an array';
		}
	}

	/**
	 * 文字列フィールドを検証する。
	 *
	 * @param array<string, mixed> $data データ。
	 * @param string               $key キー。
	 * @param string               $context コンテキスト。
	 * @return void
	 */
	private function require_string( array $data, string $key, string $context ): void {
		if ( empty( $data[ $key ] ) || ! is_string( $data[ $key ] ) ) {
			$this->errors[] = $context . '.' . $key . ' must be a string';
		}
	}

	/**
	 * 文字列フィールドの完全一致を検証する。
	 *
	 * @param array<string, mixed> $data データ。
	 * @param string               $key キー。
	 * @param string               $expected 期待値。
	 * @param string               $context コンテキスト。
	 * @return void
	 */
	private function require_exact_string( array $data, string $key, string $expected, string $context ): void {
		if ( ! isset( $data[ $key ] ) || $expected !== $data[ $key ] ) {
			$this->errors[] = $context . '.' . $key . ' must be ' . $expected;
		}
	}

	/**
	 * 文字列配列を検証する。
	 *
	 * @param array<string, mixed> $data データ。
	 * @param string               $key キー。
	 * @param string               $context コンテキスト。
	 * @return void
	 */
	private function require_string_list( array $data, string $key, string $context ): void {
		if ( empty( $data[ $key ] ) || ! is_array( $data[ $key ] ) ) {
			$this->errors[] = $context . '.' . $key . ' must be an array';
			return;
		}

		foreach ( $data[ $key ] as $value ) {
			if ( ! is_string( $value ) ) {
				$this->errors[] = $context . '.' . $key . ' must contain only strings';
				return;
			}
		}
	}
}
