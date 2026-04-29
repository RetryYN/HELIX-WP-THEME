# package-matrix — D-ACC（受入条件）

## 概要

`package-matrix` の受入条件は、ライセンス検証・feature flag 制御・個人/法人パッケージ境界・Theme Core / Companion Plugin の責務分離が正確に機能することを検証する。

## 受入条件テーブル

| ID | 対応要件 | テスト条件 | 期待結果 | 測定方法 |
|---|---|---|---|---|
| ACC-PF-001 | PF-001 | 有効な個人版ライセンスキーで `wp agent-neo license activate` を実行する | `personal` パッケージが有効化され、feature フラグが個人版に設定される | ライセンステスト |
| ACC-PF-002 | PF-001 | 無効なライセンスキーで activate を試みる | エラーが返り、機能フラグが変更されない | ライセンスエラーテスト |
| ACC-PF-003 | PF-003 | `GET /wp-json/agent-neo/v1/features` を実行する | `{"personal": {"affiliation_blocks": true, "corporate_lp": false, ...}}` の形式でフラグ一覧が返る | API contract test |
| ACC-PF-004 | PF-004 | 個人版環境で法人専用 REST エンドポイント（A/B テスト管理）を呼ぶ | 403 + `FORBIDDEN` が返る | 機能ガードテスト |
| ACC-PF-005 | PF-004 | 個人版環境で管理画面の法人専用メニューを開こうとする | アップグレード案内画面が表示される | UI ガードテスト |
| ACC-PF-006 | PF-005 | 個人版から法人版へアップグレードする | 既存 API キー・デザイントークン・CTA 設定が引き継がれ、法人専用機能が即時開放される | アップグレードテスト |
| ACC-PF-007 | PF-006 | `functions.php` と全テンプレートに `rest_api_init` hook がないことを静的解析する | CI PHPCS チェックが 0 件エラーで通過する | CI static analysis |
| ACC-PF-008 | PF-007 | Companion Plugin を無効化した状態でテーマを有効化する | テーマが fatal error なしで表示される（Companion Plugin 無しでも表示が壊れない） | dependency test |
| ACC-PF-009 | PF-008 | Companion Plugin なしで移行プラグインを起動する | 診断・プレビューは機能し、apply ボタンは「AGENT NEO が必要」と表示される | 移行プラグイン依存テスト |
| ACC-PF-010 | PF-010 | ライセンスサーバーを遮断した状態で48時間以内に操作する | grace period 内は機能が維持される | offline fallback test |
| ACC-PF-011 | PF-010 | ライセンスサーバーを遮断した状態で49時間後に操作する | readonly モードに降格し、apply 操作がブロックされる | grace period 超過テスト |
| ACC-PF-012 | PF-011 | Companion Plugin をアンインストールした後に WP Options を確認する | `agent_neo_license_*`・API キー・操作ログ Option が存在しない | クリーンアップテスト |
| ACC-PF-013 | PNF-007 | CI で Theme Core に REST ルート登録コードを追加した PR を作成する | CI PHPCS が失敗する | CI ガードテスト |
| ACC-PF-014 | PNF-008 | Theme Core・Companion Plugin の全ファイルライセンスヘッダーを確認する | GPL-2.0-or-later のライセンスが全 PHP ファイルに記載されている | ライセンス監査 |
| ACC-PF-015 | PNF-001 | WP データベースの Options テーブルで license_key を検索する | ハッシュ値で保存されており、平文が存在しない | DB 監査 |

## 異常系・境界値

| ID | 条件 | 期待動作 |
|---|---|---|
| ACC-PF-ERR-001 | ライセンスサーバーがタイムアウトを返す | エラーをログに記録し、Transient キャッシュを継続使用する |
| ACC-PF-ERR-002 | 同一 license_key を2サイトで同時使用する | サーバー側で重複検出し、後者は `FORBIDDEN` を返す（ライセンスポリシーによる） |
| ACC-PF-ERR-003 | feature flag のフロントエンド改ざんを試みる（localStorage 書き換え等） | バックエンドの REST 権限チェックが守り、操作が実行されない |
| ACC-PF-ERR-004 | アップグレード途中でサーバーエラーが発生する | アップグレード前の状態に戻り、一部機能が開放されない中途半端な状態にならない |

## 受入条件のカバレッジ

| 要件 | ACC ID |
|---|---|
| PF-001 ライセンス検証 | ACC-PF-001, 002 |
| PF-003 feature flag API | ACC-PF-003 |
| PF-004 機能ガード | ACC-PF-004, 005 |
| PF-005 アップグレードパス | ACC-PF-006 |
| PF-006 Theme Core 境界 | ACC-PF-007, 013 |
| PF-007 Companion Plugin 境界 | ACC-PF-008 |
| PF-008 移行プラグイン独立 | ACC-PF-009 |
| PF-010 offline fallback | ACC-PF-010, 011 |
| PF-011 最小保持 | ACC-PF-015 |
| PNF-001 安全保存 | ACC-PF-015 |
| PNF-006 クリーンアップ | ACC-PF-012 |
| PNF-008 GPL 互換 | ACC-PF-014 |

## アップグレードシナリオの検証手順

1. 個人版ライセンスを有効化し、現在の設定・API キー・ログを記録する
2. 法人版 upgrade_key を使ってアップグレードを実行する
3. 全設定・API キー・ログが引き継がれていることを確認する
4. 法人版専用機能フラグが true になっていることを `GET /features` で確認する
5. 個人版専用機能フラグは true のままであることを確認する（上位互換）

## CI パイプラインでの境界テスト

| テスト | CI 実行タイミング |
|---|---|
| Theme Core PHPCS（rest_api_init 禁止） | commit 時 |
| feature flag REST contract test | PR マージ時 |
| ライセンス offline grace period test | リリース前 |
| アンインストールクリーンアップ test | リリース前 |
| アップグレードシナリオ E2E test | リリース前 |

## 参照

- L1: ACC-010, ACC-NF-002, ACC-NF-003, ACC-NF-004
- 解析レポート: 28-共通強化プラグイン（§2. 不都合な真実）
