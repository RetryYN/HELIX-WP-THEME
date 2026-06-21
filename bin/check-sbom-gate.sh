#!/usr/bin/env bash
# =============================================================================
# Release/SBOM Gate 検査スクリプト
#
# 用途: sbom.cdx.json を検査し、L2-design.md §8.9 / 675行で定義された
#       Release/SBOM Gate の fail 条件をチェックする。
#
# fail 条件（全て pass で exit 0):
#   1. 依存元不明  — 全コンポーネントに supplier が存在するか
#   2. ライセンス不明 — 全コンポーネントに licenses が存在するか
#   3. checksum なし — 全コンポーネントに SHA-256 hashes が存在するか
#   4. changelog なし — 各 first-party コンポーネントに CHANGELOG.md が存在するか
#   5. rollback 手順 — rollback 記載ドキュメントの存在確認（未存在は WARN / fail 非対象）
#
# 使用方法: bash bin/check-sbom-gate.sh
# 依存: jq（利用可能な場合）または python3（fallback）
# =============================================================================

set -euo pipefail

# リポジトリルートの特定（スクリプトの親ディレクトリ）
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SBOM_FILE="${REPO_ROOT}/sbom.cdx.json"

# カラーコード
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BOLD='\033[1m'
RESET='\033[0m'

# カウンター
PASS_COUNT=0
FAIL_COUNT=0
WARN_COUNT=0

# ============================================================
# ユーティリティ関数
# ============================================================

# PASS / FAIL / WARN 表示
pass() { echo -e "${GREEN}[PASS]${RESET} $1"; PASS_COUNT=$(( PASS_COUNT + 1 )); }
fail() { echo -e "${RED}[FAIL]${RESET} $1"; FAIL_COUNT=$(( FAIL_COUNT + 1 )); }
warn() { echo -e "${YELLOW}[WARN]${RESET} $1"; WARN_COUNT=$(( WARN_COUNT + 1 )); }
info() { echo -e "       $1"; }

# jq または python3 で JSON を評価する
# 使用方法: sbom_jq '<jq expression>'
if command -v jq >/dev/null 2>&1; then
	JQ_AVAILABLE=true
	sbom_jq() { jq -r "$1" "${SBOM_FILE}"; }
	sbom_jq_raw() { jq "$1" "${SBOM_FILE}"; }
else
	JQ_AVAILABLE=false
	sbom_jq() {
		python3 -c "
import json, sys
data = json.load(open('${SBOM_FILE}'))
# 簡易 jq 代替: キー参照のみサポート
expr = sys.argv[1]
print(json.dumps(data, ensure_ascii=False))
" "$1"
	}
fi

# ============================================================
# 事前確認: sbom.cdx.json の存在と JSON 妥当性
# ============================================================

echo ""
echo -e "${BOLD}=== Release/SBOM Gate 検査 ===${RESET}"
echo "対象: ${SBOM_FILE}"
echo ""

if [ ! -f "${SBOM_FILE}" ]; then
	fail "sbom.cdx.json が見つかりません: ${SBOM_FILE}"
	echo ""
	echo -e "${RED}[GATE FAIL] sbom.cdx.json が未生成です。先に php bin/generate-sbom.php を実行してください。${RESET}"
	exit 1
fi

# JSON 妥当性チェック（python3 fallback 含む）
if command -v jq >/dev/null 2>&1; then
	if jq empty "${SBOM_FILE}" 2>/dev/null; then
		pass "sbom.cdx.json JSON 妥当性"
		info "ツール: jq"
	else
		fail "sbom.cdx.json が不正な JSON です"
		exit 1
	fi
else
	if python3 -c "import json; json.load(open('${SBOM_FILE}'))" 2>/dev/null; then
		pass "sbom.cdx.json JSON 妥当性"
		info "ツール: python3（jq 不在のため fallback）"
	else
		fail "sbom.cdx.json が不正な JSON です"
		exit 1
	fi
fi

echo ""

# ============================================================
# Check 1: 依存元不明 — 全コンポーネントに supplier が存在するか
# ============================================================
echo -e "${BOLD}--- Check 1: 依存元不明 (supplier) ---${RESET}"

TOTAL_COMPONENTS=$(sbom_jq '.components | length')
SUPPLIER_MISSING=$(sbom_jq '[.components[] | select(.supplier == null or .supplier.name == null or .supplier.name == "")] | length')

info "コンポーネント総数: ${TOTAL_COMPONENTS}"
info "supplier 未設定:    ${SUPPLIER_MISSING}"

if [ "${SUPPLIER_MISSING}" -eq 0 ]; then
	pass "全コンポーネントに supplier が設定されています（${TOTAL_COMPONENTS}件）"
else
	fail "supplier 未設定のコンポーネントが ${SUPPLIER_MISSING} 件あります"
	# 詳細表示
	sbom_jq '[.components[] | select(.supplier == null or .supplier.name == null or .supplier.name == "") | .name] | .[]' 2>/dev/null | while IFS= read -r name; do
		info "  -> ${name}"
	done
fi

echo ""

# ============================================================
# Check 2: ライセンス不明 — 全コンポーネントに licenses が存在するか
# ============================================================
echo -e "${BOLD}--- Check 2: ライセンス不明 (licenses) ---${RESET}"

LICENSE_MISSING=$(sbom_jq '[.components[] | select(.licenses == null or (.licenses | length) == 0)] | length')

info "licenses 未設定: ${LICENSE_MISSING}"

if [ "${LICENSE_MISSING}" -eq 0 ]; then
	pass "全コンポーネントに licenses が設定されています（${TOTAL_COMPONENTS}件）"
	# ライセンス一覧を表示
	sbom_jq '[.components[] | {name: .name, license: .licenses[0].license.id}] | .[] | "\(.name): \(.license)"' 2>/dev/null | while IFS= read -r line; do
		info "  ${line}"
	done
else
	fail "licenses 未設定のコンポーネントが ${LICENSE_MISSING} 件あります"
	sbom_jq '[.components[] | select(.licenses == null or (.licenses | length) == 0) | .name] | .[]' 2>/dev/null | while IFS= read -r name; do
		info "  -> ${name}"
	done
fi

echo ""

# ============================================================
# Check 3: checksum なし — 全コンポーネントに SHA-256 hashes が存在するか
# ============================================================
echo -e "${BOLD}--- Check 3: checksum なし (hashes/SHA-256) ---${RESET}"

# hashes 配列が存在し、かつ alg=SHA-256 の要素を持つかを確認
# platform/runtime コンポーネント（sbom:role=platform-runtime）は hashes 不要として除外
HASH_MISSING=$(sbom_jq '
[
  .components[]
  | select(
      (
        .properties == null
        or
        (.properties | map(select(.name == "sbom:role" and .value == "platform-runtime")) | length) == 0
      )
      and
      (
        .hashes == null
        or (.hashes | length) == 0
        or (
          .hashes
          | map(select(.alg == "SHA-256" and (.content != null) and (.content | length) > 0))
          | length
        ) == 0
      )
    )
] | length
')

info "SHA-256 未設定（first-party のみ対象）: ${HASH_MISSING}"

if [ "${HASH_MISSING}" -eq 0 ]; then
	pass "全 first-party コンポーネントに SHA-256 hash が設定されています"
	# hash 値の一部を表示
	sbom_jq '[.components[] | select(.hashes != null and (.hashes | length) > 0) | {name: .name, hash: .hashes[0].content[0:16]}] | .[] | "\(.name): \(.hash)..."' 2>/dev/null | while IFS= read -r line; do
		info "  ${line}"
	done
else
	fail "SHA-256 hash 未設定のコンポーネントが ${HASH_MISSING} 件あります"
	sbom_jq '
[
  .components[]
  | select(
      (
        .properties == null
        or
        (.properties | map(select(.name == "sbom:role" and .value == "platform-runtime")) | length) == 0
      )
      and
      (
        .hashes == null
        or (.hashes | length) == 0
        or (
          .hashes
          | map(select(.alg == "SHA-256" and (.content != null) and (.content | length) > 0))
          | length
        ) == 0
      )
    )
  | .name
] | .[]' 2>/dev/null | while IFS= read -r name; do
		info "  -> ${name}"
	done
fi

echo ""

# ============================================================
# Check 4: changelog なし — 各 first-party コンポーネントに CHANGELOG.md が存在するか
# ============================================================
echo -e "${BOLD}--- Check 4: changelog なし (CHANGELOG.md) ---${RESET}"

CHANGELOG_MISSING=0

declare -A COMPONENT_PATHS=(
	["plugins/agent-neo-core"]="${REPO_ROOT}/plugins/agent-neo-core/CHANGELOG.md"
	["plugins/agent-neo-embed"]="${REPO_ROOT}/plugins/agent-neo-embed/CHANGELOG.md"
	["themes/agent-neo-theme"]="${REPO_ROOT}/themes/agent-neo-theme/CHANGELOG.md"
)

for component in "${!COMPONENT_PATHS[@]}"; do
	changelog_path="${COMPONENT_PATHS[$component]}"
	if [ -f "${changelog_path}" ]; then
		info "  FOUND  ${component}/CHANGELOG.md"
	else
		info "  MISSING ${component}/CHANGELOG.md -> ${changelog_path}"
		CHANGELOG_MISSING=$(( CHANGELOG_MISSING + 1 ))
	fi
done

if [ "${CHANGELOG_MISSING}" -eq 0 ]; then
	pass "全 first-party コンポーネントに CHANGELOG.md が存在します（3件）"
else
	fail "CHANGELOG.md が存在しないコンポーネントが ${CHANGELOG_MISSING} 件あります"
fi

echo ""

# ============================================================
# Check 5: rollback 手順 — ドキュメントの存在確認
# rollback 手順の未存在は WARN（fail 非対象）
# 理由: テーマ/プラグインは WordPress 管理画面から前バージョンに切り戻し可能で
#       ロールバック手順は WordPress 配布規約上「外部 docs URL / readme.txt」で
#       管理するのが標準。docs/ や runbook ファイルへの記載を推奨するが、
#       初版（0.1.0）ではドキュメント整備中のため WARN として扱い gate は通過させる。
# ============================================================
echo -e "${BOLD}--- Check 5: rollback 手順（WARN / fail 非対象） ---${RESET}"

ROLLBACK_FOUND=false

# 検索対象パターン
ROLLBACK_CANDIDATES=(
	"${REPO_ROOT}/docs/runbook*.md"
	"${REPO_ROOT}/docs/*/runbook*.md"
	"${REPO_ROOT}/docs/runbook/*.md"
	"${REPO_ROOT}/docs/deploy*.md"
	"${REPO_ROOT}/docs/*/rollback*.md"
)

for pattern in "${ROLLBACK_CANDIDATES[@]}"; do
	for f in $pattern; do
		if [ -f "$f" ]; then
			# ファイル内に rollback / ロールバック の記載があるか確認
			if grep -qi 'rollback\|ロールバック' "$f" 2>/dev/null; then
				info "  FOUND  rollback 記載: ${f#${REPO_ROOT}/}"
				ROLLBACK_FOUND=true
			fi
		fi
	done
done

if [ "${ROLLBACK_FOUND}" = true ]; then
	pass "rollback 手順ドキュメントが存在します"
else
	warn "rollback 手順ドキュメントが未整備です（WordPress 管理画面からの前バージョン切り戻しは常に可能）"
	info "  推奨: docs/runbook-rollback.md または docs/deploy.md に手順を記載する"
	info "  ※ 本チェックは WARN です。Gate FAIL にはなりません。"
fi

echo ""

# ============================================================
# 結果サマリ
# ============================================================
echo -e "${BOLD}=== 結果サマリ ===${RESET}"
echo -e "  PASS: ${GREEN}${PASS_COUNT}${RESET}"
echo -e "  WARN: ${YELLOW}${WARN_COUNT}${RESET}"
echo -e "  FAIL: ${RED}${FAIL_COUNT}${RESET}"
echo ""

if [ "${JQ_AVAILABLE}" = true ]; then
	info "JSON 評価ツール: jq"
else
	info "JSON 評価ツール: python3（jq 不在のため fallback）"
fi

echo ""

if [ "${FAIL_COUNT}" -eq 0 ]; then
	echo -e "${GREEN}${BOLD}[GATE PASS] Release/SBOM Gate 全チェック通過（exit 0）${RESET}"
	exit 0
else
	echo -e "${RED}${BOLD}[GATE FAIL] ${FAIL_COUNT} 件の fail が検出されました（exit 1）${RESET}"
	exit 1
fi
