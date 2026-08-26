# THEME-INV-14: テーマA ブロック属性表を保存済みコンテンツから帰納する

labels: investigation, blocks, priority:high
depends: THEME-INV-01

> **状態: 一次完了**（2026-08-26）／レポート: `../reports/INV-14-themeA-attribute-induction.md`
> `blogcard` の全属性を確定。**共通属性 7 種**を特定し、その値が
> **数値ではなく CSS クラス名の文字列**であることを確認（中間 JSON へは逆変換が要る）。
> 抽出スクリプト `../extract-themeA-attrs.sh` を用意（読み取り専用）。
> **残**: スクリプトの実行。1 回で INV-01（属性層）/ 02 / 10 / 11 の未了も同時に閉じる。

## 背景（`10-reverse-themeA.md` §6 / `12-mechanism-comparison.md` §6）
テーマA は **block.json を持たない**。25 ブロックは `functions.php` から
`register_block_type()` で登録され、属性定義はエディタ側の単一 minified バンドル
（`editor/build/index.js`）の中にしか存在しない。

つまり テーマB のように「block.json を読めば属性表ができる」という道が無い。
一方で `<!-- wp:themeA-blocks/xxx {…} -->` のコメントには**実際に使われた属性が JSON で残っている**。
実記事 59 本という母集団があるので、そこから帰納するのが現実的。

さらに render_callback は**未指定属性をカスタマイザ値へフォールバック**するため、
「保存された属性」だけでは出力が決まらない。実効値の解決工程が要る。

## 調査項目
1. 公開記事の `post_content` から `themeA-blocks/*` のブロックコメントを全抽出し、
   ブロックごとに出現した属性キーと値域を集計する（読み取り専用）
2. `editor/build/index.js` から属性の既定値・型を可能な範囲で復元し、①と突き合わせる
3. render_callback を持つ 9 種以上について、**未指定時に参照されるカスタマイザ値**を対応付ける
   （例: `blogcardDesign` 未指定 → `themeA__blogcard_design()`）
4. 「保存属性 + サイト設定 → 実効属性」を解決する関数を定義できるか判定する
5. `themeABlocksCSSAttribute`（ブロック単位インライン CSS）の実使用有無と扱いを決める

## 完了条件
- [ ] 25 ブロック × 属性キー × 値域 の帰納表が存在する
- [ ] フォールバック対応表（属性 → カスタマイザ関数）が埋まっている
- [ ] 実効属性の解決手順が定義され、決定論レンダリングの前提を満たすか結論が出ている
