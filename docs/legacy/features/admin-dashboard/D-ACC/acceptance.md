# admin-dashboard — D-ACC（受入条件）

## 概要

`admin-dashboard` の受入条件は、React 管理画面の各画面が正しく機能し、個人/法人パッケージ別の表示切替・権限制御・REST API 連動・Automation SEO 連携状態表示が正確に動作することを検証する。

## 受入条件テーブル

| ID | 対応要件 | テスト条件 | 期待結果 | 測定方法 |
|---|---|---|---|---|
| ACC-DF-001 | DF-001 | 管理者で WP 管理画面にログインする | サイドバーに「AGENT NEO」メニューと6サブメニューが表示される | 手動確認 |
| ACC-DF-002 | DF-002 | API Keys 画面で新規 API キーを発行する | 発行時にキー全文が1回だけ表示され、次回アクセス時は `****xxxx` でマスク表示される | UI テスト |
| ACC-DF-003 | DF-002 | 発行済み API キーを失効させる | 失効後にそのキーで REST リクエストを送ると 401 が返る | 統合テスト |
| ACC-DF-004 | DF-003 | apply 操作実行後に Activity Log 画面を開く | actor, target_type, action, status が記録されたログ行が表示される | ログ確認テスト |
| ACC-DF-005 | DF-003 | Activity Log をアクション種別「apply」でフィルタする | apply 操作のみが表示される | フィルタ機能テスト |
| ACC-DF-006 | DF-004 | `agent_readonly` ユーザーで管理画面を開く | API Keys 発行ボタン・apply ボタンが非表示または disabled になる | ロール UI テスト |
| ACC-DF-007 | DF-004 | `agent_readonly` ユーザーが apply REST API を直接呼ぶ | 403 が返る（フロントエンド隠蔽に依存しない） | バックエンド権限テスト |
| ACC-DF-008 | DF-005 | seo-tool-connector が正常接続されている状態で Dashboard を開く | 接続状態「Connected」・最終同期日時・API レイテンシが表示される | 統合テスト |
| ACC-DF-009 | DF-005 | seo-tool-connector を切断した状態で Dashboard を開く | 接続状態「Disconnected」・last_error が警告表示される | エラー表示テスト |
| ACC-DF-010 | DF-006 | migrate plan-a を実行中に Migration 画面を開く | 進捗バー・現在ステップ・ETA が表示される | ジョブ進捗テスト |
| ACC-DF-011 | DF-006 | 移行完了後に Migration 画面でロールバックボタンを押す | 確認ダイアログが表示され、確認後に rollback ジョブが起動する | rollback UI テスト |
| ACC-DF-012 | DF-008 | 個人版ライセンスで管理画面を開く | 移行機能・A/B テスト管理・Automation SEO 詳細が非表示またはアップグレード案内に変わる | パッケージ表示テスト |
| ACC-DF-013 | DF-008 | 法人版ライセンスで管理画面を開く | 移行機能・A/B テスト管理・CLI ログが表示される | パッケージ表示テスト |
| ACC-DF-014 | DF-009 | Settings 画面から section タイトルを変更し、Save を押す | dryRun 差分ビューが表示され、「適用する」ボタンが有効になる | dryRun UI テスト |
| ACC-DF-015 | DF-009 | dryRun 差分ビューで「キャンセル」を押す | apply が実行されず、元の値が維持される | UI キャンセルテスト |
| ACC-DF-016 | DF-010 | LP に noindex が誤設定されている状態で Dashboard を開く | SEO 警告カードに対象ページと問題内容が表示される | SEO 状態テスト |
| ACC-DF-017 | DF-012 | License 画面でライセンスキーを activate する | activate 成功後にライセンス種別と機能フラグが更新される | ライセンステスト |
| ACC-DF-018 | DF-013 | Cron が動作していない状態で Dashboard を開く | health check カードに Cron 異常が警告表示される | health check テスト |
| ACC-DF-019 | DNF-004 | 管理画面を初めて開いた際のネットワーク転送量を計測する | 初期 JS bundle が gzip 200KB 以下 | bundle size test |

## 異常系・境界値

| ID | 条件 | 期待動作 |
|---|---|---|
| ACC-DF-ERR-001 | REST API が 503 を返す状態で管理画面を操作する | ユーザー向けエラーメッセージを表示し、管理画面がクラッシュしない |
| ACC-DF-ERR-002 | rate limit（429）に到達した状態で操作する | 「時間をおいて再試行してください」メッセージと Retry-After 秒数を表示する |
| ACC-DF-ERR-003 | 法人専用画面 URL を個人版ユーザーが直接アクセスする | アップグレード案内画面が表示される |
| ACC-DF-ERR-004 | apply 確認ダイアログで確認チェックボックスを押さずに「適用」を押す | 「適用する」ボタンが disabled のまま機能しない |

## 画面別受入条件のカバレッジ

| 画面 | ACC ID |
|---|---|
| Dashboard | ACC-DF-008, 009, 013, 016, 018 |
| API Keys | ACC-DF-002, 003 |
| Activity Log | ACC-DF-004, 005 |
| Migration | ACC-DF-010, 011 |
| Settings | ACC-DF-014, 015 |
| License | ACC-DF-012, 013, 017 |

## E2E テストシナリオ

Playwright を使って以下のフローを自動確認する。

| シナリオ | ステップ |
|---|---|
| API キー ライフサイクル | 発行 → REST 疎通確認 → 失効 → 401 確認 |
| dryRun → apply フロー | Settings で値変更 → dryRun 差分確認 → apply → 変更反映確認 |
| ロールバック | apply 後 → rollback → 元値の確認 |
| ライセンス切替 | 個人版 → 法人版アップグレード → 機能フラグ変更確認 |
| 移行ジョブ進捗 | plan-a 起動 → 進捗バー確認 → 完了後レポート確認 |

## 参照

- L1: ACC-003, ACC-007, ACC-008, ACC-010, ACC-NF-007, ACC-NF-008
- 解析レポート: 28-共通強化プラグイン（§6. Automation SEO側とAGENT NEO側の責務分離）
