#!/usr/bin/env bash
# E2E: page_type サーフェス分離検証
# ==================================
#
# 【採用理由】
# wp-phpunit は WP コア別途必要で CI 非可搬。
# 本スクリプトは docker WP（http://localhost:8086）に curl -sL し、
# wp_localize_script で注入される agentNeoTracking.pageType を
# 各ページ種別（home/lp/post/other）でアサートする。
#
# 【検証対象ロジック】
# class-tracking-assets.php detect_page_type():
#   1. is_page() && template starts with 'page-lp' → 'lp'
#   2. is_front_page() || is_home()                → 'home'
#   3. is_singular('post')                         → 'post'
#   4. is_page()                                   → 'page'
#   5. else                                        → 'other'
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
# ヘルパー: pageType を抽出する
# ---------------------------------------------------------------------------
get_page_type() {
    local url="$1"
    curl -sL --max-time 10 "$url" \
        | grep -o '"pageType":"[^"]*"' \
        | head -1 \
        | sed 's/"pageType":"\([^"]*\)"/\1/'
}

assert_page_type() {
    local desc="$1"
    local url="$2"
    local expected="$3"

    local actual
    actual="$(get_page_type "$url")"

    if [ "$actual" = "$expected" ]; then
        echo "  [PASS] ${desc}: pageType=\"${actual}\""
        PASS=$(( PASS + 1 ))
    else
        echo "  [FAIL] ${desc}: expected=\"${expected}\" actual=\"${actual}\"  url=${url}"
        FAIL=$(( FAIL + 1 ))
    fi
}

# ---------------------------------------------------------------------------
# テストケース
# ---------------------------------------------------------------------------
echo "=== E2E: page_type サーフェス分離 (live docker WP) ==="
echo "  endpoint: ${WP_URL}"
echo ""

# (1) トップページ → home
assert_page_type "home / トップページ" \
    "${WP_URL}/" \
    "home"

# (2) LP 固定ページ（lp-sample は page-lp テンプレート適用済み）→ lp
assert_page_type "lp / 固定LP（lp-sample）" \
    "${WP_URL}/lp-sample/" \
    "lp"

# (3) 投稿単一ページ → post
# ?p=256 は投稿 ID 256 の記事（パーマリンク正規化前でも post 判定される）
assert_page_type "post / 投稿単一（?p=256）" \
    "${WP_URL}/?p=256" \
    "post"

# (4) カテゴリアーカイブ → other
assert_page_type "other / カテゴリアーカイブ（seo-strategy）" \
    "${WP_URL}/category/seo-strategy/" \
    "other"

# (5) 404 → other
assert_page_type "other / 404（存在しないページ）" \
    "${WP_URL}/no-such-page-xyz-e2e-test/" \
    "other"

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
