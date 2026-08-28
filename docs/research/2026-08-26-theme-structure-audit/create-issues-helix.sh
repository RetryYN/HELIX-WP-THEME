#!/usr/bin/env bash
# THEME-INV-01〜17 を HELIX 本体の Issue 階層化規律
# (helix/docs/governance/github-issue-hierarchy-rules.md, github-operation-rules.md §6) に従って起票する。
#
#  - root Issue 1 本 + task Issue 17 本（parent_issue = root）
#  - 本文に issue_role / parent_issue / blocks / blocked_by / duplicate_search / disposition / duplicate_of の exact block
#  - 依存を持つ Issue には helix-issue-dependency.v1 block（depends_on / blocks を双方向一致）
#  - label: type（bug|enhancement）+ priority:* + area:* を同一操作で付与
#  - 公開リポのため、第三者テーマ名・ベンダー名・サイトドメインは伏せ字へ置換する
#
# 実行: bash docs/research/2026-08-26-theme-structure-audit/create-issues-helix.sh
#       DRY_RUN=1 で内容確認のみ。
set -uo pipefail
REPO="RetryYN/HELIX-WP-THEME"
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DIR="$HERE/issues"
DRY_RUN="${DRY_RUN:-0}"
ROOT_TITLE="THEME-AUDIT: テーマ構造監査（WP テーマ要求棚卸し）"

redact() {
  # issues/ 配下の原本は既に伏せ字済みのため、ここでは変換しない。
  # 検査パターン自体が対応表になるため公開リポには置かず、実行時に必須入力とする。
  # 未設定のまま起票できると guard が実質無効になるため fail-close する。
  local s; s=$(cat)
  if [ -z "${REDACT_GUARD_RE:-}" ]; then
    echo "[guard] REDACT_GUARD_RE が未設定です。起票を中断します。" >&2; exit 1
  fi
  if printf '%s' "$s" | grep -qiE "$REDACT_GUARD_RE"; then
    echo "[guard] 伏せ字漏れを検出しました。起票を中断します。" >&2; exit 1
  fi
  printf '%s' "$s"
}

gh_() { if [ "$DRY_RUN" = "1" ]; then echo "  [dry-run] gh $*" >&2; echo "0"; else gh "$@"; fi; }

mk_label() { gh label create "$1" --repo "$REPO" --color "$2" --description "$3" --force >/dev/null 2>&1 || true; }
if [ "$DRY_RUN" != "1" ]; then
  echo "== labels =="
  mk_label "priority:high"   "b60205" "優先度: 高"
  mk_label "priority:medium" "fbca04" "優先度: 中"
  mk_label "priority:low"    "c2e0c6" "優先度: 低"
  mk_label "area:theme-audit" "0052cc" "テーマ構造監査（WP テーマ要求棚卸し）"
  mk_label "po-decision"     "d93f0b" "PO 判断が要る"
  mk_label "security"        "b60205" "セキュリティ"
  mk_label "investigation"   "0e8a16" "調査"
fi

ALL_TITLES="$(gh issue list --repo "$REPO" --state all --limit 500 --json title,number --jq '.[] | "\(.number)\t\(.title)"' 2>/dev/null || true)"
find_issue() { printf '%s\n' "$ALL_TITLES" | awk -F'\t' -v t="$1" '$2==t{print $1; exit}'; }

contract() { # role parent
  printf '```yaml\nissue_role: %s\nparent_issue: %s\nblocks: []\nblocked_by: []\nduplicate_search: completed\ndisposition: active\nduplicate_of: null\n```\n' "$1" "$2"
}

# ---- root ----
echo "== root =="
ROOT="$(find_issue "$ROOT_TITLE")"
if [ -z "$ROOT" ]; then
  body="$(cat <<B
$(contract root null)

## 目的
既存商用テーマ 2 種（テーマA / テーマB）と AGENT NEO の差分台帳から、WP テーマの要求を棚卸しするための入力
（意図語彙・中間 JSON 契約・移管方式・スコープ境界）を確定する。

## 正本
- レポート: \`docs/research/2026-08-26-theme-structure-audit/\`（PROGRESS.md / reports/ / evidence/）
- 本 Issue は projection。進捗の正本はリポ内 PROGRESS.md。

## 公開範囲
第三者テーマ名・ベンダー名・サイトドメインは伏せ字にしている。原本はリポ内 \`issues/\` を参照。
B
)"
  if [ "$DRY_RUN" = "1" ]; then echo "  [dry-run] create root: $ROOT_TITLE"; ROOT=0
  else
    url="$(printf '%s' "$body" | gh issue create --repo "$REPO" --title "$ROOT_TITLE" --label "enhancement,priority:high,area:theme-audit" --body-file -)"
    ROOT="${url##*/}"; echo "  created root #$ROOT"
  fi
else echo "  exists root #$ROOT"; fi

# ---- tasks ----
declare -A NUM DEPS
echo "== tasks =="
for f in "$DIR"/THEME-INV-*.md; do
  id="$(basename "$f" | grep -oE 'THEME-INV-[0-9]+')"
  title="$(head -1 "$f" | sed -E 's/^#\s*//' | redact)"
  labels="$(grep -m1 -E '^labels:' "$f" | sed -E 's/^labels:\s*//' | tr -d ' ')"
  deps="$(grep -m1 -E '^depends:' "$f" | sed -E 's/^depends:\s*//')"
  DEPS[$id]="$deps"
  type="enhancement"; case "$id" in THEME-INV-13) type="bug";; esac
  prio="$(printf '%s' "$labels" | tr ',' '\n' | grep -m1 '^priority:')"
  extra="$(printf '%s' "$labels" | tr ',' '\n' | grep -E '^(security|po-decision|investigation)$' | paste -sd, -)"
  lbl="$type,${prio:-priority:medium},area:theme-audit${extra:+,$extra}"
  body="$( { contract task "$ROOT"; echo; sed '1d' "$f" | grep -vE '^(labels|depends):'; echo; echo '---'; echo '_公開リポのため第三者テーマ名・ベンダー名・ドメインは伏せ字。原本: `docs/research/2026-08-26-theme-structure-audit/issues/`_'; } | redact )"
  n="$(find_issue "$title")"
  if [ -n "$n" ]; then echo "  exists #$n $id"; NUM[$id]=$n; continue; fi
  if [ "$DRY_RUN" = "1" ]; then echo "  [dry-run] $id | $title | $lbl | depends: $deps"; NUM[$id]=0; continue; fi
  url="$(printf '%s' "$body" | gh issue create --repo "$REPO" --title "$title" --label "$lbl" --body-file - 2>&1)" \
    && { NUM[$id]="${url##*/}"; echo "  created #${NUM[$id]} $id"; } \
    || echo "  FAILED $id: $url"
done

# ---- dependency projection (双方向) ----
echo "== dependencies =="
declare -A BLOCKS
expand() { # "THEME-INV-01..11" / "THEME-INV-01, THEME-INV-07" / "なし..."
  printf '%s' "$1" | grep -oE 'THEME-INV-[0-9]+(\.\.[0-9]+)?' | while read -r t; do
    if [[ "$t" == *..* ]]; then a="${t#THEME-INV-}"; a="${a%%..*}"; b="${t##*..}"; for i in $(seq -f '%02g' "$a" "$b"); do echo "THEME-INV-$i"; done
    else echo "$t"; fi
  done
}
for id in "${!DEPS[@]}"; do
  for d in $(expand "${DEPS[$id]}"); do BLOCKS[$d]="${BLOCKS[$d]:-} $id"; done
done
for id in $(printf '%s\n' "${!NUM[@]}" | sort); do
  dep_ids="$(expand "${DEPS[$id]}" | sort -u | sed 's/^/${NUM[/;s/$/]}/' )"
  dep_nums="$(for d in $(expand "${DEPS[$id]}" | sort -u); do echo "${NUM[$d]}"; done | paste -sd, -)"
  blk_nums="$(for b in ${BLOCKS[$id]:-}; do echo "${NUM[$b]}"; done | sort -un | paste -sd, -)"
  [ -z "$dep_nums" ] && [ -z "$blk_nums" ] && continue
  echo "  $id #${NUM[$id]}: depends_on [$dep_nums] blocks [$blk_nums]"
  [ "$DRY_RUN" = "1" ] && continue
  cur="$(gh issue view "${NUM[$id]}" --repo "$REPO" --json body -q .body)"
  new="$(printf '%s' "$cur" \
    | sed -E "s/^blocks: \[\]/blocks: [${blk_nums}]/; s/^blocked_by: \[\]/blocked_by: [${dep_nums}]/")"
  new="$(printf '%s\n\n```yaml\n# helix-issue-dependency.v1\ndepends_on: [%s]\nblocks: [%s]\n```\n' "$new" "$dep_nums" "$blk_nums")"
  printf '%s' "$new" | gh issue edit "${NUM[$id]}" --repo "$REPO" --body-file - >/dev/null && echo "    updated"
done
echo; echo "完了。PROGRESS.md に Issue 番号を転記すること。"
