# admin-dashboard — D-REQ-NF（非機能要件）

## 概要

`admin-dashboard` の非機能要件は、React 管理画面が WP 管理画面の制約の中でセキュリティ・性能・アクセシビリティ・互換性・配布品質の基準を満たすことを保証する。特に、管理画面が REST API 層から独立したビジネスロジックを持たないことと、nonce/capability 管理の適切な実装を重点とする。

## 非機能要件の分類

| 観点 | 要件ID | 件数 |
|---|---|---|
| セキュリティ | DNF-001〜003 | 3 |
| 性能 | DNF-004〜006 | 3 |
| アクセシビリティ | DNF-007 | 1 |
| 互換性 | DNF-008〜010 | 3 |
| 信頼性 | DNF-011〜012 | 2 |
| 配布品質 | DNF-013〜014 | 2 |

## 詳細要件

| ID | 要件名 | 観点 | 説明 | 優先度 | 上位 L1 ID |
|---|---|---|---|---|---|
| DNF-001 | nonce 管理 | セキュリティ | WP 管理画面の nonce を React アプリに wp_localize_script で渡し、全 REST リクエストに `X-WP-Nonce` ヘッダーを付与する | P0 | REQ-NF-002 |
| DNF-002 | API キー表示保護 | セキュリティ | API キーは発行時1度だけプレーンテキスト表示し、以降は `****xxxx` 形式でマスクする。コンソールログへの出力を禁止する | P0 | REQ-NF-002 |
| DNF-003 | ロール別 UI 制御 | セキュリティ | 権限のない画面・ボタンは非表示または disabled にする。フロントエンドの制御は補助であり、バックエンド（REST）で capability を再検証する | P0 | REQ-NF-002 |
| DNF-004 | 初期表示速度 | 性能 | 管理画面の初期 JS bundle は 200KB（gzip）以下とし、初期表示を3秒以内に完了する | P1 | REQ-NF-001 |
| DNF-005 | コード分割 | 性能 | 各管理画面（API Keys / Activity Log / Migration 等）を lazy import でコード分割し、不要な画面の JS を初期読み込みに含めない | P1 | REQ-NF-001 |
| DNF-006 | ポーリング間隔 | 性能 | health check・ジョブ進捗の自動更新ポーリングはデフォルト30秒とし、タブ非表示時は停止する（Visibility API 使用） | P1 | REQ-NF-001 |
| DNF-007 | WCAG 2.2 AA | アクセシビリティ | 管理画面の全インタラクション要素（テーブル・モーダル・フォーム・ドロップダウン）が keyboard 操作可能であること | P1 | REQ-NF-005 |
| DNF-008 | WP 管理画面スタイル | 互換性 | `@wordpress/components` または WP 管理画面の CSS クラスを使用し、テーマの CSS と競合しないようにする | P0 | REQ-NF-013 |
| DNF-009 | WP 6.6+ 互換 | 互換性 | WP 6.6 以上の Block Editor・wp.apiFetch・Gutenberg コンポーネントとの互換性を確保する | P0 | REQ-F-001 |
| DNF-010 | 外部プラグイン競合 | 互換性 | Yoast SEO・WooCommerce・Contact Form 7 有効時に管理画面が正常動作することを smoke test で確認する | P1 | REQ-NF-010 |
| DNF-011 | エラーハンドリング | 信頼性 | REST API エラー（401/403/429/500）をユーザー向けメッセージに変換し、技術的詳細はアコーディオン内に表示する | P0 | REQ-NF-007 |
| DNF-012 | 操作確認ダイアログ | 信頼性 | apply / rollback / license activate など不可逆操作は確認ダイアログを必須とする。チェックボックス型確認を採用してAccidentalクリックを防ぐ | P0 | REQ-NF-013 |
| DNF-013 | build 成果物 | 配布品質 | `npm run build` で WP 管理画面読み込み可能な ES5 互換 JS を生成する。ビルド成果物を Theme Core から分離して Companion Plugin に含める | P0 | REQ-NF-008 |
| DNF-014 | 国際化対応 | 配布品質 | React コンポーネントの文字列は `@wordpress/i18n` の `__()` を使用し、`wp i18n make-json` で .json ファイルに変換できる状態にする | P1 | REQ-NF-006 |

## 補足・設計指針

**manage_options vs agent_readonly**: `manage_options` ユーザーは全機能を使える。`edit_posts` ユーザーは投稿操作と Activity Log 閲覧のみ。`agent_readonly` は Dashboard・ログ閲覧のみで write 操作 UI を隠蔽する。フロントエンドの隠蔽はあくまで UX 補助であり、REST 側の capability チェックが正本。

**ライブアップデート**: ジョブ進捗（移行・blueprint rebuild）はポーリングまたは WordPress の heartbeat API を使い、ページリロードなしで状態更新する。

**React アーキテクチャ方針**: `@wordpress/components` を優先使用し、MUI/Tailwind などの外部 CSS フレームワークを管理画面内で使う場合は WP 管理画面の既存スタイルとの衝突を防ぐためにスコープを限定する。WP 管理画面は`enqueue_scripts` フックで `in_admin` 条件付きで JS を読み込み、フロントエンドに管理画面用 JS を配信しない。

**テスト方針**: React コンポーネントは @testing-library/react でユニットテストを実施する。Playwright による E2E テストで API キー発行→失効・apply→rollback の一連フローを自動確認する。REST モックは msw（Mock Service Worker）を使用する。

**エラー境界の実装**: React の ErrorBoundary を各ページコンポーネントに配置し、個別画面のクラッシュが管理画面全体に波及しないようにする。ErrorBoundary でキャッチしたエラーは操作ログに記録する。

## バージョン管理・リリース連動

| 項目 | 方針 |
|---|---|
| JS/CSS バンドル | Companion Plugin の zip に含める。Theme Core には含めない |
| バージョニング | Companion Plugin のバージョンと同期する |
| 後方互換 | REST API の contract version が変わった場合、管理画面も同時アップデート対象とする |

## セキュリティヘッダー要件

WP 管理画面の React アプリが読み込まれる admin ページで以下のレスポンスヘッダーを設定する。

| ヘッダー | 値 | 目的 |
|---|---|---|
| `X-Content-Type-Options` | `nosniff` | MIME スニッフィング防止 |
| `X-Frame-Options` | `SAMEORIGIN` | クリックジャッキング防止 |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | リファラー制御 |
| `Content-Security-Policy` | `script-src 'self' 'nonce-{nonce}'` | XSS 防止 |

WP 管理画面のデフォルトヘッダーを上書きしないよう `add_action('admin_head', ...)` で追加する。

## 状態管理設計

| 状態種別 | 管理方法 | 永続化 |
|---|---|---|
| ライセンス種別・フラグ | REST `GET /features` から取得 | なし（毎回取得） |
| API キー一覧 | REST `GET /api-keys` から取得 | なし |
| 操作ログ | REST `GET /logs` から取得、ページネーション | なし |
| ジョブ進捗 | REST `GET /jobs/{id}` ポーリング | なし |
| UI 設定（フィルタ状態等） | localStorage（オプション） | あり |

## 参照

- L1: REQ-NF-001, REQ-NF-002, REQ-NF-005, REQ-NF-006, REQ-NF-007, REQ-NF-008, REQ-NF-013
- 解析レポート: 28-共通強化プラグイン（§7. MVP優先順位）
