/**
 * AGENT NEO 同意ゲート — Consent Mode v2 対応
 *
 * 責務:
 *   - window.agent_neo_consent.updateConsent(state) の公開
 *   - 同意状態の Cookie/localStorage 永続化（再訪問で再表示しない）
 *   - 同意後に GA4 を非同期（parser-blocking でない）でロード
 *   - 同意バナー（PERF-CARRY-002 暫定内蔵バナー）の表示制御
 *
 * 設計根拠: L3-A4-performance-contract-gaps.md §GAP-RT-022
 */

( function () {
	'use strict';

	/** 永続化キー */
	var STORAGE_KEY = 'agent_neo_consent_v2';

	/** Cookie 有効期限（日数） */
	var COOKIE_DAYS = 365;

	/**
	 * 同意状態を Cookie に保存する。
	 * localStorage も併用し、どちらかで読み込める冗長構成とする。
	 *
	 * @param {Object} state - 同意状態オブジェクト
	 */
	function saveConsent( state ) {
		var value = JSON.stringify( state );

		// localStorage への保存
		try {
			localStorage.setItem( STORAGE_KEY, value );
		} catch ( e ) {
			// プライベートブラウジング等で localStorage が使えない場合は無視
		}

		// Cookie への保存（SameSite=Lax / Secure 属性は本番環境が付与）
		var expires = new Date();
		expires.setDate( expires.getDate() + COOKIE_DAYS );
		document.cookie =
			STORAGE_KEY + '=' + encodeURIComponent( value ) +
			'; expires=' + expires.toUTCString() +
			'; path=/; SameSite=Lax';
	}

	/**
	 * 永続化された同意状態を読み込む。
	 * localStorage → Cookie の順に試みる。
	 *
	 * @returns {Object|null} 同意状態、または null
	 */
	function loadConsent() {
		// localStorage から読み込み
		try {
			var ls = localStorage.getItem( STORAGE_KEY );
			if ( ls ) {
				return JSON.parse( ls );
			}
		} catch ( e ) {
			// 無視
		}

		// Cookie から読み込み
		var cookies = document.cookie.split( ';' );
		for ( var i = 0; i < cookies.length; i++ ) {
			var parts = cookies[ i ].trim().split( '=' );
			if ( parts[ 0 ] === STORAGE_KEY && parts.length > 1 ) {
				// value 内に '=' が含まれる場合（JSON の base64 等）を正しく結合する
				var rawValue = parts.slice( 1 ).join( '=' );
				try {
					return JSON.parse( decodeURIComponent( rawValue ) );
				} catch ( e ) {
					return null;
				}
			}
		}

		return null;
	}

	/**
	 * analytics カテゴリへの同意が得られているか確認する。
	 *
	 * @param {Object} state - 同意状態オブジェクト
	 * @returns {boolean}
	 */
	function hasAnalyticsConsent( state ) {
		return state && state.analytics_storage === 'granted';
	}

	/**
	 * GA4 タグを非同期でロードする。
	 * parser-blocking を避けるため createElement + async 属性を使用する。
	 *
	 * 設計: この関数が呼ばれる経路（init 再訪問 / updateConsent 初回同意）に関わらず、
	 * 必ずここで gtag('consent','update',...) を発行する。
	 * これにより Consent Mode v2 の update 漏れ（同意済み再訪問ユーザーの計測欠落）を防ぐ。
	 */
	function loadGA4() {
		// agentNeoConsentData は wp_localize_script で PHP 側から注入される
		var data = window.agentNeoConsentData;
		if ( ! data || ! data.measurementId ) {
			return;
		}

		var measurementId = data.measurementId;

		// gtag consent update を先に実行する（全経路で必ず発行）。
		// _agentNeoGA4Loaded ガードより前に置くことで、再訪問パスでも update が必ず出る。
		if ( typeof window.gtag === 'function' ) {
			window.gtag( 'consent', 'update', {
				analytics_storage: 'granted'
			} );
			// E2E テスト・デバッグ用に window フラグを立てる
			window._agentNeoConsentGranted = true;
			console.log( '[agent-neo consent] gtag consent update: analytics_storage=granted' );
		}

		// すでに GA4 ロード済みの場合は update のみ発行してスクリプト挿入はスキップ
		if ( window._agentNeoGA4Loaded ) {
			return;
		}
		window._agentNeoGA4Loaded = true;

		// GA4 スクリプトを非同期で挿入（parser-blocking でない）
		var script = document.createElement( 'script' );
		script.async = true;
		script.src = 'https://www.googletagmanager.com/gtag/js?id=' + measurementId;
		script.onload = function () {
			// gtag 関数が存在しない場合は初期化する
			window.dataLayer = window.dataLayer || [];
			if ( typeof window.gtag !== 'function' ) {
				window.gtag = function () {
					window.dataLayer.push( arguments );
				};
			}
			window.gtag( 'js', new Date() );
			window.gtag( 'config', measurementId );
			console.log( '[agent-neo consent] GA4 loaded: ' + measurementId );
		};
		document.head.appendChild( script );
	}

	/**
	 * 同意状態を更新し、GA4 等の async_after_consent タグを発火する。
	 * window.agent_neo_consent.updateConsent() として公開する。
	 *
	 * @param {Object} state - 同意状態オブジェクト（gtag consent キーと値）
	 *   例: { analytics_storage: 'granted', ad_storage: 'denied' }
	 */
	function updateConsent( state ) {
		// gtag consent update は analytics_storage=granted の場合 loadGA4() 内で発行する。
		// analytics_storage が denied または拒否ボタン押下時は gtag を初期化していないため
		// window.gtag が存在しない可能性があるが、denied 状態ではタグを挿入しないので問題ない。
		// denied 時の update（念のため拒否状態を明示）は gtag が存在する場合のみ発行する。
		if ( ! hasAnalyticsConsent( state ) && typeof window.gtag === 'function' ) {
			window.gtag( 'consent', 'update', state );
		}

		// 同意状態を永続化する
		saveConsent( state );

		// analytics_storage が granted になった場合は GA4 を即時ロードする
		// 設計契約: 同意 click から 1 秒以内に GA4 ロードが始まること（L3-A4 §GAP-RT-022）
		if ( hasAnalyticsConsent( state ) ) {
			loadGA4();
		}

		// バナーを非表示にする
		hideBanner();

		console.log( '[agent-neo consent] consent updated:', state );
	}

	/**
	 * 同意バナーを非表示にする。
	 */
	function hideBanner() {
		var banner = document.getElementById( 'agent-neo-consent-banner' );
		if ( banner ) {
			banner.style.display = 'none';
		}
	}

	/**
	 * 同意バナーを表示する。
	 * PERF-CARRY-002 暫定内蔵バナー / 本番は外部プラグイン差し替え可。
	 */
	function showBanner() {
		var banner = document.getElementById( 'agent-neo-consent-banner' );
		if ( banner ) {
			banner.style.display = 'block';
		}
	}

	/**
	 * 初期化処理。
	 * 保存済み同意状態があれば適用し、なければバナーを表示する。
	 */
	function init() {
		// window.agent_neo_consent 名前空間を公開する
		window.agent_neo_consent = {
			updateConsent: updateConsent
		};

		var savedConsent = loadConsent();
		if ( savedConsent ) {
			// 保存済み同意がある場合: バナーを表示せず、同意状態に応じてタグを発火する
			if ( hasAnalyticsConsent( savedConsent ) ) {
				loadGA4();
			}
			hideBanner();
		} else {
			// 同意状態が未設定の場合: バナーを表示する
			showBanner();
		}
	}

	// DOM 構築完了後に初期化する
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
