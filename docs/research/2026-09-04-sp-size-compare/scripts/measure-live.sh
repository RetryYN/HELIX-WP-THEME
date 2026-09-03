#!/usr/bin/env bash
# 実運用サイトの公開ページを GET 閲覧のみで計測する。本手順としての書き込み（ログイン・投稿・フォーム送信）はしない。
# 使い方: measure-live.sh <series-label> <base-url> <post-path>
# 生 JSON（URL・本文抜粋・実クラス名を含む）と webp は SHOT_DIR（リポ外）にだけ置き、リポ側 OUT_DIR には
# sanitize-live.js で URL・テキスト・クラス名を落とした JSON だけを書く。画像をリポへ入れる場合は
# 転載許諾と識別部位の匿名化を確認したうえで手動でコピーする。
set -euo pipefail
SERIES="$1"; BASE="$2"; POST_PATH="$3"
SHOT_DIR="${SHOT_DIR:?scratchpad dir}"; OUT_DIR="${OUT_DIR:?results dir}"; PW_NODE_PATH="${PW_NODE_PATH:?node_modules with playwright}"
HERE="$(cd "$(dirname "$0")" && pwd)"
mkdir -p "$SHOT_DIR" "$OUT_DIR"
NODE_PATH="$PW_NODE_PATH" PW_EXECUTABLE="${PW_EXECUTABLE:-}" node "$HERE/measure-sp.js" "$SERIES" "$BASE" "$POST_PATH" "$SHOT_DIR/$SERIES.raw.json" "$SHOT_DIR" "$OUT_DIR"
node "$HERE/sanitize-live.js" "$SHOT_DIR/$SERIES.raw.json" "$OUT_DIR/$SERIES.json"
ffmpeg -y -loglevel error -i "$SHOT_DIR/$SERIES-article-fv-390.png" -c:v libwebp -q:v 80 "$SHOT_DIR/$SERIES-article-fv-390.webp"
