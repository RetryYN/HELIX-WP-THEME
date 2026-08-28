# migration-plugin — D-ACC（受入条件）

## 概要

`migration-plugin` の受入条件は、Plan A/B の移行フロー（抽出・変換・プレビュー・適用・ロールバック）が各対象テーマで正しく機能し、移行元サイトへの破壊的影響がなく、AGENT NEO への変換品質が基準を満たすことを検証する。テーマ別検証は ThemeB → テーマD → テーマC → テーマA旧版 → テーマE の順序で実施する。

## 受入条件テーブル

| ID | 対応要件 | テスト条件 | 期待結果 | 測定方法 |
|---|---|---|---|---|
| ACC-MF-001 | MF-001 | 移行プラグインの管理画面を開く | Plan A と Plan B の説明・所要時間・リスク表示が表示され、プランを選択できる | UI 確認 |
| ACC-MF-002 | MF-002 | ThemeB サイトの WP REST API から投稿・メディア・カテゴリを抽出する | 投稿本文・タイトル・カテゴリ・featured_media が抽出される | 抽出テスト |
| ACC-MF-003 | MF-002 | 抽出中に移行元サイトへ GET 以外のリクエストが発生するか確認する | GET のみが使われ、POST/PUT/DELETE は発生しない | セキュリティ確認 |
| ACC-MF-004 | MF-003 | ThemeB サイトの `lp` CPT 投稿を Plan A で変換する | AGENT NEO の LP blueprint 構造に変換され、section_id が付与される | ThemeB 変換テスト |
| ACC-MF-005 | MF-003 | テーマD サイトの通常投稿を Plan A で変換する | block.json 準拠のブロック構造に変換され、cta_id が付与される | テーマD 変換テスト |
| ACC-MF-006 | MF-003 | テーマC の shortcode を含む投稿を Plan A で変換する | 変換できた shortcode 数と未変換（manual review 必要）一覧がプレビューに表示される | テーマC 変換テスト |
| ACC-MF-007 | MF-003 | テーマA旧版/テーマA のテーマ内 SEO メタを抽出する | canonical/noindex/OGP が AGENT NEO SEO Core 形式に正規化される | テーマA旧版 変換テスト |
| ACC-MF-008 | MF-003 | テーマE の法人 LP ページを Plan A で変換する | Hero/CTA/Feature/Proof の各セクションが section_id 付きで変換される | テーマE 変換テスト |
| ACC-MF-009 | MF-005 | Plan A の変換結果プレビューを確認する | 元コンテンツと変換後の diff・未変換要素リスト・section_id マッピングが表示される | プレビュー UI テスト |
| ACC-MF-010 | MF-006 | プレビュー確認後に「適用する」ボタンを押す | `POST /jobs` が呼ばれ、job_id が返り、移行ジョブが開始される | apply テスト |
| ACC-MF-011 | MF-006 | AGENT NEO Companion Plugin なしで「適用する」ボタンを押す | 「AGENT NEO が必要」メッセージが表示され、apply が実行されない | 依存テスト |
| ACC-MF-012 | MF-007 | apply 完了後にロールバックを実行する | 移行前のコンテンツ状態に戻り、AGENT NEO 側の投稿が削除または元に戻る | ロールバックテスト |
| ACC-MF-013 | MF-008 | 移行実行中に進捗画面を確認する | extract/transform/apply の各ステップ・処理済み件数・エラー件数が表示される | 進捗表示テスト |
| ACC-MF-014 | MF-010 | Yoast SEO が有効化された ThemeB サイトから SEO メタを抽出する | Yoast メタと ThemeB テーマ内メタの両方が取得され、重複が検出・解決される | SEO 正規化テスト |
| ACC-MF-015 | MF-011 | 移行元ページのボタン・ASP リンクから CTA を推定する | 各 CTA に confidence スコアが付与され、0.7 未満は manual review フラグが立つ | CTA 推定テスト |
| ACC-MF-016 | MF-012 | AGENT NEO がない環境で移行プラグインを起動する | 診断・プレビューが実行できる。apply は disabled 状態 | 単体動作テスト |
| ACC-MF-017 | MNF-004 | rollback snapshot の保存が失敗する状態（DB 容量不足等）で apply を試みる | apply がブロックされ、「rollback 保存が失敗したため適用できません」エラーが返る | rollback ガードテスト |
| ACC-MF-018 | MNF-003 | 移行元 URL として `192.168.1.1` を入力する | SSRF_BLOCKED エラーが返り、接続が拒否される | SSRF テスト |
| ACC-MF-019 | MNF-005 | 同一 job_id で apply を2回実行する | 2回目は既存ジョブ結果を返し、重複インポートが発生しない | idempotency テスト |

## テーマ別変換品質基準

| テーマ | 最低変換率 | SEO メタ変換率 | 許容 manual review 率 |
|---|---|---|---|
| ThemeB | 80% | 95% | 20% 以下 |
| テーマD | 85% | 90% | 15% 以下 |
| テーマC | 70% | 90% | 30% 以下 |
| テーマA旧版 / テーマA | 75% | 95% | 25% 以下 |
| テーマE | 75% | 90% | 25% 以下 |

## 異常系・境界値

| ID | 条件 | 期待動作 |
|---|---|---|
| ACC-MF-ERR-001 | 移行元サイトの REST API が認証エラーを返す | 「認証が必要です」メッセージを表示し、ジョブを開始しない |
| ACC-MF-ERR-002 | 500件超の投稿を移行する | 100件/バッチで分割実行し、タイムアウトが発生しない |
| ACC-MF-ERR-003 | ジョブが3回リトライ後も失敗する | dead_letter に移動し、管理画面で再試行ボタンが表示される |
| ACC-MF-ERR-004 | Plan B で LLMRouter が利用不能 | エラーを表示し、Plan A への切り替えを提案する |
| ACC-MF-ERR-005 | 移行プラグインをアンインストールする | ジョブ記録・rollback snapshots・Options が全て削除される |

## テーマ別テスト環境の準備

各テーマの検証には以下の環境を使用する。

| テーマ | テスト環境の準備方法 |
|---|---|
| ThemeB | ThemeB 2.16.x を wp-env で有効化、lp CPT の投稿を作成 |
| テーマD | テーマD の子テーマを wp-env で有効化、タグ・カテゴリ・記事を作成 |
| テーマC | テーマC6 を wp-env で有効化、shortcode 多用の記事を作成 |
| テーマA旧版 / テーマA | テーマA を wp-env で有効化、テーマ内 SEO メタ入力済み記事を作成 |
| テーマE | テーマE G3 を wp-env で有効化、法人 HP・事例ページを作成 |

## テーマ変換品質の計測方法

変換後の `conversion_report.json` から以下の指標を計測する。

| 指標 | 計測方法 |
|---|---|
| 変換率 | `converted_items / total_items * 100` |
| SEO メタ変換率 | `seo_converted / seo_total * 100` |
| manual review 率 | `confidence_below_threshold / total_items * 100` |
| section_id 付与率 | `sections_with_id / total_sections * 100` |

## 参照

- L1: ACC-008, ACC-NF-013, ACC-NF-014, REQ-NF-019, REQ-NF-020
- 解析レポート: 28-共通強化プラグイン（§5. テーマ別に強化できるポイント, §8. 契約サンプル）
