# 端末別パーツ宣言の保存・公開 PoC

2026-09-13。`WT-AC-PARTS-01A/B` の部分対応。現行 HELIX WT の `core/template-part` に `wtPartsDeclaration` 属性を追加する。新規ブロック登録は0。既存 `header` / `header-center` / `header-band` を参照し、別デザインを複製しない。

## 宣言と保存

```json
{"common":"header","pc":null,"sp":"header-center"}
```

common / pc / sp をすべて明示する。nullはcommonの継承。参照はテーマのpartsに存在するslugに限定する。公開時は `wp_is_mobile()` で選択し、コアのrender前にslugを差し替える。未選択パーツを描画してCSSで隠す方式ではない。不正な宣言はpre-renderで空文字にし、既定headerへの暗黙のフォールバックをしない。

専用WordPress labでWP-CLIからpageを保存し、`post_content` の完全一致を読み返して確認する。初期宣言を公開したあと、`common=header-center / pc=header-band / sp=null` へ再保存し、PC/SP × JSあり/なしで表示と再読込を確認する。公開画面の1つだけのheaderと、選択されなかったcenter/bandの不在を検査する。

## 検査と画像

`node scripts/verify-parts-declaration.mjs`：**77/77**。

- 初期と更新後のDB読み返し一致
- PC/SP × JSあり/なしの選択参照、公開再読込、非選択DOM不在
- ページoverflow、headerと本文の重なり、44pxの可視header操作対象
- 本文見出し・段落の計算色AA 4.5:1（header内全テキストの包括監査ではない）
- 存在しない参照、device差分未宣言、不正型、パストラバーサル、未知keyのPHP validator拒否と公開出力拒否
- 専用fixture削除確認

全景8枚は `initial-pc-js.png` など。`verification.json` に結果とsource SHA256を保存する。`schema.json` は宣言の形式で、ファイル実在はPHP validatorが追加確認する。初回に既存headerのPC操作対象が44px未満だったため、専用テンプレートに限ったCSSで補った。

## 残る範囲

**Site Editor実操作・client側属性登録と保存往復は未検証。** WP-CLI保存をSite Editor保存の証拠にしない。wp_is_mobileと画面幅の相違、cache segmentation、画面回転は未検証。今回はパーツ参照の差分だけで、端末別のテンプレート全体切替は扱っていない。DBだけに作成されたパーツは受け付けず、theme-file-backedパーツだけが対象。保存時のUIエラーメッセージは未実装。不正宣言はレンダリング時に拒否する。したがって01A/B全体はpartial。

旧 `themes/agent-neo-theme` / `plugins/agent-neo-*` は変更していない。他リポジトリも変更していない。

## 公式資料（参照日 2026-09-13）

- [Template Parts](https://developer.wordpress.org/themes/templates/template-parts/)：partsのファイルとTemplate Part block参照の関係。
- [Introduction to Templates](https://developer.wordpress.org/themes/templates/introduction-to-templates/)：Site Editorがテンプレート・パーツを上書きできるため、CLI保存とは別の検証が必要。
- [block_template_part](https://developer.wordpress.org/reference/functions/block_template_part/)：パーツ出力とラッパーの扱い。今回の実装はコアブロックの描画を再利用する。
