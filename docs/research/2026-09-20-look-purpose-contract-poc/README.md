# 見た目の用途・品質ゲート契約 PoC

`WT-AC-LOOK-01E` の未登録受入行を、既存の用途台帳、CATALOG-INDEX、実機 `results/verify.json` の 90 pass / 0 fail へ再束縛する。見出し・囲み・CTA・比較表・グラフ・ブログカード・関連一覧・画像処理・4軸と、PC/SP・JS無効・reduced-motion・コントラストの境界を機械検査する。

```bash
node scripts/verify-look-purpose-contract-poc.mjs
```

このPoCは既存証跡の契約化であり、新しいCSSやテーマ実装、Site Editor接続、全組合せの再撮影を主張しない。
