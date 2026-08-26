# migration-plugin — D-REQ-NF（非機能要件）

## 概要

`migration-plugin` の非機能要件は、移行作業が安全・可逆・透明性のある形で実行され、移行元サイトへの破壊的影響を与えないことを保証する。性能・セキュリティ・互換性・配布品質・信頼性の観点を定義する。

## 詳細要件

| ID | 要件名 | 観点 | 説明 | 優先度 | 上位 L1 ID |
|---|---|---|---|---|---|
| MNF-001 | 移行元サイトへの読み取り専用アクセス | セキュリティ | WP REST API 抽出は GET のみ使用し、移行元サイトへの書き込みは行わない | P0 | REQ-NF-002 |
| MNF-002 | 認証トークンの最小保持 | セキュリティ | 移行元サイトの Application Password / APIキーはジョブ実行中のみメモリに保持し、DB への平文保存を禁止する | P0 | REQ-NF-002, REQ-NF-004 |
| MNF-003 | SSRF 対策 | セキュリティ | 移行元 WP REST URL として private IP・ループバック・リンクローカルを拒否する。移行元は公開 URL のみを許可する | P0 | REQ-NF-002, REQ-NF-014 |
| MNF-004 | rollback 保証 | 信頼性 | apply 実行前に全変換対象の snapshot を保存し、rollback が失敗しないことを事前確認する。rollback 保存失敗時は apply をブロックする | P0 | REQ-F-008, REQ-NF-014 |
| MNF-005 | 冪等性（idempotency） | 信頼性 | 同一 job_id での apply 再実行は2回目以降スキップされ、既存結果を返す | P0 | REQ-NF-014 |
| MNF-006 | 大量コンテンツ対応 | 性能 | 500件以上の投稿を移行する場合はバッチ分割（100件/バッチ）で非同期ジョブを実行し、タイムアウトを防ぐ | P1 | REQ-NF-001 |
| MNF-007 | 移行ジョブの DLQ | 信頼性 | 移行ジョブが3回リトライ後も失敗した場合は dead_letter に移動し、管理画面で個別再試行できる | P1 | REQ-NF-014 |
| MNF-008 | メディアファイル移行 | 性能 | 画像・PDFは移行元 URL から直接取得せず、サーバー側での遅延インポートをデフォルトとする。一括取得オプションはタイムアウトガードを適用する | P1 | REQ-NF-001 |
| MNF-009 | WP 6.6+ 互換 | 互換性 | WP 6.6 以上で動作確認する。WP REST API v2 仕様に依存し、カスタム WP REST ルートには依存しない | P0 | REQ-F-001 |
| MNF-010 | PHP 8.1+ 互換 | 互換性 | PHP 8.1〜8.3 で CI を実行する | P0 | REQ-F-001 |
| MNF-011 | 移行元テーマ非依存 | 互換性 | ThemeB/Cocoon/AFFINGER/JIN/Lightning のテーマファイルに直接依存しない。WP REST API と HTML 解析のみで変換する | P0 | REQ-NF-010 |
| MNF-012 | wp.org 申請品質 | 配布品質 | Theme Check・PHPCS/WPCS・Plugin Review Guidelines を通過する品質を維持する。未エスケープ出力・直接 DB アクセス・hardcoded credentials を排除する | P0 | REQ-NF-016 |
| MNF-013 | uninstall cleanup | 配布品質 | プラグインアンインストール時に移行ジョブ記録・rollback snapshots・設定 Options を削除する | P0 | REQ-NF-016 |
| MNF-014 | 変換品質レポート | 運用 | 移行完了後に変換率・未変換要素数・manual review 必要件数・SEO メタ変換率をレポートとして管理画面に表示する | P1 | REQ-NF-007 |
| MNF-015 | 段階的移行の整合性 | 信頼性 | 部分移行を複数回実行した場合に同一 post_id が重複インポートされないよう、インポート済み記録を保持する | P1 | REQ-F-008 |

## 補足・設計指針

**移行は読み取り専用が原則**: migration-plugin の本質的な責務は「抽出・変換・プレビュー」であり、書き込みは AGENT NEO Companion Plugin の `POST /jobs` に委譲する。この責務分離により、移行プラグインのバグが移行先サイトを破壊するリスクを低減する。

**confidence スコアの用途**: CTA 推定・セクション分類・SEO メタ正規化の各処理では `confidence` スコアを出力する。`0.7` 未満のアイテムは管理画面で manual review フラグを付け、ユーザーが確認してから apply できるようにする。

**LLMRouter との通信コスト**: Plan B の AI 再構築は LLMRouter のトークンコストが発生する。移行前にコスト試算をプレビューに表示し、ユーザーが承認してから LLMRouter を呼び出す。

**段階的移行の重複防止**: 部分移行を複数回実行する場合に備え、インポート済み post_id リストを `agent_neo_migration_log` Options に保持する。同一 post_id の2回目のインポートは skip してログに記録する。インポートログは移行プラグインのアンインストール時に削除する。

**メモリ制限対策**: PHP の `memory_limit` が低い環境では、1バッチあたりのアイテム数を自動的に削減する。WP-CLI 実行時は `--url` と `--path` 引数で対象を明示し、CLI 経由では WP のメモリ制限が事実上外れることが多い点を活用する。

**wp.org Plugin Review Guidelines 準拠チェックリスト**:
- README.txt に stable tag / requires at least / tested up to を記載する
- `sanitize_*` / `esc_*` / `wp_nonce_*` を適切に使用する
- 外部 HTTP リクエストに `wp_remote_get/post` を使い `file_get_contents` を使わない
- プラグインの設定を `register_setting` + `sanitize_callback` で登録する
- アンインストール処理を `uninstall.php` に記述する（フックではなく）

**confidence 低下時の UX**: `0.4` 未満の low confidence 項目はプレビュー画面でハイライト表示し、ユーザーに手動編集を促す。`0.4〜0.69` は medium として注意アイコン付きで表示。`0.7` 以上は auto-approved として適用可能とする。この閾値は管理画面の Settings から変更できる。

**テーマ別特殊処理の設計**: テーマ固有の変換ロジック（ThemeB の `lp` CPT 処理・JIN の SEO メタ読み取り等）は `ThemeAdapter` インターフェースを実装した各 Adapter クラスに閉じ込め、テーマ固有コードがコアロジックに混入しない設計にする。各 Adapter は WP REST API と HTML 解析のみを使い、テーマ PHP コードには依存しない。

**rollback snapshot のストレージ**: rollback snapshot は WP uploads ディレクトリに JSON で保存し、デフォルト保持期間は30日とする。WP CLI で `wp agent-neo migrate rollback --job_id=<id>` を実行した場合と管理画面からの実行で同一のロジックを使う。

## 非機能要件サマリー

| 観点 | 要件数 | 最重要 |
|---|---|---|
| セキュリティ | MNF-001〜003 | 読み取り専用・SSRF ガード・認証トークン最小保持 |
| 信頼性 | MNF-004〜005, MNF-007, MNF-015 | rollback 保証・idempotency・DLQ |
| 性能 | MNF-006, MNF-008 | 500件バッチ分割・メディア遅延インポート |
| 互換性 | MNF-009〜011 | WP 6.6+・テーマ非依存 |
| 配布品質 | MNF-012〜013 | wp.org 準拠・アンインストール cleanup |
| 運用 | MNF-014 | 変換品質レポート |

## テーマ別移行アダプタ設計方針

| テーマ | 主な Adapter 処理 | 非依存項目 |
|---|---|---|
| ThemeB | `lp` CPT 抽出、ブログパーツ変換、REST 経由 settings 読み取り | ThemeB PHP クラス直接参照 |
| Cocoon | 通常投稿/ウィジェット抽出、shortcode テキスト変換 | Cocoon PHP 関数 |
| AFFINGER | 設定 Options 読み取り（WP REST 経由）、shortcode カタログマッチ | AFFINGER PHP |
| JIN / テーマA | テーマ内 SEO meta post_meta 読み取り、classic template HTML 解析 | JIN PHP クラス |
| Lightning | フォームプラグイン (VK Blocks) 依存 CTA の URL 推定 | Lightning PHP |

## 参照

- L1: REQ-NF-002, REQ-NF-004, REQ-NF-010, REQ-NF-014, REQ-NF-016, REQ-NF-019, REQ-NF-020
- 解析レポート: 28-共通強化プラグイン（§2. 不都合な真実, §6. 責務分離）
