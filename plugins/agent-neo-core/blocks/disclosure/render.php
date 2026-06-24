<?php
/**
 * Disclosure ブロック サーバレンダリング。
 *
 * ADR-025 準拠: AI生成コンテンツ開示・アフィリエイト開示（PR表記・ステマ規制）の静的表示。
 * REQ-NF-025 厳守: AIロジック・モデル呼び出し・統計判定を一切含まない。
 * マーキング判断は Automation SEO 側の責務。テーマ（このブロック）は静的な文言表示のみ行う。
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
$disclosure_type = isset( $attributes['disclosureType'] ) ? (string) $attributes['disclosureType'] : 'ai_generated';
$custom_text     = isset( $attributes['customText'] )     ? (string) $attributes['customText']     : '';
$position        = isset( $attributes['position'] )       ? (string) $attributes['position']       : 'before_content';
$show_icon       = isset( $attributes['showIcon'] )       ? (bool)   $attributes['showIcon']       : true;

// disclosureType ごとの既定文言マップ。文言は静的定義のみ。AI 判定ゼロ。
$default_texts = array(
	'ai_generated' => __( 'この記事はAIが生成しています', 'agent-neo-core' ),
	'affiliate'    => __( '本ページはアフィリエイト広告（PR）を含みます', 'agent-neo-core' ),
	'sponsored'    => __( 'スポンサード（PR）', 'agent-neo-core' ),
	'custom'       => '',
);

// 表示文言を決定する。
if ( 'custom' === $disclosure_type ) {
	// カスタムテキスト: sanitize_text_field で無害化済みの値を表示する。
	$display_text = sanitize_text_field( $custom_text );
} else {
	$display_text = isset( $default_texts[ $disclosure_type ] ) ? $default_texts[ $disclosure_type ] : $default_texts['ai_generated'];
}

// 文言が空の場合は何も出力しない（customText 未入力時の保護）。
if ( '' === $display_text ) {
	return;
}

// position・disclosureType を wrapper クラスに反映する。
$wrapper_classes = array(
	'wp-block-agent-neo-disclosure',
	'agent-neo-disclosure',
	'agent-neo-disclosure--' . sanitize_html_class( $disclosure_type ),
	'agent-neo-disclosure--pos-' . sanitize_html_class( $position ),
);

// get_block_wrapper_attributes() で block supports（customClassName 等）を統合する。
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'      => implode( ' ', $wrapper_classes ),
		'role'       => 'note',
		'aria-label' => esc_attr__( '開示情報', 'agent-neo-core' ),
	)
);

// アイコン SVG（showIcon=true の場合のみ表示）。静的インライン SVG・AI ゼロ。
$icon_html = '';
if ( $show_icon ) {
	$icon_html = '<span class="agent-neo-disclosure__icon" aria-hidden="true">'
		. '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="16" height="16" fill="currentColor">'
		. '<path d="M10 2a8 8 0 1 0 0 16A8 8 0 0 0 10 2zm.75 11.5h-1.5v-5h1.5v5zm0-6.5h-1.5V5.5h1.5V7z"/>'
		. '</svg>'
		. '</span>';
}
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — get_block_wrapper_attributes() が返す属性文字列。 ?>>
	<?php echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — 静的 SVG のみ含む。 ?>
	<span class="agent-neo-disclosure__text"><?php echo esc_html( $display_text ); ?></span>
</div>
