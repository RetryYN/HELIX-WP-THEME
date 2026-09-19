# 共通ヘッダーナビ実機 PoC

対象は現行 `helix-wt`。専用 WordPress lab の一時投稿・navigation・設定・Site Editor 保存済み template part を用い、各ランナーの finally で元状態へ戻す。実行中に同じ lab の設定を変える検証を並列実行しない。

## 実装

- 全9ヘッダーは固定 navigation-link を持たず、標準 core/navigation が公開済み wp_navigation を参照する。native と shared の生成責務を共通化し、本文・footer の独立参照を保つ。
- Site Editor の「共通ヘッダーナビ」で参照先を選択・保存する。標準ナビゲーション編集でリンク内容を更新する。参照先保存は全ヘッダーへ適用されることを UI に明示する。
- SP テキストナビも標準ブロックとして保存する。HTML 展開で参照が固定される問題を除去した。
- 各 header の contentSize/wideSize を除去し、親の content/wide と独立した header-max を継承する。style variation が後から読み込まれる場合も親 header-max が優先する。
- 独立 CTA と電話リンクを維持。JS が無効でもリンクが露出し、動かないメニュー開閉ボタンを表示しない。

## 再現

```sh
node scripts/verify-header-navigation-static.mjs
node scripts/verify-header-navigation-editor.mjs
node scripts/verify-header-navigation-boundaries.mjs
node scripts/verify-header-navigation-complete.mjs
node scripts/summarize-header-navigation-complete.mjs
node scripts/verify-header-navigation.mjs
node scripts/verify-i18n-profile.mjs
```

## 条件と assertion の区別

| 系統 | 条件 | 内容 |
|---|---:|---|
| 主行列 | 504 | A/B × JS有無 × native/shared × 9型 × PC＋SP6軸 |
| 境界 | 459 | 無効参照180、階層・長い日本語216、親尺度27、幅境界36 |
| Editor保存後 | 72 | 全9保存済み型 × native/shared × 390/1440 × JS有無 |
| 静的・Editor負例 | 条件数に加算しない | ブロック再帰構造、固定リンク、全style、REST権限・nonce、保存・再入場 |

合計 1,035 条件。assertion 数は `verify.json` に別集計する。各ランナーの全行と cleanup を含み、単に条件数を assertion 数として扱わない。

Editor では A 選択保存→再入場→B 選択保存→標準リンク編集→保存→再入場を実操作する。全9 template part を保存・再入場し、その保存済み状態で公開面72条件を検査する。空、存在しないID、誤った投稿型、不正な型、draft/private/trash、未設定を実機で検査する。subscriber は自身の有効な nonce を取得して拒否を確認し、admin の欠落/不正 nonce も拒否を確認する。

## 目視

390px: `native-band-cta.png` はブランド、独立CTA、開閉ボタンが1行内で分離。`native-two-rows-text-nav.png` は保存後ラベルが揃い、本文に重ならない。`hierarchy-text-nav-false.png` は JS無効の階層リンクと長い項目が横溢れせず露出する。

1440px: `native-center-pc.png` は中央ブランドと独立したナビ段が整列。`editor/reference-selection.png` は参照先・保存操作・全体適用説明が読み取れる。

PHP構文検査（content-navigation.php / header-navigation-settings.php）、Editor JS構文検査、git diff --check は成功。i18n検査は失敗0、未翻訳CJK 0。

Site Editor の編集キャンバスは SP補助ナビも編集対象として見えるため、公開PCと同じ表示とは限らない。公開面の表示判定は72条件および504条件で分けて検査する。

この記録は WT-FR-PARTS-02 の PoC 証跡であり、G3や製品全体の受入を宣言しない。他の受入条件の古い証跡は各 oracle の再実行なしにハッシュだけ更新しない。

## 最終結果

- 主行列504条件 / 3,985 assertions、境界459条件 / 1,077 assertions、Editor72公開条件と操作負例 / 261 assertions、静的146 assertions: 全件成功。
- 集計整合検査を含む統合証跡: 1,035条件 / 5,496 assertions、`completed: true`。
- 既存 header-navigation oracle: 62 / 62成功として再実行。
- WT-AC-PARTS-02A/B をこの証跡に対応付けた。依存する既存oracleも再実行し、最終監査は missing 236 / partial 26 / verified_in_poc 32 / stale 0。カタログは633候補 / 1,137画像 / 133要求として決定的に再生成した。
- 変更は現行テーマ・再現scripts・新証跡・PARTS-02カタログ証拠登録に限定。旧AGENT NEOテーマ・プラグインは変更していない。
