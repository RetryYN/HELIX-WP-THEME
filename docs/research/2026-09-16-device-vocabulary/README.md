# 記事の端末別完成比較

WT-FR-SP-03（WT-AC-SP-03A/B）の部分PoC。Issue #114/#116、関連VOCAB-01/LOOK-01。`compare`は横比較と本文末CTA、`read`はSP項目カードと到達後固定CTA。同じ架空本文・写真を使い、表示色だけでなく読む操作の違いを比較する。

## 実証

`node scripts/verify-device-vocabulary.mjs`: **104/104 PASS**。PC1280/SP390 × JS有無、reduced-motion reduce。core Tabsの保存ブロックを選択styleで拡張し、初期HTMLに全panel本文を保持。PC Arrow/Home/End、SP Enter/Space、幅変更往復、比較表の列ラベル・scroll/card、写真横送りbutton、目次開閉、CTA到達後固定と元位置の高さ予約を実測。4枚の完成全体画像を保存。fixture所有照合・削除・予約slug不在・lock解除・source不変を確認。option変更なし。

PHP構文、WordPress-Core PHPCS、i18n POT/検証、JS構文、diff-check PASS。

## AC対応と未実証

| AC | verification.jsonの実証行 | 未実証 |
| --- | --- | --- |
| WT-AC-SP-03A | *:contract/table-device-shape/table-labels、*:tabs/accordion、*:toc-*、*:gallery-button-scroll、*:cta-* | JSON任意編集を全表示へ反映する契約検証、管理画面/MCPプレビュー一致、全5語彙の独立編集、選択schemaの厳格検証 |
| WT-AC-SP-03B | *:core-panels-in-html、*:all-content-visible/no-dead-controls、*:end-selects-last/home-selects-first/arrow-selects-second、*:enter-collapses/space-opens、*:resize-* | 正式負例ゲート、支援技術実機、全APG契約、フォーカス保持の全入れ子状態 |
| WT-AC-VOCAB-01A/B/C | core/table、core/tabs、core/gallery、core/details、core/buttonsの受け皿を使用 | 全14語彙、Editor保存往復、block上限判断 |
| WT-AC-LOOK-01E | 2用途説明、4完成画像、no-overflow/nojs | LOOK要求全体、全型・全端末 |

SP-03A/Bは上記の実証範囲だけを `acceptance-evidence.json` へ `partial` 登録した。AC全体完了や `verified_in_poc` への昇格ではない。比較表の列ラベル生成は既存テーマの実装を再利用。galleryには装飾画像（alt空）とcaptionを使用。Tabs styleは独自新規blockではないが、coreの対話実装をそのstyleに限りテーマのAPG操作へ置換する。Coreの通常Tabsは変更しない。現在のJSONは既定の2選択セットであり任意のschema入力を受ける管理機能ではない。

## 検収側への引継ぎ

41 ACに対応する32コマンドを現ソースで直列再実行し、各proofの`completed:true`と指定row全PASSを確認してから既存状態のまま再束縛した。監査は missing 236 / partial 26 / verified_in_poc 32 / stale 0。HOME 3,235件、EVENT 2,004件、BANNER 403件、DEVICE 104件も現ソースで再実行し、カタログは651候補 / 1,173画像 / 133要求へ生成した。カタログE2Eは新規DEVICEを含む33/33 PASS。

1. capability scannerを`inc/*.php`のobject形式`register_block_style()`にも対応させ、manifestを57 patterns / 78 block stylesへ再生成した。実機登録は68 patterns / 78 stylesで宣言対象の欠落0。
2. `regression-command-map.json` の **41 AC→32コマンド→証拠→row_names**を全件照合した。DB使用verifierは直列実行し、unmapped 0。
3. カタログ生成とDEVICE/HOME/EVENT/BANNERおよび既存カタログE2Eを実行済み。
4. SP-03A/Bを証拠行・source digest・oracle digest付きで `partial` 登録した。正式な管理UI、MCPプレビュー一致、全14語彙、支援技術実機は引き続き未実証。

本担当はcommit/push/PRを行っていない。正式面積/認可/外部配信/同意機能は本バッチ対象外。
