#!/usr/bin/env bash
# =============================================================================
# check-theme-quality.sh
# AGENT NEO テーマ品質ゲート pipeline（T-023 / CARRY-G2-013）
#
# 検査対象: themes/agent-neo-theme/
# ゲート: i18n / RTL / a11y（axe-core） / perf（静的）
# 終了コード: 0=全PASS  1=FAILあり
# =============================================================================
set -euo pipefail

# ---------------------------------------------------------------------------
# 設定
# ---------------------------------------------------------------------------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
THEME_DIR="${REPO_ROOT}/themes/agent-neo-theme"
BUDGET_FILE="${THEME_DIR}/config/web-vitals-budget.json"
I18N_FILE="${THEME_DIR}/config/i18n-profile.json"
WP_URL="${WP_URL:-http://localhost:8086}"

# ---------------------------------------------------------------------------
# カラー出力
# ---------------------------------------------------------------------------
RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

GATE_FAIL=0  # FAILカウンター（1以上で exit 1）
GATE_WARN=0  # WARNカウンター

pass()  { echo -e "${GREEN}[PASS]${NC} $*"; }
fail()  { echo -e "${RED}[FAIL]${NC} $*"; GATE_FAIL=$((GATE_FAIL + 1)); }
warn()  { echo -e "${YELLOW}[WARN]${NC} $*"; GATE_WARN=$((GATE_WARN + 1)); }
info()  { echo -e "${CYAN}[INFO]${NC} $*"; }
title() { echo -e "\n${BOLD}=== $* ===${NC}"; }

# ---------------------------------------------------------------------------
# ヘルパー: テーマ PHP ファイル一覧
# ---------------------------------------------------------------------------
theme_php_files() {
  find "${THEME_DIR}" -name "*.php" -type f
}

# =============================================================================
# GATE 1: i18n ゲート
# =============================================================================
title "GATE 1: i18n（text domain / 未翻訳文字列）"

TEXT_DOMAIN="agent-neo"
I18N_ERRORS=0
I18N_WARNS=0

# --- 1a. text domain 不一致チェック ---
info "1a. text domain チェック（期待値: '${TEXT_DOMAIN}'）"
while IFS= read -r file; do
  # i18n 関数を使っているのに text domain が違う行を検出
  wrong=$(grep -nP "__\s*\(|_e\s*\(|esc_html__\s*\(|esc_html_e\s*\(|esc_attr__\s*\(|esc_attr_e\s*\(" "${file}" 2>/dev/null \
    | grep -v "'" \
    || true)
  # i18n 関数の第2引数（text domain）が 'agent-neo' と異なるものを検出
  mismatched=$(grep -nP "(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\s*\([^,]+,\s*'(?!${TEXT_DOMAIN}')" "${file}" 2>/dev/null || true)
  if [ -n "$mismatched" ]; then
    while IFS= read -r line; do
      fail "text domain 不一致: ${file}:${line}"
      I18N_ERRORS=$((I18N_ERRORS + 1))
    done <<< "$mismatched"
  fi
done < <(theme_php_files)

if [ "$I18N_ERRORS" -eq 0 ]; then
  pass "text domain 全一致（'${TEXT_DOMAIN}'）"
fi

# --- 1b. ハードコード日本語文字列の検出 ---
info "1b. ハードコード日本語文字列チェック（echo の生 UTF-8 テキスト）"
HARDCODE_COUNT=0

# 「日本語」の判定を実 CJK 範囲に限定する。
# em-dash（U+2014）等の一般句読点・記号は対象外。
# 対象: ひらがな / カタカナ / CJK統合漢字 / CJK記号と句読点 / 全角形
CJK_PATTERN='[\p{Hiragana}\p{Katakana}\p{Han}\x{3000}-\x{303F}\x{FF00}-\x{FFEF}]'

while IFS= read -r file; do
  # CJK 文字を含む行を候補として取得
  while IFS= read -r match; do
    # i18n 関数でラップされている行は除外
    if echo "$match" | grep -qP 'esc_html_e|esc_html__|esc_attr_e|esc_attr__|_e\s*\(|__\s*\('; then
      continue
    fi
    # PHP コメント行（* // # /*）はスキップ（行番号:コンテンツ 形式）
    if echo "$match" | grep -qP '^\s*[0-9]+:\s*(\*|//|#|/\*)'; then
      continue
    fi
    # 行内コメント（// 以降）を除去してから CJK を再判定する。
    # 除去後に CJK が残らない場合は「コメント内の日本語」なのでスキップ。
    # （例: echo $var; // 日本語コメント → スキップ）
    stripped=$(echo "$match" | sed 's|//.*||')
    if ! echo "$stripped" | grep -qP "${CJK_PATTERN}"; then
      continue
    fi
    # PHP の echo/print で日本語を直書きしている行（単語境界で一致: blueprint/sprint を除外）
    if echo "$stripped" | grep -qP '\b(echo|print)\b'; then
      fail "未翻訳ハードコード: ${file}: $(echo "${match}" | head -c 120)"
      HARDCODE_COUNT=$((HARDCODE_COUNT + 1))
    fi
  done < <(grep -nP "${CJK_PATTERN}" "${file}" 2>/dev/null || true)
done < <(theme_php_files)

# パターンファイルの HTML 内（WordPress ブロックコメント外）のハードコードを検出
info "1c. パターンファイル HTML 内ハードコード文字列チェック"
PATTERN_HARDCODE=0
while IFS= read -r pfile; do
  # PHP ヒアドキュメント / HTML 部分の日本語直書き（i18n 関数非使用）
  # wp:heading や wp:paragraph の content に直接書かれた日本語テキストを検出
  # （PHP echo 外の HTML テキストノード）
  # 「日本語」判定を CJK 範囲に限定（em-dash 等の一般記号は対象外）
  matches=$(grep -nP ">(?:[^<]*)${CJK_PATTERN}+(?:[^<]*)<" "${pfile}" 2>/dev/null \
    | grep -vP '<!--.*-->' \
    || true)
  if [ -n "$matches" ]; then
    while IFS= read -r line; do
      # 1b と同様に esc_* でラップ済みの行はスキップ（FP 回避）
      if echo "$line" | grep -qP 'esc_html_e|esc_html__|esc_attr_e|esc_attr__|_e\s*\(|__\s*\('; then
        continue
      fi
      warn "HTML内ハードコード（パターンファイル・改善推奨）: ${pfile}: $(echo "${line}" | head -c 100)"
      PATTERN_HARDCODE=$((PATTERN_HARDCODE + 1))
    done <<< "$matches"
  fi
done < <(find "${THEME_DIR}/patterns" -name "*.php" -type f 2>/dev/null)

# i18n ゲート総評
if [ "$HARDCODE_COUNT" -gt 0 ]; then
  fail "i18n GATE: PHP ハードコード日本語 ${HARDCODE_COUNT} 件（FAIL）"
else
  pass "i18n GATE: PHP ハードコード日本語 0 件"
fi
if [ "$PATTERN_HARDCODE" -gt 0 ]; then
  warn "i18n GATE: パターン HTML 内ハードコード ${PATTERN_HARDCODE} 件（WARN / 次の改善項目）"
fi


# =============================================================================
# GATE 2: RTL ゲート（logical properties 静的検査）
# =============================================================================
title "GATE 2: RTL（CSS logical properties）"

# 対象: *.css + theme.json の spacing/padding/margin の "left"/"right" キー
RTL_FAIL=0
RTL_WARN=0

# --- 2a. CSS ファイルの物理プロパティ検出 ---
info "2a. CSS ファイル物理プロパティ検出"
while IFS= read -r cssfile; do
  # 禁止: margin-left / margin-right / padding-left / padding-right
  # 例外: border-radius, top, bottom は許容
  matches=$(grep -nP 'margin-(?:left|right)\s*:|padding-(?:left|right)\s*:|border-(?:left|right)\s*:' "${cssfile}" 2>/dev/null || true)
  if [ -n "$matches" ]; then
    while IFS= read -r line; do
      fail "物理プロパティ（RTL non-safe）: ${cssfile}: ${line}"
      RTL_FAIL=$((RTL_FAIL + 1))
    done <<< "$matches"
  fi
done < <(find "${THEME_DIR}" -name "*.css" -type f 2>/dev/null)

if [ "$RTL_FAIL" -eq 0 ]; then
  pass "CSS ファイル: 物理プロパティ 0 件"
fi

# --- 2b. theme.json の spacing.padding 物理キー検出 ---
info "2b. theme.json spacing 物理キー（'left' / 'right'）検出"
if [ -f "${THEME_DIR}/theme.json" ]; then
  # spacing.padding.left / spacing.padding.right を検出
  phys_json=$(python3 -c "
import json, sys

def find_physical(obj, path=''):
    results = []
    if isinstance(obj, dict):
        for k, v in obj.items():
            new_path = f'{path}.{k}' if path else k
            if k in ('left', 'right') and 'padding' in path.lower():
                results.append(f'  {new_path} = {json.dumps(v)}')
            results.extend(find_physical(v, new_path))
    elif isinstance(obj, list):
        for i, v in enumerate(obj):
            results.extend(find_physical(v, f'{path}[{i}]'))
    return results

with open('${THEME_DIR}/theme.json') as f:
    data = json.load(f)
hits = find_physical(data)
for h in hits:
    print(h)
" 2>/dev/null || true)

  if [ -n "$phys_json" ]; then
    while IFS= read -r line; do
      warn "theme.json spacing 物理キー（WARN / theme.json は padding logical 変換を推奨）: ${line}"
      RTL_WARN=$((RTL_WARN + 1))
    done <<< "$phys_json"
  else
    pass "theme.json: 物理 padding キー 0 件"
  fi
fi

# --- 2c. logical properties 使用確認 ---
info "2c. logical properties 使用確認（margin-inline-start 等）"
LOGICAL_COUNT=$(grep -rn 'margin-inline\|padding-inline\|inset-inline\|border-inline' "${THEME_DIR}/" --include="*.css" 2>/dev/null | wc -l | tr -d ' ') || true
LOGICAL_COUNT=${LOGICAL_COUNT:-0}
if [ "$LOGICAL_COUNT" -gt 0 ]; then
  pass "logical properties 使用確認: ${LOGICAL_COUNT} 件"
else
  warn "logical properties 未使用（CSS ファイルに margin-inline 等が見つからない）"
  RTL_WARN=$((RTL_WARN + 1))
fi

# RTL ゲート総評
if [ "$RTL_FAIL" -gt 0 ]; then
  fail "RTL GATE: FAIL ${RTL_FAIL} 件"
else
  pass "RTL GATE: CSS 物理プロパティ違反 0 件"
fi
if [ "$RTL_WARN" -gt 0 ]; then
  warn "RTL GATE: WARN ${RTL_WARN} 件（改善推奨）"
fi


# =============================================================================
# GATE 3: a11y ゲート（axe-core via Playwright）
# =============================================================================
title "GATE 3: a11y（axe-core / critical・serious 違反 0 が合格）"

AXE_AVAILABLE=false
AXE_FAIL=0

# axe-core CLI または Playwright + axe-core の存在確認
if command -v axe &>/dev/null; then
  AXE_AVAILABLE=true
  AXE_METHOD="axe-cli"
elif command -v npx &>/dev/null && npx --yes axe-core --version &>/dev/null 2>&1; then
  AXE_AVAILABLE=true
  AXE_METHOD="npx-axe"
elif python3 -c "import playwright" &>/dev/null 2>&1; then
  AXE_AVAILABLE=true
  AXE_METHOD="playwright-python"
fi

# WP 稼働確認
WP_ALIVE=false
if curl -s -o /dev/null -w "%{http_code}" "${WP_URL}/" 2>/dev/null | grep -q "200\|301\|302"; then
  WP_ALIVE=true
  info "WP 稼働確認: ${WP_URL} OK"
else
  warn "WP 未稼働（${WP_URL}）: a11y ゲートをスキップ"
fi

if [ "$WP_ALIVE" = "true" ] && [ "$AXE_AVAILABLE" = "true" ]; then
  info "axe-core 実行方法: ${AXE_METHOD}"

  # ---------------------------------------------------------------------------
  # axe.min.js のローカルキャッシュ確認（なければ一度だけ取得）
  # ---------------------------------------------------------------------------
  AXE_VENDOR_JS="${SCRIPT_DIR}/vendor/axe.min.js"
  AXE_CDN_URL="https://cdnjs.cloudflare.com/ajax/libs/axe-core/4.9.1/axe.min.js"

  if [ ! -f "${AXE_VENDOR_JS}" ]; then
    info "axe.min.js ローカルキャッシュなし → CDN から取得を試みます（1回限り）"
    mkdir -p "${SCRIPT_DIR}/vendor"
    if curl -sL --max-time 20 "${AXE_CDN_URL}" -o "${AXE_VENDOR_JS}" 2>/dev/null \
       && [ -s "${AXE_VENDOR_JS}" ]; then
      info "axe.min.js 取得成功: ${AXE_VENDOR_JS}"
    else
      warn "axe.min.js の取得に失敗しました。a11y ゲートをスキップします（WARN）"
      AXE_AVAILABLE=false
    fi
  else
    info "axe.min.js ローカルキャッシュ使用: ${AXE_VENDOR_JS}"
  fi

  # ---------------------------------------------------------------------------
  # Playwright Python で axe-core を実行（最大 90s で打ち切り）
  # ---------------------------------------------------------------------------
  AXE_RESULT_FILE="/tmp/axe-results-$$.json"

  if [ "$AXE_AVAILABLE" = "true" ]; then
    timeout 90 python3 - <<PYEOF 2>/tmp/axe-stderr-$$.txt
import json, os, shutil, sys
from pathlib import Path
from playwright.sync_api import sync_playwright

wp_url = os.environ.get("WP_URL", "http://localhost:8086")
result_file = "/tmp/axe-results-$$.json"
axe_js_path = "${AXE_VENDOR_JS}"

# Playwright キャッシュ済 Chromium を探す（verify.py と同パターン）
def chromium_executable():
    cache_root = Path.home() / ".cache" / "ms-playwright"
    bundled = sorted(cache_root.glob("chromium-*/chrome-linux*/chrome"), reverse=True)
    if bundled:
        return str(bundled[0])
    for name in ("chromium", "chromium-browser", "google-chrome"):
        path = shutil.which(name)
        if path:
            return path
    return None

AXE_CONFIG = {
    "runOnly": {
        "type": "tag",
        "values": ["wcag2a", "wcag2aa", "best-practice"]
    },
    "resultTypes": ["violations", "incomplete"]
}

PAGES = [wp_url + "/"]

results = {}
executable_path = chromium_executable()
launch_options = {
    "headless": True,
    "timeout": 15000,
    "args": ["--no-sandbox", "--disable-setuid-sandbox",
             "--disable-crash-reporter", "--disable-crashpad"],
}
if executable_path:
    launch_options["executable_path"] = executable_path

try:
    with sync_playwright() as p:
        browser = p.chromium.launch(**launch_options)
        page = browser.new_page()
        for url in PAGES:
            try:
                page.goto(url, wait_until="domcontentloaded", timeout=30000)
                # ローカルファイルから axe-core を inject
                with open(axe_js_path, "r", encoding="utf-8") as f:
                    axe_src = f.read()
                page.add_script_tag(content=axe_src)
                page.wait_for_function("() => typeof window.axe !== 'undefined'", timeout=10000)
                result = page.evaluate(
                    "([cfg]) => axe.run(document, cfg)",
                    [AXE_CONFIG]
                )
                results[url] = result
            except Exception as e:
                results[url] = {"error": str(e)}
        browser.close()
except Exception as e:
    results["__launch_error__"] = {"error": str(e)}

with open(result_file, "w", encoding="utf-8") as f:
    json.dump(results, f, ensure_ascii=False, indent=2)
print(f"axe results saved to {result_file}", flush=True)
PYEOF

    AXE_RC=$?
    # timeout(124) または他の非ゼロ終了でもファイルなければ WARN で続行
    if [ "$AXE_RC" -eq 124 ]; then
      warn "a11y GATE: axe 実行が 90s でタイムアウトしました（WARN / スキップ）"
    fi
  else
    AXE_RC=1
  fi

  if [ -f "${AXE_RESULT_FILE}" ]; then
    # 結果解析
    AXE_SUMMARY=$(python3 - <<PYEOF2 2>/dev/null
import json, sys
with open("${AXE_RESULT_FILE}") as f:
    all_results = json.load(f)

critical_count = 0
serious_count = 0
moderate_count = 0
minor_count = 0

for url, result in all_results.items():
    if "error" in result:
        print(f"ERROR [{url}]: {result['error']}")
        continue
    violations = result.get("violations", [])
    for v in violations:
        impact = v.get("impact", "")
        count = len(v.get("nodes", []))
        desc = v.get("description", v.get("id", "unknown"))
        rule_id = v.get("id", "?")
        if impact == "critical":
            critical_count += count
            print(f"CRITICAL [{url}] {rule_id}: {desc} ({count} nodes)")
        elif impact == "serious":
            serious_count += count
            print(f"SERIOUS  [{url}] {rule_id}: {desc} ({count} nodes)")
        elif impact == "moderate":
            moderate_count += count
            print(f"MODERATE [{url}] {rule_id}: {desc} ({count} nodes)")
        elif impact == "minor":
            minor_count += count
            print(f"MINOR    [{url}] {rule_id}: {desc} ({count} nodes)")

print(f"---SUMMARY--- critical={critical_count} serious={serious_count} moderate={moderate_count} minor={minor_count}")
PYEOF2
)

    # 詳細行の出力（サブシェル内なので AXE_FAIL カウントはここではしない）
    while IFS= read -r line; do
      if echo "$line" | grep -q "^CRITICAL\|^SERIOUS"; then
        fail "axe: ${line}"
      elif echo "$line" | grep -q "^MODERATE\|^MINOR"; then
        warn "axe: ${line}"
      elif echo "$line" | grep -q "^ERROR"; then
        warn "axe: ${line}"
      elif [ -n "$line" ]; then
        info "axe: ${line}"
      fi
    done < <(echo "$AXE_SUMMARY" | grep -v "^---SUMMARY---")

    # SUMMARY 行からカウントを取得（親シェルで AXE_FAIL を更新）
    SUMMARY_LINE=$(echo "$AXE_SUMMARY" | grep "^---SUMMARY---" || true)
    if [ -n "$SUMMARY_LINE" ]; then
      AXE_CRITICAL=$(echo "$SUMMARY_LINE" | grep -oP 'critical=\K[0-9]+' || echo 0)
      AXE_SERIOUS=$(echo "$SUMMARY_LINE" | grep -oP 'serious=\K[0-9]+' || echo 0)
      AXE_MODERATE=$(echo "$SUMMARY_LINE" | grep -oP 'moderate=\K[0-9]+' || echo 0)
      AXE_MINOR=$(echo "$SUMMARY_LINE" | grep -oP 'minor=\K[0-9]+' || echo 0)
      AXE_CRITICAL=${AXE_CRITICAL:-0}
      AXE_SERIOUS=${AXE_SERIOUS:-0}
      AXE_MODERATE=${AXE_MODERATE:-0}
      AXE_MINOR=${AXE_MINOR:-0}

      info "axe 結果集計: critical=${AXE_CRITICAL} serious=${AXE_SERIOUS} moderate=${AXE_MODERATE} minor=${AXE_MINOR}"

      TOTAL_FAIL_AXE=$((AXE_CRITICAL + AXE_SERIOUS))
      if [ "$TOTAL_FAIL_AXE" -eq 0 ]; then
        pass "a11y GATE: critical/serious 違反 0 件（PASS）"
        [ "$AXE_MODERATE" -gt 0 ] && warn "a11y: moderate ${AXE_MODERATE} 件（改善推奨）"
        [ "$AXE_MINOR" -gt 0 ]    && warn "a11y: minor ${AXE_MINOR} 件（改善推奨）"
      else
        fail "a11y GATE: critical ${AXE_CRITICAL} + serious ${AXE_SERIOUS} = ${TOTAL_FAIL_AXE} 件（FAIL）"
        AXE_FAIL=$((AXE_FAIL + TOTAL_FAIL_AXE))
      fi
    fi

    rm -f "${AXE_RESULT_FILE}"
  else
    warn "a11y GATE: axe 結果ファイルが生成されなかった（スキップ）"
    [ -f /tmp/axe-stderr-$$.txt ] && cat /tmp/axe-stderr-$$.txt | head -20
  fi

  rm -f /tmp/axe-stderr-$$.txt
else
  if [ "$WP_ALIVE" = "false" ]; then
    warn "a11y GATE: WP 未稼働のためスキップ（ローカル実行時は 'docker compose up' で WP を起動してください）"
  else
    warn "a11y GATE: axe 実行環境なし（Playwright / axe-cli が必要）。CI では playwright install ステップを追加してください"
  fi
fi


# =============================================================================
# GATE 4: perf ゲート（静的検査 + budget 妥当性確認）
# =============================================================================
title "GATE 4: perf（render-blocking 静的検査 / budget 妥当性確認）"

PERF_FAIL=0
PERF_WARN=0

# --- 4a. budget ファイル存在確認 ---
info "4a. web-vitals-budget.json 存在確認"
if [ -f "${BUDGET_FILE}" ]; then
  pass "budget ファイル存在: ${BUDGET_FILE}"
  # budget 値妥当性
  # 引数でパスを渡し quoted heredoc を保持（bash 展開せず sys.argv[1] で受け取る）
  # || true で set -e による abort を防ぐ。[FAIL] 行は後段で grep してフラグ化
  BUDGET_PY_OUT=$(python3 - "${BUDGET_FILE}" <<'PYEOF3' 2>/dev/null || true
import json, sys
with open(sys.argv[1]) as f:
    b = json.load(f)

errors = []
warns = []
defaults = b.get("defaults", {})

for metric, limits in defaults.items():
    if metric in ("LCP", "TTFB", "FID", "INP"):
        budget = limits.get("budget_ms", 0)
        if metric == "LCP" and budget > 4000:
            errors.append(f"{metric} budget {budget}ms > 4000ms (Core Web Vitals 基準外)")
        elif metric == "INP" and budget > 500:
            errors.append(f"{metric} budget {budget}ms > 500ms (基準外)")
    elif metric == "CLS":
        budget = limits.get("budget", 0)
        if budget > 0.25:
            errors.append(f"CLS budget {budget} > 0.25 (基準外)")

for e in errors:
    print(f"[FAIL] budget: {e}")
for w in warns:
    print(f"[WARN] budget: {w}")
if not errors:
    print("[PASS] budget 値: 全メトリクス Core Web Vitals 基準範囲内")
PYEOF3
)
  # 出力を表示し、[FAIL] があれば PERF_FAIL に加算
  echo "${BUDGET_PY_OUT}"
  if echo "${BUDGET_PY_OUT}" | grep -q '^\[FAIL\]'; then
    PERF_FAIL=$((PERF_FAIL + 1))
  fi
else
  fail "budget ファイル未存在: ${BUDGET_FILE}"
  PERF_FAIL=$((PERF_FAIL + 1))
fi

# --- 4b. テーマ PHP の同期スクリプト検出 ---
info "4b. render-blocking リソース検出（同期 script / @import 過多）"
SYNC_SCRIPT_COUNT=0
CSS_IMPORT_COUNT=0

while IFS= read -r file; do
  # wp_enqueue_script で defer/async 指定なし（古い形式）を検出
  sync_scripts=$(grep -nP "wp_enqueue_script\s*\(" "${file}" 2>/dev/null \
    | grep -vP 'defer|async|strategy' || true)
  if [ -n "$sync_scripts" ]; then
    while IFS= read -r line; do
      warn "同期 script 登録（defer/async 指定なし）: ${file}: $(echo "${line}" | head -c 100)"
      SYNC_SCRIPT_COUNT=$((SYNC_SCRIPT_COUNT + 1))
    done <<< "$sync_scripts"
  fi
done < <(theme_php_files)

while IFS= read -r cssfile; do
  import_count=$(grep -cP '@import\s' "${cssfile}" 2>/dev/null | head -1 || true)
  import_count=${import_count:-0}
  max_imports=2
  if [ "$import_count" -gt "$max_imports" ]; then
    warn "CSS @import 多用（${import_count} 件 > 上限 ${max_imports}）: ${cssfile}"
    CSS_IMPORT_COUNT=$((CSS_IMPORT_COUNT + 1))
  fi
done < <(find "${THEME_DIR}" -name "*.css" -type f 2>/dev/null)

if [ "$SYNC_SCRIPT_COUNT" -eq 0 ]; then
  pass "同期 script: 0 件"
fi
if [ "$CSS_IMPORT_COUNT" -eq 0 ]; then
  pass "CSS @import 過多: 0 件"
fi

# --- 4c. Lighthouse CI 案内（フル測定は CI workflow 内） ---
info "4c. 実 LCP/INP/CLS 測定"
info "フル Core Web Vitals 測定は CI workflow の lhci ステップで実施"
info "ローカル実行: npx lhci autorun --config=lighthouserc.json（要: WP 稼働中）"
warn "perf GATE: 実測 LCP/INP/CLS は CI（lhci ステップ）で検証してください"

# perf ゲート総評
if [ "$PERF_FAIL" -gt 0 ]; then
  fail "perf GATE: FAIL ${PERF_FAIL} 件"
else
  pass "perf GATE: 静的検査 PASS"
fi


# =============================================================================
# GATE 5: Theme Review Checklist（静的検査）
# REQ-NF-016 / ACC-NF-010
# =============================================================================
title "GATE 5: Theme Review Checklist（静的検査）"

REVIEW_FAIL=0
REVIEW_WARN=0

# --- 5a. screenshot.png 存在確認 ---
info "5a. screenshot.png 存在確認"
if [ -f "${THEME_DIR}/screenshot.png" ]; then
  pass "screenshot.png 存在"
else
  fail "screenshot.png が存在しません（${THEME_DIR}/screenshot.png）"
  REVIEW_FAIL=$((REVIEW_FAIL + 1))
fi

# --- 5b. style.css 必須ヘッダー項目確認 ---
info "5b. style.css 必須ヘッダー項目確認"
STYLE_CSS="${THEME_DIR}/style.css"
REQUIRED_HEADERS=("Theme Name" "Version" "License" "License URI" "Text Domain" "Requires at least" "Requires PHP")
HEADER_FAIL=0

if [ -f "${STYLE_CSS}" ]; then
  # style.css の先頭コメントブロック（最大 50 行）を取得
  HEADER_BLOCK=$(head -50 "${STYLE_CSS}")
  for header in "${REQUIRED_HEADERS[@]}"; do
    if echo "${HEADER_BLOCK}" | grep -qiP "^\s*\*?\s*${header}\s*:"; then
      pass "style.css ヘッダー存在: ${header}"
    else
      fail "style.css ヘッダー欠落: ${header}"
      HEADER_FAIL=$((HEADER_FAIL + 1))
      REVIEW_FAIL=$((REVIEW_FAIL + 1))
    fi
  done
else
  fail "style.css が存在しません: ${STYLE_CSS}"
  REVIEW_FAIL=$((REVIEW_FAIL + 1))
fi

# --- 5c. README.md または readme.txt 存在確認 ---
info "5c. README.md / readme.txt 存在確認"
if [ -f "${THEME_DIR}/README.md" ] || [ -f "${THEME_DIR}/readme.txt" ]; then
  FOUND_README=""
  [ -f "${THEME_DIR}/README.md" ] && FOUND_README="README.md"
  [ -f "${THEME_DIR}/readme.txt" ] && FOUND_README="${FOUND_README:+${FOUND_README}, }readme.txt"
  pass "README ファイル存在: ${FOUND_README}"
else
  fail "README.md / readme.txt が存在しません"
  REVIEW_FAIL=$((REVIEW_FAIL + 1))
fi

# --- 5d. 必須 FSE テンプレート確認 ---
info "5d. 必須 FSE テンプレートファイル確認"
REQUIRED_TEMPLATES=("index.html" "single.html" "page.html" "archive.html" "search.html" "404.html")
TEMPLATE_DIR="${THEME_DIR}/templates"
TEMPLATE_FAIL=0

for tpl in "${REQUIRED_TEMPLATES[@]}"; do
  if [ -f "${TEMPLATE_DIR}/${tpl}" ]; then
    pass "必須テンプレート存在: templates/${tpl}"
  else
    fail "必須テンプレート欠落: templates/${tpl}"
    TEMPLATE_FAIL=$((TEMPLATE_FAIL + 1))
    REVIEW_FAIL=$((REVIEW_FAIL + 1))
  fi
done

# --- 5e. languages/ に .pot ファイル存在確認 ---
info "5e. languages/ .pot ファイル存在確認"
LANG_DIR="${THEME_DIR}/languages"
POT_COUNT=$(find "${LANG_DIR}" -name "*.pot" -type f 2>/dev/null | wc -l | tr -d ' ')
POT_COUNT=${POT_COUNT:-0}

if [ "${POT_COUNT}" -gt 0 ]; then
  POT_FILES=$(find "${LANG_DIR}" -name "*.pot" -type f 2>/dev/null | head -5 | xargs -I{} basename {})
  pass ".pot ファイル存在（${POT_COUNT} 件）: ${POT_FILES}"
else
  fail "languages/ に .pot ファイルが存在しません"
  REVIEW_FAIL=$((REVIEW_FAIL + 1))
fi

# --- 5f. theme.json customTemplates の実体ファイル整合確認 ---
info "5f. theme.json customTemplates ↔ templates/ 実体ファイル整合確認"
if [ -f "${THEME_DIR}/theme.json" ]; then
  # 環境変数でパスを渡し、heredoc 内は展開しない（set -e と相性が良い形式）
  CUSTOM_TPL_CHECK=$(
    export THEME_JSON_PATH="${THEME_DIR}/theme.json"
    export TEMPLATE_DIR_PATH="${TEMPLATE_DIR}"
    python3 - <<'PYEOF_TPL' 2>/dev/null || true
import json, sys, os

theme_json_path = os.environ.get("THEME_JSON_PATH", "")
template_dir    = os.environ.get("TEMPLATE_DIR_PATH", "")

if not theme_json_path:
    print("SKIP: THEME_JSON_PATH 未設定")
    sys.exit(0)

with open(theme_json_path) as f:
    data = json.load(f)

custom_templates = data.get("customTemplates", [])
if not custom_templates:
    print("SKIP: customTemplates 宣言なし（0件）")
    sys.exit(0)

errors = []
for tpl in custom_templates:
    name = tpl.get("name", "")
    title = tpl.get("title", name)
    expected_file = os.path.join(template_dir, f"{name}.html")
    if os.path.isfile(expected_file):
        print(f"PASS: customTemplate '{name}' ({title}) -> templates/{name}.html 存在")
    else:
        print(f"FAIL: customTemplate '{name}' ({title}) -> templates/{name}.html が見つかりません")
        errors.append(name)

if errors:
    print(f"---CT_FAIL--- count={len(errors)}")
else:
    print("---CT_PASS---")
PYEOF_TPL
  )

  # 結果を1行ずつ処理
  while IFS= read -r line; do
    if echo "${line}" | grep -q "^PASS:"; then
      pass "${line#PASS: }"
    elif echo "${line}" | grep -q "^FAIL:"; then
      fail "${line#FAIL: }"
      REVIEW_FAIL=$((REVIEW_FAIL + 1))
    elif echo "${line}" | grep -q "^SKIP:"; then
      info "${line#SKIP: }"
    elif echo "${line}" | grep -q "^---CT_FAIL---"; then
      CT_COUNT=$(echo "${line}" | grep -oP 'count=\K[0-9]+' || echo "?")
      fail "customTemplate 実体不整合: ${CT_COUNT} 件"
    fi
  done <<< "${CUSTOM_TPL_CHECK}"

  if echo "${CUSTOM_TPL_CHECK}" | grep -q "^---CT_PASS---"; then
    pass "customTemplates 全件実体ファイル確認（整合）"
  fi
else
  warn "theme.json が存在しないため customTemplates チェックをスキップ"
  REVIEW_WARN=$((REVIEW_WARN + 1))
fi

# GATE 5 総評
if [ "${REVIEW_FAIL}" -gt 0 ]; then
  fail "Theme Review GATE: FAIL ${REVIEW_FAIL} 件"
else
  pass "Theme Review GATE: 全項目 PASS"
fi
if [ "${REVIEW_WARN}" -gt 0 ]; then
  warn "Theme Review GATE: WARN ${REVIEW_WARN} 件"
fi


# =============================================================================
# GATE 6: synced パターン不在チェック（WARN only / REQ-NF-026-c / FC-008 / AC6）
# =============================================================================
title "GATE 6: synced パターン不在チェック（patterns/ 内に同期パターン参照がないこと）"

SYNCED_WARN=0
PATTERNS_DIR="${THEME_DIR}/patterns"

if [ -d "${PATTERNS_DIR}" ]; then
  info "6a. patterns/ 配下 .php ファイルに Synced: yes / wp:block 参照 / core/block 参照がないことを確認"

  while IFS= read -r pfile; do
    # 「Synced: yes」（再利用ブロックヘッダー）
    synced_yes=$(grep -nP 'Synced\s*:\s*yes' "${pfile}" 2>/dev/null || true)
    # 「<!-- wp:block ...」（コア再利用ブロック参照）
    wp_block=$(grep -nP '<!--\s*wp:block\s' "${pfile}" 2>/dev/null || true)
    # 「core/block」（同上の別表記パターン）
    core_block=$(grep -nP '\bcore/block\b' "${pfile}" 2>/dev/null || true)

    for match in "$synced_yes" "$wp_block" "$core_block"; do
      if [ -n "$match" ]; then
        while IFS= read -r line; do
          warn "synced パターン参照検出（非同期化を推奨）: ${pfile}: $(echo "${line}" | head -c 120)"
          SYNCED_WARN=$((SYNCED_WARN + 1))
        done <<< "$match"
      fi
    done
  done < <(find "${PATTERNS_DIR}" -name "*.php" -type f 2>/dev/null)

  if [ "${SYNCED_WARN}" -eq 0 ]; then
    pass "synced パターン: patterns/ 内に同期パターン参照 0 件（PASS）"
  else
    warn "synced パターン GATE: ${SYNCED_WARN} 件の同期パターン参照を検出（WARN のみ / gate は落とさない）"
  fi
else
  info "patterns/ ディレクトリ未存在のため synced チェックをスキップ"
fi


# =============================================================================
# 最終サマリ
# =============================================================================
title "品質ゲート サマリ"
echo ""
echo "  FAIL : ${GATE_FAIL} 件"
echo "  WARN : ${GATE_WARN} 件"
echo ""

if [ "$GATE_FAIL" -gt 0 ]; then
  echo -e "${RED}${BOLD}RESULT: FAIL（${GATE_FAIL} 件の FAIL があります）${NC}"
  exit 1
else
  echo -e "${GREEN}${BOLD}RESULT: PASS（全ゲート通過）${NC}"
  exit 0
fi
