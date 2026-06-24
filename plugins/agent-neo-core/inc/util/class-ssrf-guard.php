<?php
/**
 * SSRF ガードユーティリティ。
 *
 * C-A1-001: 外部URL取得前にプライベート/ループバック/リンクローカル/クラウドメタデータ
 * IPへのアクセスを拒否し、redirect を非追従にする静的ヘルパー。
 *
 * REQ-NF-025 厳守: AIロジック・モデル呼び出し・統計判定を一切含まない。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 外部 URL 取得時の SSRF 防止ヘルパー。
 */
final class Agent_Neo_Core_SSRF_Guard {

	/**
	 * 拒否対象 CIDR レンジ（IPv4 / IPv6）。
	 *
	 * 対象:
	 *   - ループバック       : 127.0.0.0/8、::1/128
	 *   - プライベート        : 10.0.0.0/8、172.16.0.0/12、192.168.0.0/16
	 *   - リンクローカル      : 169.254.0.0/16（AWS/GCP metadata）、fe80::/10
	 *   - ユニークローカル     : fc00::/7
	 *
	 * @var string[]
	 */
	private const BLOCKED_RANGES = array(
		// IPv4 ループバック。
		'127.0.0.0/8',
		// IPv4 プライベート。
		'10.0.0.0/8',
		'172.16.0.0/12',
		'192.168.0.0/16',
		// IPv4 リンクローカル（AWS/GCP/Azure instance metadata）。
		'169.254.0.0/16',
		// IPv4 その他予約済み。
		'0.0.0.0/8',
		'100.64.0.0/10',
		'192.0.0.0/24',
		'192.0.2.0/24',
		'198.18.0.0/15',
		'198.51.100.0/24',
		'203.0.113.0/24',
		'240.0.0.0/4',
		'255.255.255.255/32',
		// IPv6 ループバック。
		'::1/128',
		// IPv6 リンクローカル。
		'fe80::/10',
		// IPv6 ユニークローカル。
		'fc00::/7',
		// IPv6 マルチキャスト。
		'ff00::/8',
	);

	/**
	 * 拒否対象ホスト名（大文字小文字問わず完全一致）。
	 *
	 * @var string[]
	 */
	private const BLOCKED_HOSTNAMES = array(
		'localhost',
		'metadata.google.internal',
		'169.254.169.254',
	);

	/**
	 * URL を検証し、内部アドレスへのアクセスを拒否する。
	 *
	 * @param string $url 検証対象 URL。
	 * @return true|WP_Error
	 */
	public static function validate( string $url ) {
		if ( '' === trim( $url ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'URL is required.', 'agent-neo-core' ),
				array( 'field' => 'url' )
			);
		}

		$parsed = wp_parse_url( $url );
		if ( ! is_array( $parsed ) || empty( $parsed['host'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'URL is not valid.', 'agent-neo-core' ),
				array( 'field' => 'url' )
			);
		}

		// スキームは http / https のみ許可。
		$scheme = isset( $parsed['scheme'] ) ? strtolower( (string) $parsed['scheme'] ) : '';
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'URL scheme must be http or https.', 'agent-neo-core' ),
				array( 'field' => 'url' )
			);
		}

		$host = strtolower( trim( (string) $parsed['host'] ) );

		// 既知の危険ホスト名を拒否。
		if ( in_array( $host, self::BLOCKED_HOSTNAMES, true ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'URL must not point to an internal address.', 'agent-neo-core' ),
				array( 'field' => 'url' )
			);
		}

		// IPv6 ブラケット記法を除去して IP として解釈を試みる。
		$ip_candidate = ltrim( rtrim( $host, ']' ), '[' );
		if ( false !== filter_var( $ip_candidate, FILTER_VALIDATE_IP ) ) {
			foreach ( self::BLOCKED_RANGES as $cidr ) {
				if ( self::ip_in_cidr( $ip_candidate, $cidr ) ) {
					return Agent_Neo_Core_Auth::error(
						'VALIDATION_ERROR',
						__( 'URL must not point to an internal address.', 'agent-neo-core' ),
						array( 'field' => 'url' )
					);
				}
			}
		}

		return true;
	}

	/**
	 * SSRF ガードを適用した wp_remote_get ラッパー。
	 *
	 * redirect を非追従（redirection=0）にし、内部アドレスへのアクセスを遮断する。
	 *
	 * @param string               $url     取得対象 URL。
	 * @param array<string, mixed> $args    wp_remote_get 追加引数。
	 * @return array<string, mixed>|WP_Error
	 */
	public static function safe_get( string $url, array $args = array() ) {
		$validation = self::validate( $url );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// redirect 非追従: リダイレクト先が内部IPになるopen redirectを防ぐ。
		$args['redirection'] = 0;
		$args['timeout']     = isset( $args['timeout'] ) ? (int) $args['timeout'] : 10;

		return wp_remote_get( esc_url_raw( $url ), $args );
	}

	/**
	 * IP アドレスが CIDR 範囲内かを判定する（IPv4 / IPv6 両対応）。
	 *
	 * @param string $ip   対象 IP アドレス文字列。
	 * @param string $cidr CIDR 表記のネットワーク範囲。
	 * @return bool
	 */
	public static function ip_in_cidr( string $ip, string $cidr ): bool {
		$parts = explode( '/', $cidr, 2 );
		if ( 2 !== count( $parts ) ) {
			return false;
		}

		$network = $parts[0];
		$prefix  = (int) $parts[1];

		$ip_bin      = inet_pton( $ip );
		$network_bin = inet_pton( $network );

		if ( false === $ip_bin || false === $network_bin ) {
			return false;
		}

		if ( strlen( $ip_bin ) !== strlen( $network_bin ) ) {
			return false;
		}

		$total_bits = strlen( $ip_bin ) * 8;
		if ( $prefix < 0 || $prefix > $total_bits ) {
			return false;
		}

		$bytes    = intdiv( $prefix, 8 );
		$rem_bits = $prefix % 8;

		if ( $bytes > 0 && substr( $ip_bin, 0, $bytes ) !== substr( $network_bin, 0, $bytes ) ) {
			return false;
		}

		if ( 0 === $rem_bits ) {
			return true;
		}

		$mask = ( 0xff << ( 8 - $rem_bits ) ) & 0xff;
		return ( ord( $ip_bin[ $bytes ] ) & $mask ) === ( ord( $network_bin[ $bytes ] ) & $mask );
	}
}
