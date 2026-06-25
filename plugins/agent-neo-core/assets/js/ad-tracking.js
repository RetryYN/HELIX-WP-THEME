/**
 * AGENT NEO 広告計測フロント JS。
 *
 * CARRY-A2-003: 広告計測イベント（impression/viewable/click 等）のフロント計測実装。
 * 軽量 / lazy 読み込み / IntersectionObserver ベース。
 *
 * REQ-NF-025 厳守: AIロジック・モデル呼び出し・統計判定を一切含まない。
 * 静的なDOM観測 + HMAC署名は Automation SEO 側で付与されたサイトトークンを使用。
 *
 * 依存: なし（バニラJS / ES2017+）。
 *
 * @package AgentNeoCore
 */

/* global agentNeoTracking */

( function () {
	'use strict';

	/**
	 * ページロード時に window.agentNeoTracking から設定を読む。
	 * Automation SEO が wp_localize_script で注入する。
	 *
	 * @type {{endpoint: string, siteToken: string, hmacKey: string}}
	 */
	var cfg = ( typeof agentNeoTracking !== 'undefined' ) ? agentNeoTracking : null;

	// 設定がない場合は無効化。
	if ( ! cfg || ! cfg.endpoint || ! cfg.siteToken ) {
		return;
	}

	/** 送信済みイベントを重複防止するためのセット（ページライフサイクル内）。 */
	var sent = new Set();

	/**
	 * HMAC-SHA256 署名を Web Crypto API で生成する。
	 * 非対応環境では null を返す（計測はベストエフォート）。
	 *
	 * @param {string} payload  署名対象文字列。
	 * @param {string} keyStr   HMAC キー文字列。
	 * @returns {Promise<string|null>}
	 */
	async function hmacSHA256( payload, keyStr ) {
		if ( ! window.crypto || ! window.crypto.subtle ) {
			return null;
		}
		try {
			var enc = new TextEncoder();
			var key = await window.crypto.subtle.importKey(
				'raw',
				enc.encode( keyStr ),
				{ name: 'HMAC', hash: 'SHA-256' },
				false,
				[ 'sign' ]
			);
			var sig = await window.crypto.subtle.sign( 'HMAC', key, enc.encode( payload ) );
			return Array.from( new Uint8Array( sig ) )
				.map( function ( b ) { return b.toString( 16 ).padStart( 2, '0' ); } )
				.join( '' );
		} catch ( e ) {
			return null;
		}
	}

	/**
	 * 16文字のランダム nonce を生成する。
	 *
	 * @returns {string}
	 */
	function generateNonce() {
		var arr = new Uint8Array( 12 );
		( window.crypto || window.msCrypto ).getRandomValues( arr );
		return Array.from( arr ).map( function ( b ) { return b.toString( 16 ).padStart( 2, '0' ); } ).join( '' );
	}

	/**
	 * analytics 同意が得られているか確認する。
	 *
	 * consent.js が保存する localStorage/Cookie（STORAGE_KEY = consentKey）の
	 * analytics_storage === 'granted' を確認する。
	 * consentKey が未設定の場合は fail-open（計測継続）とする。
	 *
	 * @returns {boolean}
	 */
	function hasConsent() {
		var consentKey = cfg.consentKey;
		if ( ! consentKey ) {
			// consentKey が注入されていない場合は従来どおり動作する（fail-open）。
			return true;
		}

		var raw = null;

		// localStorage から読み込む（consent.js と同じ優先順位）。
		try {
			raw = localStorage.getItem( consentKey );
		} catch ( e ) {
			// プライベートブラウジング等で localStorage が使えない場合は Cookie にフォールバック。
		}

		// Cookie から読み込む。
		if ( ! raw ) {
			var cookies = document.cookie.split( ';' );
			for ( var i = 0; i < cookies.length; i++ ) {
				var parts = cookies[ i ].trim().split( '=' );
				if ( parts[ 0 ] === consentKey && parts.length > 1 ) {
					raw = decodeURIComponent( parts.slice( 1 ).join( '=' ) );
					break;
				}
			}
		}

		if ( ! raw ) {
			return false;
		}

		try {
			var state = JSON.parse( raw );
			return state && state.analytics_storage === 'granted';
		} catch ( e ) {
			return false;
		}
	}

	/**
	 * 計測イベントを REST エンドポイントに送信する。
	 *
	 * @param {string} eventType イベント種別（ad_impression / viewable_impression / affiliate_click 等）。
	 * @param {string} ctaId     CTA 識別子。
	 * @param {string} variantId バリアント識別子。
	 * @param {Object} metadata  追加メタデータ。
	 */
	async function sendEvent( eventType, ctaId, variantId, metadata ) {
		// consent gate: analytics 未同意の場合は送信しない（プライバシー必須要件）。
		if ( ! hasConsent() ) {
			return;
		}
		var dedupeKey = eventType + ':' + ctaId + ':' + variantId;

		// viewable_impression と ad_impression は同一要素で1回のみ。
		if ( sent.has( dedupeKey ) ) {
			return;
		}
		sent.add( dedupeKey );

		var nonce = generateNonce();
		var body = {
			site_token: cfg.siteToken,
			nonce:      nonce,
			event_type: eventType,
			section_id: cfg.sectionId || 'ad',
			cta_id:     ctaId,
			variant_id: variantId,
			metadata:   metadata || {}
		};

		// JSON をキー昇順に並べた canonical string で HMAC 署名。
		var canonical = JSON.stringify(
			Object.keys( body )
				.filter( function ( k ) { return k !== 'signature'; } )
				.sort()
				.reduce( function ( acc, k ) { acc[ k ] = body[ k ]; return acc; }, {} )
		);
		var payload = 'POST|/agent-neo/v1/tracking/event|' + nonce + '|' + await sha256( canonical );

		var signature = await hmacSHA256( payload, cfg.hmacKey || '' );
		if ( signature ) {
			body.signature = signature;
		} else {
			// HMAC 非対応環境: 署名なし（サーバー側で SIGNATURE_INVALID 返却）。
			body.signature = '';
		}

		try {
			var url = cfg.endpoint.replace( /\/$/, '' ) + '/agent-neo/v1/tracking/event';
			navigator.sendBeacon
				? navigator.sendBeacon( url, new Blob( [ JSON.stringify( body ) ], { type: 'application/json' } ) )
				: fetch( url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify( body ), keepalive: true } );
		} catch ( e ) {
			// 送信失敗はサイレントに無視する（計測はベストエフォート）。
		}
	}

	/**
	 * SHA-256 ハッシュ文字列を返す。
	 *
	 * @param {string} text 入力文字列。
	 * @returns {Promise<string>}
	 */
	async function sha256( text ) {
		if ( ! window.crypto || ! window.crypto.subtle ) {
			return text;
		}
		try {
			var enc = new TextEncoder();
			var buf = await window.crypto.subtle.digest( 'SHA-256', enc.encode( text ) );
			return Array.from( new Uint8Array( buf ) )
				.map( function ( b ) { return b.toString( 16 ).padStart( 2, '0' ); } )
				.join( '' );
		} catch ( e ) {
			return text;
		}
	}

	/**
	 * IntersectionObserver で広告要素の viewable impression を計測する。
	 * 50% 以上が 1 秒以上連続表示された場合に viewable_impression を送信する。
	 *
	 * @param {Element} el 観測対象要素。
	 */
	function observeViewable( el ) {
		var ctaId     = el.dataset.ctaId     || '';
		var variantId = el.dataset.variantId || 'default';
		var visibleSince = 0;
		var timer = null;

		// ad_impression は即時（表示開始時）。
		sendEvent( 'ad_impression', ctaId, variantId, { element: el.dataset.adType || 'ad' } );

		if ( ! window.IntersectionObserver ) {
			// IntersectionObserver 非対応環境: viewable_impression を ad_impression と同時送信。
			sendEvent( 'viewable_impression', ctaId, variantId, {} );
			return;
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting && entry.intersectionRatio >= 0.5 ) {
						if ( ! timer ) {
							timer = setTimeout( function () {
								sendEvent( 'viewable_impression', ctaId, variantId, {
									ratio: Math.round( entry.intersectionRatio * 100 )
								} );
								observer.disconnect();
							}, 1000 );
						}
					} else {
						if ( timer ) {
							clearTimeout( timer );
							timer = null;
						}
					}
				} );
			},
			{ threshold: [ 0, 0.5, 1.0 ] }
		);

		observer.observe( el );
	}

	/**
	 * アフィリエイトリンクのクリック計測。
	 *
	 * @param {Element} el アンカー要素。
	 */
	function observeAffiliateClick( el ) {
		el.addEventListener( 'click', function () {
			var ctaId     = el.dataset.ctaId     || '';
			var variantId = el.dataset.variantId || 'default';
			sendEvent( 'affiliate_click', ctaId, variantId, {
				href: el.href || '',
				label: ( el.textContent || '' ).trim().slice( 0, 64 )
			} );
		} );
	}

	/**
	 * スクロール深度計測（25/50/75/100% のしきい値）。
	 */
	function initScrollDepth() {
		var milestones = [ 25, 50, 75, 100 ];
		var reached    = {};

		function onScroll() {
			var scrollTop    = window.scrollY || document.documentElement.scrollTop;
			var docHeight    = document.documentElement.scrollHeight - window.innerHeight;
			if ( docHeight <= 0 ) {
				return;
			}
			var pct = Math.round( ( scrollTop / docHeight ) * 100 );

			milestones.forEach( function ( m ) {
				if ( pct >= m && ! reached[ m ] ) {
					reached[ m ] = true;
					sendEvent( 'scroll_depth', 'page', 'default', { depth_pct: m } );
				}
			} );
		}

		// passive: true でパフォーマンス低下を防ぐ。
		window.addEventListener( 'scroll', onScroll, { passive: true } );
	}

	/**
	 * DOMContentLoaded 後に各計測を初期化する。
	 */
	function init() {
		// 広告ゾーン要素の viewable impression 計測。
		document.querySelectorAll( '[data-agent-neo-ad]' ).forEach( observeViewable );

		// アフィリエイトリンクのクリック計測。
		document.querySelectorAll( 'a[data-agent-neo-affiliate]' ).forEach( observeAffiliateClick );

		// スクロール深度計測。
		initScrollDepth();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

} )();
