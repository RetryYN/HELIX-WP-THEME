#!/usr/bin/env bash
# THEME-INV-01〜17 を GitHub イシューとして起票する。
#
# 前提: gh 認証済み / このリポジトリ（RetryYN/HELIX-WP-THEME）に issue が有効
# 実行: bash docs/research/2026-08-26-theme-structure-audit/create-issues.sh
#       DRY_RUN=1 を付けると内容だけ表示して起票しない。
#
# 冪等性: 同じタイトルの open issue が既にあればスキップする。
set -uo pipefail

REPO="RetryYN/HELIX-WP-THEME"
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/issues"
DRY_RUN="${DRY_RUN:-0}"

# ---- ラベル（無ければ作る。あってもエラーにしない） ----
create_label() {
  gh label create "$1" --repo "$REPO" --color "$2" --description "$3" 2>/dev/null \
    || echo "  label exists: $1"
}

echo "== labels =="
create_label "investigation"  "0e8a16" "調査イシュー"
create_label "blocks"         "1d76db" "ブロック・語彙まわり"
create_label "security"       "b60205" "セキュリティ"
create_label "seo"            "5319e7" "SEO"
create_label "performance"    "fbca04" "パフォーマンス"
create_label "architecture"   "0052cc" "設計・機構"
create_label "contracts"      "006b75" "契約・スキーマ"
create_label "migration"      "c2e0c6" "移管"
create_label "cpt"            "bfd4f2" "カスタム投稿タイプ"
create_label "determinism"    "d4c5f9" "決定論レンダリング"
create_label "design-tokens"  "f9d0c4" "デザイントークン"
create_label "structured-data" "c5def5" "構造化データ"
create_label "content-pipeline" "bfdadc" "本文パイプライン"
create_label "agent-interface" "5319e7" "エージェント接点"
create_label "api"            "1d76db" "API"
create_label "compat"         "e4e669" "後方互換"
create_label "scope"          "d93f0b" "スコープ"
create_label "ledger"         "0e8a16" "台帳"
create_label "risk"           "e99695" "リスク"
create_label "settings"       "c2e0c6" "設定"
create_label "zones"          "fef2c0" "広告・CV ゾーン"
create_label "po-decision"    "d93f0b" "PO 判断が要る"
create_label "priority:high"  "b60205" "優先度: 高"
create_label "priority:medium" "fbca04" "優先度: 中"
create_label "priority:low"   "c2e0c6" "優先度: 低"

# ---- issue ファイルに実在する全ラベルを保証（未定義ラベルでの起票失敗を防ぐ） ----
# 上の固定リストに無いラベルが INV-*.md に混じっていても、ここで既定色で先に作る。
echo
echo "== ensure labels referenced in issue files =="
grep -hE '^labels:' "$DIR"/THEME-INV-*.md 2>/dev/null \
  | sed -E 's/^labels:\s*//' | tr ',' '\n' | tr -d ' ' | sort -u \
  | while IFS= read -r lbl; do
      [ -n "$lbl" ] || continue
      create_label "$lbl" "ededed" "自動生成（起票プリパス）"
    done

# ---- 既存 open issue のタイトル一覧（重複起票の防止） ----
EXISTING="$(gh issue list --repo "$REPO" --state open --limit 200 --json title --jq '.[].title' 2>/dev/null || true)"

# ---- 起票 ----
echo
echo "== issues =="
for f in "$DIR"/THEME-INV-*.md; do
  [ -e "$f" ] || continue

  # 1 行目 "# THEME-INV-01: 表題" から表題を取る
  title="$(head -1 "$f" | sed -E 's/^#\s*//')"

  # "labels: a, b, c" 行からラベルを取る
  labels="$(grep -m1 -E '^labels:' "$f" | sed -E 's/^labels:\s*//' | tr -d ' ')"

  # 本文は labels: / depends: 行を除いたもの（GitHub 側のラベル欄と重複させない）
  body="$(grep -vE '^(labels|depends):' "$f")"

  if printf '%s\n' "$EXISTING" | grep -Fxq "$title"; then
    echo "  skip (already open): $title"
    continue
  fi

  if [ "$DRY_RUN" = "1" ]; then
    echo "  [dry-run] $title"
    echo "            labels: $labels"
    continue
  fi

  url="$(printf '%s' "$body" | gh issue create \
        --repo "$REPO" \
        --title "$title" \
        --label "$labels" \
        --body-file - 2>&1)" \
    && echo "  created: $title -> $url" \
    || echo "  FAILED : $title -> $url"
done

echo
echo "完了。PROGRESS.md の状態列を更新すること。"
