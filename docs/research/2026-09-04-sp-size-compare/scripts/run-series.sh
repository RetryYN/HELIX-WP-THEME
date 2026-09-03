#!/usr/bin/env bash
# 1 系列の計測: テーマ切替 → (任意で style variation 適用) → measure-sp.js。
# 使い方: run-series.sh <series-label> <theme-slug> [variation-slug|-] 
# 実 slug は引数でだけ渡し、成果物には series-label しか残さない。
set -euo pipefail
SERIES="$1"; SLUG="$2"; VAR="${3:--}"
BASE="${WP_BASE:-http://localhost:8086}"; POST_PATH="${POST_PATH:-/design-proto-article/}"
SHOT_DIR="${SHOT_DIR:?scratchpad dir}"; OUT_DIR="${OUT_DIR:?results dir}"; PW_NODE_PATH="${PW_NODE_PATH:?node_modules with playwright}"
HERE="$(cd "$(dirname "$0")" && pwd)"
docker compose run --rm -T wpcli theme activate "$SLUG" >/dev/null
if [ "$VAR" != "-" ]; then
  docker compose run --rm -T wpcli eval-file "wp-content/themes/wt-proto/set-variation.php" "$VAR" >/dev/null
fi
docker compose run --rm -T wpcli cache flush >/dev/null 2>&1 || true
mkdir -p "$SHOT_DIR" "$OUT_DIR"
NODE_PATH="$PW_NODE_PATH" PW_EXECUTABLE="${PW_EXECUTABLE:-}" node "$HERE/measure-sp.js" "$SERIES" "$BASE" "$POST_PATH" "$OUT_DIR/$SERIES.json" "$SHOT_DIR" "$OUT_DIR"
ffmpeg -y -loglevel error -i "$SHOT_DIR/$SERIES-article-fv-390.png" -c:v libwebp -q:v 80 "$OUT_DIR/$SERIES-article-fv-390.webp"
