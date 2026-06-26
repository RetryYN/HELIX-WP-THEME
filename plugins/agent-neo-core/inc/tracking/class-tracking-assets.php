<?php
/**
 * ad-tracking.js の enqueue・token 供給・consent gate 設定。
 *
 * - token/hmacKey を option から読み（無ければ初回生成）localize_script で注入する。
 * - consent キー（agent_neo_consent_v2）も localize して JS 側の consent gate と一致させる。
 * - bootstrap.php の既存 enqueue（プラグイン単体用）に localize を重ねる形で動作する。
 *
 * token/hmac 生成根拠:
 *   class-tracking-controller.php の tracking_secrets() は
 *   get_option('agent_neo_tracking_site_token') / get_option('agent_neo_tracking_hmac_key')
 *   を参照する（env 変数フォールバックは開発時のみ）。
 *   本クラスは同一 option キーに書き込むため、controller の検証と一致する。
 *
 * consent キー根拠:
 *   themes/agent-neo-theme/assets/js/consent.js の STORAGE_KEY = 'agent_neo_consent_v2'。
 *   hasAnalyticsConsent() が state.analytics_storage === 'granted' で同意確認する。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ad-tracking.js enqueue・設定注入クラス。
 */
final class Agent_Neo_Core_Tracking_Assets {

	/**
	 * option キー: site token（controller の tracking_secrets() と同一）。
	 *
	 * @var string
	 */
	private const OPTION_SITE_TOKEN = 'agent_neo_tracking_site_token';

	/**
	 * option キー: HMAC key（controller の tracking_secrets() と同一）。
	 *
	 * @var string
	 */
	private const OPTION_HMAC_KEY = 'agent_neo_tracking_hmac_key';

	/**
	 * consent.js の STORAGE_KEY（localStorage/Cookie キー名）。
	 *
	 * @var string
	 */
	private const CONSENT_STORAGE_KEY = 'agent_neo_consent_v2';

	/**
	 * wp_enqueue_scripts フックを登録する。
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_and_localize' ) );
	}

	/**
	 * フロント用 ad-tracking.js への設定を localize する。
	 *
	 * bootstrap.php が先に script 登録しているため、ここでは
	 * wp_localize_script のみ（wp_enqueue_script は冪等なので呼んでも問題ない）。
	 *
	 * @return void
	 */
	public function enqueue_and_localize(): void {
		// bootstrap.php が enqueue 済みだが、念のため未登録時も登録する。
		if ( ! wp_script_is( 'agent-neo-ad-tracking', 'registered' ) ) {
			wp_enqueue_script(
				'agent-neo-ad-tracking',
				AGENT_NEO_CORE_URL . 'assets/js/ad-tracking.js',
				array(),
				AGENT_NEO_CORE_VERSION,
				true
			);
		}

		$tokens = $this->ensure_tokens();

		wp_localize_script(
			'agent-neo-ad-tracking',
			'agentNeoTracking',
			array(
				'endpoint'   => home_url( '/' ),
				'siteToken'  => $tokens['site_token'],
				'hmacKey'    => $tokens['hmac_key'],
				'sectionId'  => 'ad',
				'consentKey' => self::CONSENT_STORAGE_KEY,
				'pageType'   => $this->detect_page_type(),
			)
		);
	}

	/**
	 * 現在表示中のページ種別を文字列で返す。
	 *
	 * 判定順（優先度高 → 低）:
	 *   1. 固定ページかつ LP テンプレート（'page-lp' で始まるスラッグ）→ 'lp'
	 *      LP をフロントページに設定した場合でも 'lp' を優先する。
	 *      LP 変換計測の分離をサイト構成に依存させないための設計。
	 *   2. トップページ / ブログトップ → 'home'
	 *   3. 投稿（post）単一表示       → 'post'
	 *   4. 固定ページ（LP テンプレートなし）→ 'page'
	 *   5. 上記以外（アーカイブ・検索・404 等）→ 'other'
	 *
	 * is_front_page() より LP テンプレート判定を先行させる理由:
	 *   SaaS では LP をドメインルートに設定するケースが多い。
	 *   旧実装では is_front_page() を最優先していたため LP が 'home' に
	 *   誤分類され、LP 変換計測が home バケットに混入していた（確定欠陥 #2）。
	 *   get_queried_object_id() を明示することで front_page 文脈でも
	 *   queried page のテンプレートを確実に取得する。
	 *
	 * この関数は wp_enqueue_scripts アクション内で呼ばれるため、
	 * WP クエリが確定した後でのみ実行される。
	 *
	 * @return string 'home' | 'post' | 'lp' | 'page' | 'other'
	 */
	private function detect_page_type(): string {
		// LP テンプレートはフロントページに設定された場合でも 'lp' を優先する
		// （LP 変換計測の分離をサイト構成に依存せず保つため）。
		// get_queried_object_id() を明示することで front_page 文脈でも
		// queried page のテンプレートを確実に取得する。
		if ( is_page() ) {
			$template = (string) get_page_template_slug( get_queried_object_id() );
			if ( 0 === strpos( $template, 'page-lp' ) ) {
				return 'lp';
			}
		}

		// トップページ・ブログトップ（非 LP）。
		if ( is_front_page() || is_home() ) {
			return 'home';
		}

		// 投稿（post）単一表示。
		if ( is_singular( 'post' ) ) {
			return 'post';
		}

		// 固定ページ（LP テンプレートなし）。
		if ( is_page() ) {
			return 'page';
		}

		// アーカイブ・検索・404・カスタム投稿タイプ single 等はすべて 'other' に集約する。
		// TODO: カスタム投稿タイプを front 描画する場合は is_singular() 分岐を追加検討。
		return 'other';
	}

	/**
	 * option から token を読み、未設定なら生成して保存する。
	 *
	 * 生成キー長 48 文字（英数字のみ）は tracking-controller の検証基準を超える十分な強度。
	 * env 変数（AGENT_NEO_SITE_TOKEN 等）が設定されている場合は controller 側が優先するため
	 * ここでは option ベースのみ管理する。
	 *
	 * @return array{site_token:string,hmac_key:string}
	 */
	private function ensure_tokens(): array {
		$site_token = (string) get_option( self::OPTION_SITE_TOKEN, '' );
		$hmac_key   = (string) get_option( self::OPTION_HMAC_KEY, '' );

		if ( '' === trim( $site_token ) ) {
			$site_token = wp_generate_password( 48, false );
			update_option( self::OPTION_SITE_TOKEN, $site_token, false );
		}

		if ( '' === trim( $hmac_key ) ) {
			$hmac_key = wp_generate_password( 48, false );
			update_option( self::OPTION_HMAC_KEY, $hmac_key, false );
		}

		return array(
			'site_token' => $site_token,
			'hmac_key'   => $hmac_key,
		);
	}
}
