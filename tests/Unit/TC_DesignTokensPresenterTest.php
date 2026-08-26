<?php
/**
 * Design token の theme.json 投影テスト。
 *
 * @package AgentNeoCore\Tests\Unit
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * theme.json data の最小テストダブル。
 */
final class ThemeJsonDataDouble {
	/** @var array<string,mixed> */
	public array $update = array();

	/**
	 * @param array<string,mixed> $update 投影内容。
	 * @return self
	 */
	public function update_with( array $update ): self {
		$this->update = $update;
		return $this;
	}
}

/**
 * Agent_Neo_Core_Design_Tokens_Presenter の契約テスト。
 */
final class TC_DesignTokensPresenterTest extends TestCase {
	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();

		if ( ! class_exists( 'Agent_Neo_Core_Design_Tokens_Presenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/design/class-design-tokens-presenter.php';
		}
	}

	protected function tear_down(): void {
		Monkey\tearDown();
		parent::tear_down();
	}

	public function test_registers_theme_json_filter(): void {
		$presenter = new \Agent_Neo_Core_Design_Tokens_Presenter();

		Functions\expect( 'add_filter' )
			->once()
			->with( 'wp_theme_json_data_theme', array( $presenter, 'project_tokens' ) );

		$presenter->register();
		$this->assertTrue( true );
	}

	public function test_projects_saved_tokens_without_losing_numeric_spacing_slug(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'agent_neo_core_design_tokens' )
			->andReturn(
				json_encode(
					array(
						'color'   => array( 'brand' => '#123456' ),
						'font'    => array( 'body' => 'system-ui' ),
						'spacing' => array( '10' => '0.5rem' ),
					)
				)
			);
		$data      = new ThemeJsonDataDouble();
		$presenter = new \Agent_Neo_Core_Design_Tokens_Presenter();
		$result    = $presenter->project_tokens( $data );

		$this->assertSame( $data, $result );
		$this->assertSame( 3, $data->update['version'] );
		$this->assertSame( 'brand', $data->update['settings']['color']['palette'][0]['slug'] );
		$this->assertSame( 'body', $data->update['settings']['typography']['fontFamilies'][0]['slug'] );
		$this->assertSame( '10', $data->update['settings']['spacing']['spacingSizes'][0]['slug'] );
		$this->assertSame( '0.5rem', $data->update['settings']['spacing']['spacingSizes'][0]['size'] );
	}

	public function test_returns_original_data_when_no_tokens_are_saved(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'agent_neo_core_design_tokens' )
			->andReturn( false );

		$data      = new ThemeJsonDataDouble();
		$presenter = new \Agent_Neo_Core_Design_Tokens_Presenter();

		$this->assertSame( $data, $presenter->project_tokens( $data ) );
		$this->assertSame( array(), $data->update );
	}
}
