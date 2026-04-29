# admin-dashboard — D-REQ-F（機能要件）

## 概要

`admin-dashboard` は AGENT NEO の React ベース管理画面（WP 管理画面に埋め込む形式）の機能要件を定義する feature である。API キー管理・操作ログ表示・権限ロール管理・Automation SEO 連携状態・移行進捗・CLI 実行ログ・個人/法人パッケージ別表示切替を提供する。

管理画面は REST API（`agent-neo/v1`）の UI 補助面として機能し、管理画面に固有のビジネスロジックを持たない。全操作は REST 経由で実行され、管理画面の状態は REST レスポンスから導出される。

## ID 体系

| 接頭辞 | 対象 |
|---|---|
| `DF-` | admin-dashboard 機能要件 |

## 詳細要件

| ID | 要件名 | 説明 | 優先度 | 上位 L1 ID |
|---|---|---|---|---|
| DF-001 | 管理画面 IA | WP 管理画面に「AGENT NEO」メニューを追加し、Dashboard / API Keys / Activity Log / Settings / Migration / License の6画面を提供する | P0 | REQ-F-003 |
| DF-002 | API キー管理 | APIキーの発行・失効・スコープ設定・有効期限設定を管理画面から行える。キー値は発行時一度だけ表示し、以降はマスク表示する | P0 | REQ-F-003, REQ-NF-002 |
| DF-003 | 操作ログ表示 | actor / target_type / target_id / action / status / timestamp でフィルタリング可能な操作ログテーブルを提供する。差分ビューを展開表示できる | P1 | REQ-NF-007 |
| DF-004 | 権限ロール管理 | `manage_options`, `edit_posts`, `agent_readonly` のロール別機能表示を管理画面に実装する。ロール割り当ては WP ユーザーロールに連動する | P0 | REQ-NF-002 |
| DF-005 | Automation SEO 連携状態 | seo-tool-connector の接続状態・最終同期日時・同期エラー・API レイテンシを Dashboard 画面にリアルタイムで表示する | P0 | REQ-F-007 |
| DF-006 | 移行進捗 | 移行ジョブの進捗バー・ステップ（extract/transform/preview/apply）・エラー詳細・ロールバックボタンを Migration 画面に表示する | P1 | REQ-F-008 |
| DF-007 | CLI 実行ログ | WP CLI コマンドの実行履歴（コマンド名・引数ハッシュ・exit code・実行時間）を Activity Log 画面で確認できる | P1 | REQ-NF-007 |
| DF-008 | 個人/法人表示切替 | ライセンス種別（personal/corporate）に応じてメニュー項目・機能フラグ・プランアップグレード案内を表示切替する | P0 | REQ-F-010 |
| DF-009 | dryRun レビュー UI | agent-api の dryRun レスポンス（差分・リスクスコア・影響フィールド）を人間が確認しやすい diff ビューで表示し、apply ボタンを提供する | P0 | REQ-F-002 |
| DF-010 | SEO 状態ダッシュボード | canonical / noindex / robots 設定の異常（重複 canonical, 誤 noindex）を Dashboard 画面に警告で表示する | P1 | REQ-F-011, REQ-NF-018 |
| DF-011 | デザイントークン管理 | design-tokens の現在値・変更プレビュー・JSON インポート/エクスポートを Settings 画面に提供する | P1 | REQ-F-009 |
| DF-012 | ライセンス画面 | ライセンス種別・有効期限・機能フラグ・購入/アップグレードリンク・activate フォームを License 画面に提供する | P0 | REQ-F-010 |
| DF-013 | health check 表示 | REST / DB / Cron / 外部連携の health 状態を Dashboard 画面に常時表示する | P0 | REQ-NF-013 |
| DF-014 | 操作ログのエクスポート | 操作ログを CSV または JSON でエクスポートするボタンを Activity Log 画面に提供する | P2 | REQ-NF-007 |
| DF-015 | CLI チートシート | 主要 WP CLI コマンドとコピー可能なコマンド例を Dashboard 画面または Help モーダルに表示する | P2 | REQ-F-003 |

## 補足・設計指針

**ビジネスロジックを持たない**: 管理画面は REST API のフロントエンドであり、独自のビジネスロジックを含まない。全操作は REST エンドポイントへのリクエストで実装し、管理画面の責務は表示・入力検証・確認 UI に限定する。

**dryRun → apply ワークフロー**: 管理画面から設定を変更する際は必ず dryRun 差分を表示し、ユーザーが確認ボタンを押した後にのみ apply を実行する。この UI ワークフローは全 write 操作に強制する。

**個人/法人の表示差異**: 法人版では A/B テスト管理・移行機能・Automation SEO 連携詳細・CLI ログが表示される。個人版ではアフィリエイト CTA 管理・収益ブロック設定が追加表示される。法人専用機能を個人版で開こうとするとアップグレード案内が表示される。

**Automation SEO 連携状態の詳細**: 接続状態カードには last_sync_at / last_error / retry_count / connector_version を表示し、手動同期ボタン（`POST /jobs` 経由）を提供する。

## dryRun → apply の UI フロー

```
1. ユーザーが Settings で値を変更して「保存する」をクリック
2. フロントエンドが POST /sections/{id}/apply?dry_run=true を送信
3. dryRun レスポンス（diff・risk_score・diff_hash）を差分ビューで表示
4. ユーザーが diff を確認して「適用する」をクリック
5. フロントエンドが diff_hash を含む POST /sections/{id}/apply を送信
6. 変更が適用され、結果を操作ログと画面に反映する
```

このフローは `apply` ボタンが diff_hash 保持中（10分以内）のみ有効になる設計とし、
期限切れ時は「再確認が必要です」のメッセージで dryRun から再開する。

## 管理画面 IA 詳細

```
AGENT NEO
├── Dashboard       状態カード / SEO 警告 / health / CLI チートシート
├── API Keys        発行 / 失効 / スコープ / 有効期限
├── Activity Log    フィルタ / 差分展開 / CSV エクスポート
├── Migration       プラン選択 / 進捗 / ロールバック
├── Settings        design-tokens / blueprint defaults / Automation SEO 接続設定
└── License         種別 / 有効期限 / 機能フラグ / activate / アップグレード
```

## 画面別権限マトリクス

| 画面 | manage_options | edit_posts | agent_readonly |
|---|---|---|---|
| Dashboard | 閲覧 + health 操作 | 閲覧のみ | 閲覧のみ |
| API Keys | 発行 / 失効 / スコープ設定 | - | - |
| Activity Log | 全ログ + エクスポート | 自分の操作のみ | 閲覧のみ |
| Migration | 実行 / ロールバック | - | - |
| Settings | 全設定変更 | - | - |
| License | activate / アップグレード | - | 閲覧のみ |

## 参照

- L1: REQ-F-002, REQ-F-003, REQ-F-007, REQ-F-008, REQ-F-009, REQ-F-010, REQ-F-011, REQ-NF-002, REQ-NF-007
- 解析レポート: 28-共通強化プラグイン（§6. Automation SEO側とAGENT NEO側の責務分離）
