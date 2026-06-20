# AGENT NEO WP7 Abilities API PoC results

- 検証日: 2026-06-21
- 対象: WordPress 6.9.4 / WordPress 7.0 (GA)
- Verdict: **PASS**
- 実行対象: `poc/wp7-abilities/zz-abilities-verify.php`
- 注意: 本リポ作業では WordPress の起動・実行は行わず、既存 PoC の実測結果を記録した。

## 実測結果

| 検証項目 | WordPress 6.9.4 | WordPress 7.0 (GA) | 判定 | 補足 |
|---|---|---|---|---|
| Abilities API 関数存在 | Y | Y | PASS | `wp_register_ability_category()` / `wp_register_ability()` / `wp_get_ability()` を確認 |
| category 登録 | Y | Y | PASS | `agent-neo` category を `wp_abilities_api_categories_init` で登録 |
| ability `reg_result` | `OK:WP_Ability` | `OK:WP_Ability` | PASS | `agent-neo/diag-ping` の登録戻り値 |
| `wp_get_ability` | OK | OK | PASS | 登録済み ability を取得可能 |
| `execute` 実行結果 | `{"pong":true,"echo":"neo7"}` | `{"pong":true,"echo":"neo7"}` | PASS | `execute_callback` が入力 `echo=neo7` を返却 |
| `total_abilities` | `4` | `4` | PASS | core 3 + 自前 1 |
| embed static | DSD `shadowrootmode` + 本文 SSR 確認 | DSD `shadowrootmode` + 本文 SSR 確認 | PASS | static embed の索引可能な SSR 本文を確認 |

## 判明した必須契約

| 契約 | 内容 |
|---|---|
| Core 標準化 | Abilities API は WordPress 6.9.0+ 標準で、実体は `wp-includes/abilities-api.php` にある |
| 登録順序 | category 登録は `wp_abilities_api_categories_init`、ability 登録は `wp_abilities_api_init` の二段で行う |
| category 必須項目 | `wp_register_ability_category()` は `label` と `description` の両方が必須 |
| ability category | ability の `category` は登録済み category が必須。未登録 category を指定すると register は `null` |

## CARRY-WP7-001 への反映

CARRY-WP7-001 は 2026-06-21 に VERIFIED とする。WordPress 7.0 (GA) と 6.9.4 の双方で `register -> get -> execute` が成立し、Abilities API が WP 6.9.0+ の標準 API として利用できることを確認した。

## 参照

- ADR: `docs/adr/ADR-020.md`
- PO decision: `docs/reviews/L3-PO-decision-packet.md`
- 検証用 mu-plugin: `poc/wp7-abilities/zz-abilities-verify.php`
