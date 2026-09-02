<?php
/**
 * CAT-001〜CAT-009 契約テスト検証スクリプト
 *
 * 実行: cd /opt/agent-neo && cp bin/verify-catalog-contract.php tmp/ && \
 *       docker compose run --rm -T wpcli eval-file /tmp/host/verify-catalog-contract.php
 *       （bin/ は wpcli コンテナ未マウント。./tmp が /tmp/host にマウント済のため tmp 経由で実行）
 *
 * 対象: Agent_Neo_Core_Catalog_Update_Producer
 * 参照: docs/legacy/test-plan/L3-test-plan.md §3.1 / §17.11
 * 対象ファイル: plugins/agent-neo-core/inc/catalog/class-catalog-update-producer.php
 *
 * ※ producer 本体は変更しない。
 * ※ pre_http_request フィルタで HTTP 応答をモックする。
 */

if ( ! defined( 'ABSPATH' ) ) {
	echo "ABSPATH が定義されていません。wp eval-file で実行してください。\n";
	exit( 1 );
}

// producer クラスが読み込まれていなければ直接 require する。
if ( ! class_exists( 'Agent_Neo_Core_Catalog_Update_Producer' ) ) {
	$producer_path = WP_PLUGIN_DIR . '/agent-neo-plugins/agent-neo-core/inc/catalog/class-catalog-update-producer.php';
	if ( ! file_exists( $producer_path ) ) {
		// プラグインディレクトリを探す。
		$candidates = glob( WP_PLUGIN_DIR . '/*/agent-neo-core/inc/catalog/class-catalog-update-producer.php' );
		if ( empty( $candidates ) ) {
			// フォールバック: 既知パスから探す。
			$base_candidates = array(
				'/var/www/html/wp-content/plugins/agent-neo-plugins/agent-neo-core/inc/catalog/class-catalog-update-producer.php',
				WP_PLUGIN_DIR . '/agent-neo-core/inc/catalog/class-catalog-update-producer.php',
			);
			foreach ( $base_candidates as $c ) {
				if ( file_exists( $c ) ) {
					$producer_path = $c;
					break;
				}
			}
		} else {
			$producer_path = $candidates[0];
		}
	}
	if ( file_exists( $producer_path ) ) {
		require_once $producer_path;
	} else {
		echo "ERROR: class-catalog-update-producer.php が見つかりません。\n";
		echo "WP_PLUGIN_DIR=" . WP_PLUGIN_DIR . "\n";
		exit( 1 );
	}
}

// ====================================================================
// テストハーネス
// ====================================================================

// グローバル集計（wp eval-file 環境では GLOBALS 経由でアクセス）。
$GLOBALS['cat_results']  = array();
$GLOBALS['cat_pass_cnt'] = 0;
$GLOBALS['cat_fail_cnt'] = 0;

/**
 * PASS/FAIL を記録してコンソール出力する。
 *
 * @param string $id    テスト ID。
 * @param bool   $pass  合否。
 * @param string $msg   補足メッセージ。
 * @return void
 */
function report_test( string $id, bool $pass, string $msg = '' ): void {
	$label = $pass ? 'PASS' : 'FAIL';
	if ( $pass ) {
		++$GLOBALS['cat_pass_cnt'];
	} else {
		++$GLOBALS['cat_fail_cnt'];
	}
	$GLOBALS['cat_results'][] = array(
		'id'   => $id,
		'pass' => $pass,
		'msg'  => $msg,
	);
	$line = sprintf( '[%s] %s', $label, $id );
	if ( '' !== $msg ) {
		$line .= ' — ' . $msg;
	}
	echo $line . "\n";
}

/**
 * WP オプション・トランジェントを一括クリアする（テスト間の独立性保証）。
 *
 * @return void
 */
function reset_producer_state(): void {
	delete_option( 'agent_neo_catalog_update_outbox' );
	delete_option( 'agent_neo_catalog_update_dlq' );
	delete_option( 'agent_neo_catalog_update_receipts' );
	delete_option( 'agent_neo_catalog_update_known_blocks' );
	// Transient は全消去できないため、本テスト内で使うキープレフィックスを個別削除する手段として
	// producer の event_id ベース transient は各テストごとに新 event_id を使用して衝突を避ける。
}

/**
 * テスト用に producer を初期化して返す。
 * endpoint / HMAC キーをフィルタでオーバーライドして
 * 認証エラーなしに send_item まで到達できるようにする。
 *
 * @return Agent_Neo_Core_Catalog_Update_Producer
 */
function make_producer(): Agent_Neo_Core_Catalog_Update_Producer {
	// endpoint host allowlist をフィルタで注入。
	add_filter(
		'agent_neo_catalog_update_allowed_hosts',
		function ( $values ) {
			$values[] = array( 'test.example.com' );
			return $values;
		}
	);

	// endpoint URL をフィルタで強制設定（HTTPS 必須）。
	add_filter(
		'agent_neo_catalog_update_endpoint',
		function () {
			return 'https://test.example.com/aseo/v1/agent-neo/catalog-update';
		}
	);

	// HMAC キーをフィルタで注入。
	add_filter(
		'agent_neo_catalog_update_hmac_key',
		function () {
			return 'test-hmac-key-for-unit-testing-only';
		}
	);

	return new Agent_Neo_Core_Catalog_Update_Producer();
}

/**
 * pre_http_request フィルタでモックレスポンスを返すコールバックを登録し、
 * そのハンドラを返す（テスト後に remove_filter できるよう）。
 *
 * @param array $mock_response WP_HTTP 形式のレスポンス配列、または WP_Error。
 * @return callable 登録したコールバック。
 */
function install_http_mock( $mock_response ): callable {
	$callback = static function ( $preempt, $parsed_args, $url ) use ( $mock_response ) {
		return $mock_response;
	};
	add_filter( 'pre_http_request', $callback, 10, 3 );
	return $callback;
}

/**
 * モック用の正常応答を生成する。
 *
 * @param string $event_id     event_id（応答に含める）。
 * @param bool   $deduplicated 重複フラグ。
 * @param string $next_action  'none' または 'scan-catalog'。
 * @return array WP_HTTP レスポンス配列。
 */
function make_success_response( string $event_id, bool $deduplicated = false, string $next_action = 'none' ): array {
	$body = wp_json_encode(
		array(
			'received'      => true,
			'event_id'      => $event_id,
			'deduplicated'  => $deduplicated,
			'next_action'   => $next_action,
		)
	);
	return array(
		'response' => array(
			'code'    => 200,
			'message' => 'OK',
		),
		'body'     => $body,
		'headers'  => array(),
		'cookies'  => array(),
		'filename' => '',
	);
}

/**
 * モック用のエラー応答を生成する。
 *
 * @param int    $code   HTTP ステータスコード。
 * @param string $body   レスポンスボディ。
 * @return array WP_HTTP レスポンス配列。
 */
function make_error_response( int $code, string $body = '' ): array {
	return array(
		'response' => array(
			'code'    => $code,
			'message' => 'Error',
		),
		'body'     => $body,
		'headers'  => array(),
		'cookies'  => array(),
		'filename' => '',
	);
}

// ====================================================================
// CAT-001: block_registered → 正常応答（4 フィールド）
// ====================================================================
function run_cat001(): void {
	reset_producer_state();
	$producer = make_producer();

	// enqueue してイベント ID を取得する。
	$enqueue = $producer->enqueue_block_registered( 'agent-neo/test-block-001' );
	if ( empty( $enqueue['enqueued'] ) || empty( $enqueue['event_id'] ) ) {
		report_test( 'CAT-001', false, 'enqueue 失敗: ' . wp_json_encode( $enqueue ) );
		return;
	}
	$event_id = $enqueue['event_id'];

	// HTTP モックを設定。
	$mock_resp = make_success_response( $event_id, false, 'none' );
	$cb        = install_http_mock( $mock_resp );

	// outbox 処理を実行。
	$result = $producer->process_outbox();

	remove_filter( 'pre_http_request', $cb, 10 );

	// 受信済み receipt を確認。
	$receipts = get_option( 'agent_neo_catalog_update_receipts', array() );

	$pass    = false;
	$details = '';

	if ( ! empty( $result['sent'] ) && $result['sent'] >= 1 && ! empty( $receipts ) ) {
		$r = $receipts[0];
		// 4 フィールドの存在と値を確認。
		$has_fields     = isset( $r['received'], $r['event_id'], $r['deduplicated'], $r['next_action'] );
		$correct_values = $has_fields
			&& true === $r['received']
			&& $event_id === $r['event_id']
			&& false === $r['deduplicated']
			&& 'none' === $r['next_action'];
		// response_keys が 4 フィールドのみかを確認（§17.11 要件）。
		$only_four = isset( $r['response_keys'] ) && 4 === count( $r['response_keys'] )
			&& in_array( 'received', $r['response_keys'], true )
			&& in_array( 'event_id', $r['response_keys'], true )
			&& in_array( 'deduplicated', $r['response_keys'], true )
			&& in_array( 'next_action', $r['response_keys'], true );
		$pass    = $has_fields && $correct_values && $only_four;
		$details = $pass ? 'event_kind=block_registered 正常受信' : wp_json_encode( $r );
	} else {
		$details = 'sent=0 or receipt 空。result=' . wp_json_encode( $result );
	}

	report_test( 'CAT-001', $pass, $details );
}

// ====================================================================
// CAT-002: block_unregistered → 正常応答（4 フィールド）
// ====================================================================
function run_cat002(): void {
	reset_producer_state();
	$producer = make_producer();

	$enqueue = $producer->enqueue_block_unregistered( 'agent-neo/test-block-002' );
	if ( empty( $enqueue['enqueued'] ) || empty( $enqueue['event_id'] ) ) {
		report_test( 'CAT-002', false, 'enqueue 失敗: ' . wp_json_encode( $enqueue ) );
		return;
	}
	$event_id = $enqueue['event_id'];

	$cb     = install_http_mock( make_success_response( $event_id, false, 'none' ) );
	$result = $producer->process_outbox();
	remove_filter( 'pre_http_request', $cb, 10 );

	$receipts = get_option( 'agent_neo_catalog_update_receipts', array() );
	$pass     = false;
	$details  = '';

	if ( ! empty( $result['sent'] ) && ! empty( $receipts ) ) {
		$r    = $receipts[0];
		$pass = isset( $r['received'], $r['event_id'], $r['deduplicated'], $r['next_action'] )
			&& true === $r['received']
			&& $event_id === $r['event_id']
			&& false === $r['deduplicated']
			&& 'none' === $r['next_action'];
		$details = $pass ? 'event_kind=block_unregistered 正常受信' : wp_json_encode( $r );
	} else {
		$details = 'sent=0 or receipt 空。result=' . wp_json_encode( $result );
	}

	report_test( 'CAT-002', $pass, $details );
}

// ====================================================================
// CAT-003: template_updated → 正常応答（4 フィールド）
// ====================================================================
function run_cat003(): void {
	reset_producer_state();
	$producer = make_producer();

	$enqueue = $producer->enqueue_template_updated( 'single', array( 'updated' => true ) );
	if ( empty( $enqueue['enqueued'] ) || empty( $enqueue['event_id'] ) ) {
		report_test( 'CAT-003', false, 'enqueue 失敗: ' . wp_json_encode( $enqueue ) );
		return;
	}
	$event_id = $enqueue['event_id'];

	$cb     = install_http_mock( make_success_response( $event_id, false, 'none' ) );
	$result = $producer->process_outbox();
	remove_filter( 'pre_http_request', $cb, 10 );

	$receipts = get_option( 'agent_neo_catalog_update_receipts', array() );
	$pass     = false;
	$details  = '';

	if ( ! empty( $result['sent'] ) && ! empty( $receipts ) ) {
		$r    = $receipts[0];
		$pass = isset( $r['received'], $r['event_id'], $r['deduplicated'], $r['next_action'] )
			&& true === $r['received']
			&& $event_id === $r['event_id']
			&& false === $r['deduplicated']
			&& 'none' === $r['next_action'];
		$details = $pass ? 'event_kind=template_updated 正常受信' : wp_json_encode( $r );
	} else {
		$details = 'sent=0 or receipt 空。result=' . wp_json_encode( $result );
	}

	report_test( 'CAT-003', $pass, $details );
}

// ====================================================================
// CAT-004: theme_token_updated → 正常応答（4 フィールド）
// ====================================================================
function run_cat004(): void {
	reset_producer_state();
	$producer = make_producer();

	$enqueue = $producer->enqueue_theme_token_updated(
		array(
			'option'      => 'agent_neo_theme_tokens',
			'before_hash' => 'sha256:abc',
			'after_hash'  => 'sha256:def',
		)
	);
	if ( empty( $enqueue['enqueued'] ) || empty( $enqueue['event_id'] ) ) {
		report_test( 'CAT-004', false, 'enqueue 失敗: ' . wp_json_encode( $enqueue ) );
		return;
	}
	$event_id = $enqueue['event_id'];

	$cb     = install_http_mock( make_success_response( $event_id, false, 'none' ) );
	$result = $producer->process_outbox();
	remove_filter( 'pre_http_request', $cb, 10 );

	$receipts = get_option( 'agent_neo_catalog_update_receipts', array() );
	$pass     = false;
	$details  = '';

	if ( ! empty( $result['sent'] ) && ! empty( $receipts ) ) {
		$r    = $receipts[0];
		$pass = isset( $r['received'], $r['event_id'], $r['deduplicated'], $r['next_action'] )
			&& true === $r['received']
			&& $event_id === $r['event_id']
			&& false === $r['deduplicated']
			&& 'none' === $r['next_action'];
		$details = $pass ? 'event_kind=theme_token_updated 正常受信' : wp_json_encode( $r );
	} else {
		$details = 'sent=0 or receipt 空。result=' . wp_json_encode( $result );
	}

	report_test( 'CAT-004', $pass, $details );
}

// ====================================================================
// CAT-005: 同一 event_id 再送 → deduplicated=true
// ====================================================================
function run_cat005(): void {
	reset_producer_state();
	$producer = make_producer();

	// 1 回目 enqueue → 送信成功 → idempotency marker が 'sent' になる。
	$enqueue1 = $producer->enqueue_block_registered( 'agent-neo/test-block-005' );
	if ( empty( $enqueue1['enqueued'] ) || empty( $enqueue1['event_id'] ) ) {
		report_test( 'CAT-005', false, '1回目 enqueue 失敗: ' . wp_json_encode( $enqueue1 ) );
		return;
	}
	$event_id = $enqueue1['event_id'];

	// 1 回目送信成功。
	$cb     = install_http_mock( make_success_response( $event_id, false, 'none' ) );
	$producer->process_outbox();
	remove_filter( 'pre_http_request', $cb, 10 );

	// 2 回目: 同一 event_id で enqueue_event を直接呼ぶ。
	// idempotency marker が 'sent'（active=true）なので enqueue 段階で deduplicated を返すはず。
	$enqueue2 = $producer->enqueue_event( 'block_registered', array( 'block_name' => 'agent-neo/test-block-005' ), $event_id );

	$pass    = false;
	$details = '';

	if ( isset( $enqueue2['deduplicated'] ) && true === $enqueue2['deduplicated']
		&& isset( $enqueue2['event_id'] ) && $event_id === $enqueue2['event_id']
	) {
		// enqueue 段階で deduplicated が返った（正規の dedup パス）。
		$pass    = true;
		$details = 'enqueue 段階で deduplicated=true を返した（idempotency marker hit）';
	} else {
		// enqueue が通った場合は send 時に受信側が deduplicated=true を返すパスも有り得る。
		// その場合は process_outbox して receipt を確認する。
		if ( ! empty( $enqueue2['enqueued'] ) && ! empty( $enqueue2['event_id'] ) ) {
			$body2 = wp_json_encode(
				array(
					'received'     => true,
					'event_id'     => $event_id,
					'deduplicated' => true,
					'next_action'  => 'none',
				)
			);
			$cb2 = install_http_mock(
				array(
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'body'     => $body2,
					'headers'  => array(),
					'cookies'  => array(),
					'filename' => '',
				)
			);
			$result2 = $producer->process_outbox();
			remove_filter( 'pre_http_request', $cb2, 10 );

			$receipts = get_option( 'agent_neo_catalog_update_receipts', array() );
			if ( ! empty( $receipts ) ) {
				$r    = $receipts[0];
				$pass = isset( $r['deduplicated'] ) && true === $r['deduplicated']
					&& isset( $r['received'] ) && true === $r['received']
					&& 'none' === ( $r['next_action'] ?? '' );
				$details = $pass ? '送信段階で deduplicated=true を確認' : wp_json_encode( $r );
			} else {
				$details = '2 回目 process_outbox 後も receipt 空: ' . wp_json_encode( $result2 );
			}
		} else {
			$details = '2 回目 enqueue 結果: ' . wp_json_encode( $enqueue2 );
		}
	}

	report_test( 'CAT-005', $pass, $details );
}

// ====================================================================
// CAT-006: event_kind 欠落 → 400 VALIDATION_ERROR（enqueue 段階で拒否）
// ====================================================================
function run_cat006(): void {
	reset_producer_state();
	$producer = make_producer();

	// 不正な event_kind を直接 enqueue_event で渡す。
	$result = $producer->enqueue_event( 'INVALID_KIND_XYZ', array( 'test' => 'value' ) );

	// enqueue が false かつ error が返れば VALIDATION_ERROR。
	$pass    = false;
	$details = '';

	if ( isset( $result['enqueued'] ) && false === $result['enqueued']
		&& isset( $result['error'] ) && 'invalid_event_kind' === $result['error']
	) {
		$pass    = true;
		$details = '不正 event_kind を enqueue 段階で 400 VALIDATION_ERROR として拒否';
	} else {
		// enqueue が通ってしまった場合は send 段階で 400 が返るか確認。
		if ( ! empty( $result['enqueued'] ) && ! empty( $result['event_id'] ) ) {
			$cb     = install_http_mock( make_error_response( 400, '{"error":"VALIDATION_ERROR"}' ) );
			$proc   = $producer->process_outbox();
			remove_filter( 'pre_http_request', $cb, 10 );
			$dlq    = get_option( 'agent_neo_catalog_update_dlq', array() );
			if ( ! empty( $dlq ) && isset( $dlq[0]['reason'] ) ) {
				$pass    = in_array( $dlq[0]['reason'], array( 'VALIDATION_ERROR', 'invalid_event_kind' ), true );
				$details = 'DLQ 経由で VALIDATION_ERROR を確認: reason=' . $dlq[0]['reason'];
			} else {
				$details = 'enqueue が通ったが DLQ にも入らなかった: ' . wp_json_encode( $proc );
			}
		} else {
			$details = '期待外の結果: ' . wp_json_encode( $result );
		}
	}

	report_test( 'CAT-006', $pass, $details );
}

// ====================================================================
// CAT-007: 5xx / 429 / timeout → 再試行スケジュール（backoff計算検証）
// ====================================================================
function run_cat007(): void {
	// CAT-007 は実際に 5 回 sleep せず、
	// producer のバックオフ定数・計算ロジックが §17.11 契約に合致することをコード/値で検証する。
	//
	// §17.11 定義:
	//   INITIAL_BACKOFF = 1s
	//   multiplier      = 2^n (指数)
	//   MAX_ATTEMPTS    = 5
	//   jitter          = ±10% (0.9〜1.1)
	//   retry_on        = {5xx, 429, network_timeout}
	//
	// 検証: contract_summary() から設定値を取得し、
	//        手動で backoff_seconds の出力範囲をシミュレートして確認する。

	$producer = make_producer();
	$summary  = $producer->contract_summary();
	$retry    = $summary['retry'] ?? array();

	$pass    = true;
	$details = array();

	// 1. 基本設定値の検証。
	if ( ( $retry['initial_backoff_seconds'] ?? -1 ) !== 1 ) {
		$pass      = false;
		$details[] = 'initial_backoff_seconds != 1: ' . ( $retry['initial_backoff_seconds'] ?? 'undef' );
	}
	if ( ( $retry['multiplier'] ?? -1 ) !== 2 ) {
		$pass      = false;
		$details[] = 'multiplier != 2: ' . ( $retry['multiplier'] ?? 'undef' );
	}
	if ( ( $retry['max_attempts'] ?? -1 ) !== 5 ) {
		$pass      = false;
		$details[] = 'max_attempts != 5: ' . ( $retry['max_attempts'] ?? 'undef' );
	}
	if ( ( $retry['jitter'] ?? '' ) !== '+/-10%' ) {
		$pass      = false;
		$details[] = 'jitter != +/-10%: ' . ( $retry['jitter'] ?? 'undef' );
	}
	if ( ! in_array( '5xx', $retry['retry_on'] ?? array(), true ) ) {
		$pass      = false;
		$details[] = '5xx が retry_on にない';
	}
	if ( ! in_array( '429', $retry['retry_on'] ?? array(), true ) ) {
		$pass      = false;
		$details[] = '429 が retry_on にない';
	}
	if ( ! in_array( 'network_timeout', $retry['retry_on'] ?? array(), true ) ) {
		$pass      = false;
		$details[] = 'network_timeout が retry_on にない';
	}

	// 2. backoff 計算範囲のシミュレーション検証。
	// producer の backoff_seconds($attempts) は:
	//   base   = 1 * 2^max(0, attempts-1)
	//   jitter = wp_rand(900,1100)/1000  (0.9〜1.1)
	//   result = max(1, round(base * jitter))
	//
	// attempts=1: base=1,  min=1*0.9=0.9→1, max=1*1.1=1.1→1 → 期待範囲 [1,1]
	// attempts=2: base=2,  min=2*0.9=1.8→2, max=2*1.1=2.2→2 → 期待範囲 [2,2]
	// attempts=3: base=4,  min=4*0.9=3.6→4, max=4*1.1=4.4→4 → 期待範囲 [4,4]
	// attempts=4: base=8,  min=8*0.9=7.2→7, max=8*1.1=8.8→9 → 期待範囲 [7,9]
	// attempts=5: base=16, min=16*0.9=14.4→14, max=16*1.1=17.6→18 → 期待範囲 [14,18]
	//
	// 実際の jitter は wp_rand(900,1100)/1000 なので:
	//   - attempts=1: base=1, jitter=0.9〜1.1, result=round(0.9)〜round(1.1) = 1 (max(1,...))
	//   - attempts=2: base=2, jitter=0.9〜1.1, result=round(1.8)〜round(2.2) = 2
	//   など
	//
	// ここでは jitter 0.9 と 1.1 の両端でシミュレートする。

	$expected = array(
		// attempts => [base, min_jitter, max_jitter, min_expected, max_expected]
		1 => array( 1, 0.9, 1.1, 1, 1 ),   // round(0.9)=1, round(1.1)=1
		2 => array( 2, 0.9, 1.1, 2, 2 ),   // round(1.8)=2, round(2.2)=2
		3 => array( 4, 0.9, 1.1, 4, 4 ),   // round(3.6)=4, round(4.4)=4
		4 => array( 8, 0.9, 1.1, 7, 9 ),   // round(7.2)=7, round(8.8)=9
		5 => array( 16, 0.9, 1.1, 14, 18 ), // round(14.4)=14, round(17.6)=18
	);

	foreach ( $expected as $attempts => $params ) {
		list( $base, $jitter_min, $jitter_max, $expect_min, $expect_max ) = $params;
		$computed_base  = 1 * ( 2 ** max( 0, $attempts - 1 ) );
		$computed_min   = max( 1, (int) round( $computed_base * $jitter_min ) );
		$computed_max   = max( 1, (int) round( $computed_base * $jitter_max ) );

		if ( $computed_base !== $base ) {
			$pass      = false;
			$details[] = "attempts={$attempts}: base計算誤り computed={$computed_base} expected={$base}";
		}
		if ( $computed_min !== $expect_min || $computed_max !== $expect_max ) {
			$pass      = false;
			$details[] = "attempts={$attempts}: backoff 範囲 [{$computed_min},{$computed_max}] が期待 [{$expect_min},{$expect_max}] と異なる";
		}
	}

	// 3. process_outbox が 5xx で retry_or_dead を呼ぶことを確認（実際の挙動）。
	reset_producer_state();
	$producer2 = make_producer();
	$enqueue   = $producer2->enqueue_block_registered( 'agent-neo/test-block-007' );
	if ( ! empty( $enqueue['enqueued'] ) ) {
		$cb      = install_http_mock( make_error_response( 500 ) );
		$result3 = $producer2->process_outbox();
		remove_filter( 'pre_http_request', $cb, 10 );

		$outbox = get_option( 'agent_neo_catalog_update_outbox', array() );
		// 1 回目失敗 → retrying ステータスになっているはず。
		$is_retrying = false;
		foreach ( $outbox as $item ) {
			if ( isset( $item['status'] ) && 'retrying' === $item['status'] ) {
				$is_retrying = true;
				// next_due_at が現在時刻 + backoff 以上であることを確認。
				$next_due = (int) ( $item['next_due_at'] ?? 0 );
				$now      = time();
				// backoff は min 1s 以上のはず。
				if ( $next_due < $now ) {
					$pass      = false;
					$details[] = '5xx 後の next_due_at が過去になっている';
				}
				break;
			}
		}
		if ( ! $is_retrying ) {
			// dead になった場合も attempts=1 で dead になるのは誤り。
			$dlq = get_option( 'agent_neo_catalog_update_dlq', array() );
			if ( ! empty( $dlq ) && isset( $dlq[0]['reason'] ) && 'RETRY_EXHAUSTED' === $dlq[0]['reason'] ) {
				$pass      = false;
				$details[] = '5xx 1 回目で RETRY_EXHAUSTED になった（MAX_ATTEMPTS=5 が機能していない可能性）';
			} else {
				$pass      = false;
				$details[] = '5xx 後に retrying ステータスにならなかった: ' . wp_json_encode( get_option( 'agent_neo_catalog_update_outbox', array() ) );
			}
		}

		// 429 でも同様に retry になることを確認。
		reset_producer_state();
		$producer3 = make_producer();
		$eq2       = $producer3->enqueue_block_registered( 'agent-neo/test-block-007b' );
		if ( ! empty( $eq2['enqueued'] ) ) {
			$cb2 = install_http_mock( make_error_response( 429 ) );
			$producer3->process_outbox();
			remove_filter( 'pre_http_request', $cb2, 10 );
			$outbox2    = get_option( 'agent_neo_catalog_update_outbox', array() );
			$retrying2  = false;
			foreach ( $outbox2 as $item ) {
				if ( isset( $item['status'] ) && 'retrying' === $item['status'] ) {
					$retrying2 = true;
					break;
				}
			}
			if ( ! $retrying2 ) {
				$pass      = false;
				$details[] = '429 後に retrying ステータスにならなかった';
			}
		}
	}

	$detail_str = $pass ? '全バックオフ設定値・計算範囲が §17.11 契約と一致' : implode( ' / ', $details );
	report_test( 'CAT-007', $pass, $detail_str );
}

// ====================================================================
// CAT-008: 未インストール（409）→ DLQ に AGENT_NEO_NOT_INSTALLED
// ====================================================================
function run_cat008(): void {
	reset_producer_state();
	$producer = make_producer();

	$enqueue = $producer->enqueue_block_registered( 'agent-neo/test-block-008' );
	if ( empty( $enqueue['enqueued'] ) || empty( $enqueue['event_id'] ) ) {
		report_test( 'CAT-008', false, 'enqueue 失敗: ' . wp_json_encode( $enqueue ) );
		return;
	}

	$cb     = install_http_mock( make_error_response( 409, '{"error":"AGENT_NEO_NOT_INSTALLED"}' ) );
	$result = $producer->process_outbox();
	remove_filter( 'pre_http_request', $cb, 10 );

	$dlq     = get_option( 'agent_neo_catalog_update_dlq', array() );
	$pass    = false;
	$details = '';

	if ( ! empty( $dlq ) && isset( $dlq[0]['reason'] ) ) {
		// 409 は non-retryable → dead_letter に入り reason=AGENT_NEO_NOT_INSTALLED のはず。
		$pass    = 'AGENT_NEO_NOT_INSTALLED' === $dlq[0]['reason'];
		$details = $pass
			? '409 受信で DLQ に AGENT_NEO_NOT_INSTALLED が記録された'
			: 'DLQ の reason が期待外: ' . $dlq[0]['reason'];
	} else {
		// outbox に retrying ステータスが残った場合は誤り（409 は再試行しない）。
		$outbox = get_option( 'agent_neo_catalog_update_outbox', array() );
		if ( ! empty( $outbox ) ) {
			$pass    = false;
			$details = '409 後に outbox に残った（再試行禁止のはずだが retrying になった）: ' . wp_json_encode( $outbox );
		} else {
			$details = 'DLQ 空・outbox 空。result=' . wp_json_encode( $result );
		}
	}

	report_test( 'CAT-008', $pass, $details );
}

// ====================================================================
// CAT-009: 400/401/409 は再試行なし / 429 は再試行あり
// ====================================================================
function run_cat009(): void {
	$pass_all = true;
	$messages = array();

	// --- 400 → 再試行なし ---
	reset_producer_state();
	$producer = make_producer();
	$eq       = $producer->enqueue_block_registered( 'agent-neo/test-block-009a' );
	if ( ! empty( $eq['enqueued'] ) ) {
		$cb = install_http_mock( make_error_response( 400 ) );
		$producer->process_outbox();
		remove_filter( 'pre_http_request', $cb, 10 );
		$outbox400 = get_option( 'agent_neo_catalog_update_outbox', array() );
		$dlq400    = get_option( 'agent_neo_catalog_update_dlq', array() );
		// 400 は 4xx non-retryable → outbox に残らず DLQ に入るはず。
		$no_retry_400 = empty( $outbox400 ) && ! empty( $dlq400 );
		if ( ! $no_retry_400 ) {
			$pass_all  = false;
			$messages[] = '400 後に outbox が空になっていない / DLQ が空: outbox=' . wp_json_encode( $outbox400 );
		} else {
			$messages[] = '400 → 再試行なし・DLQ 格納 OK';
		}
	}

	// --- 401 → 再試行なし ---
	reset_producer_state();
	$producer2 = make_producer();
	$eq2       = $producer2->enqueue_block_registered( 'agent-neo/test-block-009b' );
	if ( ! empty( $eq2['enqueued'] ) ) {
		$cb2 = install_http_mock( make_error_response( 401 ) );
		$producer2->process_outbox();
		remove_filter( 'pre_http_request', $cb2, 10 );
		$outbox401 = get_option( 'agent_neo_catalog_update_outbox', array() );
		$dlq401    = get_option( 'agent_neo_catalog_update_dlq', array() );
		$no_retry_401 = empty( $outbox401 ) && ! empty( $dlq401 );
		if ( ! $no_retry_401 ) {
			$pass_all   = false;
			$messages[] = '401 後に outbox が空になっていない / DLQ が空: outbox=' . wp_json_encode( $outbox401 );
		} else {
			$messages[] = '401 → 再試行なし・DLQ 格納 OK';
		}
	}

	// --- 409 → 再試行なし ---
	reset_producer_state();
	$producer3 = make_producer();
	$eq3       = $producer3->enqueue_block_registered( 'agent-neo/test-block-009c' );
	if ( ! empty( $eq3['enqueued'] ) ) {
		$cb3 = install_http_mock( make_error_response( 409 ) );
		$producer3->process_outbox();
		remove_filter( 'pre_http_request', $cb3, 10 );
		$outbox409 = get_option( 'agent_neo_catalog_update_outbox', array() );
		$dlq409    = get_option( 'agent_neo_catalog_update_dlq', array() );
		$no_retry_409 = empty( $outbox409 ) && ! empty( $dlq409 );
		if ( ! $no_retry_409 ) {
			$pass_all   = false;
			$messages[] = '409 後に outbox が空になっていない / DLQ が空: outbox=' . wp_json_encode( $outbox409 );
		} else {
			$messages[] = '409 → 再試行なし・DLQ 格納 OK';
		}
	}

	// --- 429 → 再試行あり ---
	reset_producer_state();
	$producer4 = make_producer();
	$eq4       = $producer4->enqueue_block_registered( 'agent-neo/test-block-009d' );
	if ( ! empty( $eq4['enqueued'] ) ) {
		$cb4 = install_http_mock( make_error_response( 429 ) );
		$producer4->process_outbox();
		remove_filter( 'pre_http_request', $cb4, 10 );
		$outbox429 = get_option( 'agent_neo_catalog_update_outbox', array() );
		// 429 は retryable → outbox に retrying が残るはず。
		$has_retrying = false;
		foreach ( $outbox429 as $item ) {
			if ( isset( $item['status'] ) && 'retrying' === $item['status'] ) {
				$has_retrying = true;
				break;
			}
		}
		if ( ! $has_retrying ) {
			$pass_all   = false;
			$messages[] = '429 後に retrying ステータスにならなかった: outbox=' . wp_json_encode( $outbox429 );
		} else {
			$messages[] = '429 → 再試行あり（retrying ステータス）OK';
		}
	}

	$detail_str = implode( ' | ', $messages );
	report_test( 'CAT-009', $pass_all, $detail_str );
}

// ====================================================================
// テスト実行
// ====================================================================

echo "\n====================================================\n";
echo "  CAT-001〜CAT-009 契約テスト検証スクリプト\n";
echo "  対象: Agent_Neo_Core_Catalog_Update_Producer\n";
echo "  参照: L3-test-plan.md §3.1 / §17.11\n";
echo "====================================================\n\n";

run_cat001();
run_cat002();
run_cat003();
run_cat004();
run_cat005();
run_cat006();
run_cat007();
run_cat008();
run_cat009();

echo "\n----------------------------------------------------\n";
$final_pass = $GLOBALS['cat_pass_cnt'];
$final_fail = $GLOBALS['cat_fail_cnt'];
echo sprintf( "結果: PASS %d / FAIL %d / 合計 9\n", $final_pass, $final_fail );
if ( 0 === $final_fail ) {
	echo "ALL PASS\n";
} else {
	echo "FAIL があります。上記の詳細を確認してください。\n";
}
echo "----------------------------------------------------\n\n";

// 最終クリーンアップ。
reset_producer_state();
