<?php
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || get_option( 'blogname' ) !== 'HELIX Content Lab' ) { throw new RuntimeException( 'Dedicated lab required' ); }
function wtcf_seed_learning( $slug, $title, $summary, $kind, $sections, $parent = 0, $order = 0, $status = 'publish' ) {
	$old = get_posts( array( 'post_type' => 'wt_learning', 'post_status' => 'any', 'name' => $slug, 'post_parent' => $parent, 'posts_per_page' => 1 ) );
	$content = '';
	foreach ( $sections as $i => $section ) {
		$content .= '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">' . esc_html( $section['title'] ) . '</h2><!-- /wp:heading -->';
		$content .= '<!-- wp:paragraph --><p>' . esc_html( $section['text'] ) . '</p><!-- /wp:paragraph -->';
	}
	$id = wp_insert_post( array( 'ID' => $old ? $old[0]->ID : 0, 'post_type' => 'wt_learning', 'post_name' => $slug,
		'post_title' => $title, 'post_excerpt' => $summary, 'post_status' => $status, 'post_parent' => $parent, 'menu_order' => $order,
		'post_content' => $content, 'meta_input' => array( '_wtcf_document' => array( 'kind' => $kind ) ) ), true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	return $id;
}
$learning_ids = array();
$learning_ids['course'] = wtcf_seed_learning( 'page-design', '伝わるページのつくり方', '誰に、何を、どの順番で伝えるか。3つのレッスンで情報設計の基本を学びます。', 'course', array(
	array( 'title' => 'この講座で学ぶこと', 'text' => '読み手の課題を言葉にし、判断材料を揃え、次に進める導線をつくります。専門知識がなくても始められる入門講座です。' ),
	array( 'title' => '学習の進め方', 'text' => '最初から順に読むことも、知りたいレッスンから読むこともできます。ページ内の目次は内容の移動に、学ぶ順番のナビはレッスン間の移動に使います。' ) ) );
$lessons = array(
	array( 'readers', '01 読み手の疑問を見つける', '読み手が最初に知りたいことを、ひとつの問いにまとめます。', array( array( 'title' => '状況から考える', 'text' => '読み手がいつ、どこで、何に困ってページを開くのかを具体的に考えます。属性だけでなく、いま判断したいことを探しましょう。' ), array( 'title' => '問いを一文にする', 'text' => '「自分に合うか分からない」のように、判断を止めている疑問を書き出します。解決に必要な説明を次のレッスンで整理します。' ) ) ),
	array( 'evidence', '02 判断材料を揃える', '特徴だけでなく、条件と限界を伝える方法を考えます。', array( array( 'title' => '比較条件を揃える', 'text' => '価格・対象・提供範囲を同じ条件で比べます。根拠のない順位や断定に頼らず、判断に必要な違いを示します。' ), array( 'title' => '適さない条件も書く', 'text' => 'どんな場合には別の方法が向くかを示すと、読み手が自分で選びやすくなります。説明の根拠と更新日も確認しましょう。' ) ) ),
	array( 'next-step', '03 次の一歩を示す', '理解した内容から、迷わず行動できる導線を組み立てます。', array( array( 'title' => '移動先を具体的に伝える', 'text' => '「詳しく」だけでなく、移動すると何が分かるのかをリンクの言葉にします。相談・比較・資料の違いを分けましょう。' ), array( 'title' => '読み手の選択を残す', 'text' => '今すぐ申し込まない人にも、比較を続ける、資料を読むなどの道筋を残します。ここまでで基本のレッスンは終了です。' ) ) ) );
foreach ( $lessons as $i => $lesson ) { $learning_ids[ $lesson[0] ] = wtcf_seed_learning( $lesson[0], $lesson[1], $lesson[2], 'lesson', $lesson[3], $learning_ids['course'], $i + 1 ); }
$learning_ids['glossary'] = wtcf_seed_learning( 'glossary', '情報設計の用語集', '聞き慣れない言葉を、作業の場面と結びつけて理解します。', 'glossary', array(
	array( 'title' => 'アクセシビリティ', 'text' => '人や利用環境の違いがあっても、必要な情報や機能にアクセスできること。キーボード操作、文字の読みやすさ、内容の構造などから確認します。' ),
	array( 'title' => '情報設計', 'text' => '読み手が理解・判断しやすいように、内容の関係と並び方を考えること。項目を増やす前に、目的と順番を確かめます。' ),
	array( 'title' => '導線', 'text' => '読み手が次の情報や行動へ進むための道筋。リンクの言葉と移動先が一致しているかを確認します。' ) ), 0, 4 );
$learning_ids['help'] = wtcf_seed_learning( 'help', '学習中に困ったら', 'よくある疑問と、再開するためのヒントをまとめました。', 'help', array(
	array( 'title' => '途中のレッスンから始められますか？', 'text' => 'はい。学ぶ順番のナビから知りたいレッスンを選べます。全体の目的を確認したい場合は講座の親ページに戻ってください。' ),
	array( 'title' => '検索で見つからないときは？', 'text' => '短い言葉や、言い換えた言葉で検索してみてください。一覧や用語集から探すこともできます。' ),
	array( 'title' => 'ログインは必要ですか？', 'text' => 'この学習ガイドは公開ページです。有料記事の閲覧権限とは別に扱い、ログインなしで読むことができます。' ) ), 0, 5 );
$learning_ids['checklist'] = wtcf_seed_learning( 'checklist', '公開前の確認マニュアル', '見出し・リンク・操作を、読み手の立場でひとつずつ確認します。', 'lesson', array(
	array( 'title' => '内容を確かめる', 'text' => 'タイトルが内容を表しているか、説明と根拠が対応しているか、古い情報が残っていないかを確認します。' ),
	array( 'title' => '操作を確かめる', 'text' => 'キーボードだけで操作し、検索やリンクの移動先を確認します。画面幅を変えて、横にはみ出す内容がないかも調べます。' ) ), 0, 6 );
$learning_ids['draft'] = wtcf_seed_learning( 'unpublished-lesson', '未公開レッスンの内部確認語', '公開検索に出してはいけない下書き。', 'lesson', array(), 0, 7, 'draft' );
update_option( 'wtcf_learning_ids', $learning_ids, false );
flush_rewrite_rules();
