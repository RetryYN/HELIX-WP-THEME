#!/usr/bin/env bash
# run_e2e.sh — AGENT NEO live-docker E2E ランナー
# ================================================
#
# 【採用理由: wp-phpunit 非可搬のため live-docker E2E を採用】
#   tests/Integration/ の WP_UnitTestCase 依存テストは wp-phpunit/WordPress
#   コアが別途必要なため CI 非可搬。本スクリプトは docker の agent-neo-wp
#   （http://localhost:8086）に対して実 HTTP 検証を行う E2E スイートを
#   一括実行し PASS/FAIL サマリを返す。
#
# 【token はサーバ tracking_secrets() と同順で解決】
#   e2e_tracking_roundtrip.py が WP コンテナの env と DB option を
#   tracking_secrets() と同じ優先順（env:AGENT_NEO_SITE_TOKEN →
#   env:AGENT_NEO_TRACKING_SITE_TOKEN → option:agent_neo_site_token →
#   option:agent_neo_tracking_site_token）で解決する。
#
# 【agent_neo_site_token は本番未設定が前提】
#   env または DB に agent_neo_site_token（legacy キー）が残存すると
#   tracking_secrets() が優先し、frontend が localize している
#   agent_neo_tracking_site_token と不一致 → 全 HTTP 401 になる。
#   roundtrip スクリプトの token 整合性アサーションでこの構成ミスを検出する。
#
# 【実行スクリプト】
#   1. e2e_page_type.sh        — home/lp/post/category/404 の pageType アサート
#   2. e2e_cta_instrumentation.sh — LP 5CTA + 記事 article_cta の計装アサート
#   3. e2e_tracking_roundtrip.py  — 全イベント種別 HMAC 署名 HTTP 200 + 負の対照 401
#   4. e2e_a11y.py             — 主要ページ light 版 axe serious/critical=0
#
# 【前提条件】
#   - docker コンテナ agent-neo-wp が起動中
#   - python3 が利用可能
#   - e2e_a11y.py 実行には playwright が必要（未インストール時は SKIP）
#
# exit code: 0=全 PASS / 1=1件以上 FAIL（SKIP は PASS 扱い）

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WP_URL="http://localhost:8086"

# 結果集計
PASS_COUNT=0
FAIL_COUNT=0
SKIP_COUNT=0
declare -a RESULTS=()

# ---------------------------------------------------------------------------
# 前提条件チェック: docker agent-neo-wp が起動しているか
# ---------------------------------------------------------------------------
echo "=== AGENT NEO E2E Suite (live-docker) ==="
echo "  docker container: agent-neo-wp"
echo "  WP URL: ${WP_URL}"
echo ""

if ! docker ps --format "{{.Names}}" 2>/dev/null | grep -q "^agent-neo-wp$"; then
    echo "[ERROR] docker コンテナ agent-neo-wp が起動していない"
    echo "  docker ps で確認してください"
    exit 2
fi

if ! curl -sf --max-time 5 "${WP_URL}/" > /dev/null 2>&1; then
    echo "[ERROR] ${WP_URL}/ が応答しない"
    echo "  docker コンテナが起動していても WP が応答しない可能性があります"
    exit 2
fi

echo "[OK] docker agent-neo-wp 起動確認"
echo ""

# ---------------------------------------------------------------------------
# ヘルパー: スクリプトを実行して結果を集計する
# ---------------------------------------------------------------------------
run_script() {
    local name="$1"
    local cmd=("${@:2}")

    echo "----------------------------------------------------------------------"
    echo "Running: ${name}"
    echo "----------------------------------------------------------------------"

    # exit code 2 = SKIP（docker 未起動）として扱う
    set +e
    "${cmd[@]}"
    local code=$?
    set -e

    if [ "${code}" -eq 0 ]; then
        PASS_COUNT=$(( PASS_COUNT + 1 ))
        RESULTS+=("  [PASS] ${name}")
    elif [ "${code}" -eq 2 ]; then
        SKIP_COUNT=$(( SKIP_COUNT + 1 ))
        RESULTS+=("  [SKIP] ${name}")
    else
        FAIL_COUNT=$(( FAIL_COUNT + 1 ))
        RESULTS+=("  [FAIL] ${name} (exit ${code})")
    fi

    echo ""
}

# ---------------------------------------------------------------------------
# 各 E2E スクリプトを実行
# ---------------------------------------------------------------------------

# 1. page_type サーフェス分離
run_script "e2e_page_type" bash "${SCRIPT_DIR}/e2e_page_type.sh"

# 2. CTA 計装
run_script "e2e_cta_instrumentation" bash "${SCRIPT_DIR}/e2e_cta_instrumentation.sh"

# 3. tracking roundtrip（HMAC 署名 + token 整合性 + 負の対照）
run_script "e2e_tracking_roundtrip" python3 "${SCRIPT_DIR}/e2e_tracking_roundtrip.py"

# 4. a11y（playwright 未インストール時は SKIP=exit2）
run_script "e2e_a11y" python3 "${SCRIPT_DIR}/e2e_a11y.py"

# ---------------------------------------------------------------------------
# サマリ
# ---------------------------------------------------------------------------
echo "======================================================================"
echo "E2E Suite Summary"
echo "======================================================================"
for r in "${RESULTS[@]}"; do
    echo "${r}"
done
echo ""
echo "  PASS: ${PASS_COUNT}  FAIL: ${FAIL_COUNT}  SKIP: ${SKIP_COUNT}"
echo ""

if [ "${FAIL_COUNT}" -eq 0 ]; then
    echo "PASS: 全スクリプト通過 (SKIP は環境依存のため PASS 扱い)"
    exit 0
else
    echo "FAIL: ${FAIL_COUNT} スクリプト失敗"
    exit 1
fi
