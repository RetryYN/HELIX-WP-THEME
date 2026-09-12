# 独立コンテンツの実機カタログ

試作テーマに有料記事・インタビュー・BLP・獲得LP・学習を追加。コンテンツの保存と管理は別の試作プラグインの5CPTに置く。[比較カタログ](../2026-09-08-selection-catalog/index.html)から画像比較・メモ保存・ローカル実機へ移動できる。

## 再現

Docker・Node.js・Pythonを用意し、リポジトリのルートで実行する。既存環境とは別名のコンテナ・DB・volumeを使い、HTTPはloopbackの8098番だけで公開する。

```sh
python3 scripts/start-content-lab.py
npx playwright install chromium
WTCF_LAB_CREDENTIALS="${TMPDIR:-/tmp}/helix-content-lab/credentials.json" node scripts/verify-content-faces.mjs
node scripts/verify-learning-faces.mjs
python3 scripts/verify-content-management.py
node scripts/verify-content-passwords.mjs
node scripts/build-selection-catalog.mjs
```

管理検査は一時的に専用labのテーマを切り替えて戻すため、撮影と同時実行しない。

`WTCF_STATE_DIR`を指定した場合は、そのディレクトリの`credentials.json`を検証スクリプトへ渡す。資格情報はリポジトリ外で生成・保持し、ログや公開成果物に含めない。起動スクリプトは既存labを再利用し、専用fixtureだけを再投入する。異なるcheckoutをマウントした同名コンテナは変更せず停止する。

独立レビューでは既存の資格情報を共有しない。レビュー対象checkoutで `python3 scripts/start-content-lab.py` を実行すると、`WTCF_STATE_DIR`（未指定なら `${TMPDIR:-/tmp}/helix-content-lab`）へ新しい `credentials.json` がmode 0600で生成される。その直後に上記の `WTCF_LAB_CREDENTIALS=... node scripts/verify-content-faces.mjs` を実行すれば、リポジトリへ秘密値を置かず同じ検査を再現できる。出力JSONとログには資格情報を保存しない。

| 用途 | ローカルURLのパス | 確認内容 |
| --- | --- | --- |
| 有料記事一覧 | `/library/` | 買い切り・購読の記事と価格、詳細への参照 |
| 買い切り | `/library/decision-design/` | `?view=sales` / `preview` / `body`。権限がなければ本文を返さない |
| 購読 | `/library/monthly-notes/` | 有効購読だけ全文。失効・買い切り権限では試し読み |
| インタビュー | `/voices/making-room/` | 2人物・所属・3発言・掲載確認 |
| 参照カード | `/voices-in-context/` | 同じ独立投稿への参照。確認前投稿は公開しない |
| BLP | `/guides/before-redesign/` | 理解・適否判断からLPへ送客 |
| 獲得LP | `/start/editorial-session/` | 提供内容から入力へ到達。実送信・保存なし |

ログイン済みの購入者本文は検証用アカウントのWordPressセッションで確認する。URLやブラウザストレージの値を変更して認可を迂回する仕組みは設けていない。

## 見た目の比較

`?design=standard`と`?design=editorial`を同じ本文・PC1440px/SP375pxで撮影。標準案は詰めた本文行間・小さい見出し、編集重視案は見出し階層・本文の行間・章間・淡い紙面色・補助欄の分離を試す。人物の発言は丸い識別子と同じ人物IDで追う。

これは**新規面での2案比較**であり、既存の全ページに対する変更前後の改善実績ではない。画像に頼らない組版の案として追加した。既存記事・HOME・LPの改善、写真を含む比較、読者評価、全面のアクセシビリティ・速度検証は残る。

## 証拠と限界

- [表示・アクセス検査](results/verify.json): 6閲覧者状態×2課金方式×3表示用途、no-store、公開HTML/REST/検索/feed/一覧、掲載確認、参照カード、BLP→LP、PC/SP・JS無効、横溢れ、h1。各実行の完了フラグ・全行・撮影索引を保持する。
- [管理とテーマ変更](results/management.json): 5CPTの管理UI/REST、確認前draft、別テーマへ切替後の同一レコード保持、試作テーマ復元を独立したWP-CLIプロセスで照合した。
- 初期の権限は外部認可サービスを置き換える**ローカルfixture**。実決済・購読契約・外部認可サービス障害・CDN・実データ移行の検証ではない。
- テーマは表示projectionだけを受け取る。保護本文は公開post_contentや公開RESTメタに保存しない。配送経路の追加時には新しい経路の検査が必要。
- インタビュー編集用の専用入力UI、全CPTの管理権限行列、全継承セットへの接続は今後の作業。現状の3状態のヘッダーは代表表示であり、既存の共通設定束全体の実証ではない。
- [追調査差分](research-delta.md)で3 ACを追加。全132要求の再現は継続中。

## 学習・支援系の追加

`seed.php`は学習データも投入する。起動済みlabで学習だけを更新する場合は同じ専用WP-CLIコンテナで`seed-learning.php`を実行する。`node scripts/verify-learning-faces.mjs`で講座/レッスン/目次/用語/FAQ/検索を検証し、結果と14画像を`results/learning/`へ保存する。本文は標準の見出し・段落ブロックを正本とし、目次と区画はその読み取り結果。メタに別の本文を保持しない。検索はタイトルだけでなく本文内の用語も対象にする。

公開投稿7件・下書き1件、講座配下の3レッスンをfixtureとして持つ。専用環境の管理検査は学習投稿の親ID・順序・標準ブロック本文も照合する。共通継承/独自/非表示の束への接続は未完了。検索noindexはlab全体のnoindex設定と重なるため、製品SEO契約の単独達成証拠には使わない。

## 同期前の検証

表示・アクセス191項目、学習252項目、公開状態変更15項目、管理5項目を確認。[標準パスワード保護](results/passwords.json)も5種別のHTML/REST計10項目を確認した。独自表示でも保護フォームへ分岐し、参照カードのHTML生成はテーマが担当する。パスワード検査は専用fixtureへ一時設定して復元するため、管理検査と同様に他のWP検査・撮影と同時実行しない。

## インタビューの掲載状態

[状態遷移の手順と範囲](interview-lifecycle-plan.md)、[修正前の不成立記録](results/interview-lifecycle-before.json)、[現行の検証結果](results/interview-lifecycle.json)。`node scripts/verify-interview-lifecycle.mjs`で専用の一時投稿を使い、確認取り消し・人物参照破損・公開中の確認文書削除を検査する。他のWP検査と同時実行しない。公開不可となった投稿は下書きに戻り、確認を戻しただけでは再公開しない。

学習検索の範囲外ページからの復帰もPC/SP画像で比較できる。`node scripts/verify-learning-publication.mjs`は専用の一時投稿を作り、同じ検索URLを再読込して公開件数の減少・非公開本文の除外・全件非公開後の一覧復帰を検証する。他のWP検査と同時実行せず、終了時に作成した投稿だけを削除する。

## 常設案内・規約の追加

[11面の宣言](plugin/site-pages.json)をプラグインから読み、テーマがブロックパターンとHTMLを生成する。通常の固定ページとして保存し、`page-site-guide`テンプレートで表示する。起動時に`seed-site-pages.php`も実行する。会社・サービス・料金・採用・問い合わせ・プライバシー・販売表示・外部送信先・アクセシビリティ・拠点・利用案内は、それぞれ`/site-<key>/`で開ける。

`node scripts/verify-site-pages.mjs`で専用labの事業者設定を一時変更・復元し、全ページの追従と外部送信先の変更/空、パスワード保護、別オリジンの受付例へのGET遷移を検証する。静的カタログ用8099サーバーも起動しておく。他のWP検査と同時実行しない。22画像と結果は`results/site-pages/`。受付例は架空の分類だけを選び、送信・保存・通知の業務処理は行わない。

`node scripts/verify-site-page-editor.mjs`で、実パターン一覧から会社案内を挿入し、専用テンプレート適用、11種類のプレビュー・保存・再読込・公開反映を42検査で確認した。コードdigest付き結果は`results/site-pages/editor.json`。専用labの管理者情報はリポジトリ外の環境ファイルを使う。他のWP検証と同時実行しない。

本文・料金等の個別編集と共通事業者設定の管理UI、共通継承全体、実在する第三者フォームとの接続契約は未完了。規約面は構造の例であり、実運用の法的な十分性を示すものではない。新しい面のデザインであり、既存全面の改善実績とは別に扱う。

## 操作領域の改善比較

[変更前後の比較](results/site-quality/)で会社案内・料金のPC/SPを並べる。`node scripts/verify-site-page-quality.mjs`は常設11面×PC/SP×JS有無の44条件で、リンク44px、本文16px、横溢れなし、スキップリンクのフォーカスと本文移動、コード変更なしを265検査する。変更前は延べ370のリンク測定が44px未満、変更後は0。共通の`tap-min`を使い、ロゴ・ナビ・目次・フッターの操作領域を確保する。

基準画像と`baseline.json`は4f7851dの表示CSSに対する記録。`--baseline`は変更前の再採取用で、現在の表示で上書きすると元の比較を失うため、通常の回帰では指定しない。背景や本文を変えずに操作寸法を改善した比較であり、全面の品質・速度・CWV・固定面・同意バーや管理画面の成立とは区別する。

## 共通パーツ設定への接続

個別デザインは`content_chrome:native`、既存の共通パーツは`content_chrome:shared`で選択する。例: 有料記事URLへ`?wt=content_chrome:shared,content_paid_head:own,own_content_paid_header:center,content_paid_side:article`を付ける。既存の`wt_opt`と同じtheme_modキー（例`wt_content_chrome`）へ保存できる。対象面の宣言はテーマの`config/content-chrome.json`。独自ヘッダー・フッター・固定CTA・サイドバーは面別の軸に分離し、共通設定のコピーを保持しない。

`node scripts/verify-content-inheritance.mjs`は専用labの設定を一時変更・復元するため、他のWP検証と並行実行しない。6面の3状態をPC/SPで撮影し、428検査で共通/独自/非表示・保存設定の反映と面間の独立性を確認する。結果と画像は`results/inheritance/`、操作例は選択カタログの「共通設定の継承」。[確認範囲と残件](inheritance-plan.md)を参照。

## 共通ヘッダーの保存ナビ

[ナビ参照の確認範囲](header-navigation-plan.md)。`seed-navigation.php`でlabの初期ナビを作成する。再実行は編集済み選択を保持する。`node scripts/verify-header-navigation.mjs`は一時ナビの更新・非公開・空・不正参照とPC/SPの移動を実測し、終了時に設定を復元する。ほかの設定変更を伴うWP検証とは並行実行しない。
