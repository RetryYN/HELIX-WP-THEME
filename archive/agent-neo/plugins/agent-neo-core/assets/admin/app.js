/**
 * AGENT NEO Core — 管理ページ React UI
 *
 * ビルド不要（JSX なし / webpack なし）。
 * WordPress 本体同梱の wp.element（React wrapper）と wp.apiFetch を使用する。
 * 既存 REST エンドポイント /agent-neo/v1/status と /agent-neo/v1/features?include=all を
 * apiFetch で消費し、同一 JSON 契約を React UI サーフェスとして表示する（ADR-002/012）。
 */
( function () {
	'use strict';

	const { createRoot, useState, useEffect, createElement: h } = wp.element;
	const apiFetch = wp.apiFetch;

	// nonce middleware をセットアップ（bootstrap.php 側で window.agentNeoAdmin に渡した値を使用）。
	if ( window.agentNeoAdmin && window.agentNeoAdmin.nonce ) {
		apiFetch.use( apiFetch.createNonceMiddleware( window.agentNeoAdmin.nonce ) );
	}

	// -------------------------------------------------------------------------
	// 型 / 定数
	// -------------------------------------------------------------------------

	const REST_BASE = ( window.agentNeoAdmin && window.agentNeoAdmin.restBase )
		? window.agentNeoAdmin.restBase
		: '/wp-json/agent-neo/v1';

	// -------------------------------------------------------------------------
	// StatusCard — /status の応答を表示するカード
	// -------------------------------------------------------------------------

	/**
	 * ステータスバッジ（healthy / degraded）。
	 *
	 * @param {{ value: string }} props
	 */
	function StatusBadge( { value } ) {
		const isHealthy = value === 'healthy';
		return h(
			'span',
			{
				className: 'an-badge ' + ( isHealthy ? 'an-badge--healthy' : 'an-badge--degraded' ),
				title: value,
			},
			isHealthy ? '稼働中' : '劣化'
		);
	}

	/**
	 * ラベル＋値の1行。
	 *
	 * @param {{ label: string, value: unknown, mono?: boolean }} props
	 */
	function Row( { label, value, mono } ) {
		const displayValue = value === null || value === undefined ? '—' : String( value );
		return h(
			'tr',
			null,
			h( 'th', { className: 'an-row-label' }, label ),
			h( 'td', { className: mono ? 'an-row-value an-mono' : 'an-row-value' }, displayValue )
		);
	}

	/**
	 * /status を消費するカード。
	 *
	 * @param {{ data: object|null }} props
	 */
	function StatusCard( { data } ) {
		if ( ! data ) return null;

		const modulesCount = Array.isArray( data.loaded_modules ) ? data.loaded_modules.length : 0;
		const themeLabel   = data.theme
			? ( data.theme.active ? '✓ ' : '' ) + ( data.theme.stylesheet || '—' )
			: '—';

		return h(
			'section',
			{ className: 'an-card' },
			h( 'h2', { className: 'an-card__title' }, 'コアステータス' ),
			h(
				'p',
				{ className: 'an-endpoint-hint' },
				'GET ',
				h( 'code', null, '/agent-neo/v1/status' )
			),
			h(
				'table',
				{ className: 'an-table' },
				h(
					'tbody',
					null,
					h(
						'tr',
						null,
						h( 'th', { className: 'an-row-label' }, 'ステータス' ),
						h( 'td', { className: 'an-row-value' }, h( StatusBadge, { value: data.status || 'unknown' } ) )
					),
					h( Row, { label: 'ライセンス', value: data.license_mode } ),
					h( Row, { label: 'パッケージ', value: data.package } ),
					h( Row, { label: 'テーマ', value: themeLabel } ),
					h( Row, { label: 'バージョン', value: data.core_plugin_version, mono: true } ),
					h( Row, { label: '読込モジュール数', value: modulesCount + ' 件' } )
				)
			)
		);
	}

	// -------------------------------------------------------------------------
	// FeaturesTable — /features?include=all の応答を表示する表
	// -------------------------------------------------------------------------

	/**
	 * 1パッケージ分の機能フラグ行群。
	 *
	 * @param {{ packageName: string, flags: Record<string, boolean> }} props
	 */
	function FlagsBlock( { packageName, flags } ) {
		return h(
			'tbody',
			null,
			h(
				'tr',
				{ className: 'an-pkg-header' },
				h( 'th', { colSpan: 2, className: 'an-pkg-label' }, packageName )
			),
			Object.entries( flags ).map( function ( [ flag, enabled ] ) {
				return h(
					'tr',
					{ key: flag },
					h( 'td', { className: 'an-flag-name an-mono' }, flag ),
					h(
						'td',
						{ className: 'an-flag-val' },
						h(
							'span',
							{ className: 'an-dot ' + ( enabled ? 'an-dot--on' : 'an-dot--off' ) },
							enabled ? '有効' : '無効'
						)
					)
				);
			} )
		);
	}

	/**
	 * /features?include=all を消費する表カード。
	 *
	 * @param {{ data: object|null }} props
	 */
	function FeaturesTable( { data } ) {
		if ( ! data ) return null;

		const packages = Object.keys( data );

		return h(
			'section',
			{ className: 'an-card' },
			h( 'h2', { className: 'an-card__title' }, '機能フラグ' ),
			h(
				'p',
				{ className: 'an-endpoint-hint' },
				'GET ',
				h( 'code', null, '/agent-neo/v1/features?include=all' )
			),
			h(
				'table',
				{ className: 'an-table' },
				packages.map( function ( pkgName ) {
					return h( FlagsBlock, {
						key: pkgName,
						packageName: pkgName,
						flags: data[ pkgName ] || {},
					} );
				} )
			)
		);
	}

	// -------------------------------------------------------------------------
	// SkeletonCard — ローディング中のプレースホルダ
	// -------------------------------------------------------------------------

	function SkeletonCard() {
		return h(
			'section',
			{ className: 'an-card an-card--loading' },
			h( 'div', { className: 'an-skeleton an-skeleton--title' } ),
			h( 'div', { className: 'an-skeleton an-skeleton--row' } ),
			h( 'div', { className: 'an-skeleton an-skeleton--row' } ),
			h( 'div', { className: 'an-skeleton an-skeleton--row' } )
		);
	}

	// -------------------------------------------------------------------------
	// ErrorBanner — fetch エラー表示
	// -------------------------------------------------------------------------

	function ErrorBanner( { message } ) {
		return h(
			'div',
			{ className: 'an-error', role: 'alert' },
			h( 'strong', null, 'エラー: ' ),
			message
		);
	}

	// -------------------------------------------------------------------------
	// App — ルートコンポーネント
	// -------------------------------------------------------------------------

	function App() {
		const [ status,   setStatus   ] = useState( null );
		const [ features, setFeatures ] = useState( null );
		const [ loading,  setLoading  ] = useState( true );
		const [ error,    setError    ] = useState( null );

		useEffect( function () {
			let cancelled = false;

			Promise.all( [
				// 同一 REST 契約を apiFetch で消費（React UI サーフェス = 4面の1つ）。
				apiFetch( { path: '/agent-neo/v1/status' } ),
				apiFetch( { path: '/agent-neo/v1/features?include=all' } ),
			] )
				.then( function ( [ statusRes, featuresRes ] ) {
					if ( cancelled ) return;
					// StandardResponse 封筒 { success, data, meta, error } から data を取り出す。
					setStatus( statusRes && statusRes.data ? statusRes.data : statusRes );
					setFeatures( featuresRes && featuresRes.data ? featuresRes.data : featuresRes );
				} )
				.catch( function ( err ) {
					if ( cancelled ) return;
					setError( err && err.message ? err.message : String( err ) );
				} )
				.finally( function () {
					if ( ! cancelled ) setLoading( false );
				} );

			return function () {
				cancelled = true;
			};
		}, [] );

		return h(
			'div',
			{ className: 'an-wrap' },
			h(
				'header',
				{ className: 'an-header' },
				h( 'span', { className: 'an-header__logo' }, '◆' ),
				h( 'h1', { className: 'an-header__title' }, 'AGENT NEO' )
			),
			error && h( ErrorBanner, { message: error } ),
			loading
				? h(
					'div',
					{ className: 'an-grid' },
					h( SkeletonCard, null ),
					h( SkeletonCard, null )
				)
				: h(
					'div',
					{ className: 'an-grid' },
					h( StatusCard,   { data: status   } ),
					h( FeaturesTable, { data: features } )
				)
		);
	}

	// -------------------------------------------------------------------------
	// マウント
	// -------------------------------------------------------------------------

	document.addEventListener( 'DOMContentLoaded', function () {
		const root = document.getElementById( 'agent-neo-admin-root' );
		if ( ! root ) return;

		if ( createRoot ) {
			// React 18 createRoot（wp-element 経由）
			createRoot( root ).render( h( App, null ) );
		} else {
			// フォールバック: React 17 以前
			wp.element.render( h( App, null ), root );
		}
	} );
} )();
