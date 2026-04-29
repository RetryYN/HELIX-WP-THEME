#!/usr/bin/env bash
# AGENT NEO - SWELL / JIN:R 実機検証スクリプト
# Docker WP 環境でSWELL / JIN:R を実際に動かし、解析レポートでは見えない実挙動を捕捉する
# Usage: bash scripts/verify-themes.sh

set -euo pipefail

cd "$(dirname "$0")/.."

OUT_DIR="解析レポート/35-実機検証ログ"
mkdir -p "$OUT_DIR"

# WP CLI ラッパー
wp() {
  docker compose run --rm wpcli "$@"
}

REPORT="$OUT_DIR/verification-$(date +%Y%m%d-%H%M%S).md"
echo "# SWELL / JIN:R 実機検証レポート" > "$REPORT"
echo "" >> "$REPORT"
echo "**実施日時**: $(date -Iseconds)" >> "$REPORT"
echo "**環境**: Docker WP $(wp core version 2>/dev/null | head -1) / PHP $(wp php-version 2>/dev/null | head -1 || echo 'unknown')" >> "$REPORT"
echo "" >> "$REPORT"

log_section() {
  echo "" >> "$REPORT"
  echo "## $1" >> "$REPORT"
  echo "" >> "$REPORT"
}

run_and_log() {
  local title="$1"
  shift
  echo "" >> "$REPORT"
  echo "### $title" >> "$REPORT"
  echo "" >> "$REPORT"
  echo '```' >> "$REPORT"
  "$@" 2>&1 | tee -a "$REPORT"
  echo '```' >> "$REPORT"
}

echo "==> 1. テーマ検出"
log_section "1. テーマ検出"
run_and_log "wp theme list" wp theme list --format=table

echo "==> 2. SWELL 検証"
log_section "2. SWELL 検証"
echo "==> 2.1 SWELL を有効化"
wp theme activate swell || echo "(失敗してもスキップ)"
run_and_log "アクティブテーマ" wp theme list --status=active --format=table
run_and_log "SWELL CPT 一覧" wp post-type list --format=table
run_and_log "SWELL 登録 REST routes (swell/v1)" curl -s http://localhost:8086/wp-json/swell/v1
run_and_log "SWELL ブロックエディタブロック一覧" wp eval 'foreach (\WP_Block_Type_Registry::get_instance()->get_all_registered() as $name=>$b) { if (strpos($name, "loos/")===0) echo $name . "\n"; }'
run_and_log "SWELL カスタマイザー設定数" wp eval 'global $wp_customize; if(!$wp_customize){ require_once ABSPATH."wp-includes/class-wp-customize-manager.php"; $wp_customize = new WP_Customize_Manager(); do_action("customize_register", $wp_customize); } echo "settings: " . count($wp_customize->settings()) . "\nsections: " . count($wp_customize->sections()) . "\npanels: " . count($wp_customize->panels()) . "\n";'
run_and_log "SWELL 主要オプション値" wp option get SWELL_SETTINGS --format=json | head -50 || echo "no SWELL_SETTINGS option"
run_and_log "SWELL 全オプション (swell_*)" wp option list --search='swell_*' --format=table
run_and_log "/?p=1 のレスポンスヘッダ + 一部本文" curl -sI http://localhost:8086/?p=1
run_and_log "SWELL JSON-LD 出力 (top page)" sh -c 'curl -s http://localhost:8086/ | grep -oE "<script[^>]*application/ld\\+json[^>]*>.*</script>" | head -3'

echo "==> 3. JIN:R 検証"
log_section "3. JIN:R 検証"
echo "==> 3.1 JIN:R を有効化"
wp theme activate jinr || echo "(失敗してもスキップ)"
run_and_log "アクティブテーマ" wp theme list --status=active --format=table
run_and_log "JIN:R CPT 一覧 (差分確認)" wp post-type list --format=table
run_and_log "JIN:R post_meta keys (_jinr_*)" wp eval '$keys = $GLOBALS["wpdb"]->get_col("SELECT DISTINCT meta_key FROM {$GLOBALS[\"wpdb\"]->postmeta} WHERE meta_key LIKE \"_jinr_%\" LIMIT 50"); print_r($keys);'
run_and_log "JIN:R オプション (jinr_*)" wp option list --search='jinr*' --format=table
run_and_log "JIN:R theme_mod 設定 数" wp eval '$mods = get_theme_mods(); echo "theme_mods count: " . count($mods) . "\n"; foreach (array_slice(array_keys($mods),0,30) as $k) echo $k . "\n";'
run_and_log "/?p=1 のレスポンスヘッダ + 一部本文 (JIN:R)" curl -sI http://localhost:8086/?p=1
run_and_log "JIN:R JSON-LD 出力" sh -c 'curl -s http://localhost:8086/ | grep -oE "<script[^>]*application/ld\\+json[^>]*>.*</script>" | head -3'
run_and_log "JIN:R OGP / canonical / noindex 出力" sh -c 'curl -s http://localhost:8086/ | grep -E "<(meta|link)[^>]*(og:|twitter:|canonical|robots)" | head -20'

echo "==> 4. 性能比較"
log_section "4. 性能比較（簡易）"
run_and_log "SWELL → JIN:R 切替時間/応答測定 (ab 風 curl)" sh -c '
  wp theme activate swell >/dev/null 2>&1
  echo "--- SWELL ---"
  for i in 1 2 3; do
    t=$(curl -s -o /dev/null -w "%{time_total}" http://localhost:8086/)
    echo "trial $i: ${t}s"
  done
  wp theme activate jinr >/dev/null 2>&1
  echo "--- JIN:R ---"
  for i in 1 2 3; do
    t=$(curl -s -o /dev/null -w "%{time_total}" http://localhost:8086/)
    echo "trial $i: ${t}s"
  done
' 2>/dev/null || true

echo "==> 5. PSI 風アセット計測"
log_section "5. レスポンス本体サイズと CSS/JS リクエスト数"
run_and_log "SWELL アクティブ時 HTML 取得とリンク本数" sh -c '
  wp theme activate swell >/dev/null 2>&1
  body=$(curl -s http://localhost:8086/)
  echo "html bytes: $(echo -n "$body" | wc -c)"
  echo "link CSS: $(echo "$body" | grep -c "<link[^>]*\\.css")"
  echo "script JS: $(echo "$body" | grep -c "<script[^>]*\\.js")"
' 2>/dev/null || true
run_and_log "JIN:R アクティブ時 HTML 取得とリンク本数" sh -c '
  wp theme activate jinr >/dev/null 2>&1
  body=$(curl -s http://localhost:8086/)
  echo "html bytes: $(echo -n "$body" | wc -c)"
  echo "link CSS: $(echo "$body" | grep -c "<link[^>]*\\.css")"
  echo "script JS: $(echo "$body" | grep -c "<script[^>]*\\.js")"
' 2>/dev/null || true

echo ""
echo "==> 完了: $REPORT"
echo ""
ls -la "$REPORT"
