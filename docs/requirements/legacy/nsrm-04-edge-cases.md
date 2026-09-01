# NSRM-04 エッジケース洗い出し

> 根拠: L1-requirements.md（43 REQ-F）/ L0-planning.md / 受入条件 §4.1 異常系・境界値
> 作成日: 2026-04-30
> ステータス: L1 Draft 段階の抽出。各 REQ-F に対し境界条件 3 件以上 + 失敗ケース 2 件以上 + 回復シナリオ 1 件以上を記述。

---

## REQ-F-001（FSEテーマ基盤）

境界条件:
- WP 6.5（要件未満）でテーマを有効化 → `activation_error` フックで `incompatible_version` を返し管理画面に通知。旧版テーマを自動ロールバックしない
- PHP 8.0（要件未満）で起動 → functions.php の ABSPATH チェックでバージョン検出し、admin_notice で警告。フロント表示は継続（fatal しない）
- FSE 非対応テーマと同時有効化試行（マルチサイトでの子テーマ重複）→ WP は 1 テーマのみ有効化する仕様のため発生しないが、テーマ切替時に旧テーマのカスタマイザ設定が残留する → 移行前に export/import を促す

失敗ケース:
- `theme.json` が JSON 不正（UTF-8 BOM 含む等）→ WP がデフォルト値にフォールバック。`wp admin_notice` で「theme.json 読み込みエラー」表示。全フロント表示は崩れるが fatal しない
- `inc/bootstrap.php` が書き換えられた（サプライチェーン攻撃）→ PHPCS / SBOM hash check が CI で検出。本番では文字化けやコード実行のリスク。`auto-update` を無効化し手動適用フローを採用

回復シナリオ:
- テーマ更新後に fatal error が発生 → WP 管理画面の「テーマを切り替える」から旧版へロールバック（WP 標準 rollback 機能）。Companion Plugin は互換バージョン確認後に再有効化

---

## REQ-F-002（JSON操作API）

境界条件:
- `dry_run: true` で schema validation が通らない JSON を送信 → 400 + `VALIDATION_ERROR`。`errors[]` に違反フィールド・期待型・実際値を返す。apply は実行しない
- `diff_hash` が dryRun から 10 分経過後に apply を試行 → 409 `DIFF_HASH_EXPIRED`。再 dryRun を促すメッセージを返す
- 操作対象 path が allowlist 外（例: `wp_options` への直接書き込み）→ 403 `FORBIDDEN_PATH`。audit log に記録

失敗ケース:
- DB transaction が apply 途中で失敗 → rollback。500 `APPLY_TRANSACTION_FAILED`。rollback_point は apply 前に生成済みのため復元可能
- 並列 apply（同一 post_id に 2 リクエスト同時）→ 楽観ロック（ETag mismatch）で後着リクエストが 412 `PRECONDITION_FAILED`

回復シナリオ:
- apply 失敗後に管理画面から「rollback」を実行 → rollback_point（apply 前 30 日保持）から完全復元。操作ログに `rollback_actor` と `rollback_reason` を記録

---

## REQ-F-003（4操作面）

境界条件:
- REST / MCP / WP CLI / React UI が同一の設定を同時に書き込む（race condition）→ 全面でスキーマ validation + ETag + 楽観ロックを使用。後着は 412 で拒否
- MCP ツールが allowlist にない操作を要求 → `OPERATION_NOT_ALLOWED`。MCP は allowlist 外の操作を返さない（capability フィルタリング）
- WP CLI を非管理者権限で実行 → `Error: This command needs a higher capability` を返し、操作を実行しない

失敗ケース:
- WP CLI で `--force` なしに apply を試行 → dry-run と同等の preview 出力のみ。実際の変更は適用されない（WF-003 設計）
- React UI のローカルキャッシュと REST レスポンスが乖離（長時間セッション）→ 「変更が検出されました。リロードしてください」を表示し、上書き apply をブロック

回復シナリオ:
- 3 操作面から同一操作を試して 2 面が競合エラー → ETag を取り直して再実行。操作ログで「どの面が先に書き込んだか」を確認可能

---

## REQ-F-004（個人版収益化ブロック）

境界条件:
- Amazon Product API のレート制限到達（1秒間 N 件超）→ 429 をキャッチし、exponential backoff + fallback（キャッシュ済み商品情報を表示）
- PR 表記が自動付与されていない状態でアフィリエイトリンクを公開 → `AffiliateDisclosureGuard` が検出し、公開前に警告。強制的に `#ad` / `PR:` を挿入する設計
- AdSense コードが `<script>` 経由で挿入試行 → REQ-F-036 の sanitize で禁止。AdSense は専用ブロック経由のみ許可

失敗ケース:
- Amazon Product API の認証情報（API key）が期限切れ → 商品カードがエラー表示。管理画面に「認証情報を更新してください」通知
- ASP CTR サマリ集計でタイムアウト（外部 API 遅延）→ キャッシュ値を表示し、「最終更新: N 分前」を表示。外部依存障害として `availability-profile.json` に記録

回復シナリオ:
- Amazon 商品情報が取得できない状態でブロックを保存済み → fallback として手動入力した商品名・価格・画像を表示。API 復旧後に自動 refresh オプション

---

## REQ-F-005（法人 HP / LP / BLP 三位一体）

境界条件:
- LP に 12 セクション未満の blueprint を送信 → schema validation で `required_section_missing`（必須セクション名を列挙して返す）
- 個人版環境で LP blueprint API を呼ぶ → 403 `LICENSE_INSUFFICIENT`。ACC-016 で確認済みの境界
- service_id が存在しない状態で BLP にサービス導線を紐付け → 404 `SERVICE_NOT_FOUND`

失敗ケース:
- LP blueprint 生成中に DB 書き込みが失敗（disk full 等）→ transaction rollback。503 `STORAGE_UNAVAILABLE`。WP admin notice 発火
- BLP の inline CTA に cta_id が未付与で公開 → `AffiliateDisclosureGuard` / CTA 計測 guard が検出。公開をブロックし、未付与 CTA のリストを管理画面に表示

回復シナリオ:
- LP blueprint の 1 セクションだけ壊れた → セクション単位 rollback（REQ-F-022 の section rollback を流用）。他セクションは無変更

---

## REQ-F-006（計測/A-B/CTA）

境界条件:
- variant_id が存在しない状態で impression イベントを受信 → `VARIANT_NOT_FOUND` を返すが、イベントは dead letter に保存（後で variant が作られた際に replay 可能）
- A/B テストで variant 重みの合計が 100% を超える → schema validation で `WEIGHT_OVERFLOW`（合計値・各 variant の現在値を返す）
- section_id なしの計測イベント → `SECTION_ID_REQUIRED`。計測基盤が section_id なしイベントを受け付けない設計

失敗ケース:
- 計測イベント API に DDoS 相当の大量リクエスト → rate limit（60 req/min 公開 / bot filter）で 429。bot_filter_score が閾値超でシャットアウト
- A/B テスト結果が途中で DB 障害により失われる → Automation SEO 側（aseo/v1）が独立した集計 DB を持つため、AGENT NEO 側の障害が統計判定に影響しない設計（責務分離）

回復シナリオ:
- 計測イベント欠損が発生（1% 超）→ `observability-profile.json` のアラートが発火。Automation SEO 側で欠損期間を識別し、統計判定を「サンプル不足」扱いにして再計測を待つ

---

## REQ-F-007（Automation SEO連携）

境界条件:
- seo-tool-connector が未インストールの状態で `/tracking/context` を送信 → 200 で受け付けるが、Automation SEO への転送なし。管理画面に「seo-tool-connector が必要」の notice
- site_token が失効・不正 → 401 `SITE_TOKEN_INVALID`。Automation SEO との再認証フローを案内
- v2 DB スキーマの WP_PAGE_SECTIONS に section_type が未登録の種別を送信 → 422 `UNKNOWN_SECTION_TYPE`

失敗ケース:
- Automation SEO が一時停止（外部サービス障害）→ `availability-profile.json` の fallback 定義に従いローカル軽量計測へ自動切替。接続復旧後にキュー分をリプレイ
- tracking context の JSON が v2 の期待スキーマと version mismatch → `Accept: application/vnd.agent-neo.v1+json` ヘッダで version 検出。不一致は 422 `CONTRACT_VERSION_UNSUPPORTED`

回復シナリオ:
- 長時間の同期失敗 → `/automation-seo/fit` エンドポイントで同期状態を診断。差分がある場合は `/batch` で一括再同期

---

## REQ-F-008（移行プラグイン）

境界条件:
- 移行元サイトの `wp/v2` REST API が無効化されている → 接続テストで `REST_API_DISABLED` を検出。手動エクスポート（WP Export XML）の代替フローを案内
- 1 記事に 200 ブロック以上含まれる大規模記事の変換 → メモリ上限超（PHP memory_limit）のリスク。Action Scheduler で分割処理。1 バッチ = 10 ブロック
- rollback snapshot の保存失敗（disk full）→ apply をブロック。「rollback 保証が取れないため apply できません」エラー（MNF-004）

失敗ケース:
- Plan A 変換で shortcode が 変換できない（未対応形式）→ 未変換要素リストに追記し、`manual_review` フラグを立てる。apply はブロックせず進行可（ユーザーが承認）
- Plan B で LLMRouter が timeout（30 秒超）→ 429 / 503 を受け取り、DLQ に積んで管理画面に「AI 再構築を再試行してください」を表示

回復シナリオ:
- apply 後に崩れが発生 → 30 日保持の rollback snapshot から `POST /rollback/{id}` で完全復元。ページ別の部分 rollback も可能

---

## REQ-F-009（設定エクスポート/インポート）

境界条件:
- import JSON の schema version が現在バージョンと不一致 → `CONTRACT_VERSION_UNSUPPORTED` で import ブロック。compatible な version range を返す
- design-tokens が循環参照（token A → token B → token A）→ schema validation で `CIRCULAR_REFERENCE` を検出
- 同一の design-token key が既存値と衝突する import → diff 表示で「上書き確認」を要求。承認なしに上書きしない

失敗ケース:
- export 中に DB コネクション断絶 → 部分的な JSON が返る。`meta.partial: true` フラグを含め、クライアントに不完全を通知
- 巨大な settings JSON（設計トークン 1000 件超）の import でタイムアウト → Action Scheduler で非同期 import。import_job_id を返す

回復シナリオ:
- import 後に design が崩れた → import 前の snapshot（export 時点）を保持しており、再 import で上書き復元可能

---

## REQ-F-010（ライセンス/パッケージ制御）

境界条件:
- ライセンス検証サーバーが 3 回連続で到達不能 → grace_period（72 時間）に入り、機能は維持。grace_period 終了後は read-only モードへ移行。`/health` に `license_status: grace` を返す
- 個人版ライセンスキーで法人機能を呼ぶ → 403 `LICENSE_INSUFFICIENT`。利用しようとした機能名とアップグレード案内を返す
- ライセンスキーを異なるドメインで同時使用（1 key = 1 domain 制約の場合）→ 二重起動検出。後から登録したドメインに `DOMAIN_CONFLICT` を返す（Q-005 で license 検証方式は open）

失敗ケース:
- ライセンスキーが期限切れ → read-only モード移行。agent-neo/v1 の Write 系エンドポイントが 402 `LICENSE_EXPIRED` を返す
- 個人 → 法人アップグレード時のパッケージ切替で機能フラグ更新に失敗 → grace_period 中は旧 package で動作継続。再起動 or `/license/validate` で強制更新

回復シナリオ:
- read-only モード中に法人機能が利用不能 → ライセンスキーを更新後、`POST /license/validate` を呼ぶと即座に通常モードに復帰

---

## REQ-F-011（SEO Core）

境界条件:
- Yoast SEO と canonical が重複出力 → `SeoConflictDetector` が検出。Yoast の canonical を dequeue し、AGENT NEO の canonical を優先（設定変更可）
- noindex が誤って全ページに設定される → SEO risk diff で `NOINDEX_GLOBAL` を検出し、管理画面に緊急警告。変更実行者と変更タイムスタンプを表示
- 同一ページに複数の `<link rel=canonical>` が出力される → schema output 検証で重複を除去。最後の 1 件のみ出力

失敗ケース:
- OGP 画像 URL が存在しない（メディア削除済み）→ fallback としてサイトロゴ画像を使用。管理画面に「OGP 画像が見つかりません」の警告
- JSON-LD の `@graph` に必須プロパティ（`@type`, `@id`）が欠損 → schema 検証で 422 `INVALID_JSONLD`。欠損フィールドリストを返す

回復シナリオ:
- SEO メタを誤って削除した → `GET /seo/{post_id}` の ETag ベースの version 履歴から直前の状態を確認し、`POST /seo/{post_id}/apply` で再適用

---

## REQ-F-012（LP/HP/BLPブループリント）

境界条件:
- LP blueprint に offer_id が未設定 → schema validation で `OFFER_ID_REQUIRED`（法人版のみ）
- service_id が 3 件以上ある HP で Gateway Grid の route が存在しないサービスを参照 → 404 `SERVICE_ROUTE_NOT_FOUND`
- LP blueprint の section 順序が標準外（Hero が最後等）→ 警告は出すが apply はブロックしない（カスタム順序を許容）。L5 CV 監査で `section_order_risk` を検出

失敗ケース:
- blueprint の DB 保存サイズが post_meta の上限を超える（jsonb 1MB 制約等）→ 422 `PAYLOAD_TOO_LARGE`。slot 単位の分割保存を提案
- LP blueprint 生成中に必須 section（CTA）が AI によって省略された → slot の `must_contain` 制約チェックで apply ブロック。`required_element_missing: cta_id` を返す

回復シナリオ:
- LP blueprint 更新後に CV が下落 → blueprint-level version 履歴 N 版から旧 blueprint を取得し、`POST /pages/{id}/rollback` で復元。A/B テストで新旧を比較再開可能

---

## REQ-F-013（法人版リード獲得）

境界条件:
- reCAPTCHA v3 スコアが閾値未満（bot 疑い）→ 422 `CAPTCHA_FAILED`。honeypot フィールドが埋まっている場合も同様
- 同一 IP から 10 分以内に 5 件以上の form submit → rate limit 429。IP ベースのカウンタはサーバーキャッシュ（APCu/Redis）に保持
- 自動返信メール送信で SMTP サーバーがダウン → submission は DB に保存済み。メール失敗は `_agent_neo_email_status: failed` に記録し、管理画面で再送トリガー可能

失敗ケース:
- submission の DB 書き込みが失敗（disk full）→ 503。ユーザーには「一時的なエラー」を表示。管理者に slack/email アラート（observability-profile.json 定義）
- CSV エクスポート中に大量データ（10 万件超）でタイムアウト → Action Scheduler でバックグラウンド生成。完了後にダウンロード URL を通知

回復シナリオ:
- reCAPTCHA 誤検知で正規ユーザーが弾かれた → 管理画面からスコア閾値を下げる。または個別 IP/メールを許可リストに追加

---

## REQ-F-014（法人版顧客行動管理）

境界条件:
- 1 セッションで 200 ページ以上のジャーニーを追跡 → メモリ上限を避けるため、セッション履歴は最新 N イベントのみ保持。古いイベントは集計済みとしてアーカイブ
- リードスコアリングのアルゴリズムが更新された後の既存スコアとの不整合 → スコア再計算ジョブを Action Scheduler で実行。移行期間中は「スコア再計算中」バッジ表示
- health score が同時多発的に更新（多ユーザー並列セッション）→ 楽観ロックで score テーブルを更新。競合時は最後の書き込みが優先（集計ベースのため整合性は許容範囲）

失敗ケース:
- ジャーニー追跡イベントが欠損（計測 JS がブロックされた）→ ファネル分析に空白が生じる。「データ欠損あり」フラグをレポートに表示
- ダッシュボードクエリが大量データで重い（1 分以上）→ index 最適化。それでも超過する場合は result をキャッシュし「最終更新: N 分前」表示

回復シナリオ:
- 誤ったスコアリングロジックを本番適用後に気づいた → scoring algorithm の version を管理し、旧 version で再計算ジョブを実行可能

---

## REQ-F-015（CRM/MA連携アドオン）

境界条件:
- HubSpot Webhook の送信が失敗（外部 API ダウン）→ retry queue に積む。最大 3 回 retry / exponential backoff。3 回失敗で dead letter queue + 管理画面通知
- Webhook 署名検証が失敗（HMAC 不一致）→ 403 `WEBHOOK_SIGNATURE_INVALID`。リプレイ攻撃防止のため timestamp 検証（5 分以内）
- Zapier 経由でデータが変形して連携 → L3 で入出力スキーマを contract 化。contract 違反は 422 で拒否

失敗ケース:
- kintone への Webhook が rate limit（1 req/sec）に抵触 → キューベースで調整。burst は許容しない
- CRM 側のフィールド削除後に AGENT NEO の mapping が壊れる → 「CRM フィールドが見つかりません」アラート。壊れた mapping はスキップして残りを継続送信

回復シナリオ:
- dead letter queue の Webhook を手動でリプレイ → 管理画面の「再送」ボタンで dead letter の個別または一括リプレイ可能

---

## REQ-F-016（個人版テンプレ固定構成）

境界条件:
- 個人版で `POST /pages/blueprint` を呼ぶ → 403 `LICENSE_INSUFFICIENT`（ACC-016 で確認済み）
- 個人版で `wp agent-neo template modify` 相当の CLI コマンドを実行 → 権限チェックで拒否。`Error: Template modification requires corporate license`
- 個人版の AI が design-tokens の更新を試みる → 403。AI 操作スコープ（記事 CRUD のみ）の enforcement

失敗ケース:
- 個人版テーマの固定テンプレートが WordPress アップデートで FSE 仕様変更により壊れる → CI の compatibility matrix テストで事前検出。緊急 patch リリース
- 個人版で子テーマを使ってテンプレートを上書き試行 → テーマ範囲外のため技術的に可能だが、license gate が機能 API レベルで阻止（テンプレートファイル自体の変更は防げない制約あり）

回復シナリオ:
- 個人版テンプレートが誰かの child theme 改変で壊れた → `wp agent-neo template reset` で標準テンプレートを再展開

---

## REQ-F-017（画像変換パイプライン）

境界条件:
- 5MB ちょうどの画像をアップロード → 5MB ちょうどは「超」ではないため同期処理。5MB + 1 byte から Action Scheduler でバックグラウンド処理
- 既に WebP の画像をアップロード → スキップ。`_agent_neo_webp_status: skipped_already_webp` を attachment meta に記録
- GD も Imagick もインストールされていない環境 → WebP 変換不可。`_agent_neo_webp_status: failed_no_library` を記録し、admin notice で警告

失敗ケース:
- WebP 変換中に PHP プロセスが OOM で落ちる → `_agent_neo_webp_status: failed` を記録。Action Scheduler が未完ジョブを検出し再試行（最大 3 回）
- `wp agent-neo media regenerate-webp --all` 実行中に中断 → `--resume` オプションで中断位置から再開可能（処理済み attachment ID を記録）

回復シナリオ:
- バックグラウンド WebP 生成が全て失敗した → `_agent_neo_webp_status: failed` のメディアを一覧表示し、ライブラリ確認後に `--retry-failed` で再実行

---

## REQ-F-018（SNS連携基盤）

境界条件:
- X API v2 の rate limit（tweet 投稿: 300/15min）に到達 → 投稿をキューに入れて 15 分後に再試行。管理画面に「X 投稿を保留中」を表示
- Instagram が動画形式（WebM/MP4）の投稿を拒否 → 対応フォーマット（MP4 H264 のみ）に自動変換試行。変換失敗時は「Instagram 対応形式に変換できませんでした」エラー
- Threads API の oEmbed が埋め込み URL を解決できない → lazy load で iframe を表示し、解決失敗時は「埋め込みを読み込めませんでした」のフォールバックテキスト

失敗ケース:
- SNS API キーが期限切れで自動投稿が全て失敗 → post meta に `_sns_publish_status: failed` を記録。管理画面に「認証情報を更新してください」バナー
- 自動投稿成功後に SNS 側で投稿を削除 → AGENT NEO 側では成功として記録。SNS 側の削除は検出しない（※推定: 自動同期削除は Phase 2 機能）

回復シナリオ:
- 投稿失敗した SNS を再送 → 管理画面の「投稿失敗一覧」から個別再送。再送前に API キー有効性を確認

---

## REQ-F-019（法人版 SNS 深い統合）

境界条件:
- LINE Webhook の検証（X-Line-Signature）が失敗 → 403 `LINE_SIGNATURE_INVALID`。HMAC-SHA256 で署名検証
- 複数 LINE 公式アカウントを設定した状態で service_id なしのリクエスト → `SERVICE_ID_REQUIRED`（複数サービス対応は service_id 必須）
- utm パラメータ付き URL が LINE 共有時に短縮 URL に変換されて utm が失われる → LINE 共有時に utm を URL fragment ではなくクエリパラメータで付与するが、短縮後の追跡は LINE 側の制約

失敗ケース:
- LINE 経由 CV 計測で Webhook が遅延到達（LINE サーバー側遅延）→ AGENT NEO が Webhook を受信した時刻を記録。遅延分は統計にそのまま反映（発生時刻ベースではなく受信時刻ベース）
- SNS チャネル別 CV 計測で utm が存在しない流入 → `utm_source: direct` として扱う。utm 欠損は「計測漏れ」としてレポートに表示

回復シナリオ:
- LINE Webhook が一時停止後に復旧 → 未受信分は LINE 側に保存されない（Webhook は fire-and-forget）。計測欠損として記録し、次回以降の統計に影響しない設計

---

## REQ-F-020（SNS API 認証情報管理）

境界条件:
- 暗号化に使う `AUTH_KEY` が wp-config.php で未設定 → `openssl_encrypt` が失敗。admin notice で「wp-config.php に AUTH_KEY を設定してください」警告。API キーは平文では保存しない
- `AUTH_KEY` が変更された後に既存の暗号化 API キーを復号しようとする → 復号失敗 → 「API キーを再入力してください」通知。旧キーは再設定不可
- 編集者権限ユーザーが API キー閲覧エンドポイントにアクセス → 403 `CAPABILITY_INSUFFICIENT`（管理者のみ閲覧可）

失敗ケース:
- API キーを暗号化保存後、DB が破損 → 復号不可。「SNS API キーを再設定してください」エラー。秘密情報は platform 側でも保持していないため再発行が必要
- トークン期限切れ後に自動投稿を試みる → 投稿失敗 + `_sns_token_expired: true` フラグ。管理画面に「X のトークンを更新してください」通知

回復シナリオ:
- API キーが漏洩した疑いがある → 管理画面から「API キーを無効化」して旧キーを削除。SNS 側でもキーを無効化。新しいキーを再設定

---

## REQ-F-021（部分更新性）

境界条件:
- `block_id` が DB に存在しない → 404 `BLOCK_NOT_FOUND`
- `block_id` が他の post に属する → 403 `BLOCK_BELONGS_TO_OTHER_POST`
- 同時編集（楽観ロック衝突）→ ETag mismatch で 412 `PRECONDITION_FAILED`。最新 ETag を `If-None-Match` で確認してから再 PATCH
- 5MB 超の HTML を 1 ブロックに部分更新 → page_type 予算チェックで 422 `ASSET_BUDGET_EXCEEDED`

失敗ケース:
- DB transaction 失敗 → rollback。500 `APPLY_TRANSACTION_FAILED`。block 変更前の状態は version 履歴に保持
- idempotency-key 衝突（同じ key で異なる payload を送信）→ 409 `IDEMPOTENCY_KEY_CONFLICT`。1 件目の response を返す

回復シナリオ:
- apply 失敗時に dryRun 結果を保持し再試行 → dryRun の diff_hash は 10 分有効。失敗後にそのまま再 apply。timeout した場合は再 dryRun

---

## REQ-F-022（H2 単位 LLM 編集）

境界条件:
- `section_id` が存在しない → 404 `SECTION_NOT_FOUND`
- AI が操作する section の前後にある section を誤って変更 → API は section 単位のトランザクションで隔離。前後 section のハッシュを事前・事後で比較し、変更があれば `SIDE_EFFECT_DETECTED` を返す
- 同一 section に dryRun と apply が並列実行 → 後着 apply は 412 で拒否

失敗ケース:
- LLM（Automation SEO 側）が 30 秒以内に rewrite 結果を返さない → Gateway timeout（504）。dryRun は失敗扱い。再試行を促す
- セクション内の HTML が sanitize 後に空になる（全 script タグ等）→ 422 `EMPTY_CONTENT_AFTER_SANITIZE`。apply はブロック

回復シナリオ:
- apply 失敗時に dryRun 結果を保持し再試行可能。section の version 履歴から rollback も可能（ACC-022a）

---

## REQ-F-023（要素差し替え機構）

境界条件:
- swap 先の `cta_id` が存在しない → 404 `CTA_NOT_FOUND`
- 同一記事に同じ `cta_id` のインスタンスが 0 件（swap 対象なし）→ 204 `NO_CONTENT`（変更なし）ではなく 404 `CTA_INSTANCE_NOT_FOUND`（明示エラー）
- `link_id` の URL 差し替えで差替え先 URL が private IP（SSRF リスク）→ 422 `URL_FORBIDDEN`（private IP レンジ拒否）

失敗ケース:
- 同時に複数の CTA を swap するバッチで 1 件が失敗 → 部分失敗。成功した swap をロールバックするか「成功分を保持してエラー分をリポート」するかを `PATCH /batch` の部分失敗ポリシーに従う
- `blueprint_id` 差し替えで新 blueprint のセクション数が旧より少ない → L5 CV 監査で `blueprint_section_reduction_risk` を検出し警告。適用はブロックしない

回復シナリオ:
- 誤った CTA を全記事に swap した → `cta_id` の swap を逆に実行（旧 cta_id に swap し直す）。rollback エンドポイント直接実行よりも swap API の逆操作が推奨

---

## REQ-F-024（AI 自律 A/B テスト機構）

境界条件:
- サンプルサイズ閾値に到達する前に A/B テストが停止される → `wp agent-neo ab-test stop --post_id=X` で即座に停止。停止時点の計測ログは保持
- variant 数が 10 件を超える → L3 で上限 N 件を決定（※推定: 推奨は 2〜3 件）。超過時は `VARIANT_LIMIT_EXCEEDED`
- 法人版の承認 gate で承認者が 30 日以上放置 → 「承認期限切れ」通知。自動採用はしない。再 A/B テストを促す

失敗ケース:
- 統計判定ロジック（Automation SEO 側）が Webhook で AGENT NEO に結果を送る際にネットワーク断絶 → retry queue。失敗が続く場合は管理画面に「A/B テスト判定結果が取得できません」通知
- A/B テスト中に該当投稿が削除される → テスト自動停止。variant は archive。計測データは残す

回復シナリオ:
- 誤って loser が default に昇格した → 承認 gate のログから正しい結果を確認し、手動で default を差し替え（`/ctas/{cta_id}/apply`）

---

## REQ-F-025（JSON 統一データモデル）

境界条件:
- export した JSON を別バージョンの AGENT NEO に import（schema version mismatch）→ `CONTRACT_VERSION_UNSUPPORTED`。対応バージョン範囲を返す
- jsonb カラムに非 JSON 文字列を保存試行 → DB レベルの constraint でエラー。500 を返さず、API 層の schema validation で先に 422 を返す設計
- 独自バイナリ（serialized PHP）が post_meta に残存している移行ケース → 移行プラグインの Plan A が `unserialize` で展開し、JSON 化する

失敗ケース:
- RFC 6902 JSON Patch の `op: move` で存在しない path を参照 → 422 `JSONPATCH_INVALID_PATH`
- JSON Patch の適用で循環参照が生じる → 422 `CIRCULAR_REFERENCE_DETECTED`

回復シナリオ:
- dryRun で JSON Patch を確認後に apply → dryRun の返り値が RFC 6902 形式なので、適用前に diff を人間・AI が確認可能

---

## REQ-F-026（v2 連携最適化 API）

境界条件:
- `?since=<ts>` に未来のタイムスタンプを指定 → 400 `INVALID_TIMESTAMP`（RFC 3339 形式チェック + 未来日時拒否）
- `?fields=` に存在しないフィールドを指定 → 400 `UNKNOWN_FIELD`。有効フィールド一覧を返す
- ETag が無効（改ざん）で `If-None-Match` を送信 → ETag 検証失敗。全 body を返す（304 にしない）

失敗ケース:
- outbound webhook の送信先 URL が到達不能（v2 側障害）→ retry / dead letter。v2 が復旧後にリプレイ可能な設計
- `/posts/{id}/markdown` 変換で Gutenberg JSON が未知のブロックタイプを含む → 未知ブロックをプレーンテキストに fallback 変換。変換できなかったブロック名を `meta.skipped_blocks` に列挙

回復シナリオ:
- ETag キャッシュの invalidation が漏れた（古いキャッシュが返る）→ `Cache-Control: no-cache` を明示して強制取得。AGENT NEO 側のキャッシュは post update フックで自動 purge

---

## REQ-F-027（v2 DB スキーマ直接マッピング）

境界条件:
- AGENT NEO の post_id が v2 の WP_PAGES に存在しない状態で section_id を参照 → v2 側に 404。AGENT NEO 側では post_id / section_id は常に正規データとして露出しているため、v2 が同期済みかどうかは v2 の責務
- article_id と post_id の bidirectional mapping が不整合（削除後に article_id が残留）→ 移行・削除 hook で mapping も削除。`_agent_neo_article_id` を `post_delete` フックで cleanup
- SECTION_METRICS_DAILY への書き込みが seo-tool-connector を経由しない（直接書き込み試行）→ seo-tool-connector の専用経路のみ。直接書き込みは 403

失敗ケース:
- seo-tool-connector が v2 側の TRACKING_EVENTS に書き込む際に jsonb の型エラー → 422 `INVALID_EVENT_PAYLOAD`。event shape を contract として定義済みの場合は AGENT NEO 側で pre-validation
- v2 が `/v1/wordpress/pages/sync/<site_id>` で大量データを一括 sync 要求 → rate limit（認証済み 300 req/min）。bulk read 用の `?since` + ETag を使うよう誘導

回復シナリオ:
- section_id の mapping が壊れた → `POST /automation-seo/fit` で section ID confidence を再診断し、mapping を rebuild

---

## REQ-F-028（拡張性保証）

境界条件:
- agent-neo/v1 の破壊的変更を 6 ヶ月未満でリリースしようとする → CI の OpenAPI diff が `BREAKING_CHANGE` を検出。リリースをブロック。v2 として分岐
- 新 block type の `agentNeo.allowedPageTypes` が既存ページタイプと重複 → L3 の block schema validation で `DUPLICATE_PAGE_TYPE_DECLARATION` を検出
- 第三者 plugin が agent-neo/v1 に独自 route を追加しようとする → 名前空間が `agent-neo/` であれば add_action フックで検出し警告（強制的には防げない。※推定: L3 で hook guard を設計）

失敗ケース:
- `Sunset` ヘッダの期限切れ後も旧 client が v1 を呼び続ける → v1 を 410 `GONE` で閉鎖。`Location` ヘッダで v2 を案内
- adapter が新 SNS API の仕様変更に追従しない → adapter のバージョン管理。旧 adapter は `adapter_version_deprecated` フラグ付きで動作継続

回復シナリオ:
- v2 で破壊的変更が入ったが v1 client がまだ存在 → `Deprecation` ヘッダで v1 への 6 ヶ月通知を開始。v1/v2 を併走

---

## REQ-F-029（ページタイプ別アセット振り分け機構）

境界条件:
- LP 専用ブロックを記事ページに配置試行 → エディタ側で警告表示。DB 上に保存されても記事レンダリング時にスキップ（`is_singular('post')` でブロックを除外）
- `agentNeo.jsKB` + `agentNeo.cssKB` の合計が page_type 予算を超えるブロックを使用 → CI の Lighthouse テストで `PAGE_BUDGET_EXCEEDED` を検出し失敗
- `asset-policy.schema.json` が不正 JSON → デフォルト予算値にフォールバック。CI でスキーマ lint 必須

失敗ケース:
- plugin dequeue adapter でホワイトリスト外プラグインを誤って dequeue → 該当プラグインの機能がそのページで動作しない。管理画面の「plugin allowlist」設定から再追加
- CI per-page-type Lighthouse で LP のみ予算超過 → LP 超過バンドル名と超過バイト数を CI レポートに出力。PR をブロック（マージ不可）

回復シナリオ:
- 記事ページで LP バンドルが波及した → `agentNeo.allowedPageTypes` の設定漏れを検出し、block.json を修正して再デプロイ

---

## REQ-F-030（個人版 販売寄与モジュール強化）

境界条件:
- Exit-intent CTA が同一セッションで複数回発火 → セッションストレージにフラグを保持し、1 セッション 1 回のみ表示
- Sticky CTA が画面高さより大きいコンテンツを持つ場合 → CSS `overflow: hidden` + `max-height` で制御。CLS を発生させない（REQ-NF-001b）
- Smart product recommendation で記事文脈解析 API（aseo/v1）が 3 秒以内に返答しない → タイムアウト後は「関連商品」の fallback ブロックをサーバーサイドキャッシュから表示

失敗ケース:
- Click heatmap データ収集 JS がブロックされた（広告ブロッカー）→ 収集できないが機能は継続。計測欠損として記録
- AI suggested CTA の提案が空（記事文脈解析で適合 CTA なし）→ デフォルト CTA を表示。「AI 提案なし」を管理画面に記録

回復シナリオ:
- Exit-intent CTA が誤設定で常に表示された → cta_id を無効化して即座に非表示。次回 AI suggested CTA サイクルで再提案

---

## REQ-F-031（法人版 販売寄与モジュール強化）

境界条件:
- Multi-step form の最終 step で submit せずに離脱 → セッションに入力データを保持（最大 30 分）。再訪時に「入力を再開しますか？」を表示
- Conditional CTA で utm / リファラ / 時間帯の全条件が不一致 → デフォルト CTA を表示（fallback 必須）
- LINE 友だち追加ブロックで LINE 公式アカウントが未設定 → 管理画面に「LINE 公式アカウントを設定してください」通知。ブロックはエラー表示

失敗ケース:
- Multi-step form の中間 step でサーバーエラーが発生 → セッションデータを保持して「一時的なエラー。再試行してください」を表示。submission は保存済み分まで保持
- Webinar registration の外部カレンダー（Google Calendar / Zoom）連携が失敗 → AGENT NEO 内部の submission として保存。外部連携は再試行キューに積む

回復シナリオ:
- 誤った条件設定で全ユーザーにデフォルト CTA が表示された → Conditional CTA の条件を修正・保存後に即時反映（サーバーサイドレンダリングのため）

---

## REQ-F-032（AI 主導 CV 最適化）

境界条件:
- AI suggested CTA の提案で記事文脈解析が 5 秒以内に返答しない → aseo/v1 のタイムアウト。fallback として直近の「人気 CTA」（クリック数上位）を配置
- Personalized hero で utm パラメータが存在しない訪問 → デフォルト Hero を表示（fallback 必須。variant なし訪問は計測対象外）
- Dynamic pricing が外部 API から価格を取得する際に取得失敗 → キャッシュ済み価格を表示。「価格は変動する場合があります」テキストを追加

失敗ケース:
- Smart internal linking の推薦リンクが削除済み記事を指す → リンク挿入前に URL の存在確認。404 が返る URL は除外
- AI suggested CTA の承認 gate で承認者不在（法人版）→ 提案は「承認待ち」キューに残留。管理画面にリマインダー通知

回復シナリオ:
- AI 提案 CTA が CV 下落をもたらした → `/ctas/{cta_id}/apply` で手動 rollback。A/B テストの loser を手動で default に戻す

---

## REQ-F-033（CV 設計監査機能）

境界条件:
- LP に CTA が 10 件以上ある（cta.overload）→ `ui-risk.schema.json` で `cta.overload` を検出。severity: high で報告。自動除去はしない
- L5 Visual Refinement 以外のタイミングで監査を呼ぶ → API は常時呼び出し可能。L5 タイミングは CI フック（オプション）で強制
- `proof.too_late` 検出基準が定義されていない（セクション順序が固定でない場合）→ セクション出現順序と「最初の CTA からの距離」で判定（L3 でアルゴリズム定義が必要）

失敗ケース:
- CV 監査の自動検出が false positive を返す（affiliate.disclosure_weak の誤検知）→ 「無視」フラグを管理画面から設定可能。ただし「無視」記録は audit log に残す
- 監査レポート生成でページ解析がタイムアウト（非常に大きな LP）→ 部分レポートを返し `meta.partial: true` を付与

回復シナリオ:
- 監査で `hero.vague` が検出されたが修正が難しい → AI suggested rewrite（REQ-F-022）で Hero セクションを rewrite して再監査

---

## REQ-F-034（認知バイアスパターンライブラリ）

境界条件:
- scarcity パターンで「残り 0 件」を表示 → ゼロ件表示は景表法的にグレー。`scarcity.min_count: 1` のバリデーションで 0 件表示を禁止
- authority パターンで受賞・資格が未検証の状態でブロックを公開 → `trust-layer.schema.json` の `reviewer` / `verified_at` フィールドが空の場合に警告
- social proof の利用者数が「100 万人以上」等の誇大表現 → `AffiliateDisclosureGuard` が誇大数値をパターンマッチで検出し警告（正規表現ルールを定義）

失敗ケース:
- a11y 配慮（aria-label 等）が欠如したパターンを公開 → axe-core 検証（REQ-F-036 の検証パイプライン）で `a11y_violation` として apply をブロック（警告のみまたはブロックは L3 で決定）
- 「過度な恐怖訴求」の自動検出が false negative（見逃し）→ L5 の人間レビューを必須化。自動検出は補助ツール

回復シナリオ:
- 誇大表現を含むパターンが公開されてしまった → ブロックの `content` を修正し再保存。修正前後の diff を audit log に記録

---

## REQ-F-035（AI フリーフォーム HTML/CSS ブロック）

境界条件:
- ガイドモードで AI 生成 HTML に必須 anchor（`data-agent-section-id`）が含まれない → slot の `required_attributes` チェックで apply ブロック
- フリーモードで 200KB の HTML を LP 記事（予算 80KB）に貼り付け → budget 超過で apply ブロック。違反バイト数を表示
- 同一ブロックを 2 ユーザーが同時に編集 → ETag mismatch で後着ユーザーが 412。最新内容を取得して再編集を促す

失敗ケース:
- ガイドモードで Automation SEO が HTML 生成に失敗 → 「AI 生成に失敗しました。フリーモードで直接入力してください」にフォールバック
- フリーモードで CSS の syntax error → CSS scope 付与処理でパースエラー。422 `INVALID_CSS`. 違反行番号を返す

回復シナリオ:
- apply 後にデザインが崩れた → reusable-part CPT の version 履歴から旧バージョンを復元。または rollback_id を使用

---

## REQ-F-036（AI HTML/CSS 検証パイプライン）

境界条件:
- `<script>` タグが HTML の深いネスト内に存在 → `wp_kses` 拡張 allowlist の recursive sanitize で除去
- `javascript:void(0)` の `javascript:` URL → 422 `FORBIDDEN_URL_SCHEME`
- 200KB の HTML で sanitize 処理が 10 秒以上かかる → PHP タイムアウト前にキャンセル。422 `CONTENT_TOO_COMPLEX`

失敗ケース:
- axe-core の WCAG 検証で偽陽性（false positive）→ 「このエラーを無視」を承認者が override 可能。override は audit log に記録
- CSS scope 付与で selector の prefix が 512 文字超の場合に CSS が壊れる → prefix + selector の長さ制限チェック。超過した selector は `SELECTOR_TOO_LONG` として報告

回復シナリオ:
- sanitize で意図したコンテンツが削除された → sanitize ルール（allowlist）を管理画面から調整（高権限ユーザーのみ）。変更は audit log に記録

---

## REQ-F-037（Slot ベース Blueprint と編集領域制限）

境界条件:
- AI が locked slot（footer）に HTML を書き込もうとする → 403 `LOCKED_SLOT`
- Hero slot の `max_css_kb=10` に対し 15KB の CSS を送信 → 422 `SLOT_BUDGET_EXCEEDED`。超過バイト数と slot 名を返す
- `must_contain: button` の slot に button なしの HTML を apply → 422 `REQUIRED_ELEMENT_MISSING`

失敗ケース:
- blueprint.json に存在しない slot_id を指定して apply → 404 `SLOT_NOT_FOUND`
- slot の `allowed_blocks` に含まれないブロックタイプを配置 → `BLOCK_TYPE_NOT_ALLOWED`. 利用可能ブロック一覧を返す

回復シナリオ:
- locked slot が誤って外れた（blueprint 設定ミス）→ `blueprint.json` を修正・再保存。既存コンテンツへの影響なし

---

## REQ-F-038（HP/LP デザイン編集サンドボックス Tier 1）

境界条件:
- preview token が 24 時間で期限切れ後にアクセス → 404 `PREVIEW_TOKEN_EXPIRED`. 新規 preview token の発行を促す
- preview と production に同時に変更 apply が競合 → 楽観ロックで後着が 412。preview apply を優先する設計（※推定: L3 で policy 決定）
- version 履歴が N 版を超える（古い版の削除）→ LRU（最近使用でない順）で自動削除。削除前に「N 版を超えました。最古の版を削除します」通知

失敗ケース:
- preview meta の DB 書き込み失敗 → 500 `PREVIEW_SAVE_FAILED`. preview が作成されない。rollback は不要（元の production は変更なし）
- apply（preview → production）途中で断絶 → transaction rollback。production は変更前の状態を維持

回復シナリオ:
- apply 失敗後も preview meta は残存 → 再 apply を試行。成功しない場合は preview を削除して再作成

---

## REQ-F-039（HP/LP デザイン編集サンドボックス Tier 2）

境界条件:
- Automation SEO 接続なしで Tier 2 機能を呼ぶ → 503 `AUTOMATION_SEO_UNAVAILABLE`. Tier 1 への誘導メッセージ
- 5 branches を超える並行 preview を作成 → Automation SEO 側の上限（5〜N は L3 で決定）。`MAX_PREVIEW_BRANCHES_EXCEEDED`
- Tier 2 で採用した branch の apply が AGENT NEO 側で 412 → Automation SEO に ETag 不一致を通知。ETag 取得 → 再 apply を自動リトライ（最大 3 回）

失敗ケース:
- Automation SEO の v2 PostgreSQL が一時停止 → 進行中の A/B テストが停止。AGENT NEO 側は production に変更なし。Tier 1 に自動切替（Write Authority Lock OFF の場合）
- 3 並行 preview branch のうち 1 つを採用後に他の 2 つを削除しそびれた → 「archive」操作のみで削除はしない設計（計測データ保護）

回復シナリオ:
- Tier 2 障害で進行中の最適化ループが止まった → Automation SEO 復旧後に `/automation-seo/fit` で状態確認。ループを手動で再起動

---

## REQ-F-040（Write Authority Lock）

境界条件:
- Write Authority Lock ON 状態で agent-neo/v1 直接 PATCH を試みる → 423 `WRITE_AUTHORITY_LOCKED`（aseo/v1 経由のみ許可）
- Write Authority Lock ON 状態で aseo/v1 が障害で使えない → 全 Write が不可。緊急時解除は管理者が管理画面でモードを OFF に（audit log 記録必須）
- Lock の ON/OFF を低権限ユーザーが変更しようとする → 403 `CAPABILITY_INSUFFICIENT`（manage_options 以上が必要）

失敗ケース:
- Lock を ON にしたまま aseo/v1 のサブスクを解約 → 全編集不可。管理画面 UI もロック。緊急解除手順をドキュメントに必須化
- Lock ON 状態で WP CLI から直接 DB を更新 → AGENT NEO の API レイヤーをバイパスする操作は audit log に残らない。セキュリティ観点で `--dry-run` 必須設計

回復シナリオ:
- Lock が誤って ON になった → 管理者が管理画面から OFF に変更。audit log に「Lock 変更者・変更日時・変更前後の状態」を記録

---

## REQ-F-041（記事編集経路）

境界条件:
- LP（`page_template=lp`）を WP 標準エディタで直接公開しようとする → サンドボックス必須ルールで publish をブロック。「LP は Tier 1/2 サンドボックスを経由してください」エラー（ACC-041a）
- 記事の WP revision 数が上限（WP_POST_REVISIONS 設定値）に達した → 最古の revision が自動削除される。AGENT NEO は WP 標準 revision を使用するため制御しない
- aseo/v1 経由の記事更新で post_status が `draft` のまま `publish` に変更 → Automation SEO 側の意図的操作として許可。audit log に「経由: aseo/v1」を記録

失敗ケース:
- 記事を WP 標準エディタで保存中に同時に aseo/v1 が PATCH を実行（race condition）→ WP の `update_post` は最後の書き込みが優先。audit log で後着を記録
- aseo/v1 経由で記事に LP 専用ブロックを埋め込んで保存 → page_type 隔離ルールでフロント表示時にブロックをスキップ。DB 上には保存される（※推定: 保存も拒否するか否かは L3 で決定）

回復シナリオ:
- aseo/v1 と WP エディタの並行編集で内容が混在した → WP revision から意図した状態を特定して手動 revert

---

## REQ-F-042（外部エディタアクセス制御）

境界条件:
- 外部 AI エディタが wp/v2 経由で構造的書き込みを試みる → 403 `EXTERNAL_EDITOR_FORBIDDEN`。audit log に「経路: wp/v2、結果: 拒否、理由: 外部エディタアクセス制御」を記録（ACC-042）
- Authorized な外部エディタが agent-neo/v1 への認証情報を持たずにアクセス → 401 `UNAUTHORIZED`
- rate limit 内に大量の 403 を発生させる（ブルートフォース探索）→ IP ベースの rate limiting + 連続失敗で一時 ban（REQ-NF-002）

失敗ケース:
- audit log の書き込みが失敗した状態で拒否操作が発生 → 拒否は継続（audit log 失敗でも拒否を緩和しない）。audit log 失敗は admin notice で通知
- 外部エディタが User-Agent を偽装して agent-neo/v1 に見せかける → Application Password / API キーの有無で判断。UA 偽装だけでは通過できない設計

回復シナリオ:
- 誤って内部ツールが 403 された → allowlist に内部ツールの Application Password / IP を追加

---

## REQ-F-043（Open Editor Bridge Plugin）

境界条件:
- Bridge Plugin を有効化しているが月額サブスク期限切れ → Bridge が拒否。「サブスクを更新してください」メッセージを返す（ACC-043b）
- Whitelisted 外のエディタが Bridge 経由でアクセス → 403 `EDITOR_NOT_WHITELISTED`. whitelisted エディタの一覧を返す
- Bridge 経由でスロット制約違反のコードを送信 → 検証パイプラインで 422。エラーをエディタに返す（ACC-043a）

失敗ケース:
- Bridge Plugin の互換テストが通らない（外部エディタの API 変更）→ 非互換エディタからのアクセスを 503 `EDITOR_API_INCOMPATIBLE` で拒否。Bridge Plugin の緊急アップデートが必要
- サブスク認証サーバーが一時停止 → grace_period（24 時間 ※推定）。grace_period 中は Bridge を許可継続。grace_period 終了後は拒否

回復シナリオ:
- サブスク更新後も Bridge が拒否し続ける → `/license/validate` で強制再検証。キャッシュクリアが必要な場合は `wp cache flush`

---

## エッジケース複雑度ランキング（上位 3 件）

以下は境界条件・失敗ケース・回復シナリオの組み合わせで最も複雑なエッジケースを持つ REQ-F。

### 1位: REQ-F-021（部分更新性）

理由:
- block_id の存在確認・他 post 所有チェック・楽観ロック（ETag）・page_type 予算・idempotency_key 競合という 5 層のガードが必要
- idempotency_key の「同 key 異 payload」衝突は replay 攻撃との区別が困難
- rollback 可能な block-level version 履歴と API failure 時の整合性を同時に保証する必要がある

### 2位: REQ-F-024（AI 自律 A/B テスト機構）

理由:
- 非同期 ML 統計判定（Automation SEO 側）と AGENT NEO 側の API の責務境界が複雑
- 法人版承認 gate / 個人版全自動 / 緊急停止 CLI の 3 つの制御フローが並存
- variant 生成 → 配信 → 計測 → 判定 → 採用 のループ中断・復旧シナリオが多岐にわたる

### 3位: REQ-F-038 + REQ-F-039（サンドボックス 2 ティア）

理由:
- Tier 1（AGENT NEO 内蔵）と Tier 2（Automation SEO 側）の切替条件・競合・fallback が複雑
- Write Authority Lock（REQ-F-040）との 3 方向の相互作用がある
- preview token 期限・version 履歴 N 版・branch 数上限・楽観ロックが重なる多層制約
