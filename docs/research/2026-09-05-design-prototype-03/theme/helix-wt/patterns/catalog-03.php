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
	echo '<!-- wp:group {"className":"' . $cls . '","anchor":"' . $id . '","layout":{"type":"flow"}} --><div class="wp-block-group ' . $cls . '" id="' . $id . '">';
	if ( $title ) {
		echo '<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700"}}} --><p style="font-weight:700">' . $title . '</p><!-- /wp:paragraph -->';
	}
	echo '<!-- wp:paragraph --><p>' . $body . '</p><!-- /wp:paragraph --></div><!-- /wp:group -->';
};
$h2 = function ( $style, $id ) {
	echo '<!-- wp:group {"anchor":"' . $id . '","layout":{"type":"flow"}} --><div class="wp-block-group" id="' . $id . '"><!-- wp:heading {"className":"is-style-wt-' . $style . '"} --><h2 class="wp-block-heading is-style-wt-' . $style . '">昇降デスクの選び方と 3 製品の比較</h2><!-- /wp:heading --><!-- wp:paragraph {"className":"wt-sub"} --><p class="wt-sub">補助文はミュートグレー。見出し本文は SP 390 で 20 字が 1 行に収まる。</p><!-- /wp:paragraph --></div><!-- /wp:group -->';
};
$h3 = function ( $style, $id ) {
	echo '<!-- wp:group {"anchor":"' . $id . '","layout":{"type":"flow"}} --><div class="wp-block-group" id="' . $id . '"><!-- wp:heading {"level":3,"className":"is-style-wt-' . $style . '"} --><h3 class="wp-block-heading is-style-wt-' . $style . '">リフトワン L1：静かで低い位置まで下がる</h3><!-- /wp:heading --></div><!-- /wp:group -->';
};
?>
<!-- wp:heading --><h2 class="wp-block-heading">h2 6 型</h2><!-- /wp:heading -->
<?php foreach ( array( 'plain', '2tone', 'icon', 'bar', 'underline', 'band' ) as $s ) { $h2( $s, 'cat-h2-' . $s ); } ?>
<!-- wp:heading --><h2 class="wp-block-heading">h3 3 型</h2><!-- /wp:heading -->
<?php foreach ( array( 'bar-thin', 'dotted', 'num' ) as $s ) { $h3( $s, 'cat-h3-' . $s ); } ?>
<!-- wp:heading --><h2 class="wp-block-heading">囲み 7 型 + 色</h2><!-- /wp:heading -->
<?php
$body = '昇降デスクは「最低高さ」が意外に重要です。身長 160cm 前後の人は座り姿勢で 62cm 以下まで下がらないと、肩がこります。';
$box( 'plain-border', '', 'plain-border（罫線）', $body, 'cat-box-plain-border' );
$box( 'tinted', '', 'tinted（淡塗り）', $body, 'cat-box-tinted' );
$box( 'band-title', '', '帯タイトル', $body, 'cat-box-band-title' );
$box( 'tab-title', '', 'タブタイトル', $body, 'cat-box-tab-title' );
$box( 'label-title', '', 'ラベルタイトル', $body, 'cat-box-label-title' );
$box( 'card-shadow', '', '影カード', $body, 'cat-box-shadow-card' );
?>
<!-- wp:group {"anchor":"cat-box-check-list","layout":{"type":"flow"}} --><div class="wp-block-group" id="cat-box-check-list"><!-- wp:group {"className":"is-style-wt-plain-border","layout":{"type":"flow"}} --><div class="wp-block-group is-style-wt-plain-border"><!-- wp:list {"className":"is-style-wt-check"} --><ul class="wp-block-list is-style-wt-check"><li>3 製品の価格・昇降範囲・静音性の違い</li><li>部屋の広さと予算別のおすすめ</li><li>後悔しやすい 3 つの落とし穴</li></ul><!-- /wp:list --></div><!-- /wp:group --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-box-colors","layout":{"type":"flow"}} --><div class="wp-block-group" id="cat-box-colors">
<?php
$box( 'band-title', 'warn', '注意', '180cm 天板は梱包が 190×90cm を超え、エレベーターに入らない建物があります。', 'cat-box-warn' );
$box( 'tab-title', 'point', 'ポイント', '月額の差より、作業時間の削減のほうが年間コストに効きます。', 'cat-box-point' );
$box( 'tinted', 'note', '補足', '価格は 2026 年 8 月時点の公式サイトの表示。セール価格は含めていません。', 'cat-box-note' );
?>
</div><!-- /wp:group -->
<!-- wp:heading --><h2 class="wp-block-heading">記事内 CTA 4 型</h2><!-- /wp:heading -->
<!-- wp:group {"anchor":"cat-cta-product","layout":{"type":"flow"}} --><div class="wp-block-group" id="cat-cta-product"><!-- wp:pattern {"slug":"helix-wt/product-bundle"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-cta-banner","layout":{"type":"flow"}} --><div class="wp-block-group" id="cat-cta-banner"><!-- wp:pattern {"slug":"helix-wt/cta-banner"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-cta-button","layout":{"type":"flow"}} --><div class="wp-block-group" id="cat-cta-button"><!-- wp:pattern {"slug":"helix-wt/cta-button"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-cta-box","layout":{"type":"flow"}} --><div class="wp-block-group" id="cat-cta-box"><!-- wp:pattern {"slug":"helix-wt/cta-box"} /--></div><!-- /wp:group -->
<!-- wp:heading --><h2 class="wp-block-heading">比較表・メリデメ・評価バー・リンクカード・PR</h2><!-- /wp:heading -->
<!-- wp:group {"anchor":"cat-table","layout":{"type":"flow"}} --><div class="wp-block-group" id="cat-table"><!-- wp:table {"className":"is-style-wt-compare"} --><figure class="wp-block-table is-style-wt-compare"><table><caption>電動昇降デスク 3 製品の比較</caption><thead><tr><th>項目</th><th>リフトワン L1</th><th>スタンド・ライト S2</th><th>フレックス・プロ F3</th></tr></thead><tbody><tr><td>価格</td><td class="wt-num">59,800 円</td><td class="wt-num">39,800 円</td><td class="wt-num">84,800 円</td></tr><tr><td>昇降範囲</td><td>62〜127 cm</td><td>71〜118 cm</td><td>60〜125 cm</td></tr><tr><td>静音性</td><td><span class="wt-mark">◎</span> 42 dB</td><td><span class="wt-mark">○</span> 48 dB</td><td><span class="wt-mark">◎</span> 41 dB</td></tr><tr><td>保証</td><td>5 年</td><td>2 年</td><td>7 年</td></tr></tbody></table></figure><!-- /wp:table --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-prosc","layout":{"type":"flow"}} --><div class="wp-block-group" id="cat-prosc"><!-- wp:columns {"className":"wt-prosc"} --><div class="wp-block-columns wt-prosc"><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"className":"is-style-wt-label-title wt-c-ok","layout":{"type":"flow"}} --><div class="wp-block-group is-style-wt-label-title wt-c-ok"><!-- wp:paragraph --><p>メリット</p><!-- /wp:paragraph --><!-- wp:list {"className":"is-style-wt-pros"} --><ul class="wp-block-list is-style-wt-pros"><li>62cm まで下がる</li><li>昇降が静か</li><li>5 年保証</li></ul><!-- /wp:list --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"className":"is-style-wt-label-title wt-c-warn","layout":{"type":"flow"}} --><div class="wp-block-group is-style-wt-label-title wt-c-warn"><!-- wp:paragraph --><p>デメリット</p><!-- /wp:paragraph --><!-- wp:list {"className":"is-style-wt-cons"} --><ul class="wp-block-list is-style-wt-cons"><li>天板は 2 サイズのみ</li><li>最高位置で少し揺れる</li></ul><!-- /wp:list --></div><!-- /wp:group --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-rate","layout":{"type":"flow"}} --><div class="wp-block-group" id="cat-rate"><!-- wp:html --><div class="wt-rate"><span>昇降範囲</span><i style="--v:94%"></i><b>4.7</b></div><div class="wt-rate"><span>静音性</span><i style="--v:90%"></i><b>4.5</b></div><div class="wt-rate"><span>価格・保証</span><i style="--v:84%"></i><b>4.2</b></div><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-linkcard","layout":{"type":"flow"}} --><div class="wp-block-group" id="cat-linkcard"><!-- wp:pattern {"slug":"helix-wt/linkcard"} /--></div><!-- /wp:group -->
<!-- wp:group {"anchor":"cat-pr","layout":{"type":"flow"}} --><div class="wp-block-group" id="cat-pr"><!-- wp:html --><p class="wt-pr is-style-wt-pr"><span class="wt-pr__tag">PR</span>本記事にはアフィリエイト広告を含みます。評価・掲載順は報酬額で決めていません。</p><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:heading --><h2 class="wp-block-heading">de-text: 番号バッジ・アイコンリスト・引用符・数字</h2><!-- /wp:heading -->
<!-- wp:group {"anchor":"cat-detext","layout":{"type":"flow"}} --><div class="wp-block-group" id="cat-detext"><!-- wp:list {"className":"is-style-wt-badge-list"} --><ol class="wp-block-list is-style-wt-badge-list"><li>無料登録して身長と机の高さを入力する</li><li>2 週間、座り・立ちを交互に試す</li><li>合わなければ 30 日以内に返品する</li></ol><!-- /wp:list --><!-- wp:list {"className":"is-style-wt-icon-list"} --><ul class="wp-block-list is-style-wt-icon-list"><li>迷ったら → リフトワン L1</li><li>予算優先 → スタンド・ライト S2</li></ul><!-- /wp:list --><!-- wp:quote {"className":"is-style-wt-quote-mark"} --><blockquote class="wp-block-quote is-style-wt-quote-mark"><p>「最初の 1 台としては十分でした」</p><cite>読者アンケートより（架空）</cite></blockquote><!-- /wp:quote --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><span class="wt-num"><span class="wt-count" data-to="1284">1,284</span><small>件の実測</small></span></p><!-- /wp:paragraph --></div><!-- /wp:group -->
<!-- wp:heading --><h2 class="wp-block-heading">自動コントラスト guard（3 輝度）</h2><!-- /wp:heading -->
<!-- wp:group {"anchor":"cat-contrast","className":"wt-lumgrid","layout":{"type":"flow"}} --><div class="wp-block-group wt-lumgrid" id="cat-contrast">
<?php foreach ( array( 'dark' => 'lum-dark.jpg', 'mid' => 'lum-mid.jpg', 'light' => 'lum-light.jpg' ) as $k => $f ) : ?>
<!-- wp:cover {"url":"<?php echo esc_url( $u . '/' . $f ); ?>","dimRatio":0,"minHeight":220,"className":"is-style-wt-scrim wt-scrim","anchor":"cat-contrast-<?php echo $k; ?>","layout":{"type":"constrained"}} --><div class="wp-block-cover is-style-wt-scrim wt-scrim" id="cat-contrast-<?php echo $k; ?>" style="min-height:220px"><img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( $u . '/' . $f ); ?>" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container is-layout-constrained"><!-- wp:heading {"level":3,"textColor":"base","className":"wt-scrim__t"} --><h3 class="wp-block-heading wt-scrim__t has-base-color has-text-color">写真の上の見出し（<?php echo $k; ?>）</h3><!-- /wp:heading --><!-- wp:paragraph {"textColor":"base","fontSize":"s"} --><p class="has-base-color has-text-color has-s-font-size">本文サイズの白文字。スクリム強度は画像の輝度から自動で決まる。</p><!-- /wp:paragraph --></div></div><!-- /wp:cover -->
<?php endforeach; ?>
</div><!-- /wp:group -->
