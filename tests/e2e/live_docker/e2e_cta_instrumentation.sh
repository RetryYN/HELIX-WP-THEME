#!/usr/bin/env bash
# E2E: CTA 計装検証（data-cta-id / data-agent-neo-affiliate 属性）
# =================================================================
#
# 【採用理由】
# WP_UnitTestCase は DOM を描画しないため unit/integration テストでは
# 実際のレンダリング後 HTML に計装が入っているか確認できない。
# 本スクリプトは docker WP の実描画 HTML を curl で取得し、
# LP の 5 CTA + 記事の article_cta を属性ベースでアサートする。
#
# 【検証対象】
# LP（lp-sample ページ）:
#   data-cta-id="lp_hero_primary"    data-agent-neo-affiliate
#   data-cta-id="lp_hero_secondary"  data-agent-neo-affiliate
#   data-cta-id="lp_pricing_starter" data-agent-neo-affiliate
#   data-cta-id="lp_pricing_pro"     data-agent-neo-affiliate
#   data-cta-id="lp_pricing_business"data-agent-neo-affiliate
#   data-cta-id="lp_final_cta"       (最低1件 lp_final_cta が存在する)
# 記事（?p=256）:
#   data-cta-id="article_cta"        data-agent-neo-affiliate
#
# exit code: 0=PASS / 1=FAIL / 2=SKIP（docker 未起動）

set -euo pipefail

WP_URL="http://localhost:8086"
FAIL=0
PASS=0

# ---------------------------------------------------------------------------
# 前提条件チェック
# ---------------------------------------------------------------------------
if ! curl -sf --max-time 5 "${WP_URL}/" > /dev/null 2>&1; then
    echo "[SKIP] WP が応答しない: docker コンテナ agent-neo-wp を起動してください"
    exit 2
fi

# ---------------------------------------------------------------------------
# ヘルパー
# ---------------------------------------------------------------------------
fetch_html() {
    curl -sL --max-time 10 "$1"
}

assert_attr_exists() {
    local desc="$1"
    local html="$2"
    local pattern="$3"

    # here-string を使う（echo|grep -q は pipefail 下で grep 早期 exit→echo SIGPIPE→
    # マッチしても pipe が失敗扱いになる bash の既知 pitfall を回避）。
    if grep -qF -- "$pattern" <<< "$html"; then
        echo "  [PASS] ${desc}"
        PASS=$(( PASS + 1 ))
    else
        echo "  [FAIL] ${desc}  (pattern='${pattern}' not found)"
        FAIL=$(( FAIL + 1 ))
    fi
}

assert_attr_count() {
    local desc="$1"
    local html="$2"
    local pattern="$3"
    local expected="$4"

    local actual
    actual=$(grep -cF -- "$pattern" <<< "$html" || true)
    if [ "$actual" -ge "$expected" ]; then
        echo "  [PASS] ${desc}: count=${actual} (>=${expected})"
        PASS=$(( PASS + 1 ))
    else
        echo "  [FAIL] ${desc}: count=${actual} (expected>=${expected})"
        FAIL=$(( FAIL + 1 ))
    fi
}

# ---------------------------------------------------------------------------
# LP（lp-sample）の CTA 計装
# ---------------------------------------------------------------------------
echo "=== E2E: CTA 計装検証 (live docker WP) ==="
echo ""
echo "--- LP（/lp-sample/）---"

LP_HTML="$(fetch_html "${WP_URL}/lp-sample/")"

# 5 つの LP CTA が存在すること
assert_attr_exists "data-cta-id=\"lp_hero_primary\" 存在"    "$LP_HTML" 'data-cta-id="lp_hero_primary"'
assert_attr_exists "data-cta-id=\"lp_hero_secondary\" 存在"  "$LP_HTML" 'data-cta-id="lp_hero_secondary"'
assert_attr_exists "data-cta-id=\"lp_pricing_starter\" 存在" "$LP_HTML" 'data-cta-id="lp_pricing_starter"'
assert_attr_exists "data-cta-id=\"lp_pricing_pro\" 存在"     "$LP_HTML" 'data-cta-id="lp_pricing_pro"'
assert_attr_exists "data-cta-id=\"lp_pricing_business\" 存在" "$LP_HTML" 'data-cta-id="lp_pricing_business"'
assert_attr_exists "data-cta-id=\"lp_final_cta\" 存在"       "$LP_HTML" 'data-cta-id="lp_final_cta"'

# data-agent-neo-affiliate が LP に 5 件以上付与されていること
assert_attr_count  "data-agent-neo-affiliate が 5 件以上" "$LP_HTML" 'data-agent-neo-affiliate' 5

echo ""
echo "--- 記事（/?p=256）---"

POST_HTML="$(fetch_html "${WP_URL}/?p=256")"

# 記事の article_cta が存在すること
assert_attr_exists "data-cta-id=\"article_cta\" 存在"      "$POST_HTML" 'data-cta-id="article_cta"'
assert_attr_exists "data-agent-neo-affiliate 存在（記事）"  "$POST_HTML" 'data-agent-neo-affiliate'

# ---------------------------------------------------------------------------
# サマリ
# ---------------------------------------------------------------------------
echo ""
TOTAL=$(( PASS + FAIL ))
if [ "${FAIL}" -eq 0 ]; then
    echo "PASS: 全 ${TOTAL} ケース通過"
    exit 0
else
    echo "FAIL: ${FAIL}/${TOTAL} 件失敗"
    exit 1
fi
