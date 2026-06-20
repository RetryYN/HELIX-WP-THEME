#!/usr/bin/env bash
set -euo pipefail

# docs/design/api-catalog.md の契約 endpoint と、PHP 実装で登録された REST route を突合する。
#
# 使い方:
#   bin/check-impl-coverage.sh
#   bin/check-impl-coverage.sh --fail-under 100
#   bin/check-impl-coverage.sh --fail-under 57 --strict-orphan
#
# 出力例（現状の初期実装）:
#   EXPECTED: 57
#   COVERED (1)
#     GET /status
#   MISSING (56)
#     POST /actions/dry-run
#     ...
#   ORPHAN (0)
#   coverage = 1/57 (1%)
#
# 注記:
# - agent-neo/v1 は契約側の相対 path（/status 等）へ正規化して比較する。
# - aseo/v1 など他 namespace は /aseo/v1/... の形で比較する。
# - register_rest_route() 直呼びに加え、既存基底の register_agent_route() も抽出する。

readonly EXPECTED_TOTAL=57
readonly CONTRACT_FILE="docs/design/api-catalog.md"
readonly SCAN_DIRS=("plugins/agent-neo-core" "themes/agent-neo-theme")

fail_under=""
strict_orphan=0

usage() {
  echo "usage: $0 [--fail-under N|N%] [--strict-orphan]" >&2
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --fail-under)
      [[ $# -ge 2 ]] || { usage; exit 2; }
      fail_under="$2"
      shift 2
      ;;
    --strict-orphan)
      strict_orphan=1
      shift
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      usage
      exit 2
      ;;
  esac
done

if [[ ! -f "$CONTRACT_FILE" ]]; then
  echo "missing contract file: $CONTRACT_FILE" >&2
  exit 1
fi

tmpdir="$(mktemp -d)"
trap 'rm -rf "$tmpdir"' EXIT

normalize_pairs() {
  awk '
    function trim(s) {
      sub(/^[[:space:]]+/, "", s)
      sub(/[[:space:]]+$/, "", s)
      return s
    }
    function norm_path(path) {
      path = trim(path)
      gsub(/`/, "", path)
      gsub(/^\/wp-json/, "", path)
      gsub(/\/+/, "/", path)
      gsub(/\{[^}]+\}/, "{}", path)
      gsub(/\(\?P<[^>]+>[^)]*\)/, "{}", path)
      if (path != "/" && path ~ /\/$/) {
        sub(/\/$/, "", path)
      }
      return path
    }
    {
      method = toupper(trim($1))
      path = norm_path($2)
      if (method != "" && path ~ /^\//) {
        print method " " path
      }
    }
  '
}

extract_contract() {
  awk -F'|' '
    $2 ~ /^[[:space:]]*(GET|POST|PATCH|PUT|DELETE)[[:space:]]*$/ && $3 ~ /^[[:space:]]*\// {
      print $2 "\t" $3
    }
    $0 ~ /\|[[:space:]]*エンドポイント[[:space:]]*\|/ {
      if (match($0, /`(GET|POST|PATCH|PUT|DELETE)[[:space:]]+([^`]+)`/, m)) {
        print m[1] "\t" m[2]
      }
    }
  ' "$CONTRACT_FILE" | normalize_pairs
}

method_names() {
  awk '
    /WP_REST_Server::READABLE/ { print "GET" }
    /WP_REST_Server::CREATABLE/ { print "POST" }
    /WP_REST_Server::EDITABLE/ { print "POST"; print "PUT"; print "PATCH" }
    /WP_REST_Server::DELETABLE/ { print "DELETE" }
    /WP_REST_Server::ALLMETHODS/ { print "GET"; print "POST"; print "PUT"; print "PATCH"; print "DELETE" }
    /'\''GET'\''|"GET"/ { print "GET" }
    /'\''POST'\''|"POST"/ { print "POST" }
    /'\''PATCH'\''|"PATCH"/ { print "PATCH" }
    /'\''PUT'\''|"PUT"/ { print "PUT" }
    /'\''DELETE'\''|"DELETE"/ { print "DELETE" }
  ' | sort -u
}

emit_route() {
  local namespace="$1"
  local route="$2"
  local methods="$3"
  local path

  if [[ "$namespace" == "agent-neo/v1" ]]; then
    path="$route"
  else
    path="/$namespace$route"
  fi

  while IFS= read -r method; do
    [[ -n "$method" ]] && printf '%s\t%s\n' "$method" "$path"
  done < <(printf '%s\n' "$methods" | method_names)
}

extract_code() {
  local file state namespace route buffer literal_count line

  while IFS= read -r file; do
    state=0
    namespace=""
    route=""
    buffer=""
    literal_count=0

    while IFS= read -r line || [[ -n "$line" ]]; do
      if [[ "$state" -eq 0 && "$line" =~ register_agent_route[[:space:]]*\( ]]; then
        state=1
        namespace="agent-neo/v1"
        route=""
        buffer="$line"
        literal_count=0
      elif [[ "$state" -eq 0 && "$line" =~ register_rest_route[[:space:]]*\( ]]; then
        state=2
        namespace=""
        route=""
        buffer="$line"
        literal_count=0
      elif [[ "$state" -ne 0 ]]; then
        buffer+=$'\n'"$line"
      fi

      if [[ "$state" -ne 0 ]]; then
        while [[ "$line" =~ [\'\"]([^\'\"]+)[\'\"] ]]; do
          literal="${BASH_REMATCH[1]}"
          line="${line#*"${BASH_REMATCH[0]}"}"

          if [[ "$state" -eq 1 && "$literal_count" -eq 0 ]]; then
            route="$literal"
          elif [[ "$state" -eq 2 && "$literal_count" -eq 0 ]]; then
            namespace="$literal"
          elif [[ "$state" -eq 2 && "$literal_count" -eq 1 ]]; then
            route="$literal"
          fi
          literal_count=$((literal_count + 1))
        done

        if [[ "$line" == *";"* ]]; then
          if [[ -n "$namespace" && -n "$route" ]]; then
            emit_route "$namespace" "$route" "$buffer"
          fi
          state=0
          namespace=""
          route=""
          buffer=""
          literal_count=0
        fi
      fi
    done < "$file"
  done < <(find "${SCAN_DIRS[@]}" -type f -name '*.php' 2>/dev/null | sort)
}

extract_contract > "$tmpdir/contract.all"
extract_code | normalize_pairs | sort -u > "$tmpdir/code.unique"
sort -u "$tmpdir/contract.all" > "$tmpdir/contract.unique"

awk 'NR == FNR { code[$0] = 1; next } code[$0] { print }' "$tmpdir/code.unique" "$tmpdir/contract.all" > "$tmpdir/covered.all"
awk 'NR == FNR { code[$0] = 1; next } !code[$0] { print }' "$tmpdir/code.unique" "$tmpdir/contract.all" > "$tmpdir/missing.all"
awk 'NR == FNR { contract[$0] = 1; next } !contract[$0] { print }' "$tmpdir/contract.unique" "$tmpdir/code.unique" > "$tmpdir/orphan.unique"

contract_total="$(wc -l < "$tmpdir/contract.all" | tr -d ' ')"
covered_count="$(wc -l < "$tmpdir/covered.all" | tr -d ' ')"
missing_count="$(wc -l < "$tmpdir/missing.all" | tr -d ' ')"
orphan_count="$(wc -l < "$tmpdir/orphan.unique" | tr -d ' ')"
coverage_percent=$(( covered_count * 100 / EXPECTED_TOTAL ))
exit_code=0

echo "EXPECTED: $EXPECTED_TOTAL"
if [[ "$contract_total" -ne "$EXPECTED_TOTAL" ]]; then
  echo "WARNING: extracted contract endpoint count is $contract_total, expected $EXPECTED_TOTAL" >&2
fi

echo "COVERED ($covered_count)"
sed 's/^/  /' "$tmpdir/covered.all"

echo "MISSING ($missing_count)"
sed 's/^/  /' "$tmpdir/missing.all"

echo "ORPHAN ($orphan_count)"
sed 's/^/  /' "$tmpdir/orphan.unique"

echo "coverage = $covered_count/$EXPECTED_TOTAL (${coverage_percent}%)"

if [[ -n "$fail_under" ]]; then
  if [[ "$fail_under" =~ ^([0-9]+)%$ ]]; then
    threshold_percent="${BASH_REMATCH[1]}"
    if (( coverage_percent < threshold_percent )); then
      echo "FAIL: coverage ${coverage_percent}% is below ${threshold_percent}%" >&2
      exit_code=1
    fi
  elif [[ "$fail_under" =~ ^[0-9]+$ ]]; then
    if (( fail_under <= EXPECTED_TOTAL )); then
      if (( covered_count < fail_under )); then
        echo "FAIL: covered $covered_count is below $fail_under endpoints" >&2
        exit_code=1
      fi
    elif (( fail_under <= 100 )); then
      if (( coverage_percent < fail_under )); then
        echo "FAIL: coverage ${coverage_percent}% is below ${fail_under}%" >&2
        exit_code=1
      fi
    else
      echo "invalid --fail-under value: $fail_under" >&2
      exit 2
    fi
  else
    echo "invalid --fail-under value: $fail_under" >&2
    exit 2
  fi
fi

if [[ "$strict_orphan" -eq 1 && "$orphan_count" -gt 0 ]]; then
  echo "FAIL: orphan endpoints detected" >&2
  exit_code=1
fi

exit "$exit_code"
