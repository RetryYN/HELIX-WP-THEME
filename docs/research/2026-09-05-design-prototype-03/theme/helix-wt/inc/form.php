<?php
/**
 * 段 11（2026-09-07 PO 反応 21 回目 WT-EVT-0289「フォームの項目追加とかの項目調査」、24 回目 WT-EVT-0301「進めて」）: フォーム面。
 * 台帳 sidebar-forms-gap-survey §2（フォーム本体 n=31 / 取得 n=42）の観察由来の軸・項目を全部持つ（WT-EVT-0288「最大数を取りにいく」）。
 * 観察トークン → 項目キーの対応表は wt_form_field_defs() の 'obs'（verify formFace の網羅性検査が台帳 observations.json から独立に照合する）。
 * PoC の範囲: 表示・入力検証・確認 → 完了 / 失敗の遷移まで。送信内容は保存も送信もしない（メール・外部サービス・DB 書き込みなし、
 * 添付は name を持たないのでブラウザが送らない = multipart にしない。WT-EVT-0300 の境界「業務処理はテーマ外」）。
 *
 * 遷移（サーバ側、JS 無効でも同じ）: 入力（GET）→ POST step=input → 検証 NG なら入力へ戻す（エラー表示、最初の不備へ autofocus）/ OK なら
 *   form_confirm=yes|inline-review → 確認画面（同 URL、値は hidden）→ POST step=confirm → 完了（form_thanks=separate は /thanks/ へ redirect、inline は同 URL に完了表示）
 *   form_confirm=no → そのまま完了。
 * JS あり: 送信前に同じ規則でクライアント検証（エラーの出し方は form_error 軸と同じ）、steps は段階送り、inline-review は同一ページで見直し。
 */

// ---------- 種別 / 項目 ----------
function wt_form_kinds() {
	// 台帳 form_kind（取得 n=42）: contact 43% / download 19% / reservation 12% / recruit 10% / newsletter 5% / quote 5% / trial 5% / other:diagnosis 2%。apply は語彙にあるが本体観察 0（イベント申込は段 5 で既存。Claude 案で追加）
	// fields = 種別ごとの観察（§2「フォーム種別ごとの項目」）の上位。同意（consent / privacy-link）と captcha は form_consent / form_captcha 軸で決まるのでここには書かない
	return array(
		'contact'     => array( 'label' => 'お問い合わせ', 'fields' => array( 'name', 'name-kana', 'company', 'email', 'tel', 'subject-select', 'message' ), 'submit' => '送信する', 'lead' => 'ご質問・ご相談は下記フォームからお送りください。2 営業日以内にご返信します。' ),
		'apply'       => array( 'label' => '参加申込', 'fields' => array( 'name', 'email', 'tel', 'subject-radio', 'people-count', 'message' ), 'submit' => '申し込む', 'lead' => 'セミナー・説明会への参加申込フォームです。' ),
		'download'    => array( 'label' => '資料ダウンロード', 'fields' => array( 'name', 'company', 'email', 'tel', 'postal', 'address', 'faculty' ), 'submit' => '資料をダウンロード', 'lead' => '必要事項を入力すると、サービス資料（PDF）をダウンロードできます。' ),
		'reservation' => array( 'label' => 'ご予約', 'fields' => array( 'name', 'name-kana', 'email', 'tel', 'date-pref', 'time-pref', 'people-count', 'birthdate', 'gender', 'grade', 'school-name', 'message' ), 'submit' => '予約を申し込む', 'lead' => '第 1〜3 希望の日時を選んでください。確定はメールでお知らせします。' ),
		'newsletter'  => array( 'label' => 'メールマガジン登録', 'fields' => array( 'email' ), 'submit' => '登録する', 'lead' => '新着記事とセミナー情報を月 2 回お届けします。' ),
		'recruit'     => array( 'label' => '採用エントリー', 'fields' => array( 'name', 'name-kana', 'email', 'tel', 'postal', 'address', 'subject-select', 'date-pref', 'message', 'attachment' ), 'submit' => 'エントリーする', 'lead' => '希望職種と面談の希望日を入力してください。' ),
		'quote'       => array( 'label' => 'お見積り依頼', 'fields' => array( 'company', 'department', 'position', 'name', 'email', 'tel', 'industry-select', 'business-model', 'budget-select', 'message', 'attachment' ), 'submit' => '見積りを依頼する', 'lead' => '概算のお見積りを 3 営業日以内にお送りします。' ),
		'trial'       => array( 'label' => '無料トライアル', 'fields' => array( 'name', 'email', 'tel', 'company', 'relationship', 'grade', 'how-found' ), 'submit' => '無料で試す', 'lead' => '14 日間、全機能を無料でお試しいただけます。' ),
		'diagnosis'   => array( 'label' => '無料診断', 'fields' => array( 'company', 'name', 'email', 'tel', 'industry-select', 'subject-radio', 'eligibility-questions' ), 'submit' => '診断を申し込む', 'lead' => '3 つの質問に答えると、現状の課題を診断してご連絡します。' ),
	);
}

function wt_form_field_defs() {
	// type: text / kana / email / tel / postal / url / textarea / select / radio / date / month / date3 / number / file / checkbox / checks / yesno / hidden / privacy / captcha
	// obs: 台帳 §2 fields の観察トークン（other:* を含む）。語彙にあるが観察 0 のもの（url / newsletter-optin / budget-select / honeypot）は obs を持たない
	return array(
		'name'            => array( 'label' => 'お名前', 'type' => 'text', 'req' => true, 'ph' => '山田 太郎', 'ac' => 'name', 'obs' => 'name' ),
		'name-kana'       => array( 'label' => 'ふりがな', 'type' => 'kana', 'req' => true, 'ph' => 'やまだ たろう', 'obs' => 'name-kana' ),
		'company'         => array( 'label' => '会社名', 'type' => 'text', 'req' => false, 'ph' => '株式会社サンプル', 'ac' => 'organization', 'obs' => 'company' ),
		'company-kana'    => array( 'label' => '会社名（ふりがな）', 'type' => 'kana', 'req' => false, 'ph' => 'かぶしきがいしゃさんぷる', 'obs' => 'other:company-kana' ),
		'department'      => array( 'label' => '部署名', 'type' => 'text', 'req' => false, 'ph' => 'マーケティング部', 'obs' => 'department' ),
		'position'        => array( 'label' => '役職', 'type' => 'text', 'req' => false, 'ph' => '部長', 'obs' => 'position' ),
		'email'           => array( 'label' => 'メールアドレス', 'type' => 'email', 'req' => true, 'ph' => 'name@example.com', 'ac' => 'email', 'obs' => 'email' ),
		'email-confirm'   => array( 'label' => 'メールアドレス（確認）', 'type' => 'email', 'req' => true, 'ph' => '同じアドレスをもう一度', 'obs' => 'email-confirm' ),
		'tel'             => array( 'label' => '電話番号', 'type' => 'tel', 'req' => false, 'ph' => '03-1234-5678', 'ac' => 'tel', 'obs' => 'tel' ),
		'fax'             => array( 'label' => 'FAX 番号', 'type' => 'tel', 'req' => false, 'ph' => '03-1234-5679', 'obs' => 'other:fax' ),
		'postal'          => array( 'label' => '郵便番号', 'type' => 'postal', 'req' => false, 'ph' => '100-0001', 'ac' => 'postal-code', 'obs' => 'postal' ),
		'address'         => array( 'label' => '住所', 'type' => 'text', 'req' => false, 'ph' => '東京都千代田区…', 'ac' => 'street-address', 'obs' => 'address' ),
		'url'             => array( 'label' => 'Web サイト', 'type' => 'url', 'req' => false, 'ph' => 'https://', 'ac' => 'url' ),
		'subject-select'  => array( 'label' => 'お問い合わせ種別', 'type' => 'select', 'req' => true, 'options' => array( '', 'サービスについて', '料金について', '導入の相談', '取材・掲載', 'その他' ), 'obs' => 'subject-select' ),
		'subject-radio'   => array( 'label' => '参加形式', 'type' => 'radio', 'req' => true, 'options' => array( '会場参加', 'オンライン参加', 'アーカイブ視聴' ), 'obs' => 'subject-radio' ),
		'subject-free'    => array( 'label' => '件名', 'type' => 'text', 'req' => false, 'ph' => '件名を入力', 'obs' => 'other:subject-free' ),
		'message'         => array( 'label' => 'お問い合わせ内容', 'type' => 'textarea', 'req' => true, 'ph' => 'できるだけ具体的にお書きください', 'obs' => 'message' ),
		'attachment'      => array( 'label' => '添付ファイル', 'type' => 'file', 'req' => false, 'note' => 'PDF / 画像、10MB まで（PoC ではファイルを送りません）', 'obs' => 'attachment' ),
		'date-pref'       => array( 'label' => '希望日', 'type' => 'date3', 'req' => true, 'obs' => 'date-pref' ),
		'time-pref'       => array( 'label' => '希望時間帯', 'type' => 'select', 'req' => true, 'options' => array( '', '午前（10:00〜12:00）', '午後（13:00〜15:00）', '夕方（15:00〜18:00）' ), 'obs' => 'time-pref' ),
		'people-count'    => array( 'label' => '人数', 'type' => 'number', 'req' => true, 'ph' => '1', 'unit' => '名', 'obs' => 'people-count' ),
		'industry-select' => array( 'label' => '業種', 'type' => 'select', 'req' => true, 'options' => array( '', '製造', '小売・EC', 'IT・通信', '医療・福祉', '教育', 'その他' ), 'obs' => 'other:industry' ),
		'business-model'  => array( 'label' => '取引形態', 'type' => 'select', 'req' => false, 'options' => array( '', '法人向け', '個人向け', '両方' ), 'obs' => 'other:business-model' ),
		'employee-count'  => array( 'label' => '従業員数', 'type' => 'select', 'req' => false, 'options' => array( '', '1〜10 名', '11〜50 名', '51〜300 名', '301 名〜' ), 'obs' => 'other:employee-count' ),
		'revenue-select'  => array( 'label' => '年商', 'type' => 'select', 'req' => false, 'options' => array( '', '〜1 億円', '1〜10 億円', '10〜100 億円', '100 億円〜', '非公開' ), 'obs' => 'other:revenue' ),
		'founded-date'    => array( 'label' => '設立年月', 'type' => 'month', 'req' => false, 'obs' => 'other:founded-date' ),
		'budget-select'   => array( 'label' => 'ご予算', 'type' => 'select', 'req' => false, 'options' => array( '', '〜10 万円', '10〜50 万円', '50〜100 万円', '100 万円〜', '未定' ) ),
		'product-select'  => array( 'label' => '対象製品', 'type' => 'select', 'req' => false, 'options' => array( '', '製品 A', '製品 B', '製品 C', '未定' ), 'obs' => 'other:product' ),
		'order-number'    => array( 'label' => '注文番号', 'type' => 'text', 'req' => false, 'ph' => 'PoC-000000', 'obs' => 'other:order-number' ),
		'frequency'       => array( 'label' => '利用頻度', 'type' => 'select', 'req' => false, 'options' => array( '', '毎日', '週に数回', '月に数回', '初めて' ), 'obs' => 'other:frequency' ),
		'how-found'       => array( 'label' => '当サイトを知ったきっかけ', 'type' => 'select', 'req' => false, 'options' => array( '', '検索', 'SNS', '紹介', '広告', 'その他' ), 'obs' => 'how-found' ),
		'gender'          => array( 'label' => '性別', 'type' => 'radio', 'req' => false, 'options' => array( '男性', '女性', '回答しない' ), 'obs' => 'other:gender' ),
		'birthdate'       => array( 'label' => '生年月日', 'type' => 'date', 'req' => false, 'ac' => 'bday', 'obs' => 'other:birthdate' ),
		'occupation'      => array( 'label' => 'ご職業', 'type' => 'select', 'req' => false, 'options' => array( '', '会社員', '自営業', '学生', '主婦・主夫', 'その他' ), 'obs' => 'other:occupation' ),
		'relationship'    => array( 'label' => 'お申込者', 'type' => 'select', 'req' => false, 'options' => array( '', 'ご本人', '保護者', 'その他' ), 'obs' => 'other:relationship' ),
		'guardian'        => array( 'label' => '保護者のお名前', 'type' => 'text', 'req' => false, 'ph' => '山田 花子', 'obs' => 'other:guardian' ),
		'grade'           => array( 'label' => '学年', 'type' => 'select', 'req' => false, 'options' => array( '', '小学生', '中学生', '高校生', '大学生', '社会人' ), 'obs' => 'other:grade' ),
		'school-type'     => array( 'label' => '学校種別', 'type' => 'select', 'req' => false, 'options' => array( '', '公立', '私立', '国立', 'その他' ), 'obs' => 'other:school-type' ),
		'school-name'     => array( 'label' => '学校名', 'type' => 'text', 'req' => false, 'ph' => '○○高等学校', 'obs' => 'other:school-name' ),
		'faculty'         => array( 'label' => '学部・学科', 'type' => 'text', 'req' => false, 'ph' => '経済学部', 'obs' => 'other:faculty' ),
		'classroom'       => array( 'label' => '希望教室', 'type' => 'select', 'req' => false, 'options' => array( '', '本校', '駅前校', 'オンライン' ), 'obs' => 'other:classroom' ),
		'teacher-name'    => array( 'label' => '担当講師名', 'type' => 'text', 'req' => false, 'ph' => '分かれば', 'obs' => 'other:teacher-name' ),
		'format-radio'    => array( 'label' => '希望形式', 'type' => 'radio', 'req' => false, 'options' => array( '対面', 'オンライン', 'どちらでも' ), 'obs' => 'other:format' ),
		'option-questions' => array( 'label' => 'ご興味のある内容（複数可）', 'type' => 'checks', 'req' => false, 'options' => array( '料金', '導入事例', 'サポート', '他社比較' ), 'obs' => 'other:option-questions' ),
		'eligibility-questions' => array( 'label' => '現状の確認', 'type' => 'yesno', 'req' => true, 'questions' => array( 'Web からの問い合わせは月 10 件以上ありますか', '広告を出稿していますか', '効果測定の担当者はいますか' ), 'obs' => 'other:eligibility-questions' ),
		'newsletter-optin' => array( 'label' => 'メールマガジンを受け取る', 'type' => 'checkbox', 'req' => false ),
		'hidden-tracking' => array( 'label' => '流入元（非表示）', 'type' => 'hidden', 'req' => false, 'value' => 'poc-sample', 'obs' => 'other:hidden-tracking' ), // 表示モック。実在の追跡 ID は使わない
		'consent'         => array( 'label' => '個人情報の取り扱いに同意する', 'type' => 'checkbox', 'req' => true, 'obs' => 'consent-checkbox' ),
		'privacy-link'    => array( 'label' => '個人情報の取り扱い', 'type' => 'privacy', 'req' => false, 'obs' => 'privacy-link' ),
		'captcha'         => array( 'label' => 'スパム防止のための質問', 'type' => 'captcha', 'req' => true, 'obs' => 'captcha' ),
	);
}

// 段 13（WT-EVT-0303「OK」= 置き換えず選択肢を足す）: フォームの種別は置いた面の文脈で決まる。イベント面の event_apply:block-form は apply、LP の lp_form:block と固定ページは form_kind（?wt= で切替）
function wt_form_kind() {
	if ( function_exists( 'wt_is_event_page' ) && wt_is_event_page() && 'block-form' === wt_opt( 'event_apply' ) ) { return 'apply'; }
	return wt_opt( 'form_kind' );
}

function wt_form_fields( $kind = null, $set = null ) {
	$kind = $kind ?: wt_form_kind();
	$set  = $set ?: wt_opt( 'form_fields' );
	$kinds = wt_form_kinds(); $defs = wt_form_field_defs();
	$special = array( 'consent', 'privacy-link', 'captcha' );
	$sets  = array(
		'minimal'  => array( 'name', 'email', 'message' ),
		'standard' => array( 'name', 'company', 'email', 'tel', 'subject-select', 'message' ),
		'full'     => array_values( array_filter( array_keys( $defs ), fn( $f ) => ! in_array( $f, $special, true ) ) ), // 定義した項目を全部（観察トークン全部を選べる）
	);
	$fields = 'by-kind' === $set ? $kinds[ $kind ]['fields'] : $sets[ $set ];
	$fields = array_values( array_filter( $fields, fn( $f ) => ! in_array( $f, $special, true ) ) );
	// 同意の出し方（form_consent）: checkbox = チェック / link-only = リンク文だけ / in-submit = 送信ボタン文言に含める（リンク文も出す）
	if ( 'question' === wt_opt( 'form_captcha' ) ) { $fields[] = 'captcha'; }
	$fields[] = 'checkbox' === wt_opt( 'form_consent' ) ? 'consent' : 'privacy-link';
	return $fields;
}

function wt_form_submit_text( $kind = null ) {
	$kind = $kind ?: wt_form_kind();
	$v    = wt_opt( 'form_submit' );
	// 台帳 submit_text（n=15、分散）: 送信する 2 / 確認する 2 / 同意して、入力内容を確認する 2 / 個人情報の取り扱いに同意して送信する 2 / 送信 1 / 確認画面へ 1 / 次へ進む 1 / ダウンロード 1 …
	$map  = array( 'send' => '送信する', 'send-plain' => '送信', 'confirm' => '確認画面へ', 'check' => '確認する', 'apply' => '申し込む', 'register' => '登録する', 'download' => 'ダウンロード', 'next' => '次へ進む' );
	if ( 'auto' === $v ) { $t = 'no' === wt_opt( 'form_confirm' ) ? wt_form_kinds()[ $kind ]['submit'] : '入力内容を確認する'; } else { $t = $map[ $v ]; } // auto の「入力内容を確認する」は台帳の最多文言ではない Claude 暫定（確認あり 90% に合わせた文言）
	if ( 'in-submit' === wt_opt( 'form_consent' ) ) { $t = '個人情報の取り扱いに同意して' . $t; }
	return $t;
}

function wt_form_focus_id( $f ) { // エラーから移動する先（実在する入力の id）
	$t = wt_form_field_defs()[ $f ]['type'];
	if ( 'date3' === $t ) { return 'wt-f-' . $f . '-1'; }
	if ( 'radio' === $t || 'checks' === $t ) { return 'wt-f-' . $f . '-0'; }
	if ( 'yesno' === $t ) { return 'wt-f-' . $f . '-0-y'; }
	return 'wt-f-' . $f;
}

// ---------- 状態（POST の処理。template_redirect で 1 度だけ） ----------
function wt_form_state() {
	static $state = null;
	if ( null !== $state ) { return $state; }
	$state = array( 'step' => 'input', 'values' => array(), 'errors' => array() );
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['wt_form'] ) ) { return $state; }
	$vals = array();
	foreach ( (array) $_POST['wt_form'] as $k => $v ) { $k = sanitize_key( $k ); $vals[ $k ] = is_array( $v ) ? array_map( fn( $x ) => is_array( $x ) ? '' : sanitize_text_field( wp_unslash( $x ) ), $v ) : sanitize_textarea_field( wp_unslash( $v ) ); }
	$state['values'] = $vals;
	$step = $_POST['wt_step'] ?? 'input';
	if ( ! is_string( $step ) || ! in_array( wp_unslash( $step ), array( 'input', 'back', 'confirm' ), true ) ) {
		$state['errors']['_form'] = 'フォームの操作を確認できませんでした。入力内容を確認して、もう一度進んでください。';
		return $state;
	}
	$step = wp_unslash( $step );
	if ( ! isset( $_POST['wt_form_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wt_form_nonce'] ) ), 'wt_form' ) ) { $state['errors']['_form'] = 'フォームの有効期限が切れました。もう一度送信してください。'; return $state; }
	if ( ! empty( $_POST['wt_hp'] ) ) { $state['errors']['_form'] = '送信を受け付けられませんでした。'; return $state; } // honeypot
	$errors = wt_form_validate( $vals );
	if ( $errors ) { $state['errors'] = $errors; $state['step'] = 'input'; return $state; }
	if ( 'back' === $step ) { $state['step'] = 'input'; return $state; }
	$needs_confirm = 'no' !== wt_opt( 'form_confirm' );
	if ( 'input' === $step && $needs_confirm ) { $state['step'] = 'confirm'; return $state; }
	$state['step'] = 'done';
	return $state;
}

function wt_form_validate( $vals ) {
	$errors = array(); $defs = wt_form_field_defs();
	foreach ( wt_form_fields() as $f ) {
		$d = $defs[ $f ]; $key = str_replace( '-', '_', $f ); $v = $vals[ $key ] ?? '';
		if ( in_array( $d['type'], array( 'privacy', 'file', 'hidden' ), true ) ) { continue; }
		$multi = in_array( $d['type'], array( 'date3', 'yesno', 'checks' ), true );
		if ( '' !== $v && is_array( $v ) !== $multi ) { $errors[ $f ] = $d['label'] . 'の形式が正しくありません。'; continue; } // 入力の形（配列 / 単値）が違う異常 POST は通常のエラーへ
		if ( 'checkbox' === $d['type'] ) { if ( $d['req'] && ! $v ) { $errors[ $f ] = '同意が必要です。'; } continue; }
		if ( 'yesno' === $d['type'] ) { $ok = ! is_array( $v ) || ! array_diff( array_keys( $v ), array_keys( $d['questions'] ) ); foreach ( array_keys( $d['questions'] ) as $qi ) { if ( ! in_array( $v[ $qi ] ?? '', array( 'はい', 'いいえ' ), true ) ) { $ok = false; } } if ( ! $ok && ( $d['req'] || count( array_filter( (array) $v, fn( $x ) => '' !== $x ) ) ) ) { $errors[ $f ] = count( $d['questions'] ) . ' つの質問すべてに答えてください。'; } continue; } // 質問キー集合は 0..n-1 と一致（余分なキーも通さない）、値は はい / いいえ
		if ( 'checks' === $d['type'] ) { foreach ( (array) $v as $x ) { if ( '' !== $x && ! in_array( $x, $d['options'], true ) ) { $errors[ $f ] = $d['label'] . 'の選択肢にありません。'; } } continue; }
		if ( 'date3' === $d['type'] ) { if ( $d['req'] && '' === trim( (string) ( is_array( $v ) ? ( $v[0] ?? '' ) : '' ) ) ) { $errors[ $f ] = '第 1 希望日を入力してください。'; } continue; } // JS と同じ: 第 1 希望が必須
		$v = trim( (string) $v ); $empty = '' === $v;
		if ( $d['req'] && $empty ) { $errors[ $f ] = $d['label'] . ( in_array( $d['type'], array( 'select', 'radio' ), true ) ? 'を選択してください。' : 'を入力してください。' ); continue; }
		if ( $empty ) { continue; }
		if ( 'email' === $d['type'] && ! is_email( $v ) ) { $errors[ $f ] = 'メールアドレスの形式が正しくありません。'; }
		if ( 'email-confirm' === $f && ( $vals['email'] ?? '' ) !== $v ) { $errors[ $f ] = 'メールアドレスが一致しません。'; }
		if ( 'tel' === $d['type'] && ! preg_match( '/^[0-9０-９+\-() ]{8,20}$/u', $v ) ) { $errors[ $f ] = $d['label'] . 'の形式が正しくありません。'; }
		if ( 'postal' === $d['type'] && ! preg_match( '/^\d{3}-?\d{4}$/', $v ) ) { $errors[ $f ] = '郵便番号は 7 桁で入力してください。'; }
		if ( 'kana' === $d['type'] && ! preg_match( '/^[ぁ-ゖー\s　]+$/u', $v ) ) { $errors[ $f ] = 'ひらがなで入力してください。'; }
		if ( 'number' === $d['type'] && ( ! is_numeric( $v ) || (int) $v < 1 ) ) { $errors[ $f ] = '1 以上の数を入力してください。'; }
		if ( 'url' === $d['type'] && ! preg_match( '#^https?://#', $v ) ) { $errors[ $f ] = 'https:// から始まる URL を入力してください。'; }
		if ( 'date' === $d['type'] && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ) { $errors[ $f ] = '日付の形式が正しくありません。'; }
		if ( 'month' === $d['type'] && ! preg_match( '/^\d{4}-\d{2}$/', $v ) ) { $errors[ $f ] = '年月の形式が正しくありません。'; }
		if ( in_array( $d['type'], array( 'select', 'radio' ), true ) && ! in_array( $v, $d['options'], true ) ) { $errors[ $f ] = $d['label'] . 'の選択肢にありません。'; }
		if ( 'captcha' === $d['type'] && '7' !== $v ) { $errors[ $f ] = '答えが違います。'; }
	}
	return $errors;
}

// 完了 → 別ページ（form_thanks=separate）の redirect は出力前に
add_action( 'template_redirect', function () {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['wt_form'] ) ) { return; }
	$st = wt_form_state();
	if ( 'done' === $st['step'] && 'separate' === wt_opt( 'form_thanks' ) ) {
		$wt = isset( $_GET['wt'] ) ? sanitize_text_field( wp_unslash( $_GET['wt'] ) ) : '';
		if ( wt_form_kind() !== wt_opt( 'form_kind' ) ) { $wt = ( $wt ? $wt . ',' : '' ) . 'form_kind:' . wt_form_kind(); } // 段 13: 面の文脈で決めた種別（イベントの apply）を /thanks/ へ引き継ぐ
		$q = '' !== $wt ? '?wt=' . rawurlencode( $wt ) : '';
		wp_safe_redirect( home_url( '/thanks/' . $q ) ); exit;
	}
} );

// ---------- 描画 ----------
function wt_form_mark() {
	return 'label' === wt_opt( 'form_required' ) ? '<span class="wt-form__req wt-form__req--label">必須</span>' : '<span class="wt-form__req wt-form__req--asterisk" aria-hidden="true">*</span><span class="screen-reader-text">（必須）</span>';
}

function wt_form_control( $f, $val, $err ) {
	$d = wt_form_field_defs()[ $f ]; $id = 'wt-f-' . $f; $name = 'wt_form[' . str_replace( '-', '_', $f ) . ']';
	$ph  = 'placeholder-only' === wt_opt( 'form_layout' ) ? ( $d['ph'] ?? $d['label'] ) : ( $d['ph'] ?? '' );
	$req = $d['req'] ? ' aria-required="true"' : '';
	$summary = in_array( wt_opt( 'form_error' ), array( 'top-summary', 'both' ), true );
	$desc = $err ? ' aria-invalid="true" aria-describedby="' . ( $summary ? 'wt-form-summary' : $id . '-err' ) . '"' : ''; // 説明の参照先は実在する要素（項目下 or 上部まとめ）
	static $focused = false; // JS 無効でサーバから戻ったとき、最初の不備の項目へフォーカス（top-summary / both のときはまとめ側）
	if ( $err && ! $focused && ! $summary ) { $desc .= ' autofocus'; $focused = true; }
	$ac  = isset( $d['ac'] ) ? ' autocomplete="' . $d['ac'] . '"' : '';
	$v   = is_array( $val ) ? '' : $val;
	switch ( $d['type'] ) {
		case 'textarea': return '<textarea id="' . $id . '" name="' . $name . '" rows="6" placeholder="' . esc_attr( $ph ) . '"' . $req . $desc . '>' . esc_textarea( $v ) . '</textarea>';
		case 'select': $o = ''; foreach ( $d['options'] as $opt ) { $o .= '<option value="' . esc_attr( $opt ) . '"' . selected( $v, $opt, false ) . '>' . ( '' === $opt ? '選択してください' : esc_html( $opt ) ) . '</option>'; } return '<select id="' . $id . '" name="' . $name . '"' . $req . $desc . '>' . $o . '</select>';
		case 'radio': $o = ''; foreach ( $d['options'] as $i => $opt ) { $rid = $id . '-' . $i; $o .= '<label class="wt-form__radio" for="' . $rid . '"><input type="radio" id="' . $rid . '" name="' . $name . '" value="' . esc_attr( $opt ) . '"' . checked( $v, $opt, false ) . ( 0 === $i ? $req . $desc : '' ) . '> ' . esc_html( $opt ) . '</label>'; } return '<div class="wt-form__radios" role="radiogroup" aria-labelledby="' . $id . '-lb">' . $o . '</div>';
		case 'checks': $o = ''; $vals = is_array( $val ) ? $val : array(); foreach ( $d['options'] as $i => $opt ) { $rid = $id . '-' . $i; $o .= '<label class="wt-form__check" for="' . $rid . '"><input type="checkbox" id="' . $rid . '" name="' . $name . '[]" value="' . esc_attr( $opt ) . '"' . ( in_array( $opt, $vals, true ) ? ' checked' : '' ) . ( 0 === $i ? $desc : '' ) . '> ' . esc_html( $opt ) . '</label>'; } return '<div class="wt-form__radios" role="group" aria-labelledby="' . $id . '-lb">' . $o . '</div>';
		case 'date3': $o = ''; $vals = is_array( $val ) ? $val : array(); for ( $i = 1; $i <= 3; $i++ ) { $o .= '<label class="wt-form__date" for="' . $id . '-' . $i . '"><span>第 ' . $i . ' 希望</span><input type="date" id="' . $id . '-' . $i . '" name="' . $name . '[]" value="' . esc_attr( $vals[ $i - 1 ] ?? '' ) . '"' . ( 1 === $i ? $req . $desc : '' ) . '></label>'; } return '<div class="wt-form__dates">' . $o . '</div>';
		case 'date': case 'month': return '<input type="' . $d['type'] . '" id="' . $id . '" name="' . $name . '" value="' . esc_attr( $v ) . '"' . $ac . $req . $desc . '>';
		case 'number': return '<span class="wt-form__unit"><input type="number" id="' . $id . '" name="' . $name . '" min="1" value="' . esc_attr( $v ) . '" placeholder="' . esc_attr( $ph ) . '"' . $req . $desc . '><span>' . esc_html( $d['unit'] ) . '</span></span>';
		case 'file': return '<input type="file" id="' . $id . '" data-wt-poc="no-upload"' . $desc . '><small class="wt-form__note">' . esc_html( $d['note'] ) . '</small>'; // name を持たない = ブラウザは送らない（PoC の非送信契約）
		case 'hidden': return '<input type="hidden" id="' . $id . '" name="' . $name . '" value="' . esc_attr( $d['value'] ) . '"><small class="wt-form__note">非表示の項目（流入元など。PoC は固定値の表示モック）</small>';
		case 'checkbox': return '<label class="wt-form__check" for="' . $id . '"><input type="checkbox" id="' . $id . '" name="' . $name . '" value="1"' . checked( (string) $v, '1', false ) . $req . $desc . '> ' . esc_html( $d['label'] ) . ( 'consent' === $f ? '<span class="wt-form__check-link">（<a href="/privacy/" target="_blank" rel="noopener">個人情報の取り扱い</a>）</span>' : '' ) . '</label>';
		case 'privacy': return '<p class="wt-form__privacy" id="' . $id . '">送信いただいた内容は <a href="/privacy/" target="_blank" rel="noopener">個人情報の取り扱い</a> に沿って利用します。送信をもって同意したものとします。</p>';
		case 'yesno': $o = ''; $vals = is_array( $val ) ? $val : array(); foreach ( $d['questions'] as $i => $q ) { $o .= '<div class="wt-form__yesno" role="radiogroup" aria-labelledby="' . $id . '-q' . $i . '"><span id="' . $id . '-q' . $i . '">' . esc_html( $q ) . '</span><label for="' . $id . '-' . $i . '-y"><input type="radio" id="' . $id . '-' . $i . '-y" name="' . $name . '[' . $i . ']" value="はい"' . checked( $vals[ $i ] ?? '', 'はい', false ) . $req . ( 0 === $i ? $desc : '' ) . '> はい</label><label for="' . $id . '-' . $i . '-n"><input type="radio" id="' . $id . '-' . $i . '-n" name="' . $name . '[' . $i . ']" value="いいえ"' . checked( $vals[ $i ] ?? '', 'いいえ', false ) . '> いいえ</label></div>'; } return '<div class="wt-form__yesnos">' . $o . '</div>';
		case 'captcha': return '<span class="wt-form__captcha-q" id="' . $id . '-q">3 + 4 = ?</span><input type="text" id="' . $id . '" name="' . $name . '" inputmode="numeric" value="' . esc_attr( $v ) . '" aria-labelledby="' . $id . '-lb ' . $id . '-q"' . $req . $desc . '>';
		default:
			$type = in_array( $d['type'], array( 'email', 'tel', 'url' ), true ) ? $d['type'] : 'text';
			$im = 'postal' === $d['type'] ? ' inputmode="numeric"' : ''; return '<input type="' . $type . '" id="' . $id . '" name="' . $name . '" value="' . esc_attr( $v ) . '" placeholder="' . esc_attr( $ph ) . '"' . $ac . $im . $req . $desc . '>';
	}
}

function wt_form_step_of( $f ) { // steps レイアウトの段: 1 = 基本情報、2 = 内容、3 = 確認・同意
	if ( in_array( $f, array( 'consent', 'privacy-link', 'captcha', 'newsletter-optin' ), true ) ) { return 3; }
	return in_array( $f, array( 'name', 'name-kana', 'company', 'company-kana', 'department', 'position', 'email', 'email-confirm', 'tel', 'fax', 'postal', 'address', 'url', 'gender', 'birthdate', 'occupation', 'relationship', 'guardian', 'grade', 'school-type', 'school-name', 'faculty', 'employee-count', 'revenue-select', 'founded-date', 'business-model', 'hidden-tracking' ), true ) ? 1 : 2;
}

function wt_render_form( $attrs = array() ) {
	if ( wt_is_lp_page() && 'block' !== wt_opt( 'lp_form' ) ) { return ''; } // 段 13: LP / イベントでは軸で選んだときだけ描く（隠しフォームを DOM に残さない）
	if ( wt_is_event_page() && 'block-form' !== wt_opt( 'event_apply' ) ) { return ''; }
	$kind = wt_form_kind(); $k = wt_form_kinds()[ $kind ]; $st = wt_form_state(); $defs = wt_form_field_defs(); $fields = wt_form_fields();
	$layout = wt_opt( 'form_layout' ); $errmode = wt_opt( 'form_error' ); $side = wt_opt( 'form_side' );
	$cls = 'wt-form wt-form--' . esc_attr( $kind ) . ' wt-form--layout-' . esc_attr( $layout ) . ' wt-form--side-' . esc_attr( $side );
	$action = esc_url( add_query_arg( array() ) ); // 同じ URL（?wt= を保つ）
	$o = '<div class="' . $cls . '" data-wt-form="' . esc_attr( $kind ) . '" data-wt-step="' . esc_attr( $st['step'] ) . '">';
	$o .= '<div class="wt-form__main">';
	if ( 'done' === $st['step'] ) { // inline の完了（separate は template_redirect で /thanks/ へ）
		$o .= wt_form_thanks_markup( $kind, true );
	} elseif ( 'confirm' === $st['step'] ) {
		$o .= '<h2 class="wt-form__title">入力内容の確認</h2><p class="wt-form__lead">内容をご確認のうえ「' . esc_html( $k['submit'] ) . '」を押してください。</p>';
		$o .= '<form class="wt-form__confirm" method="post" action="' . $action . '" novalidate>' . wp_nonce_field( 'wt_form', 'wt_form_nonce', true, false );
		$o .= '<dl class="wt-form__review">';
		foreach ( $fields as $f ) { $d = $defs[ $f ]; if ( in_array( $d['type'], array( 'privacy', 'captcha', 'hidden' ), true ) ) { if ( 'hidden' === $d['type'] ) { $o .= '<input type="hidden" name="wt_form[' . str_replace( '-', '_', $f ) . ']" value="' . esc_attr( $d['value'] ) . '">'; } continue; } $key = str_replace( '-', '_', $f ); $v = $st['values'][ $key ] ?? '';
			$disp = is_array( $v ) ? implode( ' / ', array_filter( $v, fn( $x ) => '' !== $x ) ) : ( 'checkbox' === $d['type'] ? ( $v ? '同意する' : '—' ) : ( 'file' === $d['type'] ? '（PoC ではファイルを送りません）' : $v ) );
			$o .= '<div><dt>' . esc_html( $d['label'] ) . '</dt><dd data-wt-field="' . esc_attr( $f ) . '">' . ( '' === $disp ? '—' : nl2br( esc_html( $disp ) ) ) . '</dd></div>';
			if ( is_array( $v ) ) { foreach ( $v as $i => $x ) { $o .= '<input type="hidden" name="wt_form[' . $key . '][' . esc_attr( (string) $i ) . ']" value="' . esc_attr( $x ) . '">'; } } elseif ( 'file' !== $d['type'] ) { $o .= '<input type="hidden" name="wt_form[' . $key . ']" value="' . esc_attr( $v ) . '">'; } }
		if ( 'question' === wt_opt( 'form_captcha' ) ) { $o .= '<input type="hidden" name="wt_form[captcha]" value="7">'; }
		$o .= '</dl><div class="wt-form__actions"><button type="submit" class="wt-form__back" name="wt_step" value="back">修正する</button><button type="submit" class="wt-form__submit" name="wt_step" value="confirm">' . esc_html( $k['submit'] ) . '</button></div></form>';
	} else {
		if ( empty( $attrs['hideTitle'] ) ) { $o .= '<h2 class="wt-form__title">' . esc_html( $k['label'] ) . '</h2><p class="wt-form__lead">' . esc_html( $k['lead'] ) . '</p>'; } // 段 13: 区間に置くとき（LP / イベント）は区間の見出しがあるのでブロックの見出しを省く（hideTitle）
		if ( isset( $st['errors']['_form'] ) ) { $o .= '<div class="wt-form__summary" role="alert" tabindex="-1" autofocus><p>' . esc_html( $st['errors']['_form'] ) . '</p></div>'; }
		elseif ( $st['errors'] && in_array( $errmode, array( 'top-summary', 'both' ), true ) ) { $o .= '<div class="wt-form__summary" role="alert" id="wt-form-summary" tabindex="-1" autofocus><p>入力内容に ' . count( $st['errors'] ) . ' 件の不備があります。</p><ul>'; foreach ( $st['errors'] as $f => $m ) { $o .= '<li><a href="#' . esc_attr( wt_form_focus_id( $f ) ) . '">' . esc_html( $m ) . '</a></li>'; } $o .= '</ul></div>'; }
		$steps = 'steps' === $layout;
		$o .= '<form class="wt-form__form" method="post" action="' . $action . '" novalidate data-wt-error="' . esc_attr( $errmode ) . '" data-wt-confirm="' . esc_attr( wt_opt( 'form_confirm' ) ) . '">' . wp_nonce_field( 'wt_form', 'wt_form_nonce', true, false ) . '<input type="hidden" name="wt_step" value="input"><div class="wt-form__hp" aria-hidden="true"><label for="wt-hp">この欄は空のままにしてください</label><input type="text" id="wt-hp" name="wt_hp" tabindex="-1" autocomplete="off"></div>';
		if ( $steps ) { $by = array(); foreach ( $fields as $f ) { $by[ wt_form_step_of( $f ) ][] = $f; } ksort( $by ); $fields = array_merge( ...array_values( $by ) ); } // steps は段ごとにまとめる（種別の並びで段 1 の項目が後ろにあっても fieldset が分かれない）
		$present = array(); foreach ( $fields as $f ) { $present[ wt_form_step_of( $f ) ] = true; } ksort( $present ); $names = array( 1 => '基本情報', 2 => '内容', 3 => '確認・同意' );
		if ( $steps ) { $o .= '<ol class="wt-form__steps" aria-label="入力の段階">'; $first = true; foreach ( array_keys( $present ) as $n ) { $o .= '<li' . ( $first ? ' class="is-current"' : '' ) . ' data-wt-step-i="' . $n . '">' . $names[ $n ] . '</li>'; $first = false; } $o .= '</ol>'; } // 種別に無い段は出さない（newsletter は 2 段）
		$cur = 0;
		foreach ( $fields as $f ) { $d = $defs[ $f ]; $key = str_replace( '-', '_', $f ); $v = $st['values'][ $key ] ?? ''; $err = $st['errors'][ $f ] ?? '';
			if ( $steps && wt_form_step_of( $f ) !== $cur ) { if ( $cur ) { $o .= '</fieldset>'; } $cur = wt_form_step_of( $f ); $o .= '<fieldset class="wt-form__step" data-wt-step-i="' . $cur . '"><legend class="screen-reader-text">' . $names[ $cur ] . '</legend>'; }
			$rowcls = 'wt-form__row wt-form__row--' . esc_attr( $d['type'] ) . ( $err ? ' is-error' : '' ) . ( in_array( $d['type'], array( 'textarea', 'yesno', 'date3', 'checks' ), true ) ? ' wt-form__row--wide' : '' );
			$o .= '<div class="' . $rowcls . '" data-wt-field="' . esc_attr( $f ) . '">';
			if ( ! in_array( $d['type'], array( 'checkbox', 'privacy' ), true ) ) {
				$lbl = esc_html( $d['label'] ) . ( $d['req'] ? ' ' . wt_form_mark() : '' );
				$o .= in_array( $d['type'], array( 'radio', 'checks', 'yesno', 'date3', 'captcha' ), true ) ? '<span class="wt-form__label" id="wt-f-' . $f . '-lb">' . $lbl . '</span>' : '<label class="wt-form__label" for="wt-f-' . $f . '">' . $lbl . '</label>';
			}
			$o .= '<div class="wt-form__control">' . wt_form_control( $f, $v, $err );
			if ( $err && in_array( $errmode, array( 'inline', 'both' ), true ) ) { $o .= '<p class="wt-form__error" id="wt-f-' . $f . '-err">' . esc_html( $err ) . '</p>'; }
			$o .= '</div></div>';
		}
		if ( $steps && $cur ) { $o .= '</fieldset>'; }
		if ( 'external-slot' === wt_opt( 'form_captcha' ) ) { $o .= '<div class="wt-form__captcha-slot" aria-label="外部の認証枠（PoC では描画のみ）"><span>外部認証の枠</span></div>'; }
		$o .= '<div class="wt-form__actions">' . ( $steps ? '<button type="button" class="wt-form__back wt-form__prev" hidden>戻る</button><button type="button" class="wt-form__next" hidden>次へ進む</button>' : '' ) . '<button type="submit" class="wt-form__submit">' . esc_html( wt_form_submit_text() ) . '</button></div>'; // JS 無効では段階送りをせず全項目 + 送信ボタン（次へ / 戻る は JS が出す）
		if ( 'inline-review' === wt_opt( 'form_confirm' ) ) { $o .= '<div class="wt-form__inline-review" hidden><h3>送信前の見直し</h3><dl></dl><div class="wt-form__actions"><button type="button" class="wt-form__back wt-form__review-edit">修正する</button><button type="submit" class="wt-form__submit wt-form__review-send" name="wt_step" value="confirm">' . esc_html( $k['submit'] ) . '</button></div></div>'; }
		$o .= '</form>';
	}
	$o .= '</div>';
	// 代替導線（台帳 cta_side: tel 67% / none 26% / email 10% / chat 7% / messaging-app 5%）。電話は表示モック（発信リンクにしない。番号は架空の例）
	$sides = array(
		'tel'           => '<p class="wt-form__side-lead">お電話でもご相談いただけます</p><span class="wt-form__side-tel" data-wt-poc="no-dial"><i class="wt-i wt-i--phone" aria-hidden="true"></i>03-1234-5678</span><p class="wt-form__side-note">平日 10:00〜18:00（土日祝休）</p>',
		'email'         => '<p class="wt-form__side-lead">メールでのご連絡</p><a class="wt-form__side-link" href="mailto:info@example.com">info@example.com</a><p class="wt-form__side-note">2 営業日以内にご返信します</p>',
		'chat'          => '<p class="wt-form__side-lead">チャットで今すぐ質問</p><button type="button" class="wt-form__side-btn" aria-label="チャットを開く（PoC では開きません）"><i class="wt-i wt-i--bubble" aria-hidden="true"></i>チャットを開く</button><p class="wt-form__side-note">平日 10:00〜18:00 対応</p>',
		'messaging-app' => '<p class="wt-form__side-lead">メッセージアプリで相談</p><a class="wt-form__side-btn wt-form__side-btn--app" href="/lp/#line"><i class="wt-i wt-i--bubble" aria-hidden="true"></i>友だち追加して相談</a><p class="wt-form__side-note">返信は営業時間内</p>',
	);
	if ( isset( $sides[ $side ] ) ) { $o .= '<aside class="wt-form__side" aria-label="フォーム以外の連絡方法">' . $sides[ $side ] . '</aside>'; }
	return $o . '</div>';
}

function wt_form_thanks_markup( $kind, $inline = false ) {
	$k = wt_form_kinds()[ $kind ];
	$next = array(
		'download' => '<a class="wt-form__thanks-btn" href="#download" download>資料（PDF）をダウンロード</a>',
		'newsletter' => '<a class="wt-form__thanks-btn" href="/">最新記事を読む</a>',
		'apply' => '<a class="wt-form__thanks-btn" href="/event/">イベントページへ戻る</a>',
	);
	$o = '<div class="wt-form-thanks' . ( $inline ? ' wt-form-thanks--inline' : '' ) . '" role="status" data-wt-thanks="' . esc_attr( $kind ) . '">';
	$o .= '<i class="wt-i wt-i--check-circle wt-form-thanks__icon" aria-hidden="true"></i>';
	$o .= $inline ? '<h2 class="wt-form-thanks__title">' . esc_html( $k['label'] ) . 'を受け付けました</h2>' : '<p class="wt-form-thanks__title">' . esc_html( $k['label'] ) . 'を受け付けました</p>';
	$o .= '<p class="wt-form-thanks__lead">ご入力いただいたメールアドレスへ受付確認をお送りしました（PoC では送信していません）。届かない場合は迷惑メールフォルダをご確認ください。</p>';
	$o .= '<div class="wt-form-thanks__next">' . ( $next[ $kind ] ?? '<a class="wt-form__thanks-btn" href="/">トップページへ戻る</a>' ) . '<a class="wt-form__thanks-link" href="/category/topic-index/">記事を読んで待つ</a></div>';
	return $o . '</div>';
}

function wt_render_form_thanks( $attrs = array() ) {
	// /thanks/ 固定ページ用。種別は ?wt=form_kind で切り替え（separate の完了は redirect で ?wt= を引き継ぐ）
	return wt_form_thanks_markup( wt_opt( 'form_kind' ) );
}

add_action( 'init', function () {
	register_block_type( 'helix-wt/form', array( 'render_callback' => 'wt_render_form', 'attributes' => array( 'hideTitle' => array( 'type' => 'boolean', 'default' => false ) ) ) );
	register_block_type( 'helix-wt/form-thanks', array( 'render_callback' => 'wt_render_form_thanks' ) );
} );
