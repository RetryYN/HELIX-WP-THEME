#!/usr/bin/env bash
# =============================================================================
# check-design-consistency.sh
# 一貫性ゲート（docs/design/consistency-responsibilities.md §3）
#
#   G-T1 トークン形状   theme.json の尺度（フォント 6 段 / 余白 6 段 / 幅 2 値）と
#                      defaultFontSizes / defaultSpacingSizes=false、spacingScale なし、スラッグ集合固定
#   G-T2 生値禁止       patterns / parts / templates にサイズ・余白の生 px/rem/em を書かない
#                      （既存違反は baseline で許容し、増加のみ FAIL）
#   G-T3 階層           見出しの fontSize プリセットが h1 > h2 > h3 > h4 の順で単調非増加
#   G-S1 骨格境界       子テーマ theme.json が settings.typography/spacing/color を再定義しない
#   G-S2 参照整合       templates / parts が参照する template-part / pattern が存在する
#
# 使い方: bash bin/check-design-consistency.sh [--update-baseline]
# 終了コード: 0=全PASS  1=FAILあり
# 依存: bash, php (json_decode), grep, find
# =============================================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
THEME_DIR="${REPO_ROOT}/themes/agent-neo-theme"
THEME_JSON="${THEME_DIR}/theme.json"
BASELINE="${THEME_DIR}/config/design-consistency-baseline.json"
CHILD_JSON_GLOB="${REPO_ROOT}/themes/*/theme.json"
UPDATE_BASELINE=0
[[ "${1:-}" == "--update-baseline" ]] && UPDATE_BASELINE=1

RED='\033[0;31m'; YELLOW='\033[1;33m'; GREEN='\033[0;32m'; CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'
GATE_FAIL=0; GATE_WARN=0
pass()  { echo -e "${GREEN}[PASS]${NC} $*"; }
fail()  { echo -e "${RED}[FAIL]${NC} $*"; GATE_FAIL=$((GATE_FAIL + 1)); }
warn()  { echo -e "${YELLOW}[WARN]${NC} $*"; GATE_WARN=$((GATE_WARN + 1)); }
info()  { echo -e "${CYAN}[INFO]${NC} $*"; }
title() { echo -e "\n${BOLD}=== $* ===${NC}"; }

command -v php >/dev/null || { echo "php が必要です"; exit 1; }
[[ -f "$THEME_JSON" ]] || { echo "theme.json が見つかりません: $THEME_JSON"; exit 1; }

# 期待する尺度（層 1 の不変条件）。変更は設計判断（CHANGELOG 記載）を伴う。
EXPECT_FONT_SLUGS="small medium large x-large xx-large xxx-large"
EXPECT_SPACE_SLUGS="10 20 30 40 50 60"

# ---------------------------------------------------------------------------
# G-T1 トークン形状
# ---------------------------------------------------------------------------
title "G-T1 トークン形状（theme.json）"
T1=$(php -r '
$t=json_decode(file_get_contents($argv[1]),true);
$s=$t["settings"]??[]; $ty=$s["typography"]??[]; $sp=$s["spacing"]??[]; $ly=$s["layout"]??[];
$fs=array_map(fn($x)=>$x["slug"],$ty["fontSizes"]??[]);
$ss=array_map(fn($x)=>$x["slug"],$sp["spacingSizes"]??[]);
echo json_encode([
 "version"=>$t["version"]??null,
 "defaultFontSizes"=>$ty["defaultFontSizes"]??null,
 "defaultSpacingSizes"=>$sp["defaultSpacingSizes"]??null,
 "hasSpacingScale"=>isset($sp["spacingScale"]),
 "fontSlugs"=>implode(" ",$fs),
 "spaceSlugs"=>implode(" ",$ss),
 "contentSize"=>$ly["contentSize"]??null,
 "wideSize"=>$ly["wideSize"]??null,
]);' "$THEME_JSON")
get() { php -r '$j=json_decode($argv[1],true); $v=$j[$argv[2]]??null; echo is_bool($v)?($v?"true":"false"):(is_null($v)?"null":$v);' "$T1" "$1"; }

[[ "$(get version)" == "3" ]] && pass "theme.json version 3" || fail "theme.json version が 3 ではない: $(get version)"
[[ "$(get defaultFontSizes)" == "false" ]] && pass "typography.defaultFontSizes = false（コア既定を排除）" || fail "typography.defaultFontSizes が false ではない（コア既定がテーマ側スラッグを上書きする）: $(get defaultFontSizes)"
[[ "$(get defaultSpacingSizes)" == "false" ]] && pass "spacing.defaultSpacingSizes = false" || fail "spacing.defaultSpacingSizes が false ではない: $(get defaultSpacingSizes)"
[[ "$(get hasSpacingScale)" == "false" ]] && pass "spacingScale なし（spacingSizes が唯一の正本）" || fail "spacingScale と spacingSizes が併記されている"
[[ "$(get fontSlugs)" == "$EXPECT_FONT_SLUGS" ]] && pass "フォント尺度 6 段・スラッグ固定: $(get fontSlugs)" || fail "フォント尺度が期待と異なる: got [$(get fontSlugs)] expected [$EXPECT_FONT_SLUGS]"
[[ "$(get spaceSlugs)" == "$EXPECT_SPACE_SLUGS" ]] && pass "余白尺度 6 段・スラッグ固定: $(get spaceSlugs)" || fail "余白尺度が期待と異なる: got [$(get spaceSlugs)] expected [$EXPECT_SPACE_SLUGS]"
[[ "$(get contentSize)" != "null" && "$(get wideSize)" != "null" ]] && pass "layout.contentSize / wideSize 定義あり ($(get contentSize) / $(get wideSize))" || fail "layout.contentSize / wideSize が未定義"

# ---------------------------------------------------------------------------
# G-T2 生値禁止（baseline 方式）
# ---------------------------------------------------------------------------
title "G-T2 生値禁止（patterns / parts / templates）"
# 対象: ブロック属性 JSON 内のサイズ・余白キーに数値+単位、または style 属性内の数値 px/rem/em
# 許容: 0、1px（罫線）、% 、var(...) 参照
RAW_RE='"(fontSize|padding|margin|width|height|minHeight|gap|blockGap|radius|top|bottom|left|right)":"([0-9]*\.?[0-9]+)(px|rem|em)"'
STYLE_RE='style="[^"]*[^-a-z0-9(]([2-9]|[1-9][0-9]+|[0-9]*\.[0-9]+)(px|rem|em)'
count_raw() {
  local dir="$1"; local n=0
  [[ -d "$dir" ]] || { echo 0; return; }
  n=$(grep -rhoE "$RAW_RE" "$dir" 2>/dev/null | grep -vE '":"(0|1)(px|rem|em)"' | wc -l)
  local m
  m=$(grep -rhoE "$STYLE_RE" "$dir" 2>/dev/null | wc -l)
  echo $((n + m))
}
RAW_PAT=$(count_raw "${THEME_DIR}/patterns"); RAW_PART=$(count_raw "${THEME_DIR}/parts"); RAW_TPL=$(count_raw "${THEME_DIR}/templates")
RAW_TOTAL=$((RAW_PAT + RAW_PART + RAW_TPL))
info "生値検出: patterns=${RAW_PAT} parts=${RAW_PART} templates=${RAW_TPL} 合計=${RAW_TOTAL}"
if [[ $UPDATE_BASELINE -eq 1 ]]; then
  mkdir -p "$(dirname "$BASELINE")"
  printf '{\n  "_comment": "G-T2 生値禁止ゲートの許容上限。減らすことはできるが増やす変更は設計判断を要する。",\n  "raw_size_values_max": %d,\n  "updated": "%s"\n}\n' "$RAW_TOTAL" "$(date -u +%Y-%m-%d)" > "$BASELINE"
  info "baseline を更新: $BASELINE (max=$RAW_TOTAL)"
fi
if [[ -f "$BASELINE" ]]; then
  MAX=$(php -r 'echo (int)(json_decode(file_get_contents($argv[1]),true)["raw_size_values_max"]??0);' "$BASELINE")
  if [[ $RAW_TOTAL -le $MAX ]]; then pass "生値 ${RAW_TOTAL} 件 ≤ baseline ${MAX}（増加なし）"; else fail "生値が baseline を超過: ${RAW_TOTAL} > ${MAX}（新規の生 px/rem/em はプリセット参照 var:preset|... に置き換える）"; fi
  [[ $RAW_TOTAL -gt 0 ]] && warn "既存の生値 ${RAW_TOTAL} 件は THEME-CAT-03 の部品整理で段階的に 0 へ"
else
  warn "baseline 未作成（bin/check-design-consistency.sh --update-baseline で作成）。現状 ${RAW_TOTAL} 件"
fi

# ---------------------------------------------------------------------------
# G-T3 階層（見出しプリセットの単調性）
# ---------------------------------------------------------------------------
title "G-T3 見出し階層（theme.json styles.elements）"
T3=$(php -r '
$t=json_decode(file_get_contents($argv[1]),true);
$order=array_flip(["small","medium","large","x-large","xx-large","xxx-large"]);
$el=$t["styles"]["elements"]??[]; $out=[]; $prev=null; $ok=true;
foreach(["h1","h2","h3","h4"] as $h){ $fs=$el[$h]["typography"]["fontSize"]??null; $slug=null;
  if(is_string($fs)&&preg_match("/var:preset\|font-size\|([a-z-]+)|--wp--preset--font-size--([a-z-]+)/",$fs,$m)) $slug=$m[1]?:($m[2]??null);
  $rank=$slug!==null&&isset($order[$slug])?$order[$slug]:null; $out[]=$h."=".($slug??"(未定義)");
  if($rank!==null&&$prev!==null&&$rank>$prev)$ok=false; if($rank!==null)$prev=$rank; }
echo ($ok?"OK":"NG")." ".implode(" ",$out);' "$THEME_JSON")
if [[ "$T3" == OK* ]]; then pass "見出し階層が単調非増加: ${T3#OK }"; else fail "見出し階層が逆転している: ${T3#NG }"; fi
case "$T3" in *"(未定義)"*) warn "styles.elements に fontSize 未定義の見出しがある（コア既定に依存）: ${T3#* }";; esac

# ---------------------------------------------------------------------------
# G-S1 骨格境界（子テーマは層 1 を持たない）
# ---------------------------------------------------------------------------
title "G-S1 骨格境界（子テーマ theme.json）"
S1_FOUND=0
for f in $CHILD_JSON_GLOB; do
  [[ "$f" == "$THEME_JSON" ]] && continue
  [[ -f "$f" ]] || continue
  S1_FOUND=1
  BAD=$(php -r '$t=json_decode(file_get_contents($argv[1]),true); $s=$t["settings"]??[]; $bad=[]; foreach(["typography","spacing","color"] as $k){ if(isset($s[$k])) $bad[]=$k; } echo implode(",",$bad);' "$f")
  if [[ -z "$BAD" ]]; then pass "子テーマ $(basename "$(dirname "$f")") は層 1 を再定義していない"; else fail "子テーマ $(basename "$(dirname "$f")") が settings.{$BAD} を再定義している（層 1 の正本は親 theme.json のみ）"; fi
done
[[ $S1_FOUND -eq 0 ]] && info "リポジトリ内に子テーマなし（PoC の helix-neo はリポ外）"

# ---------------------------------------------------------------------------
# G-S2 参照整合（template-part / pattern の存在）
# ---------------------------------------------------------------------------
title "G-S2 参照整合（templates / parts → parts / patterns）"
S2_FAIL=0
PATTERN_SLUGS=$(grep -rhoE '^\s*\*\s*Slug:\s*agent-neo/[a-z0-9-]+' "${THEME_DIR}/patterns" 2>/dev/null | sed -E 's/.*Slug:\s*//' | sort -u)
for f in "${THEME_DIR}"/templates/*.html "${THEME_DIR}"/parts/*.html; do
  [[ -f "$f" ]] || continue
  for ref in $(grep -oE 'wp:template-part \{"slug":"[a-z0-9-]+"' "$f" | sed -E 's/.*"slug":"//; s/"$//' | sort -u); do
    [[ -f "${THEME_DIR}/parts/${ref}.html" ]] || { fail "$(basename "$f") が存在しない template-part を参照: $ref"; S2_FAIL=1; }
  done
  for ref in $(grep -oE 'wp:pattern \{"slug":"agent-neo/[a-z0-9-]+"' "$f" | sed -E 's/.*"slug":"//; s/"$//' | sort -u); do
    grep -qx "$ref" <<< "$PATTERN_SLUGS" || { fail "$(basename "$f") が存在しない pattern を参照: $ref"; S2_FAIL=1; }
  done
done
[[ $S2_FAIL -eq 0 ]] && pass "template-part / pattern 参照はすべて解決"

# ---------------------------------------------------------------------------
title "結果"
echo "FAIL=${GATE_FAIL} WARN=${GATE_WARN}"
[[ $GATE_FAIL -eq 0 ]] && exit 0 || exit 1
