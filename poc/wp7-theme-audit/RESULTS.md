# WP7.0 テーマ監査 生証跡（実機検証結果）

## 実機検証結果（WordPress 7.0 GA / 2026-06-21、必要に応じて 6.9.4）

| 検証項目 | 実測結果 | 証跡 |
|---|---|---|
| ブロックテーマ認識 | PASS (`wp_is_block_theme()=Y` / `wp theme activate` 成功、fatalなし) | probe fixture を WP7.0 に投入 |
| theme.json v3 受理 | PASS (`WP_Theme_JSON_Resolver::get_theme_data()` で version=3 解決) | 同上 |
| テンプレート登録 | PASS (`block_templates`=3（index/single/page）、`template_parts`=2（header/footer）) | 同上 |
| ランタイムデプリケーション | PASS（index/single/page/search/404 描画） | `WP_DEBUG` 下で deprecated/fatal=0 件 |
| Abilities API | PASS（`register`→`get`→`execute` 成功、`{"pong":true,"echo":"neo7"}`） | `poc/wp7-abilities/` |
| embed ブロック | PASS（`static=DSD shadowrootmode SSR` / 隔離 PoC 10項目 all-green） | `plugins/agent-neo-embed`, `poc/embed-isolation` |
| style.css ヘッダ | PASS（Theme Name/Version/Requires at least 6.6/Tested up to 7.0/Requires PHP 8.1/License/License URI/Text Domain/Tags） | probe |
| theme.json `$schema` | PASS（`https://schemas.wp.org/trunk/theme.json`、WP7.0 が version3 を受理） | probe |

