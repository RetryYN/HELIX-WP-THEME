# G-E1 ローカル検証記録

## 対象と環境

- 検証対象: テーマに登録された 71 パターン
- 実行環境: ローカル Docker の WordPress 7.1
- エディター URL: `http://localhost:8086`
- 認証情報: 記録せず、`WP_ADMIN_USER` / `WP_ADMIN_PASS` の環境変数から渡す

G-E1 は、パターンの保存済み HTML を下書きページに投入し、ブロックエディターが再生成する save HTML と比較して `isValid` を集計する。保存時のマークアップがブロックの save 実装と一致する必要があるため、検証用の DOM 断片ではなくエディター上の Block validation を確認する。

## 実行手順

`S` は Playwright とスラッグ一覧を置く作業用ディレクトリ、`REPO` はこのリポジトリのルートを指定する。認証情報の実値はコマンド履歴や証跡に残さない。

```sh
export REPO="<repository-root>"
export S="<scratch-directory>"
export WP_ADMIN_USER="<local-admin-user>"
export WP_ADMIN_PASS="<local-admin-password>"

cd "$S"
REPO="$REPO" S="$S" WP_ADMIN_USER="$WP_ADMIN_USER" WP_ADMIN_PASS="$WP_ADMIN_PASS" \
  node "$REPO/docs/research/2026-08-29-ge1-local/scripts/ge1-local.mjs"
```

全件スクリプトは `$S/slugs.txt` の各スラッグについて一時的な下書きページを作成し、エディターでブロックツリーを走査する。結果は `$S/ge1-local.json` に保存し、invalid ブロックがある場合だけ `$S/invalid-<slug>.jpg` を保存する。処理後は作成した下書きを削除する。

特定パターンの保存前後を確認する場合は、次を実行する。

```sh
cd "$S"
REPO="$REPO" S="$S" SLUG="<pattern-slug>" WP_ADMIN_USER="$WP_ADMIN_USER" WP_ADMIN_PASS="$WP_ADMIN_PASS" \
  node "$REPO/docs/research/2026-08-29-ge1-local/scripts/ge1-diff.mjs"
```

## 初期結果と修正分類

初期スナップショットは 71 パターン中 33 パターン、119 ブロックが invalid だった。1 回目の修正後に実機で全件を再検証した結果、13 パターン、18 ブロックが残存した。残存分は初期 119 ブロックの再修正であり、別の invalid ブロックが 18 件増えたものではない。

| 修正対象の型 | 初期 invalid | 追加修正 | 延べ修正 | 主な修正内容 |
| --- | ---: | ---: | ---: | --- |
| `core/group` | 47 | 5 | 52 | 保存されない `gap` / flex 用 inline style の除去、border class と style 順、子ブロックを含む save HTML・空白の完全一致 |
| `core/column` | 29 | 1 | 30 | `has-border-color`、border / padding の longhand と順序、再帰的な子ブロック出力の一致 |
| `core/columns` | 12 | 7 | 19 | 保存されない `gap` と不要なモバイルスタック class の除去、非ブロックコメント・改行を除いた innerBlocks の連結 |
| `core/button` | 24 | 5 | 29 | custom font-size・色/border class、wrapper / link の style 所有先、typography・spacing・border の順序と空白の一致 |
| `core/heading` | 7 | 0 | 7 | HTML の `h3` と一致する `level:3` 属性の追加 |
| **合計** | **119** | **18** | **137** | **初期 33 パターンを修正し、残存 13 パターンを再修正** |

追加修正 18 ブロックの内訳は `core/group` 5、`core/column` 1、`core/columns` 7、`core/button` 5。WordPress 7.1 の save 出力を参照し、親ブロックのラッパー、子ブロックコメント、改行・インデントを揃えた。診断スクリプトが rich-text 子ブロックを二重にラップした箇所は、正しい save HTML へ折り畳んだ。

修正はテーマの静的なパターンマークアップに限定し、AI 判定・variant 生成・統計判定・外部 API 呼び出しは追加していない。既存の PHP i18n と変数埋め込みも維持している。

## 検証記録

ローカル Docker の WordPress 7.1 で全 71 パターンを実機検証し、`TOTAL invalid: 0 / patterns: 71` を確認した。生のパターン別結果は [editor-validate-2026-08-29.json](./editor-validate-2026-08-29.json) に記録している。

必須ゲートの実測結果は次のとおり。

- G-E1: invalid 0 / 71 パターン
- design consistency: `FAIL=0`、`WARN=2`
- 生値: 433（patterns=347、parts=42、templates=44）/ baseline 438
- PHP lint: 全パターン OK
- `git diff --check`: OK

## `ge1-diff.mjs` の既知の制約

`ge1-diff.mjs` は `createBlock` で innerBlocks を再生成するため、paragraph の `content` など、すでに HTML ラッパーを含む rich-text 属性を再度ラップすることがある。例えば `<p ...>text</p>` が子ブロックの EXP では `<p ...><p ...>text</p></p>` となる。このため、EXP の子ブロック部分は参考値とし、修正時は親ブロックのラッパー差分のみを写す。rich-text 子ブロックは、エディターが保持している正しい保存 HTML を維持する。

再検証時の必須コマンドは次のとおり。

```sh
cd <repository-root>
docker run --rm -v "$PWD":/w -w /w php:8.3-cli bash bin/check-design-consistency.sh
docker run --rm -v "$PWD":/w -w /w php:8.3-cli bash -c 'for f in themes/agent-neo-theme/patterns/*.php; do php -l "$f" >/dev/null || echo SYNTAX "$f"; done'
```

## 参照

- [Edit and Save – Block Editor Handbook](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/)
- [Gutenberg versions in WordPress](https://developer.wordpress.org/block-editor/contributors/versions-in-wordpress/)
