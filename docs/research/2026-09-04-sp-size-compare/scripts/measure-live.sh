#!/usr/bin/env bash
# 実運用サイトの公開ページを読み取り専用（GET のみ）で計測する。書き込み・ログイン・フォーム送信はしない。
# 使い方: measure-live.sh <series-label> <base-url> <post-path>
# 生 JSON（URL・本文抜粋・実クラス名を含む）は SHOT_DIR（リポ外）にだけ置き、リポ側 OUT_DIR には
# sanitize-live.js で URL・テキスト・クラス名を落とした JSON と 390 幅 webp だけを書く。
set -euo pipefail
SERIES="$1"; BASE="$2"; POST_PATH="$3"
SHOT_DIR="${SHOT_DIR:?scratchpad dir}"; OUT_DIR="${OUT_DIR:?results dir}"; PW_NODE_PATH="${PW_NODE_PATH:?node_modules with playwright}"
HERE="$(cd "$(dirname "$0")" && pwd)"
mkdir -p "$SHOT_DIR" "$OUT_DIR"
NODE_PATH="$PW_NODE_PATH" PW_EXECUTABLE="${PW_EXECUTABLE:-}" node "$HERE/measure-sp.js" "$SERIES" "$BASE" "$POST_PATH" "$SHOT_DIR/$SERIES.raw.json" "$SHOT_DIR" "$OUT_DIR"
node "$HERE/sanitize-live.js" "$SHOT_DIR/$SERIES.raw.json" "$OUT_DIR/$SERIES.json"
ffmpeg -y -loglevel error -i "$SHOT_DIR/$SERIES-article-fv-390.png" -c:v libwebp -q:v 80 "$OUT_DIR/$SERIES-article-fv-390.webp"
