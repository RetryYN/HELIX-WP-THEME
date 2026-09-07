<?php
/**
 * Title: イベント / セミナー / キャンペーンページ（選択可能）
 * Slug: helix-wt/event
 * Categories: helix-wt
 * Description: 2026-09-06 PO 反応 17 回目 WT-EVT-0277「イベントとかが組めるページは？」の Claude 案。hero 4 型・開催情報 4 型・スケジュール 4 型・登壇者 4 型・申込 4 型・受付状態 4 型・地図 3 型・固定導線 3 型・共有 3 型。段8（WT-EVT-0287、台帳 home-event-recapture-v2 主集計 n=40）: 区間セット 4 種（seminar / conference / festival / campaign）・申込 +3（receipt-upload / postcard / messaging-app）・区間 +8（対象者 / 主催 / 賞品 / 対象商品 / 応募の流れ / 審査員 / ギャラリー / カウントダウン。固定ページ用パーツ helix-wt-page/* を転用）。
 *              既定は台帳 research-r17（イベント個別募集ページ 8 件、取得 20 件から page_kind 除外後）の最多型。日時・会場・人物は PoC 用の架空。フォームは送信しない。埋め込み地図・外部チケットサービスへは接続しない（リンク先はダミー）。
 */
$u = get_theme_file_uri( 'assets/img' );
$status = '<span class="wt-event-status wt-event-status--open">受付中</span><span class="wt-event-status wt-event-status--few-seats">残席わずか</span><span class="wt-event-status wt-event-status--ended">受付終了</span>';
$apply_btn = '<a class="wt-lp-cta-action wt-event-apply-link" href="#apply">参加を申し込む</a>';
?>
<!-- wp:html -->
<div class="wt-event-hero-slot" id="event-hero">
<section class="wt-event-hero wt-event-hero--photo-overlay" data-wt-scrim aria-labelledby="event-hero-photo-title"><img class="wt-event-hero__bg" src="<?php echo esc_url( $u ); ?>/media-pickup-2.jpg" alt="" width="1440" height="810" loading="eager" fetchpriority="high" decoding="async"><div class="wt-event-hero__inner"><?php echo $status; ?><p class="wt-eyebrow">SEMINAR</p><h1 id="event-hero-photo-title">中小企業のための業務改善セミナー 2026 秋</h1><p class="wt-event-hero__meta"><time datetime="2026-10-15T14:00">2026 年 10 月 15 日（木）14:00〜16:00</time><span>オンライン + 会場（サンプルホール）</span></p><?php echo $apply_btn; ?></div></section>
<section class="wt-event-hero wt-event-hero--key-visual" aria-labelledby="event-hero-kv-title"><img class="wt-event-hero__kv" src="<?php echo esc_url( $u ); ?>/media-pickup-3.jpg" alt="" width="1440" height="600" loading="eager" fetchpriority="high" decoding="async"><div class="wt-event-hero__inner"><?php echo $status; ?><p class="wt-eyebrow">SEMINAR</p><h1 id="event-hero-kv-title">中小企業のための業務改善セミナー 2026 秋</h1><p class="wt-event-hero__meta"><time datetime="2026-10-15T14:00">2026 年 10 月 15 日（木）14:00〜16:00</time><span>オンライン + 会場（サンプルホール）</span></p><?php echo $apply_btn; ?></div></section>
<section class="wt-event-hero wt-event-hero--date-place-block" aria-labelledby="event-hero-date-title"><div class="wt-event-hero__inner wt-event-hero__grid"><div class="wt-event-date"><b>10<small>/</small>15</b><span>2026 年（木）</span><span>14:00〜16:00</span></div><div><?php echo $status; ?><p class="wt-eyebrow">SEMINAR</p><h1 id="event-hero-date-title">中小企業のための業務改善セミナー 2026 秋</h1><p class="wt-event-hero__meta"><span><i class="wt-i wt-i--s wt-i--pin" aria-hidden="true"></i>オンライン + 会場（サンプルホール）</span><span><i class="wt-i wt-i--s wt-i--user" aria-hidden="true"></i>定員 50 名・参加無料</span></p><?php echo $apply_btn; ?></div></div></section>
<section class="wt-event-hero wt-event-hero--text-only" aria-labelledby="event-hero-text-title"><div class="wt-event-hero__inner"><?php echo $status; ?><p class="wt-eyebrow">SEMINAR</p><h1 id="event-hero-text-title">中小企業のための業務改善セミナー 2026 秋</h1><p class="wt-event-hero__lead">現場の手戻りを減らす「見える化」の始め方を、3 業種の事例とともに 2 時間で解説します（PoC 用の文言）。</p><?php echo $apply_btn; ?></div></section>
</div>
<!-- /wp:html -->

<!-- wp:group {"className":"wt-event__sections","layout":{"type":"default"}} -->
<div class="wp-block-group wt-event__sections">
<!-- wp:html -->
<section class="wt-event__section wt-event__section--info" id="info" aria-label="開催情報"><div class="wt-lp-section-inner">
<p class="wt-event-info wt-event-info--inline-text"><b>日時</b> 2026 年 10 月 15 日（木）14:00〜16:00（開場 13:30）／ <b>会場</b> サンプルホール 3F + オンライン配信 ／ <b>定員</b> 会場 50 名・オンライン 300 名 ／ <b>参加費</b> 無料（事前申込制）／ <b>対象</b> 中小企業の経営者・管理部門の方</p>
<table class="wt-event-info wt-event-info--table"><caption class="screen-reader-text">開催情報</caption><tbody><tr><th scope="row">日時</th><td>2026 年 10 月 15 日（木）14:00〜16:00（開場 13:30）</td></tr><tr><th scope="row">会場</th><td>サンプルホール 3F + オンライン配信</td></tr><tr><th scope="row">定員</th><td>会場 50 名・オンライン 300 名</td></tr><tr><th scope="row">参加費</th><td>無料（事前申込制）</td></tr><tr><th scope="row">対象</th><td>中小企業の経営者・管理部門の方</td></tr></tbody></table>
<ul class="wt-event-info wt-event-info--icon-list"><li><i class="wt-i wt-i--l wt-i--calendar" aria-hidden="true"></i><b>日時</b><span>10 月 15 日（木）14:00〜16:00</span></li><li><i class="wt-i wt-i--l wt-i--pin" aria-hidden="true"></i><b>会場</b><span>サンプルホール 3F + オンライン</span></li><li><i class="wt-i wt-i--l wt-i--user" aria-hidden="true"></i><b>定員</b><span>会場 50 名・オンライン 300 名</span></li><li><i class="wt-i wt-i--l wt-i--tag" aria-hidden="true"></i><b>参加費</b><span>無料（事前申込制）</span></li></ul>
</div></section>

<section class="wt-event__section wt-event__section--overview" id="overview" aria-labelledby="event-overview-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">OVERVIEW</p><h2 id="event-overview-title">このセミナーについて</h2><p>日報・申請・記録がばらばらで、集計に時間がかかる。そんな現場で「見える化」をどこから始めるかを、製造・士業・医療の 3 つの事例で解説します。翌日から使えるチェックリストを配布します（PoC 用の文言）。</p>
<ul class="wt-event-highlights"><li><i class="wt-i wt-i--l wt-i--lightbulb" aria-hidden="true"></i><b>改善の順番が分かる</b><span>効果が出やすい順に 3 段階で整理</span></li><li><i class="wt-i wt-i--l wt-i--file" aria-hidden="true"></i><b>チェックリスト配布</b><span>翌日から現場で使える 12 項目</span></li><li><i class="wt-i wt-i--l wt-i--user" aria-hidden="true"></i><b>個別相談つき</b><span>終了後 30 分・希望者のみ</span></li></ul></div></section>

<section class="wt-event__section wt-event__section--schedule" id="schedule" aria-labelledby="event-schedule-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">SCHEDULE</p><h2 id="event-schedule-title">タイムテーブル</h2>
<table class="wt-event-schedule wt-event-schedule--table"><caption class="screen-reader-text">タイムテーブル</caption><thead><tr><th scope="col">時間</th><th scope="col">内容</th><th scope="col">登壇</th></tr></thead><tbody><tr><td>14:00</td><td>開会・趣旨説明</td><td>事務局</td></tr><tr><td>14:10</td><td>見える化の始め方 3 段階</td><td>登壇者 A</td></tr><tr><td>14:50</td><td>事例: 製造・士業・医療</td><td>登壇者 B</td></tr><tr><td>15:30</td><td>質疑応答</td><td>全員</td></tr><tr><td>16:00</td><td>閉会・個別相談（希望者）</td><td>事務局</td></tr></tbody></table>
<ol class="wt-event-schedule wt-event-schedule--timeline"><li><time>14:00</time><div><b>開会・趣旨説明</b><span>事務局</span></div></li><li><time>14:10</time><div><b>見える化の始め方 3 段階</b><span>登壇者 A</span></div></li><li><time>14:50</time><div><b>事例: 製造・士業・医療</b><span>登壇者 B</span></div></li><li><time>15:30</time><div><b>質疑応答</b><span>全員</span></div></li><li><time>16:00</time><div><b>閉会・個別相談（希望者）</b><span>事務局</span></div></li></ol>
<div class="wt-event-schedule wt-event-schedule--accordion"><details open><summary><time>14:00</time> 開会・趣旨説明</summary><p>事務局より本日の流れと資料の案内。</p></details><details><summary><time>14:10</time> 見える化の始め方 3 段階</summary><p>効果が出やすい順に「記録 → 集計 → 判断」の 3 段階で整理します。</p></details><details><summary><time>14:50</time> 事例: 製造・士業・医療</summary><p>3 業種の導入前後の数字を比べます。</p></details><details><summary><time>15:30</time> 質疑応答</summary><p>事前質問と会場からの質問に答えます。</p></details></div>
</div></section>

<section class="wt-event__section wt-event__section--speakers" id="speakers" aria-labelledby="event-speakers-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">SPEAKERS</p><h2 id="event-speakers-title">登壇者</h2>
<ul class="wt-event-speakers wt-event-speakers--cards-photo"><li><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="160" height="160" loading="lazy" decoding="async"><b>登壇者 A</b><small>業務改善コンサルタント</small><span>製造業を中心に 120 社の現場改善を支援（PoC 用の架空の経歴）。</span></li><li><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="160" height="160" loading="lazy" decoding="async"><b>登壇者 B</b><small>士業事務所 代表</small><span>問い合わせ対応の 1 本化で返信を翌営業日以内に。</span></li><li><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="160" height="160" loading="lazy" decoding="async"><b>登壇者 C</b><small>医療法人 事務長</small><span>予約と問診の流れを整え、待ち時間を 2 割短縮。</span></li></ul>
<ul class="wt-event-speakers wt-event-speakers--list"><li><b>登壇者 A</b><span>業務改善コンサルタント</span></li><li><b>登壇者 B</b><span>士業事務所 代表</span></li><li><b>登壇者 C</b><span>医療法人 事務長</span></li></ul>
<div class="wt-event-speakers wt-event-speakers--single-profile"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="200" height="200" loading="lazy" decoding="async"><div><b>登壇者 A</b><small>業務改善コンサルタント</small><p>製造業を中心に 120 社の現場改善を支援。「記録 → 集計 → 判断」の 3 段階で、担当が変わっても回る仕組みづくりを専門とする（PoC 用の架空の経歴）。</p></div></div>
</div></section>

<section class="wt-event__section wt-event__section--tickets" id="tickets" aria-labelledby="event-tickets-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">TICKETS</p><h2 id="event-tickets-title">参加区分</h2>
<ul class="wt-event-tickets"><li><b>会場参加</b><p class="wt-event-tickets__price">無料</p><span>定員 50 名・資料付き・個別相談あり</span></li><li class="is-featured"><b>オンライン参加</b><p class="wt-event-tickets__price">無料</p><span>定員 300 名・後日録画を 1 週間視聴可</span></li><li><b>録画のみ</b><p class="wt-event-tickets__price">無料</p><span>当日参加できない方向け・質疑は不可</span></li></ul></div></section>

<section class="wt-event__section wt-event__section--apply" id="apply" aria-labelledby="event-apply-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">APPLY</p><h2 id="event-apply-title">参加申込</h2>
<form class="wt-event-apply wt-event-apply--inline-form wt-lp-form-inline" action="#apply" method="get" data-wt-poc-form="no-submit"><div class="wt-lp-form__grid"><div><label for="ev-name">お名前</label><input id="ev-name" name="name" type="text" autocomplete="name" required></div><div><label for="ev-company">会社名</label><input id="ev-company" name="company" type="text" autocomplete="organization"></div><div><label for="ev-email">メールアドレス</label><input id="ev-email" name="email" type="email" autocomplete="email" required></div><div><label for="ev-type">参加区分</label><select id="ev-type" name="type"><option>会場参加</option><option>オンライン参加</option><option>録画のみ</option></select></div></div><label class="wt-event-apply__consent"><input type="checkbox" name="consent" required> 個人情報の取り扱いに同意する</label><button class="wt-lp-cta-action" type="button" aria-describedby="ev-note">申し込む</button><p id="ev-note" class="wt-lp-form__note">PoC のため送信されません。</p></form>
<div class="wt-event-apply wt-event-apply--external-form"><p>申込フォームは別ページで開きます（所要 2 分）。</p><a class="wt-lp-cta-action" href="#apply" rel="nofollow">申込フォームへ進む <i class="wt-i wt-i--s wt-i--external" aria-hidden="true"></i></a><p class="wt-lp-form__note">遷移先は PoC のためダミーのアンカー。</p></div>
<div class="wt-event-apply wt-event-apply--ticket-link"><p>チケットは外部のチケットサービスで受け付けています。</p><a class="wt-lp-cta-action" href="#apply" rel="nofollow">チケットサービスで申し込む <i class="wt-i wt-i--s wt-i--external" aria-hidden="true"></i></a><p class="wt-lp-form__note">サービス名・遷移先は PoC のため置かない。</p></div>
<div class="wt-event-apply wt-event-apply--receipt-upload"><ol class="wt-event-apply__steps"><li><i class="wt-i wt-i--l wt-i--cart" aria-hidden="true"></i><b>買う</b><span>対象商品を 1,000 円以上</span></li><li><i class="wt-i wt-i--l wt-i--edit" aria-hidden="true"></i><b>撮る</b><span>レシート全体が写るように</span></li><li><i class="wt-i wt-i--l wt-i--check-circle" aria-hidden="true"></i><b>送る</b><span>下のボタンから画像を送信</span></li></ol><div class="wt-event-apply__upload" role="group" aria-labelledby="ev-upload-title"><b id="ev-upload-title">レシート画像を選ぶ</b><p>JPEG / PNG、5MB まで。購入日・店名・商品名が読めるもの。</p><button class="wt-lp-cta-action wt-event-apply-link" type="button" aria-describedby="ev-upload-note">画像を選んで応募する</button><p id="ev-upload-note" class="wt-lp-form__note">PoC のため画像は選択・送信されない（台帳 v2: other:receipt-upload、D で 38%）。</p></div></div>
<div class="wt-event-apply wt-event-apply--postcard"><div class="wt-event-apply__postcard"><div class="wt-event-apply__addr"><p class="wt-eyebrow">宛先</p><p><b>〒000-0000</b><br>設定された所在地<br>サンプル株式会社「秋のキャンペーン」係（架空）</p></div><div><p class="wt-eyebrow">必要事項</p><ol><li>郵便番号・住所・氏名・電話番号</li><li>希望の賞品（A 賞 / B 賞）</li><li>レシート原本（コピー不可）を貼付</li></ol><p class="wt-lp-form__note">当日消印有効。はがき応募（台帳 v2: other:postcard、D で 38%）。web 応募は <a class="wt-event-apply-link" href="#apply">フォーム</a> からも可（PoC のダミー）。</p></div></div></div>
<div class="wt-event-apply wt-event-apply--messaging-app"><div class="wt-event-apply__app"><div><p>公式アカウントを友だち追加し、トーク画面からレシート画像を送って応募（台帳 v2: other:messaging-app、D で 25%）。</p><a class="wt-lp-cta-action wt-event-apply-link" href="#apply" rel="nofollow"><i class="wt-i wt-i--s wt-i--bubble" aria-hidden="true"></i> 友だち追加して応募</a><p class="wt-lp-form__note">サービス名・実 URL・QR は PoC のため置かない（ダミー模様）。</p></div><svg class="wt-event-apply__qr" viewBox="0 0 21 21" role="img" aria-label="QR コードのダミー模様（実コードではない）" width="120" height="120"><path fill="currentColor" d="M0 0h7v7H0zM14 0h7v7h-7zM0 14h7v7H0zM2 2h3v3H2zM16 2h3v3h-3zM2 16h3v3H2zM9 1h1v2H9zM11 0h1v3h-1zM9 5h3v1H9zM8 8h2v2H8zM11 8h1v1h-1zM13 9h2v1h-2zM16 8h1v3h-1zM18 9h3v1h-3zM9 11h1v3H9zM11 12h3v1h-3zM15 12h1v2h-1zM17 12h2v2h-2zM20 11h1v3h-1zM9 15h2v1H9zM12 15h1v3h-1zM14 16h3v1h-3zM18 15h1v2h-1zM20 16h1v2h-1zM9 18h1v3H9zM11 19h2v2h-2zM14 18h1v3h-1zM16 19h3v1h-3zM20 19h1v2h-1z"/></svg></div></div>
<div class="wt-event-apply wt-event-apply--closed-notice" role="status"><i class="wt-i wt-i--l wt-i--info" aria-hidden="true"></i><div><b>本イベントの受付は終了しました。</b><p>次回開催のご案内はお知らせでお伝えします。<a href="/">お知らせを見る</a></p></div></div>
<!-- /wp:html -->
<!-- wp:group {"className":"wt-event-apply wt-event-apply--block-form","layout":{"type":"constrained"}} -->
<div class="wp-block-group wt-event-apply wt-event-apply--block-form"><!-- wp:helix-wt/form {"hideTitle":true} /--></div>
<!-- /wp:group -->
<!-- wp:html -->
</div></section>

<section class="wt-event__section wt-event__section--access" id="access" aria-labelledby="event-access-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">ACCESS</p><h2 id="event-access-title">会場アクセス</h2>
<div class="wt-event-map wt-event-map--static-image"><img src="<?php echo esc_url( $u ); ?>/lum-light.jpg" alt="会場周辺の地図（PoC 用のダミー画像。実運用では地図画像を置く）" width="1200" height="600" loading="lazy" decoding="async"><address><b>サンプルホール 3F</b><br>設定された所在地<br>最寄り駅から徒歩 5 分</address></div>
<address class="wt-event-map wt-event-map--text-only"><b>サンプルホール 3F</b><br>設定された所在地<br>最寄り駅から徒歩 5 分（PoC 用の文言）</address>
<?php echo wt_render_event_map_embed(); ?>
</div></section>

<section class="wt-event__section wt-event__section--faq" id="faq" aria-labelledby="event-faq-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">FAQ</p><h2 id="event-faq-title">よくある質問</h2><div class="wt-home-faq"><details><summary>途中参加・途中退出はできますか</summary><p>できます。オンラインは録画を後日視聴できます。</p></details><details><summary>資料はもらえますか</summary><p>参加者全員に PDF で配布します。</p></details><details><summary>同業の参加はできますか</summary><p>同業のコンサルティング事業者の参加はお断りする場合があります。</p></details></div></div></section>

<section class="wt-event__section wt-event__section--sponsors" id="sponsors" aria-labelledby="event-sponsors-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">SPONSORS</p><h2 id="event-sponsors-title">協賛・協力</h2><ul class="wt-lp-logo-row" aria-label="協賛ロゴ枠"><li>LOGO 1</li><li>LOGO 2</li><li>LOGO 3</li><li>LOGO 4</li><li>LOGO 5</li></ul></div></section>

<section class="wt-event__section wt-event__section--past" id="past" aria-labelledby="event-past-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">REPORT</p><h2 id="event-past-title">過去の開催</h2><ul class="wt-home-cards wt-home-cards--cases"><li><a href="#past"><img src="<?php echo esc_url( $u ); ?>/media-pickup-5.jpg" alt="" width="640" height="360" loading="lazy" decoding="async"><small>2026 年 6 月・参加 180 名</small><b>春の業務改善セミナー レポート</b></a></li><li><a href="#past"><img src="<?php echo esc_url( $u ); ?>/media-pickup-6.jpg" alt="" width="640" height="360" loading="lazy" decoding="async"><small>2026 年 2 月・参加 140 名</small><b>冬の事例共有会 レポート</b></a></li><li><a href="#past"><img src="<?php echo esc_url( $u ); ?>/media-pickup-1.jpg" alt="" width="640" height="360" loading="lazy" decoding="async"><small>2025 年 10 月・参加 120 名</small><b>秋の業務改善セミナー レポート</b></a></li></ul></div></section>

<section class="wt-event__section wt-event__section--notes" id="notes" aria-labelledby="event-notes-title"><div class="wt-lp-section-inner"><p class="wt-eyebrow">NOTES</p><h2 id="event-notes-title">注意事項</h2><ul class="wt-event-notes"><li>申込多数の場合は抽選となることがあります。</li><li>会場での撮影・録音はご遠慮ください。</li><li>内容・登壇者は予告なく変更になる場合があります。</li></ul><p class="wt-event-organizer"><b>主催</b> サンプル株式会社（架空）　<b>お問い合わせ</b> <a href="#notes">event@example.invalid</a></p>
<div class="wt-event-share wt-event-share--icons" id="share" aria-label="このイベントを共有" data-wt-poc-dummy="share-targets"><span>共有</span><a class="wt-sns" href="#share" rel="nofollow" aria-label="X でポスト"><i class="wt-i wt-i--sns-x" aria-hidden="true"></i></a><a class="wt-sns" href="#share" rel="nofollow" aria-label="LINE で送る"><i class="wt-i wt-i--bubble" aria-hidden="true"></i></a><button type="button" data-wt-share="copy" aria-label="リンクをコピー">⧉</button></div>
<div class="wt-event-share wt-event-share--add-to-calendar" id="calendar" data-wt-poc-dummy="ics"><a class="wt-lp-cta-action wt-lp-cta-action--secondary" href="#calendar" rel="nofollow"><i class="wt-i wt-i--s wt-i--calendar" aria-hidden="true"></i> カレンダーに追加</a><p class="wt-lp-form__note">実運用では .ics を配布する。PoC ではダミーのアンカー。</p></div>
</div></section>
<!-- /wp:html -->
<!-- wp:group {"anchor":"audience","className":"wt-event__section wt-event__section--audience","layout":{"type":"default"}} -->
<div class="wp-block-group wt-event__section wt-event__section--audience" id="audience"><!-- wp:pattern {"slug":"helix-wt-page/target-audience"} /--></div>
<!-- /wp:group -->
<!-- wp:group {"anchor":"organizer","className":"wt-event__section wt-event__section--organizer","layout":{"type":"default"}} -->
<div class="wp-block-group wt-event__section wt-event__section--organizer" id="organizer"><!-- wp:pattern {"slug":"helix-wt-page/organizer"} /--></div>
<!-- /wp:group -->
<!-- wp:group {"anchor":"prizes","className":"wt-event__section wt-event__section--prizes","layout":{"type":"default"}} -->
<div class="wp-block-group wt-event__section wt-event__section--prizes" id="prizes"><!-- wp:pattern {"slug":"helix-wt-page/prizes"} /--></div>
<!-- /wp:group -->
<!-- wp:group {"anchor":"products","className":"wt-event__section wt-event__section--products","layout":{"type":"default"}} -->
<div class="wp-block-group wt-event__section wt-event__section--products" id="products"><!-- wp:pattern {"slug":"helix-wt-page/target-products"} /--></div>
<!-- /wp:group -->
<!-- wp:group {"anchor":"entry","className":"wt-event__section wt-event__section--entry","layout":{"type":"default"}} -->
<div class="wp-block-group wt-event__section wt-event__section--entry" id="entry"><!-- wp:pattern {"slug":"helix-wt-page/entry-steps"} /--></div>
<!-- /wp:group -->
<!-- wp:group {"anchor":"judges","className":"wt-event__section wt-event__section--judges","layout":{"type":"default"}} -->
<div class="wp-block-group wt-event__section wt-event__section--judges" id="judges"><!-- wp:pattern {"slug":"helix-wt-page/judges"} /--></div>
<!-- /wp:group -->
<!-- wp:group {"anchor":"gallery","className":"wt-event__section wt-event__section--gallery","layout":{"type":"default"}} -->
<div class="wp-block-group wt-event__section wt-event__section--gallery" id="gallery"><!-- wp:pattern {"slug":"helix-wt-page/gallery"} /--></div>
<!-- /wp:group -->
<!-- wp:group {"anchor":"countdown","className":"wt-event__section wt-event__section--countdown","layout":{"type":"default"}} -->
<div class="wp-block-group wt-event__section wt-event__section--countdown" id="countdown"><!-- wp:pattern {"slug":"helix-wt-page/countdown"} /--></div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:html -->
<nav class="wt-event-fixed wt-event-fixed--sp-bottom-bar" aria-label="固定導線"><span class="wt-event-fixed__date"><time datetime="2026-10-15">10/15（木）14:00</time></span><a href="#apply">参加を申し込む</a></nav>
<a class="wt-event-fixed wt-event-fixed--float-apply wt-lp-cta-action" href="#apply">参加を申し込む</a>
<!-- /wp:html -->
