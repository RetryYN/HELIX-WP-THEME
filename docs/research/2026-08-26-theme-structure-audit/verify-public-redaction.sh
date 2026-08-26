#!/usr/bin/env bash
# 公開監査成果の伏せ字漏れを検査する。固有名の対応表はリポジトリへ置かず、
# REDACT_GUARD_RE としてローカルから渡す。
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [ -z "${REDACT_GUARD_RE:-}" ]; then
  echo "REDACT_GUARD_RE is required (fail-closed)." >&2
  exit 2
fi

status=0

check() {
  local description="$1"
  local pattern="$2"
  if grep -RInE --exclude='verify-public-redaction.sh' "$pattern" "$HERE"; then
    echo "[redaction] detected: $description" >&2
    status=1
  fi
}

# 公開してよい placeholder は除外し、実値らしい経路・広告計測値を検出する。
check "private name/domain mapping" "$REDACT_GUARD_RE"
check "affiliate or tracking URL" 'https?://[^[:space:]"`]*(a8mat=|/svt/|/0\.gif\?)'
check "unredacted home path" '/home/([^<[:space:]/][^[:space:]/]*)/'
check "local developer identity" '/home/(tenni|claude|codex)/'

if [ "$status" -ne 0 ]; then
  exit "$status"
fi

echo "OK: public redaction guard passed"
