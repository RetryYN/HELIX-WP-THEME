<?php
/**
 * Title: HP（ホーム / トップページ・選択可能）
 * Slug: helix-wt/home
 * Categories: helix-wt
 * Description: 2026-09-06 PO 反応 17 回目 WT-EVT-0277「HP ページは？」の Claude 案。hero 6 型・CTA 4 型・区間セット 3 種（企業 HP / サービス / メディア）・お知らせ 4 型・問い合わせ帯 5 型・固定導線 4 型。段8（WT-EVT-0287、台帳 home-event-recapture-v2）: hero +3（cards-carousel / product-shot / search-box）・CTA +1（search）・区間セット +2（店舗・スクール / 学校法人・団体）・問い合わせ帯 +1（double-cta）・固定ページ用パーツ（helix-wt-page/*）を区間として転用。
 *              既定は台帳 research-r17（HP 39 件）の最多型。文言・数値・社名は PoC 用の架空。フォームは送信しない（action="#" method="get"、type="button"）。
 */
$u = get_theme_file_uri( 'assets/img' );
$cta = function ( $id ) {
	// hero 内 CTA 4 型（body.wt-home-hero-cta-* で 1 つだけ表示）
	echo '<div class="wt-home-cta wt-home-cta--double"><a class="wt-lp-cta-action" href="#contact">お問い合わせ</a><a class="wt-lp-cta-action wt-lp-cta-action--secondary" href="#service">サービスを見る</a></div>';
	echo '<div class="wt-home-cta wt-home-cta--single"><a class="wt-lp-cta-action" href="#contact">お問い合わせ</a></div>';
	echo '<div class="wt-home-cta wt-home-cta--search" role="search" aria-label="サイト内検索"><label class="screen-reader-text" for="home-cta-q-' . $id . '">キーワード</label><input id="home-cta-q-' . $id . '" type="search" name="s" placeholder="例: 業務改善 事例" autocomplete="off"><button type="button" aria-describedby="home-cta-q-note-' . $id . '"><i class="wt-i wt-i--s wt-i--search" aria-hidden="true"></i>検索</button><span id="home-cta-q-note-' . $id . '" class="screen-reader-text">PoC のため検索は動作しない</span></div>';
	echo '<div class="wt-home-cta wt-home-cta--tel-button"><a class="wt-home-tel" href="tel:0000000000"><i class="wt-i wt-i--phone" aria-hidden="true"></i><span><b>000-000-0000</b><small>平日 9:00-18:00</small></span></a><a class="wt-lp-cta-action" href="#contact">フォームで相談</a></div>';
};
?>
<!-- wp:html -->
<div class="wt-home-hero-slot" id="home-hero">
<section class="wt-home-hero wt-home-hero--text-only" aria-labelledby="home-hero-text-title"><div class="wt-home-hero__inner"><p class="wt-eyebrow">SAMPLE WORKS</p><h1 id="home-hero-text-title">現場の課題を、設計から運用まで一緒に解く。</h1><p class="wt-home-hero__lead">製造・士業・医療の中小企業向けに、業務の見える化と改善を支援する架空の会社です（PoC 用の文言）。</p><?php $cta( 'text' ); ?></div></section>
<section class="wt-home-hero wt-home-hero--slider" aria-roledescription="carousel" aria-label="メインビジュアル"><div class="wt-home-slider__track" tabindex="0">
<div class="wt-home-slider__slide"><img src="<?php echo esc_url( $u ); ?>/media-pickup-1.jpg" alt="" width="1440" height="810" loading="eager" fetchpriority="high" decoding="async"><div class="wt-home-slider__caption"><p class="wt-eyebrow">01</p><h1>現場の課題を、設計から運用まで一緒に解く。</h1><?php $cta( 'slide1' ); ?></div></div>
<div class="wt-home-slider__slide"><img src="<?php echo esc_url( $u ); ?>/media-pickup-2.jpg" alt="" width="1440" height="810" loading="lazy" decoding="async"><div class="wt-home-slider__caption"><p class="wt-eyebrow">02</p><h2>導入事例を公開しました</h2><p>製造・士業・医療の 3 業種で、問い合わせ数と作業時間の変化をまとめています。</p></div></div>
<div class="wt-home-slider__slide"><img src="<?php echo esc_url( $u ); ?>/media-pickup-3.jpg" alt="" width="1440" height="810" loading="lazy" decoding="async"><div class="wt-home-slider__caption"><p class="wt-eyebrow">03</p><h2>秋の無料相談会（オンライン）</h2><p>毎週木曜 15:00〜。1 社 30 分の個別相談です。</p></div></div>
</div><div class="wt-home-slider__nav" hidden><button type="button" data-wt-slide="prev" aria-label="前へ">‹</button><div class="wt-home-slider__dots" aria-label="ページ送り"></div><button type="button" data-wt-slide="next" aria-label="次へ">›</button></div></section>
<section class="wt-home-hero wt-home-hero--fullbleed" data-wt-scrim aria-labelledby="home-hero-full-title"><img class="wt-home-hero__bg" src="<?php echo esc_url( $u ); ?>/hero.png" alt="" width="1440" height="820" loading="eager" fetchpriority="high" decoding="async"><div class="wt-home-hero__inner"><p class="wt-eyebrow">SAMPLE WORKS</p><h1 id="home-hero-full-title">現場の課題を、設計から運用まで一緒に解く。</h1><p class="wt-home-hero__lead">業務の見える化と改善を支援する架空の会社です（PoC 用の文言）。</p><?php $cta( 'full' ); ?></div></section>
<section class="wt-home-hero wt-home-hero--split" aria-labelledby="home-hero-split-title"><div class="wt-home-hero__inner wt-home-hero__grid"><div><p class="wt-eyebrow">SAMPLE WORKS</p><h1 id="home-hero-split-title">現場の課題を、設計から運用まで一緒に解く。</h1><p class="wt-home-hero__lead">業務の見える化と改善を支援する架空の会社です（PoC 用の文言）。</p><?php $cta( 'split' ); ?></div><div class="wt-home-hero__media"><img src="<?php echo esc_url( $u ); ?>/hero.png" alt="" width="720" height="540" loading="eager" fetchpriority="high" decoding="async"></div></div></section>
<section class="wt-home-hero wt-home-hero--video" aria-labelledby="home-hero-video-title"><div class="wt-home-hero__inner wt-home-hero__grid"><div><p class="wt-eyebrow">SAMPLE WORKS</p><h1 id="home-hero-video-title">3 分でわかる、私たちの支援。</h1><p class="wt-home-hero__lead">動画枠の型。PoC では外部動画を埋め込まず、ポスター画像と再生ボタンの見た目だけを置く。</p><?php $cta( 'video' ); ?></div><div class="wt-home-hero__media wt-home-hero__poster"><img src="<?php echo esc_url( $u ); ?>/media-pickup-4.jpg" alt="" width="720" height="405" loading="eager" decoding="async"><button type="button" class="wt-home-hero__play" aria-label="動画を再生（PoC では再生しません）"><i class="wt-i wt-i--arrow-right" aria-hidden="true"></i></button></div></div></section>
<section class="wt-home-hero wt-home-hero--cards-carousel" aria-labelledby="home-hero-cards-title"><div class="wt-home-hero__inner"><p class="wt-eyebrow">SERVICE</p><h1 id="home-hero-cards-title">サービスから探す</h1><p class="wt-home-hero__lead">製品・コースをカードで横送りする hero（台帳 v2: cards-carousel 11%）。PoC 用の文言。</p><?php $cta( 'cards' ); ?><div class="wt-hcar" data-wt-carousel><ul class="wt-hcar__track" tabindex="0" aria-label="サービス一覧（横スクロール）"><li class="wt-hcar__item"><a href="#service"><img src="<?php echo esc_url( $u ); ?>/case-factory.jpg" alt="" width="640" height="400" loading="eager" decoding="async"><b>業務診断</b><span>2 週間で改善の順番を決める</span></a></li><li class="wt-hcar__item"><a href="#service"><img src="<?php echo esc_url( $u ); ?>/case-tax.jpg" alt="" width="640" height="400" loading="eager" decoding="async"><b>仕組みづくり</b><span>台帳・申請・記録を一つの流れに</span></a></li><li class="wt-hcar__item"><a href="#service"><img src="<?php echo esc_url( $u ); ?>/case-clinic.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><b>定着支援</b><span>月 1 回の振り返りで数字を確認</span></a></li><li class="wt-hcar__item"><a href="#cases"><img src="<?php echo esc_url( $u ); ?>/media-pickup-4.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><b>導入事例</b><span>製造・士業・医療の 3 業種</span></a></li><li class="wt-hcar__item"><a href="/lp/"><img src="<?php echo esc_url( $u ); ?>/media-pickup-5.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><b>資料ダウンロード</b><span>3 分で読める診断ガイド</span></a></li></ul><div class="wt-hcar__nav" hidden><button type="button" data-wt-slide="prev" aria-label="前へ">‹</button><button type="button" data-wt-slide="next" aria-label="次へ">›</button></div></div></div></section>
<section class="wt-home-hero wt-home-hero--product-shot" aria-labelledby="home-hero-product-title"><div class="wt-home-hero__inner wt-home-hero__grid"><div><p class="wt-eyebrow">PRODUCT</p><h1 id="home-hero-product-title">現場の記録を、1 つのアプリに。</h1><p class="wt-home-hero__lead">製品画像を主役にする hero（台帳 v2: product-shot 7%、B で 33%）。数値・製品名は PoC 用の架空。</p><?php $cta( 'product' ); ?><ul class="wt-home-hero__badges" aria-label="実績（架空値）"><li><b>320</b><small>社</small></li><li><b>93</b><small>% 継続</small></li><li><b>30</b><small>日間 無料</small></li></ul></div><div class="wt-home-hero__media wt-home-hero__media--product"><img src="<?php echo esc_url( $u ); ?>/product-a.png" alt="" width="640" height="640" loading="eager" fetchpriority="high" decoding="async"></div></div></section>
<section class="wt-home-hero wt-home-hero--search-box" aria-labelledby="home-hero-search-title"><div class="wt-home-hero__inner"><p class="wt-eyebrow">FIND</p><h1 id="home-hero-search-title">知りたいことから探す</h1><p class="wt-home-hero__lead">検索欄を主役にする hero（台帳 v2: search-box 2%、メディア C）。PoC のため検索は動作しない。</p><div class="wt-home-search" role="search" aria-label="サイト内検索"><label class="screen-reader-text" for="home-hero-q">キーワード</label><input id="home-hero-q" type="search" name="s" placeholder="例: 業務改善 事例" autocomplete="off"><button type="button"><i class="wt-i wt-i--s wt-i--search" aria-hidden="true"></i>検索</button></div><ul class="wt-home-search__tags" aria-label="よく検索されるキーワード"><li><a href="/category/topic-one/">業務改善</a></li><li><a href="/category/topic-two/">比較</a></li><li><a href="/category/topic-three/">選び方</a></li><li><a href="/category/topic-index/">すべての記事</a></li></ul><?php $cta( 'search' ); ?></div></section>
</div>
<!-- /wp:html -->
<!-- wp:group {"className":"wt-home-hero wt-home-hero--article-grid","layout":{"type":"default"}} -->
<div class="wp-block-group wt-home-hero wt-home-hero--article-grid"><div class="wt-home-hero__inner">
<!-- wp:heading {"level":1,"className":"wt-home-hero__title wt-home-hero__title--grid"} --><h1 class="wp-block-heading wt-home-hero__title wt-home-hero__title--grid">新着記事</h1><!-- /wp:heading -->
<!-- wp:query {"queryId":911,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","sticky":"exclude","inherit":false},"className":"wt-home-grid wt-home-grid--hero"} -->
<div class="wp-block-query wt-home-grid wt-home-grid--hero"><!-- wp:post-template {"layout":{"type":"default"}} -->
<!-- wp:group {"className":"wt-home-card","layout":{"type":"default"}} --><div class="wp-block-group wt-home-card"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9"} /--><!-- wp:post-terms {"term":"category","fontSize":"xs"} /--><!-- wp:post-title {"isLink":true,"level":2,"fontSize":"l"} /--><!-- wp:post-date {"fontSize":"xs"} /--></div><!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->
</div></div>
<!-- /wp:group -->

<!-- wp:group {"className":"wt-home__sections","layout":{"type":"default"}} -->
<div class="wp-block-group wt-home__sections">

<!-- wp:html -->
<section class="wt-home__section wt-home__section--news" id="news" aria-labelledby="home-news-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">NEWS</p><h2 id="home-news-title">お知らせ</h2>
<ul class="wt-home-news wt-home-news--list-with-date"><li><time datetime="2026-09-01">2026.09.01</time><span class="wt-home-news__cat">お知らせ</span><a href="#news">秋の無料相談会（オンライン）の受付を始めました</a></li><li><time datetime="2026-08-20">2026.08.20</time><span class="wt-home-news__cat">導入事例</span><a href="#news">製造業の導入事例を公開しました</a></li><li><time datetime="2026-08-05">2026.08.05</time><span class="wt-home-news__cat">お知らせ</span><a href="#news">夏季休業のご案内</a></li><li><time datetime="2026-07-15">2026.07.15</time><span class="wt-home-news__cat">メディア</span><a href="#news">業界誌に代表のインタビューが掲載されました</a></li></ul>
<div class="wt-home-news wt-home-news--tabs"><div class="wt-home-tabs" role="tablist" aria-label="お知らせの種類"><button type="button" role="tab" aria-selected="true" aria-controls="home-news-tab-all" id="home-news-tabbtn-all">すべて</button><button type="button" role="tab" aria-selected="false" aria-controls="home-news-tab-news" id="home-news-tabbtn-news" tabindex="-1">お知らせ</button><button type="button" role="tab" aria-selected="false" aria-controls="home-news-tab-blog" id="home-news-tabbtn-blog" tabindex="-1">ブログ</button></div><ul id="home-news-tab-all" role="tabpanel" aria-labelledby="home-news-tabbtn-all"><li><time datetime="2026-09-01">2026.09.01</time><a href="#news">秋の無料相談会（オンライン）の受付を始めました</a></li><li><time datetime="2026-08-20">2026.08.20</time><a href="#news">製造業の導入事例を公開しました</a></li><li><time datetime="2026-08-05">2026.08.05</time><a href="#news">夏季休業のご案内</a></li></ul><ul id="home-news-tab-news" role="tabpanel" aria-labelledby="home-news-tabbtn-news" hidden><li><time datetime="2026-09-01">2026.09.01</time><a href="#news">秋の無料相談会（オンライン）の受付を始めました</a></li></ul><ul id="home-news-tab-blog" role="tabpanel" aria-labelledby="home-news-tabbtn-blog" hidden><li><time datetime="2026-08-20">2026.08.20</time><a href="#news">製造業の導入事例を公開しました</a></li></ul><p class="wt-lp-section__note">タブの切替は PoC では静的（見た目の型のみ）。</p></div>
<ul class="wt-home-news wt-home-news--cards"><li><a href="#news"><img src="<?php echo esc_url( $u ); ?>/media-pickup-5.jpg" alt="" width="640" height="360" loading="lazy" decoding="async"><time datetime="2026-09-01">2026.09.01</time><b>秋の無料相談会（オンライン）の受付を始めました</b></a></li><li><a href="#news"><img src="<?php echo esc_url( $u ); ?>/media-pickup-6.jpg" alt="" width="640" height="360" loading="lazy" decoding="async"><time datetime="2026-08-20">2026.08.20</time><b>製造業の導入事例を公開しました</b></a></li><li><a href="#news"><img src="<?php echo esc_url( $u ); ?>/media-pickup-2.jpg" alt="" width="640" height="360" loading="lazy" decoding="async"><time datetime="2026-07-15">2026.07.15</time><b>業界誌に代表のインタビューが掲載されました</b></a></li></ul>
<p class="wt-home-news__more"><a href="#news">お知らせ一覧 <i class="wt-i wt-i--s wt-i--arrow-right" aria-hidden="true"></i></a></p></div></section>

<section class="wt-home__section wt-home__section--service-cards" id="service" aria-labelledby="home-service-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">SERVICE</p><h2 id="home-service-title">サービス</h2>
<ul class="wt-home-cards wt-home-cards--3"><li><a href="#service"><i class="wt-i wt-i--xl wt-i--target" aria-hidden="true"></i><b>業務診断</b><span>現状の作業と手戻りを 2 週間で棚卸しし、改善の順番を決めます。</span></a></li><li><a href="#service"><i class="wt-i wt-i--xl wt-i--wrench" aria-hidden="true"></i><b>仕組みづくり</b><span>台帳・申請・記録を一つの流れにし、担当が変わっても回る形にします。</span></a></li><li><a href="#service"><i class="wt-i wt-i--xl wt-i--trend" aria-hidden="true"></i><b>定着支援</b><span>月 1 回の振り返りで数字を確認し、次の改善を決めます。</span></a></li></ul></div></section>

<section class="wt-home__section wt-home__section--features" id="features" aria-labelledby="home-features-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">WHY US</p><h2 id="home-features-title">選ばれる 3 つの理由</h2>
<ul class="wt-home-features"><li><img src="<?php echo esc_url( $u ); ?>/feature-1.png" alt="" width="160" height="160" loading="lazy" decoding="async"><b>成果から逆算した設計</b><span>目標の問い合わせ数や作業時間から必要な流れを決め、見た目から入りません。</span></li><li><img src="<?php echo esc_url( $u ); ?>/feature-2.png" alt="" width="160" height="160" loading="lazy" decoding="async"><b>現場と同じ言葉で</b><span>専門用語を使わず、現場の担当者が読める手順書で引き渡します。</span></li><li><img src="<?php echo esc_url( $u ); ?>/feature-3.png" alt="" width="160" height="160" loading="lazy" decoding="async"><b>数字で振り返る</b><span>導入後も月次で数字を見て、次の一手を一緒に決めます。</span></li></ul></div></section>

<section class="wt-home__section wt-home__section--numbers" id="numbers" aria-labelledby="home-numbers-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">NUMBERS</p><h2 id="home-numbers-title">数字で見る支援実績</h2>
<ul class="wt-home-numbers"><li><b>320<small>社</small></b><span>支援実績</span></li><li><b>2.4<small>倍</small></b><span>問い合わせ数の中央値（6 か月後）</span></li><li><b>93<small>%</small></b><span>契約継続率</span></li></ul><p class="wt-lp-section__note">数値は PoC 用の架空値。実運用では調査条件と母数を併記する。</p></div></section>

<section class="wt-home__section wt-home__section--cases" id="cases" aria-labelledby="home-cases-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">CASES</p><h2 id="home-cases-title">導入事例</h2>
<ul class="wt-home-cards wt-home-cards--cases"><li><a href="#cases"><img src="<?php echo esc_url( $u ); ?>/case-factory.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><small>製造業・従業員 40 名</small><b>日報の転記をなくし、月 30 時間を削減</b></a></li><li><a href="#cases"><img src="<?php echo esc_url( $u ); ?>/case-tax.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><small>士業・従業員 12 名</small><b>問い合わせ対応を 1 本化し、返信を翌営業日以内に</b></a></li><li><a href="#cases"><img src="<?php echo esc_url( $u ); ?>/case-clinic.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><small>医療・スタッフ 25 名</small><b>予約と問診の流れを整え、待ち時間を 2 割短縮</b></a></li></ul></div></section>

<section class="wt-home__section wt-home__section--price" id="price" aria-labelledby="home-price-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">PRICE</p><h2 id="home-price-title">料金</h2>
<ul class="wt-home-price"><li><b>診断</b><p class="wt-home-price__num">20<small>万円〜</small></p><span>2 週間の棚卸しと改善提案書</span></li><li class="is-featured"><b>仕組みづくり</b><p class="wt-home-price__num">月 15<small>万円〜</small></p><span>3 か月の伴走と手順書の引き渡し</span></li><li><b>定着支援</b><p class="wt-home-price__num">月 5<small>万円〜</small></p><span>月 1 回の振り返り</span></li></ul><p class="wt-lp-section__note">価格は PoC 用の架空値（税別表記の例）。</p></div></section>

<section class="wt-home__section wt-home__section--faq" id="faq" aria-labelledby="home-faq-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">FAQ</p><h2 id="home-faq-title">よくある質問</h2>
<div class="wt-home-faq"><details><summary>相談は無料ですか</summary><p>初回の 60 分は無料です。診断以降は料金表のとおりです。</p></details><details><summary>対応地域はどこですか</summary><p>オンラインで全国対応、訪問は関東圏が中心です。</p></details><details><summary>小規模でも依頼できますか</summary><p>従業員 5 名からの実績があります。</p></details></div></div></section>

<section class="wt-home__section wt-home__section--company" id="company" aria-labelledby="home-company-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">COMPANY</p><h2 id="home-company-title">会社概要</h2>
<table class="wt-home-company"><tbody><tr><th scope="row">会社名</th><td>サンプル株式会社（架空）</td></tr><tr><th scope="row">所在地</th><td>設定された所在地</td></tr><tr><th scope="row">設立</th><td>2016 年 4 月</td></tr><tr><th scope="row">代表者</th><td>代表取締役 サンプル 太郎</td></tr><tr><th scope="row">事業内容</th><td>業務改善支援、社内システムの設計・導入支援</td></tr></tbody></table></div></section>

<section class="wt-home__section wt-home__section--access" id="access" aria-labelledby="home-access-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">ACCESS</p><h2 id="home-access-title">アクセス</h2>
<div class="wt-home-access"><div class="wt-home-map" role="img" aria-label="地図の表示枠（PoC 用のダミー。実運用では地図画像または埋め込みを置く）"><span>MAP</span></div><address><b>サンプル株式会社</b><br>設定された所在地<br>最寄り駅から徒歩 5 分（PoC 用の文言）</address></div></div></section>

<section class="wt-home__section wt-home__section--contact" id="contact" aria-labelledby="home-contact-title"><div class="wt-lp-section-inner">
<div class="wt-home-contact wt-home-contact--tel-form"><div class="wt-home-contact__tel"><p class="wt-eyebrow">CONTACT</p><h2 id="home-contact-title">お問い合わせ</h2><a class="wt-home-tel wt-home-tel--big" href="tel:0000000000"><i class="wt-i wt-i--phone" aria-hidden="true"></i><span><b>000-000-0000</b><small>平日 9:00-18:00</small></span></a></div><form class="wt-lp-form-inline wt-home-form" action="#contact" method="get" data-wt-poc-form="no-submit"><div class="wt-lp-form__grid"><div><label for="home-f-name">お名前</label><input id="home-f-name" name="name" type="text" autocomplete="name" required></div><div><label for="home-f-email">メールアドレス</label><input id="home-f-email" name="email" type="email" autocomplete="email" required></div><div class="is-wide"><label for="home-f-body">ご相談内容</label><textarea id="home-f-body" name="body" rows="3"></textarea></div></div><button class="wt-lp-cta-action" type="button" aria-describedby="home-f-note">送信する</button><p id="home-f-note" class="wt-lp-form__note">PoC のため送信されません。</p></form></div>
<div class="wt-home-contact wt-home-contact--form-only"><p class="wt-eyebrow">CONTACT</p><h2>お問い合わせ</h2><form class="wt-lp-form-inline wt-home-form" action="#contact" method="get" data-wt-poc-form="no-submit"><div class="wt-lp-form__grid"><div><label for="home-f2-name">お名前</label><input id="home-f2-name" name="name" type="text" autocomplete="name" required></div><div><label for="home-f2-email">メールアドレス</label><input id="home-f2-email" name="email" type="email" autocomplete="email" required></div></div><button class="wt-lp-cta-action" type="button" aria-describedby="home-f2-note">送信する</button><p id="home-f2-note" class="wt-lp-form__note">PoC のため送信されません。</p></form></div>
<div class="wt-home-contact wt-home-contact--tel-only"><p class="wt-eyebrow">CONTACT</p><h2>お電話でのお問い合わせ</h2><a class="wt-home-tel wt-home-tel--big" href="tel:0000000000"><i class="wt-i wt-i--phone" aria-hidden="true"></i><span><b>000-000-0000</b><small>平日 9:00-18:00（PoC 用のダミー番号）</small></span></a></div>
<div class="wt-home-contact wt-home-contact--double-cta"><p class="wt-eyebrow">CONTACT</p><h2>お問い合わせ・資料請求</h2><div class="wt-home-contact__two"><div><i class="wt-i wt-i--xl wt-i--mail" aria-hidden="true"></i><b>相談したい</b><p>課題の整理から一緒に。初回 60 分は無料。</p><a class="wt-lp-cta-action" href="#contact">フォームで問い合わせ</a></div><div><i class="wt-i wt-i--xl wt-i--download" aria-hidden="true"></i><b>まず資料が欲しい</b><p>3 分で読める診断ガイドを PDF で。</p><a class="wt-lp-cta-action wt-lp-cta-action--secondary" href="/lp/">資料をダウンロード</a></div></div><p class="wt-lp-form__note">2 面の CTA（台帳 v2: hero_cta=double 46%、contact 帯に転用）。リンク先は PoC のダミー。</p></div>
<div class="wt-home-contact wt-home-contact--line"><p class="wt-eyebrow">CONTACT</p><h2>LINE で気軽に相談</h2><a class="wt-lp-line__btn" href="#contact" rel="nofollow"><span class="wt-lp-line__mark" aria-hidden="true"><i class="wt-i wt-i--bubble"></i></span>LINE で友だち追加</a><p class="wt-lp-form__note">返信は営業時間内。リンク先は PoC のダミー。</p></div>
</div></section>

<section class="wt-home__section wt-home__section--cta-band wt-lp-cta-band" id="cta" aria-labelledby="home-cta-title"><div class="wt-lp-cta-band__inner"><p class="wt-eyebrow">NEXT STEP</p><h2 id="home-cta-title">まずは 60 分の無料相談から。</h2><p>現状の課題を整理し、次の一歩を一緒に決めます。</p><a class="wt-lp-cta-action" href="#contact">無料で相談する</a></div></section>

<section class="wt-home__section wt-home__section--category-cards" id="categories" aria-labelledby="home-categories-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">CATEGORY</p><h2 id="home-categories-title">カテゴリから探す</h2>
<ul class="wt-home-cards wt-home-cards--4"><li><a href="/category/topic-index/"><i class="wt-i wt-i--l wt-i--grid" aria-hidden="true"></i><b>カテゴリ一覧</b></a></li><li><a href="/category/topic-one/"><i class="wt-i wt-i--l wt-i--file" aria-hidden="true"></i><b>最初の読みもの</b></a></li><li><a href="/category/topic-two/"><i class="wt-i wt-i--l wt-i--chart" aria-hidden="true"></i><b>比較の読みもの</b></a></li><li><a href="/category/topic-three/"><i class="wt-i wt-i--l wt-i--check-circle" aria-hidden="true"></i><b>選び方</b></a></li></ul></div></section>

<section class="wt-home__section wt-home__section--banner-row" id="banners" aria-label="バナー"><div class="wt-lp-section-inner"><ul class="wt-home-banners"><li><a href="/lp/"><span class="wt-eyebrow">GUIDE</span><b>3 分の無料診断</b></a></li><li><a href="/category/topic-two/"><span class="wt-eyebrow">RANKING</span><b>今月の比較ランキング</b></a></li><li><a href="#news"><span class="wt-eyebrow">EVENT</span><b>秋の無料相談会</b></a></li></ul></div></section>
<!-- /wp:html -->

<!-- wp:group {"anchor":"greeting","className":"wt-home__section wt-home__section--greeting","layout":{"type":"default"}} -->
<div class="wp-block-group wt-home__section wt-home__section--greeting" id="greeting"><!-- wp:pattern {"slug":"helix-wt-page/greeting"} /--></div>
<!-- /wp:group -->
<!-- wp:group {"anchor":"logos","className":"wt-home__section wt-home__section--logos","layout":{"type":"default"}} -->
<div class="wp-block-group wt-home__section wt-home__section--logos" id="logos"><!-- wp:pattern {"slug":"helix-wt-page/logos-row"} /--></div>
<!-- /wp:group -->
<!-- wp:group {"anchor":"stores","className":"wt-home__section wt-home__section--stores","layout":{"type":"default"}} -->
<div class="wp-block-group wt-home__section wt-home__section--stores" id="stores"><!-- wp:pattern {"slug":"helix-wt-page/stores"} /--></div>
<!-- /wp:group -->
<!-- wp:group {"anchor":"events","className":"wt-home__section wt-home__section--events","layout":{"type":"default"}} -->
<div class="wp-block-group wt-home__section wt-home__section--events" id="events"><!-- wp:pattern {"slug":"helix-wt-page/event-list"} /--></div>
<!-- /wp:group -->
<!-- wp:group {"anchor":"gallery","className":"wt-home__section wt-home__section--gallery","layout":{"type":"default"}} -->
<div class="wp-block-group wt-home__section wt-home__section--gallery" id="gallery"><!-- wp:pattern {"slug":"helix-wt-page/gallery"} /--></div>
<!-- /wp:group -->
<!-- wp:group {"anchor":"sns","className":"wt-home__section wt-home__section--sns","layout":{"type":"default"}} -->
<div class="wp-block-group wt-home__section wt-home__section--sns" id="sns"><!-- wp:pattern {"slug":"helix-wt-page/sns-feed"} /--></div>
<!-- /wp:group -->
<!-- wp:group {"anchor":"history","className":"wt-home__section wt-home__section--history","layout":{"type":"default"}} -->
<div class="wp-block-group wt-home__section wt-home__section--history" id="history"><!-- wp:pattern {"slug":"helix-wt-page/timeline"} /--></div>
<!-- /wp:group -->
<!-- wp:html -->
<section class="wt-home__section wt-home__section--recruit" id="recruit" aria-labelledby="home-recruit-title"><div class="wt-lp-section-inner"><div class="wt-home-recruit"><div><p class="wt-eyebrow">RECRUIT</p><h2 id="home-recruit-title">一緒に働く仲間を募集しています</h2><p>店舗スタッフ・講師・事務（PoC 用の文言。台帳 v2: D で recruit 50%）。</p><a class="wt-lp-cta-action" href="/lp/">採用情報を見る</a></div><img src="<?php echo esc_url( $u ); ?>/media-pickup-6.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"></div></div></section>
<!-- /wp:html -->

<!-- wp:group {"className":"wt-home__section wt-home__section--article-grid","layout":{"type":"default"}} -->
<div class="wp-block-group wt-home__section wt-home__section--article-grid" id="latest"><div class="wt-lp-section-inner">
<!-- wp:paragraph {"className":"wt-eyebrow"} --><p class="wt-eyebrow">LATEST</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">新着記事</h2><!-- /wp:heading -->
<!-- wp:query {"queryId":912,"query":{"perPage":6,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","sticky":"exclude","inherit":false},"className":"wt-home-grid"} -->
<div class="wp-block-query wt-home-grid"><!-- wp:post-template {"layout":{"type":"default"}} -->
<!-- wp:group {"className":"wt-home-card","layout":{"type":"default"}} --><div class="wp-block-group wt-home-card"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9"} /--><!-- wp:post-terms {"term":"category","fontSize":"xs"} /--><!-- wp:post-title {"isLink":true,"level":3,"fontSize":"m"} /--><!-- wp:post-date {"fontSize":"xs"} /--></div><!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->
</div></div>
<!-- /wp:group -->

<!-- wp:group {"className":"wt-home__section wt-home__section--ranking","layout":{"type":"default"}} -->
<div class="wp-block-group wt-home__section wt-home__section--ranking" id="ranking"><div class="wt-lp-section-inner">
<!-- wp:paragraph {"className":"wt-eyebrow"} --><p class="wt-eyebrow">RANKING</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">よく読まれている記事</h2><!-- /wp:heading -->
<!-- wp:query {"queryId":913,"query":{"perPage":5,"pages":0,"offset":0,"postType":"post","order":"asc","orderBy":"title","sticky":"exclude","inherit":false},"className":"wt-home-rank"} -->
<div class="wp-block-query wt-home-rank"><!-- wp:post-template {"layout":{"type":"default"}} -->
<!-- wp:group {"className":"wt-home-rank__item","layout":{"type":"default"}} --><div class="wp-block-group wt-home-rank__item"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9"} /--><!-- wp:post-title {"isLink":true,"level":3,"fontSize":"m"} /--></div><!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->
<!-- wp:paragraph {"className":"wt-lp-section__note"} --><p class="wt-lp-section__note">PoC では閲覧数の集計がないためタイトル順で代用。実運用では計測値で並べる。</p><!-- /wp:paragraph -->
</div></div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:html -->
<nav class="wt-home-fixed wt-home-fixed--sp-bottom-bar" aria-label="固定導線"><a href="tel:0000000000"><i class="wt-i wt-i--s wt-i--phone" aria-hidden="true"></i>電話する</a><a href="#contact"><i class="wt-i wt-i--s wt-i--mail" aria-hidden="true"></i>問い合わせ</a></nav>
<a class="wt-home-fixed wt-home-fixed--float-cta wt-lp-cta-action" href="#contact">無料で相談する</a>
<a class="wt-home-fixed wt-home-fixed--float-tel" href="tel:0000000000" aria-label="電話する（PoC 用のダミー番号）"><i class="wt-i wt-i--l wt-i--phone" aria-hidden="true"></i></a>
<!-- /wp:html -->
