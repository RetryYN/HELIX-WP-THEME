<?php
// Run only in the dedicated local PoC through WP-CLI eval-file. No production data is imported.
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || get_option( 'blogname' ) !== 'HELIX Content Lab' ) { throw new RuntimeException( 'Dedicated lab required' ); }
function wtcf_seed( $type, $slug, $title, $summary, $content, $doc ) {
	$old = get_page_by_path( $slug, OBJECT, $type );
	$id = wp_insert_post( array( 'ID' => $old ? $old->ID : 0, 'post_type' => $type, 'post_name' => $slug,
		'post_title' => $title, 'post_excerpt' => $summary, 'post_content' => $content,
		'post_status' => 'publish', 'meta_input' => array( '_wtcf_document' => $doc ) ), true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	return $id;
}
$preview = '<p>情報を増やす前に、誰が何を決めるためのページなのかを考えます。読み手の疑問と、提供する根拠がつながると、次に取る行動も選びやすくなります。</p><h2>最初に揃えるのは、判断のものさし</h2><p>制作の出発点を見た目だけに置くと、途中で目的が曖昧になります。まず「何に困っているのか」「どこまで分かれば動けるのか」を言葉にしましょう。</p>';
$private = '<h2>実践編：判断を支える3つの問い</h2><p>この段落は購入者向けの検証本文です。課題の定義、比較条件、次の行動を順に照合します。</p><ol><li>解決する課題が一文で伝わるか。</li><li>判断材料と、その限界が示されているか。</li><li>適さない場合に、別の選択ができるか。</li></ol><h2>振り返りを次の改善につなぐ</h2><p>公開後には、読み手がつまずく場所を確認します。見出し、根拠、導線の順に見直し、一度にすべてを変えずに比較してください。</p>';
$ids = array();
$ids['oneoff'] = wtcf_seed( 'wt_paid', 'decision-design', '選ばれる前に、伝わっているか。', '読み手の理解を起点に、情報の順番を組み立てる。小さなチームのためのページ設計ノート。', $preview, array( 'billing' => 'oneoff', 'price' => '800円 / 1記事', 'chapters' => array( '課題を一文で定義する', '比較に必要な根拠を揃える', '迷いを残さない次の一歩' ), 'body' => $private ) );
$ids['subscription'] = wtcf_seed( 'wt_paid', 'monthly-notes', '改善を続けるチームの、小さな習慣。', '一度の正解より、観察を重ねる仕組みを。月刊ノートで学ぶ、サイトとチームの育て方。', $preview, array( 'billing' => 'subscription', 'price' => '1,200円 / 月', 'chapters' => array( '観察の記録を残す', '変更前後を同じ条件で比べる', 'チームで判断を共有する' ), 'body' => $private ) );
$people = array( 'editor' => array( 'name' => '編集担当 A', 'affiliation' => '架空編集室 / 編集', 'mark' => 'A', 'bio' => '情報の伝わり方を考える、検証用の人物です。' ), 'designer' => array( 'name' => '制作担当 B', 'affiliation' => '架空制作室 / デザイン', 'mark' => 'B', 'bio' => '読み手の行動から設計する、検証用の人物です。' ) );
$interview = array( 'confirmed' => true, 'people' => $people, 'exchanges' => array(
	array( 'speaker' => 'editor', 'question' => '「伝わらない」と感じたとき、どこから見直しますか。', 'answer' => 'まず読み手が知りたいことを書き出します。私たちが話したいことの順番と一致するとは限りません。質問に答える順番に並べるだけで、読みやすさが変わります。' ),
	array( 'speaker' => 'designer', 'question' => '余白は、どんな役割を持っていますか。', 'answer' => '違う話題の境界や、考えるための間をつくります。広ければよいのではなく、同じ関係の情報には同じ間隔を使うことを意識しています。' ),
	array( 'speaker' => 'editor', 'question' => '編集とデザインの判断を、どう共有していますか。', 'answer' => '同じ内容で複数の案を並べ、違いを具体的に話します。好みだけでは決めず、読み手が迷わず判断できるかを確認します。' ) ) );
$ids['interview'] = wtcf_seed( 'wt_interview', 'making-room', '余白をつくる。対話から、伝わる形へ。', '編集とデザイン、それぞれの視点で考える「読み手のための順番」。制作の現場から話を聞きました。', '<p>良いページは、何から生まれるのでしょうか。見出しの言葉と情報の配置を一緒に考える、二人の対話を紹介します。</p>', $interview );
$interview['confirmed'] = false;
$ids['unconfirmed'] = wtcf_seed( 'wt_interview', 'pending-confirmation', '掲載確認前の対話', '確認が済むまで公開しない検証用データ。', '公開前の本文', $interview );
$ids['lp'] = wtcf_seed( 'wt_lp', 'editorial-session', '次の改善を、一緒に決める。', '情報設計の相談セッション。現状を整理して、取り組む順番を明確にします。', '<p><a class="wtcf-button" href="#apply">相談内容を入力する</a></p>', array( 'sections' => array(
	array( 'title' => '60分で、課題と優先順位を整理', 'text' => '事前にいただいた内容をもとに、読み手・根拠・導線の3つの視点で状況を確認します。' ),
	array( 'title' => '持ち帰るのは、次に試す具体案', 'text' => 'セッションの最後に、変更する箇所と確かめ方を一緒に決めます。このページは申込導線の検証用です。' ) ) ) );
$ids['blp'] = wtcf_seed( 'wt_blp', 'before-redesign', '作り直す、その前に。', 'ページを増やしても、相談につながらない。まずは読み手が立ち止まる理由から考えてみませんか。', '<p>新しいデザインを選ぶ前に、今あるページの役割を見直してみましょう。情報が足りないのか、順番が違うのかで、必要な改善は変わります。</p>', array( 'target' => 'editorial-session', 'sections' => array(
	array( 'title' => '見た目の問題と、理解の問題を分ける', 'text' => '何を提供しているかが分からないなら、装飾より先に説明を整えます。比較材料がないなら、特徴だけでなく条件や制約も示します。' ),
	array( 'title' => '小さな変更で、確かめられることがある', 'text' => '問い合わせを急がせる前に、疑問に答える内容を置きます。変更前後を同じ内容と画面幅で比較し、読み手の負担が減ったかを確認します。' ),
	array( 'title' => '相談が向く場合・向かない場合', 'text' => '課題の整理と改善の順番を決めたいチームには相談が向きます。すでに実装仕様が確定し、作業のみを依頼したい場合は、制作サービスの検討が適しています。' ) ) ) );
$ids['embedded'] = wtcf_seed( 'page', 'voices-in-context', '制作の考え方', '人物の詳細へつながる参照カード。', '[wtcf_interview_card slug="making-room"]', array() );
require_once __DIR__ . '/seed-learning.php';
require_once __DIR__ . '/seed-site-pages.php';
update_option( 'wtcf_fixture_ids', $ids, false );
update_option( 'blog_public', 0 );
update_option( 'permalink_structure', '/%postname%/' );
flush_rewrite_rules();
echo wp_json_encode( $ids ) . "\n";
