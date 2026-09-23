#!/usr/bin/env bash
set -euo pipefail

repo_root="$(git rev-parse --show-toplevel)"
cd "$repo_root"

usage() {
  echo "usage: $0 --staged | --base-ref <git-ref> [<head-ref>]" >&2
  exit 2
}

mode=""
base_ref=""
head_ref="HEAD"
case "${1:-}" in
  --staged)
    mode="staged"
    [[ $# -eq 1 ]] || usage
    diff_args=(--cached)
    ;;
  --base-ref)
    mode="range"
    base_ref="${2:-}"
    head_ref="${3:-HEAD}"
    [[ ( $# -eq 2 || $# -eq 3 ) && -n "$base_ref" ]] || usage
    git rev-parse --verify "${base_ref}^{commit}" >/dev/null
    git rev-parse --verify "${head_ref}^{commit}" >/dev/null
    diff_args=("$base_ref" "$head_ref")
    ;;
  *) usage ;;
esac

tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT
records="$tmp_dir/added-lines"
paths="$tmp_dir/changed-paths"
scan="$tmp_dir/scan"
: >"$records"
: >"$paths"
approval_blob=":config/public-safety-binary-approvals.tsv"
[[ "$mode" == "range" ]] && approval_blob="${head_ref}:config/public-safety-binary-approvals.tsv"
if git cat-file -e "$approval_blob" 2>/dev/null; then
  git show "$approval_blob" >"$tmp_dir/binary-approvals.tsv"
else
  : >"$tmp_dir/binary-approvals.tsv"
fi
failures=0
sensitive_changed=0

# -z keeps quoted/non-ASCII names intact. An identical rename changes only
# its path; a modified rename is scanned as a new file.
git diff --no-ext-diff --name-status -z -M "${diff_args[@]}" -- >"$tmp_dir/name-status"
while IFS= read -r -d '' status; do
  if [[ "$status" == R* || "$status" == C* ]]; then
    IFS= read -r -d '' old_path || { echo "FAIL: incomplete rename record" >&2; exit 1; }
  fi
  IFS= read -r -d '' path || { echo "FAIL: incomplete changed-path record" >&2; exit 1; }
  [[ "$status" == D* ]] && continue
  if [[ "$path" == *$'\n'* || "$path" == *$'\t'* || "$path" == *$'\r'* ]]; then
    echo "FAIL: control character in changed path" >&2
    exit 1
  fi
  printf '%s\n' "$path" >>"$paths"
  [[ "$path" =~ (^|/)(research|evidence|artifacts?|poc|raw|captures?)(/|$) ]] && sensitive_changed=1
  [[ "$status" == R100 ]] && continue

  if [[ "$mode" == "staged" ]]; then
    oid="$(git rev-parse --verify ":$path")"
    entry_mode="$(git ls-files --stage -- "$path" | awk 'NR == 1 { print $1 }')"
  else
    oid="$(git rev-parse --verify "${head_ref}:$path")"
    entry_mode="$(git ls-tree "$head_ref" -- "$path" | awk 'NR == 1 { print $1 }')"
  fi
  kind="$(git cat-file -t "$oid")"
  if [[ "$kind" != blob ]]; then
    echo "FAIL: changed gitlink or non-blob cannot be inspected" >&2
    failures=$((failures + 1))
    continue
  fi
  if [[ "$entry_mode" == 120000 ]]; then
    git cat-file blob "$oid" >>"$records"
    printf '\n' >>"$records"
    continue
  fi
  if git diff --no-ext-diff --no-renames --numstat "${diff_args[@]}" -- "$path" |
      awk -F '\t' '$1 == "-" && $2 == "-" { found=1 } END { exit !found }'; then
    digest="$(git cat-file blob "$oid" | sha256sum | cut -d ' ' -f 1)"
    approval_file="$tmp_dir/binary-approvals.tsv"
    approved=0
    if awk -F '\t' -v path="$path" -v digest="$digest" '
          NF == 2 && $1 == path && $2 == digest { found=1 }
          END { exit !found }
        ' "$approval_file"; then
      approved=1
    fi
    if (( ! approved )); then
      echo "FAIL: changed binary requires a reviewed path + SHA-256 record in config/public-safety-binary-approvals.tsv" >&2
      failures=$((failures + 1))
    fi
    continue
  fi

  git diff --no-ext-diff --no-renames --unified=0 "${diff_args[@]}" -- "$path" |
    awk '
      /^diff --git / { in_hunk=0; next }
      /^@@ / { in_hunk=1; next }
      /^\+/ && in_hunk { print substr($0, 2) }
    ' >>"$records"
done <"$tmp_dir/name-status"

cat "$paths" "$records" >"$scan"
check_pattern() {
  local description="$1"
  local pattern="$2"
  local flags="${3:--E}"
  local status=0
  # Match contents stay out of logs; invalid patterns and I/O errors fail.
  grep $flags -q -- "$pattern" "$scan" 2>"$tmp_dir/grep-error" || status=$?
  case "$status" in
    0) echo "FAIL: $description" >&2; failures=$((failures + 1)) ;;
    1) ;;
    *) echo "FAIL: $description inspection error (grep exit $status)" >&2; failures=$((failures + 1)) ;;
  esac
}

# Split well-known token prefixes so this guard does not flag its own source.
check_pattern "private key material" 'BEGIN [A-Z0-9 ]*PRIVATE KEY' '-E'
check_pattern "well-known access token format" '(gh[pousr]_[A-Za-z0-9]{20,}|github_pat_[A-Za-z0-9_]{20,}|AKIA[0-9A-Z]{16})' '-E'
check_pattern "credential-like assignment" '(password|passwd|api[_-]?key|access[_-]?token|client[_-]?secret)[[:space:]]*[:=][[:space:]]*[[:punct:]]?[[:space:]]*[A-Za-z0-9+/=_-]{12,}' '-Ei'
personal_path_pattern='(/ho''me/[^/<[:space:]]+/|/Us''ers/[^/<[:space:]]+/|[A-Za-z]:[/\\]Us''ers[/\\][^/\\<[:space:]]+[/\\])'
check_pattern "personal absolute filesystem path" "$personal_path_pattern" '-E'
check_pattern "affiliate or click-tracking URL" 'https?://[^[:space:]]*(a8mat=|/svt/|/0\.gif\?)' '-Ei'

custom_regex="${PUBLIC_REDACTION_GUARD_RE:-}"
local_regex_file="${PUBLIC_SAFETY_REGEX_FILE:-.public-safety.local.regex}"
if [[ -f "$local_regex_file" ]]; then
  file_regex="$(awk '!/^[[:space:]]*(#|$)/ { if (count++) printf "|"; printf "%s", $0 }' "$local_regex_file")" || {
    echo "FAIL: private redaction mapping could not be read" >&2
    exit 1
  }
  if [[ -n "$file_regex" ]]; then
    custom_regex="${custom_regex:+${custom_regex}|}${file_regex}"
  fi
fi
if [[ -n "$custom_regex" ]]; then
  check_pattern "private name/domain mapping" "$custom_regex" '-Ei'
fi

if (( sensitive_changed )) && [[ -z "$custom_regex" ]]; then
  echo "FAIL: research/evidence/PoC content changed without a private redaction mapping." >&2
  echo "  Set PUBLIC_REDACTION_GUARD_RE or create .public-safety.local.regex." >&2
  failures=$((failures + 1))
fi

if (( failures > 0 )); then
  echo "public safety check: $failures failure(s)" >&2
  exit 1
fi

echo "public safety check: OK ($(wc -l <"$records" | tr -d ' ') added line(s) inspected)"
