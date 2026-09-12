<?php
/**
 * Title: パーツカタログ（試作 03）
 * Slug: helix-wt/catalog-03
 * Categories: helix-wt
 * Description: 見出し・囲み・CTA・表・リンクカードなど選べる型の一覧（撮影用。各区画に anchor）
 */
$u = get_theme_file_uri( 'assets/img' );
$box = function ( $style, $mod, $title, $body, $id ) {
	$cls = 'is-style-wt-' . $style . ( $mod ? ' wt-c-' . $mod : '' );
	echo '<!-- wp:group {"className":"' . $cls . '","anchor":"' . $id . '","layout":{"type":"default"}} --><div class="wp-block-group ' . $cls . '" id="' . $id . '">';
	if ( $title ) {
		echo '<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700"}}} --><p style="font-weight:700">' . $title . '</p><!-- /wp:paragraph -->';
	}
	echo '<!-- wp:paragraph --><p>' . $body . '</p><!-- /wp:paragraph --></div><!-- /wp:group -->';
};
$h2 = function ( $style, $id ) {
	echo '<!-- wp:group {"anchor":"' . esc_attr( $id ) . '","layout":{"type":"default"}} --><div class="wp-block-group" id="' . esc_attr( $id ) . '"><!-- wp:heading {"className":"is-style-wt-' . esc_attr( $style ) . '"} --><h2 class="wp-block-heading is-style-wt-' . esc_attr( $style ) . '">' . esc_html__( '昇降デスクの選び方と 3 製品の比較', 'helix-wt' ) . '</h2><!-- /wp:heading --><!-- wp:paragraph {"className":"wt-sub"} --><p class="wt-sub">' . esc_html__( '補助文はミュートグレー。見出し本文は SP 390 で 20 字が 1 行に収まる。', 'helix-wt' ) . '</p><!-- /wp:paragraph --></div><!-- /wp:group -->';
};
$h3 = function ( $style, $id ) {
	echo '<!-- wp:group {"anchor":"' . esc_attr( $id ) . '","layout":{"type":"default"}} --><div class="wp-block-group" id="' . esc_attr( $id ) . '"><!-- wp:heading {"level":3,"className":"is-style-wt-' . esc_attr( $style ) . '"} --><h3 class="wp-block-heading is-style-wt-' . esc_attr( $style ) . '">' . esc_html__( 'リフトワン L1：静かで低い位置まで下がる', 'helix-wt' ) . '</h3><!-- /wp:heading --></div><!-- /wp:group -->';
};
?>
<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e( 'h2 10 型', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<?php foreach ( array( 'plain', '2tone', 'icon', 'bar', 'underline', 'band', 'numbox', 'barbg', 'doubleline', 'label' ) as $s ) { $h2( $s, 'cat-h2-' . $s ); } ?>
<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e( 'h3 5 型', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<?php foreach ( array( 'bar-thin', 'dotted', 'num', 'marker', 'underline-thin' ) as $s ) { $h3( $s, 'cat-h3-' . $s ); } ?>
<!-- WT-EVT-0272（Claude 解釈）: numbox の h2 と num の h3 の連番連動デモ。本文では .wp-block-post-content:has(> .is-style-wt-numbox) で同じ表示になる -->
<!-- wp:group {"className":"wt-numbox-demo","anchor":"cat-h2-numbox-h3num","layout":{"type":"default"}} --><div class="wp-block-group wt-numbox-demo" id="cat-h2-numbox-h3num">
<!-- wp:heading {"className":"is-style-wt-numbox"} --><h2 class="wp-block-heading is-style-wt-numbox"><?php esc_html_e( '比較の前提と評価軸', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<!-- wp:heading {"level":3,"className":"is-style-wt-num"} --><h3 class="wp-block-heading is-style-wt-num"><?php esc_html_e( '評価軸は 5 点', 'helix-wt' ); ?></h3><!-- /wp:heading -->
<!-- wp:heading {"level":3,"className":"is-style-wt-num"} --><h3 class="wp-block-heading is-style-wt-num"><?php esc_html_e( '測り方と条件', 'helix-wt' ); ?></h3><!-- /wp:heading -->
<!-- wp:heading {"className":"is-style-wt-numbox"} --><h2 class="wp-block-heading is-style-wt-numbox"><?php esc_html_e( '3 製品の比較表', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<!-- wp:heading {"level":3,"className":"is-style-wt-num"} --><h3 class="wp-block-heading is-style-wt-num"><?php esc_html_e( '価格と保証', 'helix-wt' ); ?></h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"wt-sub"} --><p class="wt-sub"><?php esc_html_e( 'h2 が numbox のとき、h3 の num は「01-1, 01-2 / 02-1」と親番号付きになり、h2 ごとに振り直す。', 'helix-wt' ); ?></p><!-- /wp:paragraph -->
</div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-h2-numbox-plain","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-h2-numbox-plain">
<!-- wp:heading {"className":"is-style-wt-numbox"} --><h2 class="wp-block-heading is-style-wt-numbox"><?php esc_html_e( '連番検証 A（専用 class なしの Group）', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<!-- wp:heading {"level":3,"className":"is-style-wt-num"} --><h3 class="wp-block-heading is-style-wt-num"><?php esc_html_e( '連番検証 A-1', 'helix-wt' ); ?></h3><!-- /wp:heading -->
<!-- wp:heading {"level":3,"className":"is-style-wt-num"} --><h3 class="wp-block-heading is-style-wt-num"><?php esc_html_e( '連番検証 A-2', 'helix-wt' ); ?></h3><!-- /wp:heading -->
</div><!-- /wp:group -->
<!-- wp:heading {"level":3,"className":"is-style-wt-num"} --><h3 class="wp-block-heading is-style-wt-num"><?php esc_html_e( '連番検証 L（post-content 直下の単独 num）', 'helix-wt' ); ?></h3><!-- /wp:heading -->
<!-- wp:group {"layout":{"type":"default"}} --><div class="wp-block-group">
<!-- wp:heading {"className":"is-style-wt-numbox"} --><h2 class="wp-block-heading is-style-wt-numbox"><?php esc_html_e( '連番検証 B（後続の別 Group）', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<!-- wp:heading {"level":3,"className":"is-style-wt-num"} --><h3 class="wp-block-heading is-style-wt-num"><?php esc_html_e( '連番検証 B-1', 'helix-wt' ); ?></h3><!-- /wp:heading -->
</div><!-- /wp:group -->
<!-- wp:heading {"level":3,"className":"is-style-wt-num"} --><h3 class="wp-block-heading is-style-wt-num"><?php esc_html_e( '連番検証 M（B の後の単独 num）', 'helix-wt' ); ?></h3><!-- /wp:heading -->
<!-- wp:group {"layout":{"type":"default"}} --><div class="wp-block-group">
<!-- wp:group {"layout":{"type":"default"}} --><div class="wp-block-group">
<!-- wp:heading {"className":"is-style-wt-numbox"} --><h2 class="wp-block-heading is-style-wt-numbox"><?php esc_html_e( '連番検証 C（入れ子 Group の内側）', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<!-- wp:heading {"level":3,"className":"is-style-wt-num"} --><h3 class="wp-block-heading is-style-wt-num"><?php esc_html_e( '連番検証 C-1', 'helix-wt' ); ?></h3><!-- /wp:heading -->
</div><!-- /wp:group -->
</div><!-- /wp:group -->
<!-- wp:heading {"level":3,"className":"is-style-wt-num"} --><h3 class="wp-block-heading is-style-wt-num"><?php esc_html_e( '連番検証 N（入れ子 Group の外へ出た単独 num）', 'helix-wt' ); ?></h3><!-- /wp:heading -->
<!-- wp:group {"layout":{"type":"default"}} --><div class="wp-block-group">
<!-- wp:group {"layout":{"type":"default"}} --><div class="wp-block-group">
<!-- wp:heading {"className":"is-style-wt-numbox"} --><h2 class="wp-block-heading is-style-wt-numbox"><?php esc_html_e( '連番検証 D（入れ子 Group、直後は段落）', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<!-- wp:heading {"level":3,"className":"is-style-wt-num"} --><h3 class="wp-block-heading is-style-wt-num"><?php esc_html_e( '連番検証 D-1', 'helix-wt' ); ?></h3><!-- /wp:heading -->
</div><!-- /wp:group -->
</div><!-- /wp:group -->
<!-- wp:paragraph --><p><?php esc_html_e( 'Group 直後の段落（ここで wt-h3 を 0 に戻す通常分岐）。', 'helix-wt' ); ?></p><!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"is-style-wt-num"} --><h3 class="wp-block-heading is-style-wt-num"><?php esc_html_e( '連番検証 P-1（段落の後の単独 num）', 'helix-wt' ); ?></h3><!-- /wp:heading -->
<!-- wp:heading {"level":3,"className":"is-style-wt-num"} --><h3 class="wp-block-heading is-style-wt-num"><?php esc_html_e( '連番検証 P-2（後続への継承）', 'helix-wt' ); ?></h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"wt-sub"} --><p class="wt-sub"><?php esc_html_e( '検証用: 専用 class なしの Group に入れた numbox は本文全体の h2 番号を続け（上の h2 見本が 01 なので 02, 03）、配下の num は 02-1 / 02-2 / 03-1。Group の直後から単独 num は 01 に戻る（L・M・N とも 01。N は入れ子 Group の外へ出た境界）。Group 直後が段落なら 0 に戻し、その後の単独 num は 01 / 02（P-1 / P-2）。', 'helix-wt' ); ?></p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e( '囲み 7 型 + 色', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<?php
$body = '昇降デスクは「最低高さ」が意外に重要です。身長 160cm 前後の人は座り姿勢で 62cm 以下まで下がらないと、肩がこります。';
$box( 'plain-border', '', 'plain-border（罫線）', $body, 'cat-box-plain-border' );
$box( 'tinted', '', 'tinted（淡塗り）', $body, 'cat-box-tinted' );
$box( 'band-title', '', '帯タイトル', $body, 'cat-box-band-title' );
$box( 'tab-title', '', 'タブタイトル', $body, 'cat-box-tab-title' );
$box( 'label-title', '', 'ラベルタイトル', $body, 'cat-box-label-title' );
$box( 'card-shadow', '', '影カード', $body, 'cat-box-shadow-card' );
?>
<!-- wp:group {"anchor":"cat-box-check-list","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-box-check-list"><!-- wp:group {"className":"is-style-wt-plain-border","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-plain-border"><!-- wp:list {"className":"is-style-wt-check"} --><ul class="wp-block-list is-style-wt-check"><li><?php esc_html_e( '3 製品の価格・昇降範囲・静音性の違い', 'helix-wt' ); ?></li><li><?php esc_html_e( '部屋の広さと予算別のおすすめ', 'helix-wt' ); ?></li><li><?php esc_html_e( '後悔しやすい 3 つの落とし穴', 'helix-wt' ); ?></li></ul><!-- /wp:list --></div><!-- /wp:group --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-box-colors","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-box-colors">
<?php
$box( 'band-title', 'warn', '注意', '180cm 天板は梱包が 190×90cm を超え、エレベーターに入らない建物があります。', 'cat-box-warn' );
$box( 'tab-title', 'point', 'ポイント', '月額の差より、作業時間の削減のほうが年間コストに効きます。', 'cat-box-point' );
$box( 'tinted', 'note', '補足', '価格は 2026 年 8 月時点の公式サイトの表示。セール価格は含めていません。', 'cat-box-note' );
?>
</div><!-- /wp:group -->
<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e( '囲み +5（2026-09-05 PO 反応4回目）', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<?php
$box( 'quote', '', '', '「最初の 1 台としては十分でした」', 'cat-box-quote' );
$box( 'dashed', '', '一時的な注記', '撮影時点の価格・在庫は変動します。購入前に公式サイトでご確認ください。', 'cat-box-dashed' );
$box( 'qa', '', '天板は後から交換できますか？', 'メーカー純正の天板のみ対応。他社天板は取付穴の位置が合わないことがあります。', 'cat-box-qa' );
$box( 'qa-modal', '', '天板は後から交換できますか？', 'メーカー純正の天板のみ対応。他社天板は取付穴の位置が合わないことがあります。交換時は脚部のボルト径（M6 / M8）も確認してください。', 'cat-box-qa-modal' );
$box( 'warn-soft', '', 'ご注意（軽微）', '型番により電源プラグの形状が異なります。設置場所のコンセント形状を事前にご確認ください。', 'cat-box-warn-soft' );
?>
<!-- wp:group {"className":"is-style-wt-steps","anchor":"cat-box-steps","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-steps" id="cat-box-steps"><!-- wp:paragraph --><p><?php esc_html_e( '無料登録して身長と机の高さを入力する', 'helix-wt' ); ?></p><!-- /wp:paragraph --><!-- wp:paragraph --><p><?php esc_html_e( '2 週間、座り・立ちを交互に試す', 'helix-wt' ); ?></p><!-- /wp:paragraph --><!-- wp:paragraph --><p><?php esc_html_e( '合わなければ 30 日以内に返品する', 'helix-wt' ); ?></p><!-- /wp:paragraph --></div><!-- /wp:group -->
<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e( '記事内 CTA 8 型', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<!-- wp:group {"anchor":"cat-cta-product","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-cta-product"><!-- wp:pattern {"slug":"helix-wt/product-bundle"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-cta-banner","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-cta-banner"><!-- wp:pattern {"slug":"helix-wt/cta-banner"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-cta-button","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-cta-button"><!-- wp:pattern {"slug":"helix-wt/cta-button"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-cta-box","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-cta-box"><!-- wp:pattern {"slug":"helix-wt/cta-box"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-cta-triple","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-cta-triple"><!-- wp:pattern {"slug":"helix-wt/cta-triple"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-cta-rank","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-cta-rank"><!-- wp:pattern {"slug":"helix-wt/cta-rank-featured"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-cta-price-tier","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-cta-price-tier"><!-- wp:pattern {"slug":"helix-wt/cta-price-tier"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-cta-textlink","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-cta-textlink"><!-- wp:pattern {"slug":"helix-wt/cta-textlink"} /--></div><!-- /wp:group -->
<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e( '比較表・メリデメ・評価バー・ブログカード・PR', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<!-- wp:group {"anchor":"cat-table","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-table"><!-- wp:table {"className":"is-style-wt-compare"} --><figure class="wp-block-table is-style-wt-compare"><table><caption><?php esc_html_e( '電動昇降デスク 3 製品の比較', 'helix-wt' ); ?></caption><thead><tr><th><?php esc_html_e( '項目', 'helix-wt' ); ?></th><th><?php esc_html_e( 'リフトワン L1', 'helix-wt' ); ?></th><th><?php esc_html_e( 'スタンド・ライト S2', 'helix-wt' ); ?></th><th><?php esc_html_e( 'フレックス・プロ F3', 'helix-wt' ); ?></th></tr></thead><tbody><tr><td><?php esc_html_e( '価格', 'helix-wt' ); ?></td><td class="wt-num"><?php esc_html_e( '59,800 円', 'helix-wt' ); ?></td><td class="wt-num"><?php esc_html_e( '39,800 円', 'helix-wt' ); ?></td><td class="wt-num"><?php esc_html_e( '84,800 円', 'helix-wt' ); ?></td></tr><tr><td><?php esc_html_e( '昇降範囲', 'helix-wt' ); ?></td><td>62〜127 cm</td><td>71〜118 cm</td><td>60〜125 cm</td></tr><tr><td><?php esc_html_e( '静音性', 'helix-wt' ); ?></td><td><span class="wt-mark">◎</span> 42 dB</td><td><span class="wt-mark">○</span> 48 dB</td><td><span class="wt-mark">◎</span> 41 dB</td></tr><tr><td><?php esc_html_e( '保証', 'helix-wt' ); ?></td><td><?php esc_html_e( '5 年', 'helix-wt' ); ?></td><td><?php esc_html_e( '2 年', 'helix-wt' ); ?></td><td><?php esc_html_e( '7 年', 'helix-wt' ); ?></td></tr></tbody></table></figure><!-- /wp:table --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-prosc","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-prosc"><!-- wp:columns {"className":"wt-prosc"} --><div class="wp-block-columns wt-prosc"><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"className":"is-style-wt-label-title wt-c-ok","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-label-title wt-c-ok"><!-- wp:paragraph --><p><?php esc_html_e( 'メリット', 'helix-wt' ); ?></p><!-- /wp:paragraph --><!-- wp:list {"className":"is-style-wt-pros"} --><ul class="wp-block-list is-style-wt-pros"><li><?php esc_html_e( '62cm まで下がる', 'helix-wt' ); ?></li><li><?php esc_html_e( '昇降が静か', 'helix-wt' ); ?></li><li><?php esc_html_e( '5 年保証', 'helix-wt' ); ?></li></ul><!-- /wp:list --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"className":"is-style-wt-label-title wt-c-warn","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-label-title wt-c-warn"><!-- wp:paragraph --><p><?php esc_html_e( 'デメリット', 'helix-wt' ); ?></p><!-- /wp:paragraph --><!-- wp:list {"className":"is-style-wt-cons"} --><ul class="wp-block-list is-style-wt-cons"><li><?php esc_html_e( '天板は 2 サイズのみ', 'helix-wt' ); ?></li><li><?php esc_html_e( '最高位置で少し揺れる', 'helix-wt' ); ?></li></ul><!-- /wp:list --></div><!-- /wp:group --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-rate","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-rate"><!-- wp:html --><div class="wt-rate"><span><?php esc_html_e( '昇降範囲', 'helix-wt' ); ?></span><i style="--v:94%"></i><b>4.7</b></div><div class="wt-rate"><span><?php esc_html_e( '静音性', 'helix-wt' ); ?></span><i style="--v:90%"></i><b>4.5</b></div><div class="wt-rate"><span><?php esc_html_e( '価格・保証', 'helix-wt' ); ?></span><i style="--v:84%"></i><b>4.2</b></div><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-linkcard","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-linkcard"><!-- wp:pattern {"slug":"helix-wt/linkcard"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-pr","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-pr"><!-- wp:html --><p class="wt-pr is-style-wt-pr"><span class="wt-pr__tag">PR</span><?php esc_html_e( '本記事にはプロモーションが含まれます。', 'helix-wt' ); ?></p><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e( 'de-text: 番号バッジ・アイコンリスト・引用符・数字', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<!-- wp:group {"anchor":"cat-detext","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-detext"><!-- wp:list {"className":"is-style-wt-badge-list"} --><ol class="wp-block-list is-style-wt-badge-list"><li><?php esc_html_e( '無料登録して身長と机の高さを入力する', 'helix-wt' ); ?></li><li><?php esc_html_e( '2 週間、座り・立ちを交互に試す', 'helix-wt' ); ?></li><li><?php esc_html_e( '合わなければ 30 日以内に返品する', 'helix-wt' ); ?></li></ol><!-- /wp:list --><!-- wp:list {"className":"is-style-wt-icon-list"} --><ul class="wp-block-list is-style-wt-icon-list"><li><?php esc_html_e( '迷ったら → リフトワン L1', 'helix-wt' ); ?></li><li><?php esc_html_e( '予算優先 → スタンド・ライト S2', 'helix-wt' ); ?></li></ul><!-- /wp:list --><!-- wp:quote {"className":"is-style-wt-quote-mark"} --><blockquote class="wp-block-quote is-style-wt-quote-mark"><p><?php esc_html_e( '「最初の 1 台としては十分でした」', 'helix-wt' ); ?></p><cite><?php esc_html_e( '読者アンケートより（架空）', 'helix-wt' ); ?></cite></blockquote><!-- /wp:quote --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><span class="wt-num"><span class="wt-count" data-to="1284">1,284</span><small><?php esc_html_e( '件の実測', 'helix-wt' ); ?></small></span></p><!-- /wp:paragraph --></div><!-- /wp:group -->
<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e( '自動コントラスト guard（3 輝度）', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<!-- wp:group {"anchor":"cat-contrast","className":"wt-lumgrid","layout":{"type":"default"}} --><div class="wp-block-group wt-lumgrid" id="cat-contrast">
<?php foreach ( array( 'dark' => 'lum-dark.jpg', 'mid' => 'lum-mid.jpg', 'light' => 'lum-light.jpg' ) as $k => $f ) : ?>
<!-- wp:cover {"url":"<?php echo esc_url( $u . '/' . $f ); ?>","dimRatio":0,"minHeight":220,"className":"is-style-wt-scrim","anchor":"cat-contrast-<?php echo $k; ?>","layout":{"type":"constrained"}} --><div class="wp-block-cover is-style-wt-scrim" id="cat-contrast-<?php echo $k; ?>" style="min-height:220px"><img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( $u . '/' . $f ); ?>" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container is-layout-constrained"><!-- wp:heading {"level":3,"textColor":"base","className":"wt-scrim__t"} --><h3 class="wp-block-heading wt-scrim__t has-base-color has-text-color">写真の上の見出し（<?php echo $k; ?>）</h3><!-- /wp:heading --><!-- wp:paragraph {"textColor":"base","fontSize":"s"} --><p class="has-base-color has-text-color has-s-font-size"><?php esc_html_e( '本文サイズの白文字。スクリム強度は画像の輝度から自動で決まる。', 'helix-wt' ); ?></p><!-- /wp:paragraph --></div></div><!-- /wp:cover -->
<?php endforeach; ?>
</div><!-- /wp:group -->

<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e( 'PO反応6: 比較表・メリデメ・レビューバー・ブログカード・PR・detext', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<!-- wp:paragraph --><p><?php esc_html_e( '以下は既存型を残したまま追加した Claude 案。比較記事の各製品節末尾でメリット・デメリットを並べる装置、本文の文字密度を下げ長文比較記事の読了率を上げる装置として用途を確認する。', 'helix-wt' ); ?></p><!-- /wp:paragraph -->
<?php
$reaction_table = function ( $style, $id, $caption, $heads, $rows ) {
	$class = 'is-style-wt-' . $style;
	echo '<!-- wp:group {"anchor":"' . $id . '","layout":{"type":"default"}} --><div class="wp-block-group" id="' . $id . '">';
	echo '<!-- wp:table {"className":"' . $class . '"} --><figure class="wp-block-table ' . $class . '"><table><caption>' . $caption . '</caption><thead><tr>';
	foreach ( $heads as $head ) {
		echo '<th>' . $head . '</th>';
	}
	echo '</tr></thead><tbody>';
	foreach ( $rows as $row ) {
		$row_class = isset( $row[2] ) && $row[2] ? ' class="' . $row[2] . '"' : '';
		echo '<tr' . $row_class . '><td>' . $row[0] . '</td>';
		foreach ( $row[1] as $cell ) {
			echo '<td>' . $cell . '</td>';
		}
		echo '</tr>';
	}
	echo '</tbody></table></figure><!-- /wp:table --></div><!-- /wp:group -->';
};
$reaction_heads = array( '項目', 'リフトワン L1', 'スタンド・ライト S2', 'フレックス・プロ F3' );
$reaction_rows = array(
	array( esc_html__( '価格', 'helix-wt' ), array( '<strong>' . esc_html__( '59,800 円', 'helix-wt' ) . '</strong>', esc_html__( '39,800 円', 'helix-wt' ), esc_html__( '84,800 円', 'helix-wt' ) ) ),
	array( '昇降範囲', array( '62〜127 cm', '71〜118 cm', '60〜125 cm' ) ),
	array( '静音性', array( '<span class="wt-mark">◎</span> 42 dB', '<span class="wt-mark">○</span> 48 dB', '<span class="wt-mark">◎</span> 41 dB' ) ),
	array( '保証', array( '5 年', '2 年', '7 年' ) ),
);
// PO 反応 16 回目（WT-EVT-0264 / 0265）: 画像・アイコン・購入リンクを入れられる比較表（Claude 案）
$img = function ( $file, $alt ) { return '<img class="wt-tcell__img" src="' . esc_url( get_theme_file_uri( 'assets/img/' . $file ) ) . '" alt="' . $alt . '" width="96" height="96" loading="lazy">'; };
$ico = function ( $icon, $text ) { return '<span class="wt-tcell__icon wt-tcell__icon--' . $icon . '"><i class="wt-i wt-i--s wt-i--' . ( 'ok' === $icon ? 'check-circle' : ( 'ng' === $icon ? 'close' : 'info' ) ) . '" aria-hidden="true"></i>' . $text . '</span>'; };
$buy = function ( $label ) { return '<a class="wt-tbtn" href="#" rel="sponsored nofollow">' . $label . '</a>'; };
$reaction_table( 'compare-rich', 'cat-table-rich', '画像・アイコン・購入リンク: 表の中で見て選んで買える', $reaction_heads, array(
	array( '製品', array( $img( 'product-a.png', 'リフトワン L1' ), $img( 'media-pickup-1.jpg', 'スタンド・ライト S2' ), $img( 'media-pickup-2.jpg', 'フレックス・プロ F3' ) ), 'wt-row-image' ),
	array( esc_html__( '価格', 'helix-wt' ), array( '<strong>' . esc_html__( '59,800 円', 'helix-wt' ) . '</strong>', esc_html__( '39,800 円', 'helix-wt' ), esc_html__( '84,800 円', 'helix-wt' ) ) ),
	array( '静音性', array( $ico( 'ok', '42 dB' ), $ico( 'mid', '48 dB' ), $ico( 'ok', '41 dB' ) ) ),
	array( 'メモリー機能', array( $ico( 'ok', 'あり' ), $ico( 'ng', 'なし' ), $ico( 'ok', 'あり' ) ) ),
	array( '保証', array( '5 年', '2 年', '7 年' ) ),
	array( '購入', array( $buy( '公式サイト' ), $buy( '公式サイト' ), $buy( '公式サイト' ) ), 'wt-row-buy' ),
) );
$reaction_table( 'compare-striped', 'cat-table-striped', 'シンプル縞: 3製品を行ごとに追う', $reaction_heads, $reaction_rows );
$reaction_table( 'compare-evaluation', 'cat-table-evaluation', '評価セル強調: ◎○△で差を先に読む', $reaction_heads, array(
	array( esc_html__( '静音性', 'helix-wt' ), array( '<span class="wt-mark">◎</span> ' . esc_html__( 'とても静か', 'helix-wt' ), '<span class="wt-mark">○</span> ' . esc_html__( '静か', 'helix-wt' ), '<span class="wt-mark">◎</span> ' . esc_html__( 'とても静か', 'helix-wt' ) ) ),
	array( esc_html__( '安定性', 'helix-wt' ), array( '<span class="wt-mark">○</span> ' . esc_html__( '良', 'helix-wt' ), '<span class="wt-mark">△</span> ' . esc_html__( '条件あり', 'helix-wt' ), '<span class="wt-mark">◎</span> ' . esc_html__( '優秀', 'helix-wt' ) ) ),
	array( esc_html__( '保証', 'helix-wt' ), array( '<span class="wt-mark">◎</span> ' . esc_html__( '5年', 'helix-wt' ), '<span class="wt-mark">△</span> ' . esc_html__( '2年', 'helix-wt' ), '<span class="wt-mark">◎</span> ' . esc_html__( '7年', 'helix-wt' ) ) ),
) );
$reaction_table( 'compare-price', 'cat-table-price', esc_html__( '価格行ハイライト: 比較判断の起点を目立たせる', 'helix-wt' ), $reaction_heads, array_merge( array( array( esc_html__( '価格', 'helix-wt' ), array( '<strong>' . esc_html__( '59,800 円', 'helix-wt' ) . '</strong>', '<strong>' . esc_html__( '39,800 円', 'helix-wt' ) . '</strong>', '<strong>' . esc_html__( '84,800 円', 'helix-wt' ) . '</strong>' ), 'wt-compare__price-row' ) ), array_slice( $reaction_rows, 1 ) ) );
$reaction_table( 'compare-showdown', 'cat-table-showdown', '2製品対決: 最終候補を横並びで決める', array( '比較軸', 'リフトワン L1', 'フレックス・プロ F3' ), array(
	array( '向く人', array( '初めての1台', '広さと静音を優先' ) ),
	array( '価格', array( '59,800 円', '84,800 円' ) ),
	array( esc_html__( '結論', 'helix-wt' ), array( '<span class="wt-mark">○</span> ' . esc_html__( 'バランス型', 'helix-wt' ), '<span class="wt-mark">◎</span> ' . esc_html__( '上位仕様', 'helix-wt' ) ) ),
) );
?>
<!-- wp:group {"anchor":"cat-prosc-contrast","className":"is-style-wt-pros-contrast","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-pros-contrast" id="cat-prosc-contrast"><!-- wp:html --><div class="wt-prosc__item wt-prosc__item--good"><p class="wt-prosc__title"><?php esc_html_e( 'メリット', 'helix-wt' ); ?></p><ul><li><?php esc_html_e( '低い位置まで下がる', 'helix-wt' ); ?></li><li><?php esc_html_e( '昇降音が控えめ', 'helix-wt' ); ?></li><li><?php esc_html_e( '保証が長い', 'helix-wt' ); ?></li></ul></div><div class="wt-prosc__item wt-prosc__item--bad"><p class="wt-prosc__title"><?php esc_html_e( 'デメリット', 'helix-wt' ); ?></p><ul><li><?php esc_html_e( '天板の選択肢が少ない', 'helix-wt' ); ?></li><li><?php esc_html_e( '上限位置で揺れが出る', 'helix-wt' ); ?></li></ul></div><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-prosc-icons","className":"is-style-wt-pros-icons","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-pros-icons" id="cat-prosc-icons"><!-- wp:html --><div class="wt-prosc__item wt-prosc__item--good"><p class="wt-prosc__title"><?php esc_html_e( '○ メリット', 'helix-wt' ); ?></p><ul class="wt-prosc__list"><li><?php esc_html_e( '座り姿勢に合わせやすい', 'helix-wt' ); ?></li><li><?php esc_html_e( '操作が直感的', 'helix-wt' ); ?></li><li><?php esc_html_e( '組み立て手順が短い', 'helix-wt' ); ?></li></ul></div><div class="wt-prosc__item wt-prosc__item--bad"><p class="wt-prosc__title"><?php esc_html_e( '× デメリット', 'helix-wt' ); ?></p><ul class="wt-prosc__list"><li><?php esc_html_e( '設置幅を確認する必要がある', 'helix-wt' ); ?></li><li><?php esc_html_e( '上位機より機能が少ない', 'helix-wt' ); ?></li></ul></div><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-prosc-band","className":"is-style-wt-pros-band","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-pros-band" id="cat-prosc-band"><!-- wp:html --><div class="wt-prosc__item wt-prosc__item--good"><p class="wt-prosc__title"><?php esc_html_e( 'メリット', 'helix-wt' ); ?></p><ul><li><?php esc_html_e( '日常利用のバランスがよい', 'helix-wt' ); ?></li><li><?php esc_html_e( '保証窓口が分かりやすい', 'helix-wt' ); ?></li></ul></div><div class="wt-prosc__item wt-prosc__item--bad"><p class="wt-prosc__title"><?php esc_html_e( 'デメリット', 'helix-wt' ); ?></p><ul><li><?php esc_html_e( '大型天板には不向き', 'helix-wt' ); ?></li><li><?php esc_html_e( '静音性は最上位ではない', 'helix-wt' ); ?></li></ul></div><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-rate-stars","className":"is-style-wt-review-stars","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-review-stars" id="cat-rate-stars"><!-- wp:html --><div class="wt-review__row"><span class="wt-review__label"><?php esc_html_e( '総合評価', 'helix-wt' ); ?></span><span class="wt-review__stars" aria-label="<?php echo esc_attr__( '4.7点', 'helix-wt' ); ?>">★★★★★</span><b class="wt-review__score">4.7 / 5</b></div><div class="wt-review__row"><span class="wt-review__label"><?php esc_html_e( '静音性', 'helix-wt' ); ?></span><span class="wt-review__stars" aria-label="<?php echo esc_attr__( '4.5点', 'helix-wt' ); ?>">★★★★☆</span><b class="wt-review__score">4.5</b></div><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-rate-bars","className":"is-style-wt-review-bars","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-review-bars" id="cat-rate-bars"><!-- wp:html --><div class="wt-rate"><span><?php esc_html_e( '昇降範囲', 'helix-wt' ); ?></span><i style="--v:94%"></i><b>4.7</b></div><div class="wt-rate"><span><?php esc_html_e( '静音性', 'helix-wt' ); ?></span><i style="--v:90%"></i><b>4.5</b></div><div class="wt-rate"><span><?php esc_html_e( '安定性', 'helix-wt' ); ?></span><i style="--v:88%"></i><b>4.4</b></div><div class="wt-rate"><span><?php esc_html_e( '保証', 'helix-wt' ); ?></span><i style="--v:96%"></i><b>4.8</b></div><div class="wt-rate"><span><?php esc_html_e( '価格', 'helix-wt' ); ?></span><i style="--v:84%"></i><b>4.2</b></div><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-rate-score","className":"is-style-wt-review-score","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-review-score" id="cat-rate-score"><!-- wp:html --><div class="wt-review__circle" aria-label="<?php echo esc_attr__( '総合 4.6 点', 'helix-wt' ); ?>"><strong>4.6</strong></div><div><p class="wt-review__comment"><?php esc_html_e( '総合スコア。低さ・静音性・保証をまとめて評価した参考値です。', 'helix-wt' ); ?></p></div><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-blogcard-top","className":"is-style-wt-blogcard-top","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-blogcard-top" id="cat-blogcard-top"><!-- wp:html --><figure class="wp-block-image"><img src="<?php echo esc_url( $u ); ?>/media-pickup-4.jpg" alt="" width="640" height="360"/></figure><div class="wt-blogcard__body"><p class="wt-blogcard__label"><?php esc_html_e( 'あわせて読みたい', 'helix-wt' ); ?></p><p class="wt-blogcard__title"><a href="/p2-article/"><?php esc_html_e( '椅子と机の高さを合わせるチェックリスト', 'helix-wt' ); ?></a></p><p class="wt-blogcard__desc"><?php esc_html_e( '買い替え前に確認したい寸法を短く整理します。', 'helix-wt' ); ?></p></div><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-blogcard-band","className":"is-style-wt-blogcard-band","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-blogcard-band" id="cat-blogcard-band"><!-- wp:html --><div class="wt-blogcard__body"><p class="wt-blogcard__label"><?php esc_html_e( '関連ガイド', 'helix-wt' ); ?></p><p class="wt-blogcard__title"><a href="/p3-article/"><?php esc_html_e( '部屋の広さから天板サイズを選ぶ', 'helix-wt' ); ?></a></p><p class="wt-blogcard__desc"><?php esc_html_e( 'テキスト帯だけで次の記事へ送る型。', 'helix-wt' ); ?></p></div><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-blogcard-ogp","className":"is-style-wt-blogcard-ogp","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-blogcard-ogp" id="cat-blogcard-ogp"><!-- wp:html --><figure class="wp-block-image"><img src="<?php echo esc_url( $u ); ?>/media-pickup-5.jpg" alt="" width="640" height="640"/></figure><div class="wt-blogcard__body"><p class="wt-blogcard__label"><?php esc_html_e( '外部記事の紹介', 'helix-wt' ); ?></p><p class="wt-blogcard__title"><a href="/p4-article/"><?php esc_html_e( '作業環境を整えるための基本メモ', 'helix-wt' ); ?></a></p><p class="wt-blogcard__desc"><?php esc_html_e( '外部OGP風の画像・見出し・抜粋を組み合わせた表示例。', 'helix-wt' ); ?></p></div><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-pr-intro","className":"is-style-wt-pr-intro","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-pr-intro" id="cat-pr-intro"><!-- wp:html --><p class="wt-pr is-style-wt-pr-intro"><span class="wt-pr__tag">PR</span><span><?php esc_html_e( '記事上部で広告を含むことを1文で伝えます。', 'helix-wt' ); ?></span></p><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-pr-inline","className":"is-style-wt-pr-inline","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-pr-inline" id="cat-pr-inline"><!-- wp:html --><span class="wt-pr__tag">PR</span><h3><?php esc_html_e( '製品の選び方', 'helix-wt' ); ?></h3><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-pr-double","className":"is-style-wt-pr-double","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-pr-double" id="cat-pr-double"><!-- wp:html --><p class="wt-pr"><span class="wt-pr__tag">PR</span><span><?php esc_html_e( '記事冒頭の開示。', 'helix-wt' ); ?></span></p><p class="wt-pr"><span class="wt-pr__tag">PR</span><span><?php esc_html_e( '記事末尾でも開示を再確認します。', 'helix-wt' ); ?></span></p><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-pr-band","className":"is-style-wt-pr-band","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-pr-band" id="cat-pr-band"><!-- wp:html --><p class="wt-pr is-style-wt-pr-band"><span class="wt-pr__icon" aria-hidden="true">i</span><span class="wt-pr__tag">PR</span><span><?php esc_html_e( '広告・報酬の有無をアイコン付き帯で示します。', 'helix-wt' ); ?></span></p><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-detext-takeaways","className":"is-style-wt-detext-takeaways","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-detext-takeaways" id="cat-detext-takeaways"><!-- wp:html --><div class="wt-detext__item"><strong><?php esc_html_e( '低さ', 'helix-wt' ); ?></strong><p><?php esc_html_e( '座り姿勢に合う。', 'helix-wt' ); ?></p></div><div class="wt-detext__item"><strong><?php esc_html_e( '静音', 'helix-wt' ); ?></strong><p><?php esc_html_e( '夜でも使いやすい。', 'helix-wt' ); ?></p></div><div class="wt-detext__item"><strong><?php esc_html_e( '保証', 'helix-wt' ); ?></strong><p><?php esc_html_e( '長期利用を支える。', 'helix-wt' ); ?></p></div><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-detext-metrics","className":"is-style-wt-detext-metrics","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-detext-metrics" id="cat-detext-metrics"><!-- wp:html --><div class="wt-detext__metric"><span class="wt-num">62<small>cm</small></span><small><?php esc_html_e( '最低高さ', 'helix-wt' ); ?></small></div><div class="wt-detext__metric"><span class="wt-num">41<small>dB</small></span><small><?php esc_html_e( '静音性', 'helix-wt' ); ?></small></div><div class="wt-detext__metric"><span class="wt-num">7<small><?php esc_html_e( '年', 'helix-wt' ); ?></small></span><small><?php esc_html_e( '保証', 'helix-wt' ); ?></small></div><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-graph-bar","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-graph-bar"><!-- wp:pattern {"slug":"helix-wt/graphs"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-graph-stack","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-graph-stack"><!-- wp:pattern {"slug":"helix-wt/graph-stack"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-graph-grouped","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-graph-grouped"><!-- wp:pattern {"slug":"helix-wt/graph-grouped"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-graph-column","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-graph-column"><!-- wp:pattern {"slug":"helix-wt/graph-column"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-graph-score","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-graph-score"><!-- wp:pattern {"slug":"helix-wt/graph-score"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-graph-gauge","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-graph-gauge"><!-- wp:pattern {"slug":"helix-wt/graph-gauge"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-graph-radar","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-graph-radar"><!-- wp:pattern {"slug":"helix-wt/graph-radar"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-graph-donut","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-graph-donut"><!-- wp:pattern {"slug":"helix-wt/graph-donut"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-graph-line","layout":{"type":"default"}} --><div class="wp-block-group" id="cat-graph-line"><!-- wp:pattern {"slug":"helix-wt/graph-line"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-detext-diagram","className":"is-style-wt-detext-diagram","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-detext-diagram" id="cat-detext-diagram"><!-- wp:html --><div class="wt-detext__diagram-box"><?php esc_html_e( '身長', 'helix-wt' ); ?></div><span class="wt-detext__diagram-arrow" aria-hidden="true">→</span><div class="wt-detext__diagram-box"><?php esc_html_e( '机の高さ', 'helix-wt' ); ?></div><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-detext-quote","className":"is-style-wt-detext-quote","layout":{"type":"default"}} --><div class="wp-block-group is-style-wt-detext-quote" id="cat-detext-quote"><!-- wp:html --><blockquote><p><?php esc_html_e( '「毎日の調整が、思ったより簡単だった」', 'helix-wt' ); ?></p><cite><?php esc_html_e( '短い引用でレビューの要点を残す例', 'helix-wt' ); ?></cite></blockquote><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e( 'PO反応7: contrast-guard の見せ方（5概念型）', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<!-- wp:paragraph --><p><?php esc_html_e( '既存の輝度計測→文字色・スクリム強度の自動選択を残し、その上に画像の見せ方を重ねる。下記の比率確認は近似式（画像輝度とgradient alphaの線形近似）であり、実際の文字グリフ画素を直接測るものではない。', 'helix-wt' ); ?></p><!-- /wp:paragraph -->
<!-- wp:group {"anchor":"cat-contrast-variants","className":"wt-lumgrid wt-contrast-variants","layout":{"type":"default"}} --><div class="wp-block-group wt-lumgrid wt-contrast-variants" id="cat-contrast-variants">
<?php
$contrast_variants = array(
	'white-fade'     => '白フェード',
	'overlay-warm'   => '暖色オーバーレイ',
	'overlay-cool'   => '寒色オーバーレイ',
	'overlay-brand'  => 'ブランド色オーバーレイ',
	'bottom-gradient'=> '下部グラデーション',
	'blur-bright'    => 'ぼかし + 明度調整',
	'duotone'        => 'デュオトーン風',
);
foreach ( $contrast_variants as $variant => $label ) :
	foreach ( array( 'dark' => 'lum-dark.jpg', 'mid' => 'lum-mid.jpg', 'light' => 'lum-light.jpg' ) as $lum_key => $file ) :
		$contrast_class = 'is-style-wt-contrast-' . $variant; // 型 class 単独（is-style-wt-scrim を含意する。Astra 是正）
		$text_class     = 'white-fade' === $variant ? 'has-contrast-color has-text-color' : 'has-base-color has-text-color';
		$cover_id       = 'cat-contrast-' . $variant . '-' . $lum_key;
?>
<!-- wp:cover {"url":"<?php echo esc_url( $u . '/' . $file ); ?>","dimRatio":0,"minHeight":220,"className":"<?php echo $contrast_class; ?>","anchor":"<?php echo $cover_id; ?>","layout":{"type":"constrained"}} --><div class="wp-block-cover <?php echo $contrast_class; ?>" id="<?php echo $cover_id; ?>" style="min-height:220px"><img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( $u . '/' . $file ); ?>" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container is-layout-constrained"><h3 class="wp-block-heading <?php echo $text_class; ?>"><?php echo $label; ?>（<?php echo $lum_key; ?>）</h3><p class="<?php echo $text_class; ?>"><?php esc_html_e( '写真の印象を変えながら可読性を守る表示例。', 'helix-wt' ); ?></p></div></div><!-- /wp:cover -->
<?php
	endforeach;
endforeach;
?>
</div><!-- /wp:group -->
