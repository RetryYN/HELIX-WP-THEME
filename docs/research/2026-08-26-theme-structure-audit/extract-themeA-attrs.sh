#!/usr/bin/env bash
# THEME-INV-14: 公開記事から themeA-blocks/* の属性を帰納するための抽出。
#
# 読み取り専用（wp db query の SELECT のみ）。サーバーへの書き込みは行わない。
# 実行はローカルから SSH 経由:
#   bash docs/research/2026-08-26-theme-structure-audit/extract-themeA-attrs.sh > evidence/themeA-attrs-raw.txt
set -uo pipefail

# 接続情報は環境変数から取る（公開リポのため直書きしない）:
#   XSRV_SSH_HOST=user@host  XSRV_SSH_PORT=10022  XSRV_SSH_KEY=~/.ssh/<key>  XSRV_WP_PATH='$HOME/<site>/public_html'
: "${XSRV_SSH_HOST:?XSRV_SSH_HOST を設定してください}"
: "${XSRV_WP_PATH:?XSRV_WP_PATH を設定してください}"
SSH_HOST="$XSRV_SSH_HOST"
SSH_PORT="${XSRV_SSH_PORT:-10022}"
SSH_KEY="${XSRV_SSH_KEY:-$HOME/.ssh/id_ed25519}"
WP_PATH="$XSRV_WP_PATH"
PHP='/opt/php-8.3/bin/php -d error_reporting=0 -d display_errors=0 $HOME/wp-cli.phar'

ssh -o BatchMode=yes -i "$SSH_KEY" -p "$SSH_PORT" "$SSH_HOST" "
Q='SELECT post_content FROM wp_posts WHERE post_status=\"publish\" AND post_type IN (\"post\",\"page\")'

echo '===== 1. ブロックコメントの全抽出（ブロック名 + 属性 JSON） ====='
$PHP --path=$WP_PATH db query \"\$Q\" --skip-column-names 2>/dev/null \
  | grep -oE '<!-- wp:themeA-blocks/[a-z-]+ \{[^}]*\}' | sort | uniq -c | sort -rn

echo
echo '===== 2. ブロック別の属性キー集計 ====='
for b in simplebox button blogcard fukidashi comparechild compare postlist designtitle background \\
         richmenuchild accordionchild accordion fullwidth richmenu timelinechild tabchild timeline \\
         tab iconbox designborder syntax-hl postcard slider category paidpost profile; do
  n=\$($PHP --path=$WP_PATH db query \"\$Q\" --skip-column-names 2>/dev/null \
       | grep -oE \"<!-- wp:themeA-blocks/\$b \" | wc -l)
  [ \"\$n\" = '0' ] && continue
  echo \"--- themeA-blocks/\$b (\$n) ---\"
  $PHP --path=$WP_PATH db query \"\$Q\" --skip-column-names 2>/dev/null \
    | grep -oE \"<!-- wp:themeA-blocks/\$b \{[^}]*\}\" \
    | grep -oE '\"[a-zA-Z0-9_]+\":' | sort | uniq -c | sort -rn
done

echo
echo '===== 3. 共通属性の実使用 ====='
for a in themeABlocksCSSAttribute topMarginPcAttribute bottomMarginPcAttribute \\
         topMarginSpAttribute bottomMarginSpAttribute displayDeviceAttribute className; do
  n=\$($PHP --path=$WP_PATH db query \"\$Q\" --skip-column-names 2>/dev/null | grep -o \"\$a\" | wc -l)
  echo \"\$a: \$n\"
done

echo
echo '===== 4. 余白クラス名の値域 ====='
$PHP --path=$WP_PATH db query \"\$Q\" --skip-column-names 2>/dev/null \
  | grep -oE '\"(top|bottom)Margin(Pc|Sp)Attribute\":\"[^\"]*\"' | sort | uniq -c | sort -rn

echo
echo '===== 5. [themeA_fukidashi] の位置関係（INV-10 の仮説検証） ====='
$PHP --path=$WP_PATH db query \"\$Q\" --skip-column-names 2>/dev/null \
  | grep -oE '<!-- wp:themeA-blocks/fukidashi.{0,300}' | head -3

echo
echo '===== 6. 未登録ブロック themeA-blocks/profile の実体 ====='
$PHP --path=$WP_PATH db query \"\$Q\" --skip-column-names 2>/dev/null \
  | grep -oE '<!-- wp:themeA-blocks/profile.{0,300}' | head -3

echo
echo '===== 7. コアブロックのスタイル（register_block_style 由来） ====='
for s in is-style-themeA-checkmark is-style-themeA-checkmark-square; do
  n=\$($PHP --path=$WP_PATH db query \"\$Q\" --skip-column-names 2>/dev/null | grep -o \"\$s\" | wc -l)
  echo \"\$s: \$n\"
done
" 2>/dev/null
