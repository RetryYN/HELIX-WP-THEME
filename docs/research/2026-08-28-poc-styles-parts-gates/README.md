# PoC 証跡: スタイルバリエーション 3 案 / 共有パーツ・レイアウト 22 パターン / 一貫性ゲートと Figma 取り込み

実施日: 2026-08-27〜28 / 場所: 使い捨て PoC サイト（本テーマ）/ 対応 PR: #38（styles）・#39（patterns）・#36（gates / tools）。
位置づけ: **PoC 証跡**。3 本の PR はいずれも実装ではなく証跡であり、merge しない。採用可否の分類は `docs/design/addenda/L3-A5-poc-pattern-disposition.md`（PR #48）、要求への入力は `../2026-08-27-poc-browser-verification/requirements-input-register.md`。

## 1. スタイルバリエーション 3 案（#38 / THEME-JSON-01 の材料）
同一サイト 4 画面（トップ・記事・LP・一覧）× 4 案を 1366px で撮影して比較。

| 案 | 影を持つ要素 | H1 | 本文 | トップ全長 |
|---|---|---|---|---|
| 基準 | 1 | 48px sans | 20/32 | 3519px |
| 編集誌 editorial | 1 | 64px serif | 22/35 | 5210px |
| 奥行き depth | 31 | 48px sans | 20/32 | 3709px |
| 業務 business | 1 | 36px sans | 18/29 | 3076px |

- スラッグ集合: editorial / business = 親と完全一致（font 6・space 6・color 8）、depth = color 8 + shadow 0–4（shadow の段は親 theme.json 側に置き直した）。
- 見つかった問題: business の `elements.button.spacing.padding` に生値 rem（→ プリセット参照へ）、shadow の段が variation 側にしか無い（→ 親へ）、パターン側のインライン box がカード枠と二重になる（`!important` で回避 = 恒久対処は THEME-CAT-05）。

## 2. 共有パーツ・レイアウト 22 パターン（#39 / THEME-CAT-01・02 の材料）
ヘッダー 7 / フッター 5 / サイドバー 5 / ヒーロー・セクション 3 / レイアウト 3。`Block Types: core/template-part/*` でサイトエディタの差し替え候補に出す方式。

- 描画: 全 22 登録・描画 OK、ブロック属性 JSON 241 個妥当。
- **エディタ検証（G-E1）**: 2026-08-28 再実行、21 パターン（footer-credit 含む）**invalid=0**（`editor-validate-2026-08-28.json`）。
- 見つかった問題と事実:
  - WP 7.1 では `core/group`（flex/grid）の `blockGap` は inline `gap:` として serialize **されない**。`gap:` を書いた側が invalid になる（レビューの前提が逆だった）。main の `parts/header.html` にも同型あり → THEME-CAT-06（#40）。
  - 罫線は辺ごとの longhand + `u002d` エスケープ、`has-border-color`、cover の class 順、small ボタンの `has-custom-font-size` など、静的検査では取れない差分で当初 34 ブロックが invalid → エディタの `getSaveContent` を正として全パターンを再生成。
  - `parts/sidebar.html` はどのテンプレートからも参照されない（テンプレ変種方針 #26 待ち）。
  - パターン file list は transient に載る（`wp_theme_files_patterns-*` を削除して再読込）。

## 3. 一貫性ゲートと Figma 取り込み（#36 / THEME-GATE-01 の材料）
- G-T1 / T1b / T2 / T3 / S1 / S2 を `bin/check-design-consistency.sh` として実装（CI 配線は PoC の範囲）。
- **G-T2 の数え方の穴 2 つ**（`1rem/1em` を見逃す・style 属性 1 件しか数えない）を修正 → 生値 310 → **438**（実ファイル不変）。「意図的に壊した fixture で検出できること」を CI に追加。
- Figma トークン往復: DTCG 25 トークン投入 → 書き出し → 変換 → dry-run 差分なし → 色変更 2 行のみ検出 → `--write` → gate FAIL=0。`--write` が `{}` を `[]` に壊す不具合を修正（差し替え対象パスだけ書き戻す）。拒否経路 4 fixture（未知スラッグ / 値キー欠落 / 型違い / 正規化後重複）はいずれも exit 1。
- Figma 構図経路: `pat/ sec/ col/ h2 @size:` 命名から骨格生成 → 生成物をエディタで invalid=0。無料枠 REST は数回で 429（`--json` で保存済みツリーを再利用）。
- 結論: 経路は通るが、無料枠と手作業の多さから当面は不採用（`docs/design/figma-intake.md`）。

## 4. 自作物のバグ記録（雑さの証跡として残す）
G-T2 の計数漏れ（1rem・属性内 1 件）／ `--write` の `{}` 破壊／ 生成器の save 側 class 欠落／ 拒否 fixture の CI 未配線と重複スラッグ fixture の不備／ `gap:` に関する誤った前提／ 正規化再生成時の PoC URL 混入とプレースホルダ段落の混入／ sed による末尾カンマ／ i18n ゲート未通過（cover の placeholder 日本語）。いずれも実機検証かレビューで検出して修正済み。**静的検査で通っても実機で壊れる**ことが繰り返し起きたため、PoC 実機ゲート（THEME-GATE-02 #42）を要求入力に載せる。

## 5. スクリプト
`scripts/editor-validate.mjs`（登録済みパターンを下書きに投入 → 編集画面 → `isValid` 集計 → 削除）、`scripts/editor-validate-raw.mjs`（任意 markup 版）。接続情報は `POC_URL` `POC_SSH` `POC_WP` `POC_ENV` の環境変数で渡し、リポジトリに置かない。
