#!/usr/bin/env bash
set -euo pipefail

theme_dir="${1:-themes/agent-neo-theme}"
failures=0

if [[ ! -d "$theme_dir" ]]; then
  echo "missing theme dir: $theme_dir" >&2
  exit 1
fi

functions_file="$theme_dir/functions.php"
allowed_functions_regex='^(if|defined|define|require|require_once|trailingslashit|get_template_directory|get_template_directory_uri)$'

echo "Checking CR-002 functions.php bootstrap-only..."
while IFS= read -r token; do
  if [[ ! "$token" =~ $allowed_functions_regex ]]; then
    echo "CR-002 violation: disallowed call in functions.php: $token"
    failures=1
  fi
done < <(
  grep -Eo '[a-zA-Z_][a-zA-Z0-9_]*[[:space:]]*\(' "$functions_file" \
    | sed -E 's/[[:space:]]*\($//' \
    | sort -u
)

echo "Checking CR-003 public PHP function prefix..."
while IFS= read -r line; do
  name="$(sed -E 's/.*function[[:space:]]+&?([a-zA-Z_][a-zA-Z0-9_]*).*/\1/' <<< "$line")"
  if [[ "$name" != agent_neo_* ]]; then
    echo "CR-003 violation: public function without agent_neo_ prefix: $line"
    failures=1
  fi
done < <(
  grep -RInE '^[[:space:]]*function[[:space:]]+&?[a-zA-Z_][a-zA-Z0-9_]*[[:space:]]*\(' "$theme_dir" --include='*.php' || true
)

echo "Checking CR-003 action/filter hook prefix..."
while IFS= read -r line; do
  hook="$(sed -E "s/.*add_(action|filter)[[:space:]]*\\([[:space:]]*'([^']+)'.*/\\2/" <<< "$line")"
  if [[ "$hook" != agent_neo_* && "$hook" != after_setup_theme && "$hook" != admin_notices && "$hook" != wp_enqueue_scripts ]]; then
    echo "CR-003 violation: custom hook without agent_neo_ prefix: $line"
    failures=1
  fi
done < <(
  grep -RInE "add_(action|filter)[[:space:]]*\\([[:space:]]*'[^']+'" "$theme_dir" --include='*.php' || true
)

echo "Checking CR-003 enqueue handle prefix..."
while IFS= read -r line; do
  handle="$(sed -E "s/.*wp_enqueue_(style|script)[[:space:]]*\\([[:space:]]*'([^']+)'.*/\\2/" <<< "$line")"
  if [[ "$handle" != agent_neo_* ]]; then
    echo "CR-003 violation: enqueue handle without agent_neo_ prefix: $line"
    failures=1
  fi
done < <(
  grep -RInE "wp_enqueue_(style|script)[[:space:]]*\\([[:space:]]*'[^']+'" "$theme_dir" --include='*.php' || true
)

echo "Checking CR-003 custom class/data prefix..."
while IFS= read -r line; do
  value="$(sed -E 's/.*"className":"([^"]+)".*/\1/' <<< "$line")"
  for class_name in $value; do
    if [[ "$class_name" != an-* && "$class_name" != is-style-* ]]; then
      echo "CR-003 violation: custom className without an- prefix: $line"
      failures=1
    fi
  done
done < <(
  grep -RInE '"className":"[^"]+"' "$theme_dir/templates" "$theme_dir/parts" "$theme_dir/patterns" || true
)

if grep -RIn 'data-' "$theme_dir/templates" "$theme_dir/parts" "$theme_dir/patterns" 2>/dev/null | grep -v 'data-agent-' >/tmp/agent-neo-prefix-data.$$; then
  while IFS= read -r line; do
    echo "CR-003 violation: data attribute without data-agent- prefix: $line"
  done < /tmp/agent-neo-prefix-data.$$
  rm -f /tmp/agent-neo-prefix-data.$$
  failures=1
else
  rm -f /tmp/agent-neo-prefix-data.$$
fi

if [[ "$failures" -ne 0 ]]; then
  echo "prefix check failed"
  exit 1
fi

echo "prefix check passed"
