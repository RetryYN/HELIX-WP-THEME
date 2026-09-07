<?php
/**
 * 段 11（2026-09-07 PO 反応 21 回目 WT-EVT-0289「フォームの項目追加とかの項目調査」、24 回目 WT-EVT-0301「進めて」）: フォーム面。
 * 台帳 sidebar-forms-gap-survey §2（フォーム本体 n=31 / 取得 n=42）の観察由来の軸を全部持つ（WT-EVT-0288「最大数を取りにいく」）。
 * PoC の範囲: 表示・入力検証・確認 → 完了 / 失敗の遷移まで。送信内容は保存も送信もしない（メール・外部サービス・DB 書き込みなし。
 * WT-EVT-0300 の境界「業務処理はテーマ外」）。
 *
 * 遷移（サーバ側、JS 無効でも同じ）: 入力（GET）→ POST step=input → 検証 NG なら入力へ戻す（エラー表示）/ OK なら
 *   form_confirm=yes|inline-review → 確認画面（同 URL、値は hidden）→ POST step=confirm → 完了（form_thanks=separate は /thanks/ へ redirect、inline は同 URL に完了表示）
 *   form_confirm=no → そのまま完了。
 * JS あり: 送信前に同じ規則でクライアント検証（エラーの出し方は form_error 軸と同じ）、steps は段階送り、inline-review は同一ページで見直し。
 */

// ---------- 種別 / 項目 ----------
function wt_form_kinds() {
	// 台帳 form_kind（取得 n=42）: contact 43% / download 19% / reservation 12% / recruit 10% / newsletter 5% / quote 5% / trial 5% / other:diagnosis 2%。apply は語彙にあるが本体観察 0（イベント申込は段 5 で既存。Claude 案で追加）
	return array(
		'contact'     => array( 'label' => 'お問い合わせ', 'fields' => array( 'name', 'name-kana', 'company', 'email', 'tel', 'subject-select', 'message', 'consent' ), 'submit' => '送信する', 'lead' => 'ご質問・ご相談は下記フォームからお送りください。2 営業日以内にご返信します。' ),
		'apply'       => array( 'label' => '参加申込', 'fields' => array( 'name', 'email', 'tel', 'subject-radio', 'people-count', 'message', 'consent' ), 'submit' => '申し込む', 'lead' => 'セミナー・説明会への参加申込フォームです。' ),
		'download'    => array( 'label' => '資料ダウンロード', 'fields' => array( 'name', 'company', 'email', 'tel', 'postal', 'address', 'privacy-link' ), 'submit' => '資料をダウンロード', 'lead' => '必要事項を入力すると、サービス資料（PDF）をダウンロードできます。' ),
		'reservation' => array( 'label' => 'ご予約', 'fields' => array( 'name', 'name-kana', 'email', 'tel', 'date-pref', 'time-pref', 'people-count', 'message', 'consent' ), 'submit' => '予約を申し込む', 'lead' => '第 1〜3 希望の日時を選んでください。確定はメールでお知らせします。' ),
		'newsletter'  => array( 'label' => 'メールマガジン登録', 'fields' => array( 'email', 'consent' ), 'submit' => '登録する', 'lead' => '新着記事とセミナー情報を月 2 回お届けします。' ),
		'recruit'     => array( 'label' => '採用エントリー', 'fields' => array( 'name', 'name-kana', 'email', 'tel', 'postal', 'address', 'subject-select', 'date-pref', 'message', 'attachment', 'consent' ), 'submit' => 'エントリーする', 'lead' => '希望職種と面談の希望日を入力してください。' ),
		'quote'       => array( 'label' => 'お見積り依頼', 'fields' => array( 'company', 'department', 'name', 'email', 'tel', 'industry-select', 'budget-select', 'message', 'attachment', 'consent' ), 'submit' => '見積りを依頼する', 'lead' => '概算のお見積りを 3 営業日以内にお送りします。' ),
		'trial'       => array( 'label' => '無料トライアル', 'fields' => array( 'name', 'email', 'tel', 'company', 'how-found', 'consent' ), 'submit' => '無料で試す', 'lead' => '14 日間、全機能を無料でお試しいただけます。' ),
		'diagnosis'   => array( 'label' => '無料診断', 'fields' => array( 'company', 'name', 'email', 'tel', 'industry-select', 'yesno-questions', 'consent' ), 'submit' => '診断を申し込む', 'lead' => '3 つの質問に答えると、現状の課題を診断してご連絡します。' ),
	);
}

function wt_form_field_defs() {
	// type: text / kana / email / tel / postal / textarea / select / radio / date3 / time / number / file / url / checkbox / privacy / yesno / hidden
	return array(
		'name'            => array( 'label' => 'お名前', 'type' => 'text', 'req' => true, 'ph' => '山田 太郎', 'ac' => 'name' ),
		'name-kana'       => array( 'label' => 'ふりがな', 'type' => 'kana', 'req' => true, 'ph' => 'やまだ たろう' ),
		'company'         => array( 'label' => '会社名', 'type' => 'text', 'req' => false, 'ph' => '株式会社サンプル', 'ac' => 'organization' ),
		'department'      => array( 'label' => '部署名', 'type' => 'text', 'req' => false, 'ph' => 'マーケティング部' ),
		'email'           => array( 'label' => 'メールアドレス', 'type' => 'email', 'req' => true, 'ph' => 'name@example.com', 'ac' => 'email' ),
		'email-confirm'   => array( 'label' => 'メールアドレス（確認）', 'type' => 'email', 'req' => true, 'ph' => '同じアドレスをもう一度' ),
		'tel'             => array( 'label' => '電話番号', 'type' => 'tel', 'req' => false, 'ph' => '03-1234-5678', 'ac' => 'tel' ),
		'postal'          => array( 'label' => '郵便番号', 'type' => 'postal', 'req' => false, 'ph' => '100-0001', 'ac' => 'postal-code' ),
		'address'         => array( 'label' => '住所', 'type' => 'text', 'req' => false, 'ph' => '東京都千代田区…', 'ac' => 'street-address' ),
		'url'             => array( 'label' => 'Web サイト', 'type' => 'url', 'req' => false, 'ph' => 'https://', 'ac' => 'url' ),
		'subject-select'  => array( 'label' => 'お問い合わせ種別', 'type' => 'select', 'req' => true, 'options' => array( '', 'サービスについて', '料金について', '導入の相談', '取材・掲載', 'その他' ) ),
		'subject-radio'   => array( 'label' => '参加形式', 'type' => 'radio', 'req' => true, 'options' => array( '会場参加', 'オンライン参加', 'アーカイブ視聴' ) ),
		'message'         => array( 'label' => 'お問い合わせ内容', 'type' => 'textarea', 'req' => true, 'ph' => 'できるだけ具体的にお書きください' ),
		'attachment'      => array( 'label' => '添付ファイル', 'type' => 'file', 'req' => false, 'note' => 'PDF / 画像、10MB まで（PoC では送信しません）' ),
		'date-pref'       => array( 'label' => '希望日', 'type' => 'date3', 'req' => true ),
		'time-pref'       => array( 'label' => '希望時間帯', 'type' => 'select', 'req' => true, 'options' => array( '', '午前（10:00〜12:00）', '午後（13:00〜15:00）', '夕方（15:00〜18:00）' ) ),
		'people-count'    => array( 'label' => '人数', 'type' => 'number', 'req' => true, 'ph' => '1', 'unit' => '名' ),
		'industry-select' => array( 'label' => '業種', 'type' => 'select', 'req' => true, 'options' => array( '', '製造', '小売・EC', 'IT・通信', '医療・福祉', '教育', 'その他' ) ),
		'budget-select'   => array( 'label' => 'ご予算', 'type' => 'select', 'req' => false, 'options' => array( '', '〜10 万円', '10〜50 万円', '50〜100 万円', '100 万円〜', '未定' ) ),
		'how-found'       => array( 'label' => '当サイトを知ったきっかけ', 'type' => 'select', 'req' => false, 'options' => array( '', '検索', 'SNS', '紹介', '広告', 'その他' ) ),
		'newsletter-optin' => array( 'label' => 'メールマガジンを受け取る', 'type' => 'checkbox', 'req' => false ),
		'yesno-questions' => array( 'label' => '現状の確認', 'type' => 'yesno', 'req' => true, 'questions' => array( 'Web からの問い合わせは月 10 件以上ありますか', '広告を出稿していますか', '効果測定の担当者はいますか' ) ),
		'consent'         => array( 'label' => '個人情報の取り扱いに同意する', 'type' => 'checkbox', 'req' => true ),
		'privacy-link'    => array( 'label' => '個人情報の取り扱い', 'type' => 'privacy', 'req' => false ),
		'captcha'         => array( 'label' => 'スパム防止のための質問', 'type' => 'captcha', 'req' => true ),
	);
}

function wt_form_fields( $kind = null, $set = null ) {
	$kind = $kind ?: wt_opt( 'form_kind' );
	$set  = $set ?: wt_opt( 'form_fields' );
	$kinds = wt_form_kinds();
	$sets  = array(
		'minimal'  => array( 'name', 'email', 'message', 'consent' ),
		'standard' => array( 'name', 'company', 'email', 'tel', 'subject-select', 'message', 'consent' ),
		'full'     => array( 'name', 'name-kana', 'company', 'department', 'email', 'email-confirm', 'tel', 'postal', 'address', 'url', 'subject-select', 'message', 'attachment', 'how-found', 'newsletter-optin', 'consent' ),
	);
	$fields = 'by-kind' === $set ? $kinds[ $kind ]['fields'] : $sets[ $set ];
	// 同意の出し方（form_consent）: checkbox = チェック / link-only = リンク文だけ / in-submit = 送信ボタン文言に含める（リンク文も出す）
	$consent = wt_opt( 'form_consent' );
	$fields  = array_values( array_filter( $fields, fn( $f ) => ! in_array( $f, array( 'consent', 'privacy-link' ), true ) ) );
	$fields[] = 'checkbox' === $consent ? 'consent' : 'privacy-link';
	if ( 'question' === wt_opt( 'form_captcha' ) ) { array_splice( $fields, count( $fields ) - 1, 0, array( 'captcha' ) ); }
	return $fields;
}

function wt_form_submit_text( $kind = null ) {
	$kind = $kind ?: wt_opt( 'form_kind' );
	$v    = wt_opt( 'form_submit' );
	$map  = array( 'send' => '送信する', 'confirm' => '確認画面へ', 'apply' => '申し込む', 'register' => '登録する', 'download' => 'ダウンロード', 'next' => '次へ進む' );
	if ( 'auto' === $v ) { $t = 'no' === wt_opt( 'form_confirm' ) ? wt_form_kinds()[ $kind ]['submit'] : '入力内容を確認する'; } else { $t = $map[ $v ]; }
	if ( 'in-submit' === wt_opt( 'form_consent' ) ) { $t = '個人情報の取り扱いに同意して' . $t; }
	return $t;
}

// ---------- 状態（POST の処理。template_redirect で 1 度だけ） ----------
function wt_form_state() {
	static $state = null;
	if ( null !== $state ) { return $state; }
	$state = array( 'step' => 'input', 'values' => array(), 'errors' => array() );
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['wt_form'] ) ) { return $state; }
	$vals = array();
	foreach ( (array) $_POST['wt_form'] as $k => $v ) { $k = sanitize_key( $k ); $vals[ $k ] = is_array( $v ) ? array_map( 'sanitize_text_field', array_map( 'wp_unslash', $v ) ) : sanitize_textarea_field( wp_unslash( $v ) ); }
	$state['values'] = $vals;
	$step = sanitize_key( $_POST['wt_step'] ?? 'input' );
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
		$d = $defs[ $f ]; $v = $vals[ str_replace( '-', '_', $f ) ] ?? '';
		$empty = is_array( $v ) ? 0 === count( array_filter( $v, fn( $x ) => '' !== $x ) ) : '' === trim( (string) $v );
		if ( 'yesno' === $d['type'] ) { $empty = ! is_array( $v ) || count( array_filter( $v ) ) < count( $d['questions'] ); }
		if ( 'checkbox' === $d['type'] && $d['req'] && ! $v ) { $errors[ $f ] = '同意が必要です。'; continue; }
		if ( 'privacy' === $d['type'] || 'file' === $d['type'] ) { continue; }
		if ( $d['req'] && $empty ) { $errors[ $f ] = $d['label'] . 'を入力してください。'; continue; }
		if ( $empty ) { continue; }
		if ( 'email' === $d['type'] && ! is_email( $v ) ) { $errors[ $f ] = 'メールアドレスの形式が正しくありません。'; }
		if ( 'email-confirm' === $f && ( $vals['email'] ?? '' ) !== $v ) { $errors[ $f ] = 'メールアドレスが一致しません。'; }
		if ( 'tel' === $d['type'] && ! preg_match( '/^[0-9０-９+\-() ]{8,20}$/u', $v ) ) { $errors[ $f ] = '電話番号の形式が正しくありません。'; }
		if ( 'postal' === $d['type'] && ! preg_match( '/^\d{3}-?\d{4}$/', $v ) ) { $errors[ $f ] = '郵便番号は 7 桁で入力してください。'; }
		if ( 'kana' === $d['type'] && ! preg_match( '/^[ぁ-ゖー\s　]+$/u', $v ) ) { $errors[ $f ] = 'ひらがなで入力してください。'; }
		if ( 'number' === $d['type'] && ( ! is_numeric( $v ) || (int) $v < 1 ) ) { $errors[ $f ] = '1 以上の数を入力してください。'; }
		if ( 'url' === $d['type'] && ! preg_match( '#^https?://#', $v ) ) { $errors[ $f ] = 'https:// から始まる URL を入力してください。'; }
		if ( 'captcha' === $d['type'] && '7' !== trim( $v ) ) { $errors[ $f ] = '答えが違います。'; }
	}
	return $errors;
}

// 完了 → 別ページ（form_thanks=separate）の redirect は出力前に
add_action( 'template_redirect', function () {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['wt_form'] ) ) { return; }
	$st = wt_form_state();
	if ( 'done' === $st['step'] && 'separate' === wt_opt( 'form_thanks' ) ) {
		$q = isset( $_GET['wt'] ) ? '?wt=' . rawurlencode( sanitize_text_field( wp_unslash( $_GET['wt'] ) ) ) : '';
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
	$desc = $err ? ' aria-invalid="true" aria-describedby="' . $id . '-err"' : '';
	static $focused = false; // JS 無効でサーバから戻ったとき、最初の不備の項目へフォーカス（top-summary のときはまとめ側）
	if ( $err && ! $focused && ! in_array( wt_opt( 'form_error' ), array( 'top-summary', 'both' ), true ) ) { $desc .= ' autofocus'; $focused = true; }
	$ac  = isset( $d['ac'] ) ? ' autocomplete="' . $d['ac'] . '"' : '';
	$v   = is_array( $val ) ? '' : $val;
	switch ( $d['type'] ) {
		case 'textarea': return '<textarea id="' . $id . '" name="' . $name . '" rows="6" placeholder="' . esc_attr( $ph ) . '"' . $req . $desc . '>' . esc_textarea( $v ) . '</textarea>';
		case 'select': $o = ''; foreach ( $d['options'] as $opt ) { $o .= '<option value="' . esc_attr( $opt ) . '"' . selected( $v, $opt, false ) . '>' . ( '' === $opt ? '選択してください' : esc_html( $opt ) ) . '</option>'; } return '<select id="' . $id . '" name="' . $name . '"' . $req . $desc . '>' . $o . '</select>';
		case 'radio': $o = ''; foreach ( $d['options'] as $i => $opt ) { $rid = $id . '-' . $i; $o .= '<label class="wt-form__radio" for="' . $rid . '"><input type="radio" id="' . $rid . '" name="' . $name . '" value="' . esc_attr( $opt ) . '"' . checked( $v, $opt, false ) . ( 0 === $i ? $req : '' ) . '> ' . esc_html( $opt ) . '</label>'; } return '<div class="wt-form__radios" role="radiogroup" aria-labelledby="' . $id . '-lb"' . $desc . '>' . $o . '</div>';
		case 'date3': $o = ''; $vals = is_array( $val ) ? $val : array(); for ( $i = 1; $i <= 3; $i++ ) { $o .= '<label class="wt-form__date" for="' . $id . '-' . $i . '"><span>第 ' . $i . ' 希望</span><input type="date" id="' . $id . '-' . $i . '" name="' . $name . '[]" value="' . esc_attr( $vals[ $i - 1 ] ?? '' ) . '"' . ( 1 === $i ? $req . $desc : '' ) . '></label>'; } return '<div class="wt-form__dates">' . $o . '</div>';
		case 'number': return '<span class="wt-form__unit"><input type="number" id="' . $id . '" name="' . $name . '" min="1" value="' . esc_attr( $v ) . '" placeholder="' . esc_attr( $ph ) . '"' . $req . $desc . '><span>' . esc_html( $d['unit'] ) . '</span></span>';
		case 'file': return '<input type="file" id="' . $id . '" name="' . $name . '"' . $desc . '><small class="wt-form__note">' . esc_html( $d['note'] ) . '</small>';
		case 'checkbox': return '<label class="wt-form__check" for="' . $id . '"><input type="checkbox" id="' . $id . '" name="' . $name . '" value="1"' . checked( (string) $v, '1', false ) . $req . $desc . '> ' . esc_html( $d['label'] ) . ( 'consent' === $f ? '<span class="wt-form__check-link">（<a href="/privacy/" target="_blank" rel="noopener">個人情報の取り扱い</a>）</span>' : '' ) . '</label>';
		case 'privacy': return '<p class="wt-form__privacy" id="' . $id . '">送信いただいた内容は <a href="/privacy/" target="_blank" rel="noopener">個人情報の取り扱い</a> に沿って利用します。送信をもって同意したものとします。</p>';
		case 'yesno': $o = ''; $vals = is_array( $val ) ? $val : array(); foreach ( $d['questions'] as $i => $q ) { $o .= '<div class="wt-form__yesno" role="group" aria-labelledby="' . $id . '-q' . $i . '"><span id="' . $id . '-q' . $i . '">' . esc_html( $q ) . '</span><label for="' . $id . '-' . $i . '-y"><input type="radio" id="' . $id . '-' . $i . '-y" name="' . $name . '[' . $i . ']" value="はい"' . checked( $vals[ $i ] ?? '', 'はい', false ) . ( 0 === $i ? $req : '' ) . '> はい</label><label for="' . $id . '-' . $i . '-n"><input type="radio" id="' . $id . '-' . $i . '-n" name="' . $name . '[' . $i . ']" value="いいえ"' . checked( $vals[ $i ] ?? '', 'いいえ', false ) . '> いいえ</label></div>'; } return '<div class="wt-form__yesnos"' . $desc . '>' . $o . '</div>';
		case 'captcha': return '<span class="wt-form__captcha-q" id="' . $id . '-q">3 + 4 = ?</span><input type="text" id="' . $id . '" name="' . $name . '" inputmode="numeric" value="' . esc_attr( $v ) . '" aria-labelledby="' . $id . '-lb ' . $id . '-q"' . $req . $desc . '>';
		default:
			$type = in_array( $d['type'], array( 'email', 'tel', 'url' ), true ) ? $d['type'] : 'text';
			$im = 'postal' === $d['type'] ? ' inputmode="numeric"' : ''; return '<input type="' . $type . '" id="' . $id . '" name="' . $name . '" value="' . esc_attr( $v ) . '" placeholder="' . esc_attr( $ph ) . '"' . $ac . $im . $req . $desc . '>';
	}
}

function wt_form_step_of( $f ) { // steps レイアウトの段: 1 = 基本情報、2 = 内容、3 = 確認・同意
	if ( in_array( $f, array( 'consent', 'privacy-link', 'captcha', 'newsletter-optin' ), true ) ) { return 3; }
	return in_array( $f, array( 'name', 'name-kana', 'company', 'department', 'email', 'email-confirm', 'tel', 'postal', 'address', 'url' ), true ) ? 1 : 2;
}

function wt_render_form( $attrs = array() ) {
	$kind = wt_opt( 'form_kind' ); $k = wt_form_kinds()[ $kind ]; $st = wt_form_state(); $defs = wt_form_field_defs(); $fields = wt_form_fields();
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
		foreach ( $fields as $f ) { $d = $defs[ $f ]; if ( in_array( $d['type'], array( 'privacy', 'captcha' ), true ) ) { continue; } $key = str_replace( '-', '_', $f ); $v = $st['values'][ $key ] ?? '';
			$disp = is_array( $v ) ? implode( ' / ', array_filter( $v ) ) : ( 'checkbox' === $d['type'] ? ( $v ? '同意する' : '—' ) : ( 'file' === $d['type'] ? '（PoC では送信しません）' : $v ) );
			$o .= '<div><dt>' . esc_html( $d['label'] ) . '</dt><dd data-wt-field="' . esc_attr( $f ) . '">' . ( '' === $disp ? '—' : nl2br( esc_html( $disp ) ) ) . '</dd></div>';
			if ( is_array( $v ) ) { foreach ( $v as $i => $x ) { $o .= '<input type="hidden" name="wt_form[' . $key . '][' . esc_attr( (string) $i ) . ']" value="' . esc_attr( $x ) . '">'; } } else { $o .= '<input type="hidden" name="wt_form[' . $key . ']" value="' . esc_attr( $v ) . '">'; } }
		if ( 'question' === wt_opt( 'form_captcha' ) ) { $o .= '<input type="hidden" name="wt_form[captcha]" value="7">'; }
		$o .= '</dl><div class="wt-form__actions"><button type="submit" class="wt-form__back" name="wt_step" value="back">修正する</button><button type="submit" class="wt-form__submit" name="wt_step" value="confirm">' . esc_html( $k['submit'] ) . '</button></div></form>';
	} else {
		$o .= '<h2 class="wt-form__title">' . esc_html( $k['label'] ) . '</h2><p class="wt-form__lead">' . esc_html( $k['lead'] ) . '</p>';
		if ( isset( $st['errors']['_form'] ) ) { $o .= '<div class="wt-form__summary" role="alert"><p>' . esc_html( $st['errors']['_form'] ) . '</p></div>'; }
		elseif ( $st['errors'] && in_array( $errmode, array( 'top-summary', 'both' ), true ) ) { $o .= '<div class="wt-form__summary" role="alert" id="wt-form-summary" tabindex="-1" autofocus><p>入力内容に ' . count( $st['errors'] ) . ' 件の不備があります。</p><ul>'; foreach ( $st['errors'] as $f => $m ) { $o .= '<li><a href="#wt-f-' . esc_attr( $f ) . '">' . esc_html( $m ) . '</a></li>'; } $o .= '</ul></div>'; }
		$steps = 'steps' === $layout;
		$o .= '<form class="wt-form__form" method="post" action="' . $action . '" novalidate enctype="multipart/form-data" data-wt-error="' . esc_attr( $errmode ) . '" data-wt-confirm="' . esc_attr( wt_opt( 'form_confirm' ) ) . '">' . wp_nonce_field( 'wt_form', 'wt_form_nonce', true, false ) . '<input type="hidden" name="wt_step" value="input"><div class="wt-form__hp" aria-hidden="true"><label for="wt-hp">この欄は空のままにしてください</label><input type="text" id="wt-hp" name="wt_hp" tabindex="-1" autocomplete="off"></div>';
		if ( $steps ) { $o .= '<ol class="wt-form__steps" aria-label="入力の段階"><li class="is-current" data-wt-step-i="1">基本情報</li><li data-wt-step-i="2">内容</li><li data-wt-step-i="3">確認・同意</li></ol>'; }
		$cur = 0;
		foreach ( $fields as $f ) { $d = $defs[ $f ]; $key = str_replace( '-', '_', $f ); $v = $st['values'][ $key ] ?? ''; $err = $st['errors'][ $f ] ?? '';
			if ( $steps && wt_form_step_of( $f ) !== $cur ) { if ( $cur ) { $o .= '</fieldset>'; } $cur = wt_form_step_of( $f ); $o .= '<fieldset class="wt-form__step" data-wt-step-i="' . $cur . '"><legend class="screen-reader-text">段階 ' . $cur . '</legend>'; }
			$rowcls = 'wt-form__row wt-form__row--' . esc_attr( $d['type'] ) . ( $err ? ' is-error' : '' ) . ( 'textarea' === $d['type'] || 'yesno' === $d['type'] || 'date3' === $d['type'] ? ' wt-form__row--wide' : '' );
			$o .= '<div class="' . $rowcls . '" data-wt-field="' . esc_attr( $f ) . '">';
			if ( ! in_array( $d['type'], array( 'checkbox', 'privacy' ), true ) ) {
				$lbl = esc_html( $d['label'] ) . ( $d['req'] ? ' ' . wt_form_mark() : '' );
				$o .= in_array( $d['type'], array( 'radio', 'yesno', 'date3', 'captcha' ), true ) ? '<span class="wt-form__label" id="wt-f-' . $f . '-lb">' . $lbl . '</span>' : '<label class="wt-form__label" for="wt-f-' . $f . '">' . $lbl . '</label>';
			}
			$o .= '<div class="wt-form__control">' . wt_form_control( $f, $v, $err );
			if ( $err && in_array( $errmode, array( 'inline', 'both' ), true ) ) { $o .= '<p class="wt-form__error" id="wt-f-' . $f . '-err">' . esc_html( $err ) . '</p>'; }
			$o .= '</div></div>';
		}
		if ( $steps && $cur ) { $o .= '</fieldset>'; }
		if ( 'external-slot' === wt_opt( 'form_captcha' ) ) { $o .= '<div class="wt-form__captcha-slot" aria-label="外部の認証枠（PoC では描画のみ）"><span>外部認証の枠</span></div>'; }
		$o .= '<div class="wt-form__actions">' . ( $steps ? '<button type="button" class="wt-form__back wt-form__prev" hidden>戻る</button><button type="button" class="wt-form__next" hidden>次へ進む</button>' : '' ) . '<button type="submit" class="wt-form__submit">' . esc_html( wt_form_submit_text() ) . '</button></div>';
		if ( 'inline-review' === wt_opt( 'form_confirm' ) ) { $o .= '<div class="wt-form__inline-review" hidden><h3>送信前の見直し</h3><dl></dl><div class="wt-form__actions"><button type="button" class="wt-form__back wt-form__review-edit">修正する</button><button type="submit" class="wt-form__submit wt-form__review-send" name="wt_step" value="confirm">' . esc_html( $k['submit'] ) . '</button></div></div>'; }
		$o .= '</form>';
	}
	$o .= '</div>';
	// 代替導線（台帳 cta_side: tel 67% / none 26% / email 10% / chat 7% / messaging-app 5%）
	$sides = array(
		'tel'           => '<p class="wt-form__side-lead">お電話でもご相談いただけます</p><a class="wt-form__side-tel" href="tel:0312345678"><i class="wt-i wt-i--phone" aria-hidden="true"></i>03-1234-5678</a><p class="wt-form__side-note">平日 10:00〜18:00（土日祝休）</p>',
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
	register_block_type( 'helix-wt/form', array( 'render_callback' => 'wt_render_form' ) );
	register_block_type( 'helix-wt/form-thanks', array( 'render_callback' => 'wt_render_form_thanks' ) );
} );
