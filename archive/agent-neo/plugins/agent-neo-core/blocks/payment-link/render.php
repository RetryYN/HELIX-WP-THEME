<?php
/**
 * 決済ボタン (Stripe) ブロック サーバレンダリング。
 *
 * REQ-NF-027 準拠: Stripe ホスト型決済 URL のみを出力し、任意 URL 転送・オープンリダイレクトを防止。
 * REQ-NF-025 厳守: シークレット鍵・API 呼び出し・カード入力フォーム・AI 判定を一切含まない。
 * ADR-029 準拠: Stripe ホスト型 Payment Link MVP。決済はすべて Stripe 側へ委譲。
 *
 * @var array<string, mixed> $attributes ブロック属性（block.json の attributes に準拠）。
 * @var string               $content    インナーブロックコンテンツ（本ブロックは使用しない）。
 * @var WP_Block             $block      現在のブロックオブジェクト。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 属性を安全に取得する。
$agent_neo_payment_url = isset( $attributes['paymentUrl'] ) ? (string) $attributes['paymentUrl'] : '';
$agent_neo_label       = isset( $attributes['label'] )      ? (string) $attributes['label']      : '購入する';
$agent_neo_note        = isset( $attributes['note'] )        ? (string) $attributes['note']        : '';

// URL が Stripe 許可ドメインでなければ何も出力しない（AC3 / REQ-NF-027）。
if ( ! agent_neo_core_is_stripe_payment_url( $agent_neo_payment_url ) ) {
	return;
}

// label が空の場合は既定文言を使う。
if ( '' === $agent_neo_label ) {
	$agent_neo_label = '購入する';
}

// get_block_wrapper_attributes() で block supports（customClassName 等）を統合する。
$agent_neo_wrapper_attributes = get_block_wrapper_attributes(
	array( 'class' => 'agent-neo-payment' )
);

?>
<div <?php echo $agent_neo_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() が返す属性文字列。 ?>>
	<a
		class="agent-neo-payment__button"
		href="<?php echo esc_url( $agent_neo_payment_url ); ?>"
		rel="noopener nofollow"
	><?php echo esc_html( $agent_neo_label ); ?></a>
	<?php if ( '' !== $agent_neo_note ) : ?>
		<p class="agent-neo-payment__note"><?php echo esc_html( sanitize_text_field( $agent_neo_note ) ); ?></p>
	<?php endif; ?>
</div>
