<?php
/**
 * Title: 記事末尾の共有先アイコン（SNS + コピー）
 * Slug: helix-wt/share-sns
 * Categories: helix-wt
 * Description: PO 反応 16 回目（WT-EVT-0262）: icons-row を SNS 3 サービス（X / LINE / はてなブックマーク。Facebook は公開情報検査の非公開 guard に共有 URL のパスが含まれるため PO 判断待ち）+ リンクコピーに。
 *              href は PHP で生成し JS 無効でも共有できる。アイコンは商標ロゴを使わず自作の汎用グリフ（X = 交差線、LINE = 吹き出し）+ はてなは文字（WT-EVT-0276 と同時に差し替え）。
 */
$wt_url   = rawurlencode( (string) get_permalink() );
$wt_title = rawurlencode( (string) get_the_title() );
$wt_targets = array(
	array( 'x', '<i class="wt-i wt-i--sns-x" aria-hidden="true"></i>', 'https://x.com/intent/post?url=' . $wt_url . '&text=' . $wt_title, 'X でポスト' ),
	array( 'line', '<i class="wt-i wt-i--bubble" aria-hidden="true"></i>', 'https://social-plugins.line.me/lineit/share?url=' . $wt_url, 'LINE で送る' ),
	array( 'hatena', '<span aria-hidden="true">B!</span>', 'https://b.hatena.ne.jp/entry/' . rawurldecode( $wt_url ), 'はてなブックマークに追加' ),
);
?>
<!-- wp:html --><div class="wt-tail-icons" aria-label="<?php echo esc_attr__( '共有先', 'helix-wt' ); ?>"><p class="wt-tail-icons__label"><?php esc_html_e( 'この記事を共有', 'helix-wt' ); ?></p><?php foreach ( $wt_targets as $t ) : ?><a class="wt-sns wt-sns--<?php echo esc_attr( $t[0] ); ?>" href="<?php echo esc_url( $t[2] ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo esc_attr( $t[3] ); ?>" data-wt-sns="<?php echo esc_attr( $t[0] ); ?>"><?php echo wp_kses( $t[1], array( 'i' => array( 'class' => array(), 'aria-hidden' => array() ), 'span' => array( 'aria-hidden' => array() ) ) ); ?></a><?php endforeach; ?><button type="button" data-wt-share="copy" aria-label="<?php echo esc_attr__( 'リンクをコピー', 'helix-wt' ); ?>">⧉</button></div><!-- /wp:html -->
