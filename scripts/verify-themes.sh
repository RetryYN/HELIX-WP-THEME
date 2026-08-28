#!/usr/bin/env bash
# AGENT NEO - テーマB / テーマA 実機検証スクリプト
# Docker WP 環境でテーマB / テーマA を実際に動かし、解析レポートでは見えない実挙動を捕捉する
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
echo "# テーマB / テーマA 実機検証レポート" > "$REPORT"
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

# 第三者テーマの実スラッグ・キーは公開リポに置かない。環境変数で与える（未設定なら停止）。
#   THEME_A_SLUG / THEME_A_OPTION_PREFIX / THEME_A_META_PREFIX
#   THEME_B_SLUG / THEME_B_OPTION_KEY / THEME_B_OPTION_PREFIX / THEME_B_REST_NS / THEME_B_BLOCK_NS
: "${THEME_A_SLUG:?THEME_A_SLUG を設定してください}"
: "${THEME_B_SLUG:?THEME_B_SLUG を設定してください}"
THEME_A_OPTION_PREFIX="${THEME_A_OPTION_PREFIX:-$THEME_A_SLUG}"
THEME_A_META_PREFIX="${THEME_A_META_PREFIX:-_$THEME_A_SLUG}"
THEME_B_OPTION_KEY="${THEME_B_OPTION_KEY:-$(echo "$THEME_B_SLUG" | tr a-z A-Z)_SETTINGS}"
THEME_B_OPTION_PREFIX="${THEME_B_OPTION_PREFIX:-$THEME_B_SLUG}"
THEME_B_REST_NS="${THEME_B_REST_NS:-$THEME_B_SLUG}"
THEME_B_BLOCK_NS="${THEME_B_BLOCK_NS:-$THEME_B_SLUG}"

echo "==> 1. テーマ検出"
log_section "1. テーマ検出"
run_and_log "wp theme list" wp theme list --format=table

echo "==> 2. テーマB 検証"
log_section "2. テーマB 検証"
echo "==> 2.1 テーマB を有効化"
wp theme activate "$THEME_B_SLUG" || echo "(失敗してもスキップ)"
run_and_log "アクティブテーマ" wp theme list --status=active --format=table
run_and_log "テーマB CPT 一覧" wp post-type list --format=table
run_and_log "テーマB 登録 REST routes (API namespace)" curl -s "http://localhost:8086/wp-json/${THEME_B_REST_NS}/v1"
run_and_log "テーマB ブロックエディタブロック一覧" wp eval 'foreach (\WP_Block_Type_Registry::get_instance()->get_all_registered() as $name=>$b) { if (strpos($name, getenv("THEME_B_BLOCK_NS")."/")===0) echo $name . "\n"; }'
run_and_log "テーマB カスタマイザー設定数" wp eval 'global $wp_customize; if(!$wp_customize){ require_once ABSPATH."wp-includes/class-wp-customize-manager.php"; $wp_customize = new WP_Customize_Manager(); do_action("customize_register", $wp_customize); } echo "settings: " . count($wp_customize->settings()) . "\nsections: " . count($wp_customize->sections()) . "\npanels: " . count($wp_customize->panels()) . "\n";'
run_and_log "テーマB 主要オプション値 (テーマB option key)" wp option get "$THEME_B_OPTION_KEY" --format=json | head -50 || echo "no テーマB option"
run_and_log "テーマB 全オプション (テーマB option pattern)" wp option list --search="${THEME_B_OPTION_PREFIX}_*" --format=table
run_and_log "/?p=1 のレスポンスヘッダ + 一部本文" curl -sI http://localhost:8086/?p=1
run_and_log "テーマB JSON-LD 出力 (top page)" sh -c 'curl -s http://localhost:8086/ | grep -oE "<script[^>]*application/ld\\+json[^>]*>.*</script>" | head -3'

echo "==> 3. テーマA 検証"
log_section "3. テーマA 検証"
echo "==> 3.1 テーマA を有効化"
wp theme activate "$THEME_A_SLUG" || echo "(失敗してもスキップ)"
run_and_log "アクティブテーマ" wp theme list --status=active --format=table
run_and_log "テーマA CPT 一覧 (差分確認)" wp post-type list --format=table
run_and_log "テーマA post_meta keys (テーマA meta-key pattern)" wp eval '$keys = $GLOBALS["wpdb"]->get_col("SELECT DISTINCT meta_key FROM {$GLOBALS[\"wpdb\"]->postmeta} WHERE meta_key LIKE \"".getenv("THEME_A_META_PREFIX")."%\" LIMIT 50"); print_r($keys);'
run_and_log "テーマA オプション (テーマA option pattern)" wp option list --search="${THEME_A_OPTION_PREFIX}*" --format=table
run_and_log "テーマA theme_mod 設定 数" wp eval '$mods = get_theme_mods(); echo "theme_mods count: " . count($mods) . "\n"; foreach (array_slice(array_keys($mods),0,30) as $k) echo $k . "\n";'
run_and_log "/?p=1 のレスポンスヘッダ + 一部本文 (テーマA)" curl -sI http://localhost:8086/?p=1
run_and_log "テーマA JSON-LD 出力" sh -c 'curl -s http://localhost:8086/ | grep -oE "<script[^>]*application/ld\\+json[^>]*>.*</script>" | head -3'
run_and_log "テーマA OGP / canonical / noindex 出力" sh -c 'curl -s http://localhost:8086/ | grep -E "<(meta|link)[^>]*(og:|twitter:|canonical|robots)" | head -20'

echo "==> 4. 性能比較"
log_section "4. 性能比較（簡易）"
run_and_log "テーマB → テーマA 切替時間/応答測定 (ab 風 curl)" sh -c '
  wp theme activate "$THEME_B_SLUG" >/dev/null 2>&1
  echo "--- テーマB ---"
  for i in 1 2 3; do
    t=$(curl -s -o /dev/null -w "%{time_total}" http://localhost:8086/)
    echo "trial $i: ${t}s"
  done
  wp theme activate "$THEME_A_SLUG" >/dev/null 2>&1
  echo "--- テーマA ---"
  for i in 1 2 3; do
    t=$(curl -s -o /dev/null -w "%{time_total}" http://localhost:8086/)
    echo "trial $i: ${t}s"
  done
' 2>/dev/null || true

echo "==> 5. PSI 風アセット計測"
log_section "5. レスポンス本体サイズと CSS/JS リクエスト数"
run_and_log "テーマB アクティブ時 HTML 取得とリンク本数" sh -c '
  wp theme activate "$THEME_B_SLUG" >/dev/null 2>&1
  body=$(curl -s http://localhost:8086/)
  echo "html bytes: $(echo -n "$body" | wc -c)"
  echo "link CSS: $(echo "$body" | grep -c "<link[^>]*\\.css")"
  echo "script JS: $(echo "$body" | grep -c "<script[^>]*\\.js")"
' 2>/dev/null || true
run_and_log "テーマA アクティブ時 HTML 取得とリンク本数" sh -c '
  wp theme activate "$THEME_A_SLUG" >/dev/null 2>&1
  body=$(curl -s http://localhost:8086/)
  echo "html bytes: $(echo -n "$body" | wc -c)"
  echo "link CSS: $(echo "$body" | grep -c "<link[^>]*\\.css")"
  echo "script JS: $(echo "$body" | grep -c "<script[^>]*\\.js")"
' 2>/dev/null || true

echo ""
echo "==> 完了: $REPORT"
echo ""
ls -la "$REPORT"
