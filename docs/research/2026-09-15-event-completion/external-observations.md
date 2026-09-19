# EVENT 追調査と要求候補

確認日: 2026-09-15。ここでは公開一次情報から観察できた表示契約だけを要求候補へ変換する。既存 `WT-FR-EVENT-01` の完了や、観察先の構成比を主張しない。

## 観察

- WordCamp Europe 2026 は6月4日から6日の複数日開催で、Contributor Dayと2日間のconferenceを分け、keynote、workshop、panel、複数分野のsessionを案内している。全sessionのlive streamも別導線として示す。出典: [WordPress News](https://wordpress.org/news/2026/05/wceu-2026-sessions/)
- WordCamp US 2026 は8月16日から19日の4日構成で、Contributor Day、Showcase Day、conference tracks、socialを区別する。現地参加のticket、Contributor Dayの登録、schedule、宿泊・交通を別の行動として示す。出典: [WordPress News](https://wordpress.org/news/2026/07/wcus-2026-guide/)
- State of the Word 2021 は現地席の申請とオンライン視聴を分け、現地席には最大50人という定員と申請期限を示している。出典: [WordPress News](https://wordpress.org/news/2021/11/join-us-for-state-of-the-word-2021-in-person-or-online/)
- WordCampのworkshopやYouth/Teen Dayには、本体参加とは別の事前登録や対象年齢が必要な場合がある。出典: [WordPress News](https://wordpress.org/news/2024/06/episode-81-its-your-first-wordcamp-welcome/)
- WordPress Eventsの一覧はOnline/In Person、種別、月、国で絞り込める。EVENT単体ページだけでなく、形式と時期を先に選ぶ一覧導線が必要になる。出典: [WordPress Events](https://events.wordpress.org/)

## 要求候補

| ID | 要求候補 | 受入条件候補 |
|---|---|---|
| WT-CAND-EVENT-FORMAT-01 | EVENTは現地、オンライン、現地＋配信を区別し、それぞれの参加導線、場所または視聴方法、定員の有無を表示できる。 | 3形式を同一本文で切り替え、存在しない会場・配信・外部通信をDOMへ出さない。PC/SP・JS有無で主CTAと補助CTAを混同しない。 |
| WT-CAND-EVENT-SCHEDULE-01 | 複数日、複数トラック、本体と副イベントを階層化し、日時・場所・対象・別登録の要否を比較できる。 | 1日、複数日、並行トラック、別登録イベントを正例とし、長い名称・時刻重複・未定枠でも表やカードが欠けず、日時だけで内容を誤認しない。 |
| WT-CAND-EVENT-ADMISSION-01 | ticket、座席申請、無料視聴、副イベント登録を別のobligationとして扱い、受付状態と対象条件を導線ごとに示す。 | 本体申込済みでも副イベント未登録なら完了表示にしない。定員あり／なし、受付前／中／満席／終了を導線単位で検証する。 |
| WT-CAND-EVENT-LIST-01 | EVENT一覧は開催形式、種別、月、地域で絞り込み、該当0件から条件を戻せる。 | 絞り込みの組合せ、0件回復、URLまたは保存状態からの再開、キーボード操作、PC/SPでの件数一致を検証する。 |
| WT-CAND-EVENT-DISRUPTION-01 | 延期、取消、中止、会場・配信URL変更は通常の受付終了と区別し、理由、更新時刻、代替行動を示す。 | 公開一次事例を追加観察して語彙と優先順位を確定するまでpending。受付終了表示だけで中止を代用しない。 |

## 現バッチとの境界

完成5構成は `seminar` / `seminar-classic` / `conference` / `festival` / `campaign` の視覚比較、申込方式、地図、受付4状態を実証した。上記候補のうち、複数日・並行トラック・導線ごとの独立状態・一覧絞り込み・中止時の更新履歴は未実証である。次回compileまでは要求候補として保持し、既存L3の達成数へ加えない。
