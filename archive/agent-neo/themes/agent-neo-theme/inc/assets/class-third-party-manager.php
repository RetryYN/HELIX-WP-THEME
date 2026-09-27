<?php
/**
 * サードパーティタグ管理 — 同意ゲート・遅延ロード制御。
 *
 * 設計根拠: 旧 L3-A4-performance-contract-gaps.md（削除済み） §GAP-RT-022
 *
 * 責務:
 *   - wp_head（priority 1）で Consent Mode v2 の consent default を出力する。
 *   - GA4（loadStrategy=async_after_consent）用の consent JS を enqueue する。
 *   - 同意バナー（PERF-CARRY-002 暫定内蔵バナー）を wp_footer で出力する。
 *   - advertising カテゴリのタグは同意なし状態では DOM に出力しない。
 *   - pageConditions（allowedPageTypes/blockedPageTypes）を is_singular() 等で照合する。
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * サードパーティタグ管理クラス。
 */
final class Agent_Neo_Third_Party_Manager {

	/**
	 * Config データ（third-party-tags.json）。
	 *
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * @param array<string, mixed> $config third-party-tags.json の内容。
	 */
	public function __construct( array $config ) {
		$this->config = $config;
	}

	/**
	 * WordPress フックを登録する。
	 *
	 * @return void
	 */
	public function register(): void {
		// consent default は <head> 最上位（priority 1）で出力する。
		// これは同意前でも出力される正常仕様（計測 ping ではない）。
		add_action( 'wp_head', array( $this, 'output_consent_default' ), 1 );

		// consent JS と同意バナー用スクリプトを enqueue する。
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_consent_assets' ) );

		// 同意バナー（暫定内蔵バナー）を wp_footer で出力する。
		add_action( 'wp_footer', array( $this, 'output_consent_banner' ) );
	}

	/**
	 * Consent Mode v2 の consent default を <head> 最上位に出力する。
	 *
	 * <head> の最上位（他のスクリプトより先）に配置することで、
	 * GA4 タグが読み込まれる前に denied 状態を宣言できる。
	 * これはサーバーサイドで必ず出力する（同意前/後に関わらず）。
	 *
	 * @return void
	 */
	public function output_consent_default(): void {
		$default_state = $this->config['defaultConsentState'] ?? array(
			'analytics_storage'  => 'denied',
			'ad_storage'         => 'denied',
			'ad_user_data'       => 'denied',
			'ad_personalization' => 'denied',
		);

		$consent_version = $this->config['consentModeVersion'] ?? 'v2';

		// dataLayer の初期化と gtag 関数の宣言は consent default より先に出力する。
		// gtag.js 本体は同意後に動的挿入するため、ここでは宣言のみ行う。
		?>
		<!-- AGENT NEO Consent Mode <?php echo esc_html( $consent_version ); ?> — denied default -->
		<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('consent', 'default', {
			analytics_storage: <?php echo wp_json_encode( $default_state['analytics_storage'] ?? 'denied' ); ?>,
			ad_storage: <?php echo wp_json_encode( $default_state['ad_storage'] ?? 'denied' ); ?>,
			ad_user_data: <?php echo wp_json_encode( $default_state['ad_user_data'] ?? 'denied' ); ?>,
			ad_personalization: <?php echo wp_json_encode( $default_state['ad_personalization'] ?? 'denied' ); ?>,
			wait_for_update: 2000
		});
		</script>
		<?php
	}

	/**
	 * 同意ゲート用 JS と wp_localize_script によるデータ注入を行う。
	 *
	 * GA4 の gtag.js 本体はここでは enqueue しない。
	 * 同意後に consent.js が動的に <script async> を挿入する（parser-blocking でない方式）。
	 *
	 * @return void
	 */
	public function enqueue_consent_assets(): void {
		$ga4_tag = $this->get_ga4_tag();

		if ( ! $ga4_tag ) {
			return;
		}

		// measurement ID をフィルタで上書き可能にする（テスト・CI でのオーバーライド用）。
		// 例: add_filter( 'agent_neo_ga4_measurement_id', fn() => 'G-XXXXXXXXXX' );
		$measurement_id = apply_filters(
			'agent_neo_ga4_measurement_id',
			$ga4_tag['measurementId'] ?? 'G-TEST0000'
		);

		// フィルタ後の値を検証する。不正な値（XSS ペイロード等）は安全なデフォルトに置換。
		// 許容形式: G-XXXXXXXXXX / GT-XXXXXXXXXX（英数字 1〜16 文字）
		if ( ! preg_match( '/\A(?:G|GT)-[A-Z0-9]{1,16}\z/', $measurement_id ) ) {
			$measurement_id = 'G-TEST0000';
		}

		wp_enqueue_script(
			'agent-neo-consent',
			AGENT_NEO_URI . 'assets/js/consent.js',
			array(),
			AGENT_NEO_VERSION,
			false // </body> でなく <head> に出力（バナー表示が body より先に初期化される必要がある）
		);

		// PHP 側データを JS へ注入する（wp_localize_script パターン）。
		wp_localize_script(
			'agent-neo-consent',
			'agentNeoConsentData',
			array(
				'measurementId' => $measurement_id,
				'version'       => AGENT_NEO_VERSION,
			)
		);
	}

	/**
	 * 同意バナー（PERF-CARRY-002 暫定内蔵バナー）を wp_footer で出力する。
	 *
	 * 本番では外部プラグイン（CookieYes / Complianz 等）に差し替える。
	 * 差し替え方法: wp_footer フックで独自バナーを出力し、
	 *   window.agent_neo_consent.updateConsent() を呼び出す JavaScript を実装する。
	 *
	 * a11y 対応:
	 *   - role="dialog" / aria-modal / aria-labelledby でスクリーンリーダーに通知。
	 *   - tabindex="-1" でプログラム的フォーカス管理が可能。
	 *
	 * @return void
	 */
	public function output_consent_banner(): void {
		// 外部プラグインがバナーを管理している場合はこのバナーを出力しない。
		// フィルタで無効化できる: add_filter( 'agent_neo_show_consent_banner', '__return_false' );
		if ( ! apply_filters( 'agent_neo_show_consent_banner', true ) ) {
			return;
		}
		?>
		<!--
		 * PERF-CARRY-002 暫定内蔵バナー / 本番は外部プラグイン差し替え可
		 * 差し替え方法: 'agent_neo_show_consent_banner' フィルタを false に設定し、
		 *   独自バナーから window.agent_neo_consent.updateConsent({ analytics_storage: 'granted' })
		 *   を呼び出すことで同意ゲートが機能する。
		 -->
		<div id="agent-neo-consent-banner"
			 class="agent-neo-consent-banner"
			 role="dialog"
			 aria-modal="false"
			 aria-labelledby="agent-neo-consent-title"
			 tabindex="-1"
			 style="display: none;">
			<div class="agent-neo-consent-banner__inner">
				<p id="agent-neo-consent-title" class="agent-neo-consent-banner__text">
					<?php esc_html_e( 'このサイトでは、利用状況の分析のために Cookie を使用しています。同意する場合は「すべて受け入れる」を、拒否する場合は「拒否する」をクリックしてください。', 'agent-neo' ); ?>
				</p>
				<div class="agent-neo-consent-banner__actions">
					<button id="agent-neo-consent-accept"
							class="agent-neo-consent-btn agent-neo-consent-btn--accept"
							type="button"
							aria-label="<?php esc_attr_e( 'すべての Cookie を受け入れる', 'agent-neo' ); ?>">
						<?php esc_html_e( 'すべて受け入れる', 'agent-neo' ); ?>
					</button>
					<button id="agent-neo-consent-deny"
							class="agent-neo-consent-btn agent-neo-consent-btn--deny"
							type="button"
							aria-label="<?php esc_attr_e( 'Cookie の使用を拒否する', 'agent-neo' ); ?>">
						<?php esc_html_e( '拒否する', 'agent-neo' ); ?>
					</button>
				</div>
			</div>
		</div>
		<style>
		.agent-neo-consent-banner {
			position: fixed;
			bottom: 0;
			left: 0;
			right: 0;
			background-color: var(--wp--preset--color--background, #ffffff);
			border-top: 2px solid var(--wp--preset--color--accent-aa, var(--wp--preset--color--accent, #ff6b00));
			box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.1);
			z-index: 9999;
			padding: 1rem 1.5rem;
		}
		.agent-neo-consent-banner__inner {
			max-width: 960px;
			margin: 0 auto;
			display: flex;
			flex-wrap: wrap;
			align-items: center;
			gap: 1rem;
		}
		.agent-neo-consent-banner__text {
			flex: 1 1 280px;
			margin: 0;
			font-size: 0.875rem;
			color: var(--wp--preset--color--foreground, #1a1a1a);
			line-height: 1.6;
		}
		.agent-neo-consent-banner__actions {
			display: flex;
			gap: 0.75rem;
			flex-wrap: wrap;
		}
		.agent-neo-consent-btn {
			padding: 0.5rem 1.25rem;
			border-radius: 4px;
			font-size: 0.875rem;
			font-weight: 600;
			cursor: pointer;
			border: 2px solid transparent;
			transition: opacity 0.2s;
			white-space: nowrap;
		}
		.agent-neo-consent-btn:hover {
			opacity: 0.85;
		}
		.agent-neo-consent-btn:focus-visible {
			outline: 3px solid var(--wp--preset--color--accent-aa, #cc4400);
			outline-offset: 2px;
		}
		.agent-neo-consent-btn--accept {
			background-color: var(--wp--preset--color--accent-aa, var(--wp--preset--color--accent, #bf5200));
			color: var(--wp--preset--color--background, #ffffff);
		}
		.agent-neo-consent-btn--deny {
			background-color: transparent;
			color: var(--wp--preset--color--foreground, #1a1a1a);
			border-color: var(--wp--preset--color--muted, #6b6b6b);
		}
		@media (max-width: 600px) {
			.agent-neo-consent-banner__inner {
				flex-direction: column;
				align-items: stretch;
			}
			.agent-neo-consent-banner__actions {
				justify-content: stretch;
			}
			.agent-neo-consent-btn {
				flex: 1;
				text-align: center;
			}
		}
		</style>
		<script>
		( function () {
			var acceptBtn = document.getElementById( 'agent-neo-consent-accept' );
			var denyBtn   = document.getElementById( 'agent-neo-consent-deny' );

			if ( acceptBtn ) {
				acceptBtn.addEventListener( 'click', function () {
					if ( window.agent_neo_consent && window.agent_neo_consent.updateConsent ) {
						window.agent_neo_consent.updateConsent( {
							analytics_storage:  'granted',
							ad_storage:         'denied',
							ad_user_data:       'denied',
							ad_personalization: 'denied'
						} );
					}
				} );
			}

			if ( denyBtn ) {
				denyBtn.addEventListener( 'click', function () {
					if ( window.agent_neo_consent && window.agent_neo_consent.updateConsent ) {
						window.agent_neo_consent.updateConsent( {
							analytics_storage:  'denied',
							ad_storage:         'denied',
							ad_user_data:       'denied',
							ad_personalization: 'denied'
						} );
					}
				} );
			}
		} )();
		</script>
		<?php
	}

	/**
	 * 現在のページタイプを返す。
	 *
	 * L3-A4 §GAP-RT-022 の pageConditions 照合に使用する。
	 *
	 * @return string ページタイプ文字列（article / archive / home / lp / fixed / search / other）
	 */
	private function get_current_page_type(): string {
		if ( is_singular( 'post' ) ) {
			return 'article';
		}
		if ( is_archive() || is_category() || is_tag() ) {
			return 'archive';
		}
		if ( is_front_page() || is_home() ) {
			return 'home';
		}
		if ( is_page_template() ) {
			// LP テンプレートの判定: page-lp または lp を含むテンプレート名で識別する
			$template = get_page_template_slug();
			if ( $template && str_contains( $template, 'lp' ) ) {
				return 'lp';
			}
			return 'fixed';
		}
		if ( is_singular( 'page' ) ) {
			return 'fixed';
		}
		if ( is_search() ) {
			return 'search';
		}
		return 'other';
	}

	/**
	 * タグの pageConditions を現在のページと照合する。
	 *
	 * @param array<string, mixed> $tag タグ定義。
	 * @return bool このページでタグを出力すべきか。
	 */
	private function is_tag_allowed_on_current_page( array $tag ): bool {
		$conditions = $tag['pageConditions'] ?? array();

		if ( empty( $conditions ) ) {
			return true;
		}

		$current_page_type = $this->get_current_page_type();

		// blockedPageTypes に一致する場合は出力しない。
		$blocked = $conditions['blockedPageTypes'] ?? array();
		if ( in_array( $current_page_type, $blocked, true ) ) {
			return false;
		}

		// allowedPageTypes が 'all' を含む、または空の場合は全ページで許可。
		$allowed = $conditions['allowedPageTypes'] ?? array();
		if ( empty( $allowed ) || in_array( 'all', $allowed, true ) ) {
			return true;
		}

		return in_array( $current_page_type, $allowed, true );
	}

	/**
	 * analytics カテゴリの GA4 タグ定義を返す。
	 *
	 * @return array<string, mixed>|null
	 */
	private function get_ga4_tag(): ?array {
		$tags = $this->config['tags'] ?? array();
		foreach ( $tags as $tag ) {
			if (
				is_array( $tag ) &&
				( $tag['category'] ?? '' ) === 'analytics' &&
				( $tag['loadStrategy'] ?? '' ) === 'async_after_consent'
			) {
				if ( $this->is_tag_allowed_on_current_page( $tag ) ) {
					return $tag;
				}
			}
		}
		return null;
	}
}
