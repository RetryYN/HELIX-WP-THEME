# バナー・配置の完成比較 PoC

6候補（お知らせ、本文前、商品派生、広告、リクエスト時rotation、補助積層）をPC/SPの全体画像で比較する。正本・描画・検証は現在の `theme/helix-wt` のみ。管理画面での登録UI、実配信、同意取得を完了した証拠ではない。

## 検証結果

`node scripts/verify-banner-zones.mjs`: **403/403 PASS**。`verification.json` にsource digest、各測定、画像hashを保存。12枚の完成画像と4枚の画像読込前後画像。6面×PC/SPで14slot到達と重複なしを確認。fixture/optionのfinally回収とsource不変を確認。PHP WordPress-Core PHPCS、i18n POT生成・検査もPASS。

既存43 ACとzone-slot 2 ACを現ソースで再実行して証拠を再束縛し、監査は missing 236 / partial 26 / verified_in_poc 32 / stale 0。カタログは649候補 / 1,169画像 / 133要求へ生成した。新規 `tests/e2e/banner-selection-catalog.spec.ts` はPC/SPの6候補、全比較事実、画像、選択理由保存を検査し、既存カタログ・HOME・EVENTと合わせて31件を検査した（新規6件と既存25件は全成功）。`npm test` も成功。

## ACと証拠（採用・昇格ではない）

全行の証拠は `verification.json`。以下は実証範囲の対応でありAC全体完了を表さない。

| AC | 実証した行 | 残る範囲 |
| --- | --- | --- |
| WT-AC-BANNER-01A | contract:valid/product-reference/slot-*/post-scope-*/rotation-attribution/category-scope/face-scope | 管理UI、全face×slot組合せ、実配信キャッシュ |
| WT-AC-BANNER-01B | contract:missing-*/image-budget/product-duplicate-source/invalid-date/start-inclusive/end-exclusive | 全不正入力、総ページ速度予算 |
| WT-AC-BANNER-01C | notice:*、placement:*:header-below | 任意正本文言・更新時の閉状態方針 |
| WT-AC-BANNER-01D | notice:*:close-persists、nojs各行 | 初期描画前の閉状態適用、別端末同期 |
| WT-AC-BANNER-01E | placement:*、placement:slot-reached:* | 全23語彙、配置管理UI、全同時設定の安全性 |
| WT-AC-ZONE-03A | stack:*:stack-order/cta-clear、*:G-E1:* | 実consent provider、既存全固定CTAとの併用 |
| WT-AC-ZONE-03B | negative:G-E1:stack-order/cta-clear | 正式G-E1パイプライン接続 |
| WT-AC-ZONE-03C | *:area-cap、modal:*、geometry:*:reserved-size | A/B実測由来の正式面積値、全体CLS計測 |
| WT-AC-ZONE-03D | negative:G-E1:area-cap/initial-modal、LP例外のpositive | 正式G-E1連結、任意slot同時設定の強制抑制 |

面積60%・画像150000bytesはfixtureの試験宣言で、確定推奨値ではない。面積違反は測定gateで検出するが、本番rendererが全slot合計を自動抑制する実装ではない。functional altとPR近接は架空creativeの実測。slot予約は読込前後の矩形一致であり総CLS値ではない。補助積層は設定先へのリンクで、同意を取得しない。rotationはリクエスト時間による選択で閲覧中timerはない。

## 検収記録

1. `../2026-09-15-event-completion/regression-commands.md` の既存verifierを直列実行。イベント時刻・境界は正式証拠を書き出す `--strict` で再実行した。
2. 43 ACは各proofの`completed:true`と指定行PASSを確認後に再束縛。変更された既存zone-slot 2 ACも35/35の専用再検証後に再束縛した。状態の昇格はしていない。
3. HOME全66シナリオ3,235件、EVENT全行列2,004件、BANNER 403件を再実行してからカタログを生成した。
4. 8099静的サーバーでカタログE2Eを実行。新規6件と既存25件は全成功。完成画像はPC/SPで目視した。
5. 外部公式資料からの追加要求候補は `external-observations.md` に記録した。commit/push/PRはこの時点では未実施。
