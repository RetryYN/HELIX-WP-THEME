# AGENT NEO Theme

## 検証ログ

- `php -l` は `themes/agent-neo-theme/` 配下の全 PHP ファイルで実行する。
- `bin/check-prefix.sh` で CR-002 / CR-003 を検査する。
- fail-fast 確認は `config/section-registry.json` の `required_sections` に未登録の `agent_neo_missing` を一時追加し、`agent_neo_health()` の `config_valid=false` と `config_errors` の `section-registry missing required section_id: agent_neo_missing` を確認する。
- 2026-06-21: `php -l themes/agent-neo-theme/inc/setup/class-boundary-guard.php` PASS。
- 2026-06-21: 現行 `config/theme-manifest.json` を `Agent_Neo_Boundary_Guard::validate()` に渡し、`is_valid()===true` を確認。
- 2026-06-21: 一時 PHP スニペット内で `boundary.json_operation_api.theme_allowed=true` に差し替え、`is_valid()===false` と `boundary.json_operation_api (core-plugin-owned) must not grant theme ownership (theme_allowed=true)` を確認。manifest 本体は未変更。
- 2026-06-21: `boundary.seo_head_render.theme_allowed=true` は `theme_adapter` として valid のまま維持されることを確認。
- 2026-06-21: `bin/check-prefix.sh` PASS。

## 境界

テーマは FSE templates、patterns、theme.json、表示 adapter のみを持つ。JSON 操作 API、CPT、A/B、tracking storage、catalog-update 発火は Core Plugin 側の責務。
