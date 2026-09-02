# fse-customization — D-ACC（受入条件）

## 概要

`fse-customization` の受入条件は、スタイルバリエーション・記事用テンプレートパーツ・Global Styles 上書き保持・パターン編集可能性が正確に機能することを検証する。受入条件 ID は `FC-ACC-` 接頭辞で採番する。

## 受入条件テーブル

| ID | 対応要件 | テスト条件 | 期待結果 | 測定方法 |
|---|---|---|---|---|
| FC-ACC-001 | REQ-F-045, FC-001, FC-002 | サイトエディタ「スタイル」メニューを開き、バリエーション一覧を確認する | 「ライト（標準）」と「ダーク」の 2 バリエーションが表示される。切り替えを実行するとフロントエンドの配色が変わる | サイトエディタ手動確認 |
| FC-ACC-002 | REQ-F-045, REQ-NF-026-a, FC-002 | dark バリエーションを適用した状態で axe-core 検証を実行する（対象: 記事ページ / HP） | axe-core で critical / serious ゼロ。本文テキスト・ボタン・リンク・カテゴリバッジのコントラスト比が WCAG 2.2 AA 基準を満たす（通常テキスト 4.5:1 以上 / 大テキスト 3:1 以上） | axe-core 自動検証（`composer test:security` 内 axe チェック相当） |
| FC-ACC-003 | REQ-F-045, REQ-NF-026-b, FC-007 | README（themes/agent-neo-theme/README.md）の「業種別バリエーションの作り方」手順に従い、styles/light.json をコピーして palette の accent 色だけを変えた新ファイルを styles/ に保存する | サイトエディタのバリエーション一覧に新 variation が表示され、選択すると accent 色が変化する。記事・ブロック・ボタンの配色が新 palette を参照していることを目視確認できる | 手順書通りの操作 + サイトエディタ手動確認 |
| FC-ACC-004 | REQ-F-046, FC-004, FC-005, FC-006 | single.html を参照する記事ページを表示する。改修前後で DOM を比較する | breadcrumb / h1 タイトル / エントリーメタ（日付・著者・カテゴリ・読了時間）/ タグ / シェアボタン / 記事 CTA / 著者ボックス / 著者プロフィール / 関連記事 / 前後ナビが欠落しない。コメント欄は single.html に残っている | フロント表示目視確認 + HTML 出力比較 |
| FC-ACC-005 | REQ-NF-026-b, FC-003 | サイトエディタの Global Styles パネルで accent 色（またはその他のグローバル色）を変更して保存する | フロントエンドで変更した色が前面に反映される（ロックされておらず上書き可能）。テーマ更新後も theme_mods として保持される（WP core の挙動による） | サイトエディタ手動確認 |
| FC-ACC-006 | REQ-NF-026-c, FC-008 | `bash bin/check-theme-quality.sh` を実行する。patterns/ 配下の全 .html ファイルを静的確認する | RESULT: PASS（FAIL 0）。patterns/ に `<!-- wp:block` 参照や `Synced: yes` の記述が存在しない | bin/check-theme-quality.sh 実行 |
| FC-ACC-007 | REQ-F-045, REQ-F-046, FC-001〜FC-006 | `composer test:unit`（unit テスト 47件）と `composer test:security`（security テスト 48件）を実行する | 全テストが緑（pass）で維持される。`bash bin/check-theme-quality.sh` も PASS | CI / ローカルテスト実行 |

## 異常系・境界値

| ID | 条件 | 期待動作 |
|---|---|---|
| FC-ACC-ERR-001 | styles/ に不正 JSON（構文エラー）を持つバリエーションファイルを配置する | WP が invalid variation をスキップし、有効な variation のみ表示。fatal error は発生しない |
| FC-ACC-ERR-002 | post-footer.html 内でパターン slug が存在しないものを参照する | 該当パターンのみレンダリングをスキップし、他の post-footer コンテンツは正常表示 |
| FC-ACC-ERR-003 | single.html から post-header / post-footer の templatePart slug を誤って変更する | WP テンプレートエディタがテンプレートパーツ解決失敗として警告し、edit 画面で確認可能 |

## 受入条件のカバレッジ

| 要件 | ACC ID |
|---|---|
| REQ-F-045 スタイルバリエーション | FC-ACC-001, FC-ACC-003, FC-ACC-007 |
| REQ-F-046 記事用テンプレートパーツ | FC-ACC-004, FC-ACC-007 |
| REQ-NF-026-a dark WCAG AA | FC-ACC-002 |
| REQ-NF-026-b Global Styles 上書き保持 | FC-ACC-005 |
| REQ-NF-026-c synced パターン禁止 | FC-ACC-006 |
| FC-007 README 手順 | FC-ACC-003 |

## 検証手順サマリー

### Wave 1 完了後に実施

1. `styles/light.json` / `styles/dark.json` が存在することを確認する
2. サイトエディタでバリエーション一覧に「ライト（標準）」「ダーク」が表示されることを確認する（FC-ACC-001）
3. dark バリエーションに切り替えて axe-core でコントラスト検証する（FC-ACC-002）
4. Global Styles で accent 色を変更して上書きが反映されることを確認する（FC-ACC-005）

### Wave 2 完了後に実施

5. 記事ページを表示し、改修前と同等のコンテンツが表示されることを確認する（FC-ACC-004）
6. `templates/single.html` が `wp:template-part` 参照になっており、インラインでブロックを保持していないことを静的確認する

### Wave 3 完了後に実施

7. `bash bin/check-theme-quality.sh` を実行し PASS を確認する（FC-ACC-006 / FC-ACC-007）
8. `composer test:unit` / `composer test:security` を実行し全緑を確認する（FC-ACC-007）
9. README 手順書を参照して業種別 variation 複製が実際に動作することを確認する（FC-ACC-003）

## CI パイプラインでの実行タイミング

| テスト | CI 実行タイミング |
|---|---|
| `composer test:unit` | commit 時 |
| `composer test:security` | commit 時 |
| `bin/check-theme-quality.sh` | PR マージ時 |
| axe-core dark バリエーション確認 | Wave 1 完了レビュー時 |
| サイトエディタ手動確認（FC-ACC-001, 003, 005） | G5 デザイン凍結前 |

## 参照

- L1 受入条件: ACC-045（予定）/ ACC-046（予定）
- 機能要件: D-REQ-F/requirements.md（FC-001〜FC-008）
- 非機能要件: D-REQ-NF/nfr.md（REQ-NF-026-a〜c）
- L1: REQ-F-045, REQ-F-046, REQ-NF-026
- ADR-028: テーマカスタマイズ余地境界の明文化
