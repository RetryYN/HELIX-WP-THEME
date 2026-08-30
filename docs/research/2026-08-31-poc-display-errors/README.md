# PoC 証跡: 未認証 REST の PHP Warning によるパス開示は display_errors=Off で止まる（finding #21 の対処案）

実施日: 2026-08-31 / 場所: **ローカル docker WP 7.1（PHP 8.3）**。実運用サイトには触れていない
（PO 判断 2026-08-31: 運用段階に入るまで実サイトのサーバ層は変更せず、検証は PoC 環境で行う）。

## 条件（site-A の観測条件に合わせた）
- `WP_DEBUG=false`（site-A と同じ。`wp_debug_mode()` が display_errors を上書きしない）
- 未認証 `permission_callback => '__return_true'` の REST route で、不正入力に対し `file_get_contents()` が Warning を出す
  最小の mu-plugin（テーマA の該当 route と同型。テーマA 本体は使っていない）
- display_errors の切替は `.htaccess` の `php_flag`（ローカルは mod_php のため。site-A は FastCGI で `.user.ini`）

## 結果
| display_errors | REST 応答 |
|---|---|
| On | `Warning: file_get_contents(http://): Failed to open stream ... in /var/www/html/wp-content/mu-plugins/poc-display-errors.php on line 4` と `headers already sent` が JSON の前に出力され、**絶対パスと行番号が開示**される（`evidence/rest-response-display-errors-on.txt`） |
| Off | `{"len":0}` のみ。開示なし（`evidence/rest-response-display-errors-off.txt`） |

## 結論
- #21 の開示は WP の設定（`WP_DEBUG`）ではなく PHP 実行環境の `display_errors` に起因し、サーバ層で Off にすれば止まる。
- 補足: ローカル既定の `WORDPRESS_DEBUG=1`（`WP_DEBUG_DISPLAY=false`）では WP が display_errors を 0 に上書きするため再現しない。
  再現には `WP_DEBUG=false` が必要（実施後に wp-config / .htaccess / mu-plugin は復元済み）。
- 実サイトへの適用（`.user.ini` 1 行）は運用段階に入ってからの PO 判断。REST 遮断・ベンダー報告要否も同様に未決。
