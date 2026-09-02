---
layer: L12
sub_doc: operational-test-design
status: candidate_projection
source_authority: docs/test-design/l12-operational-value-test-design.md
source_sha256: d178fee8a2dfea86cf74c9999a36981c579e75d1fab5f213c3a9e83b860980ab
pair_artifact: docs/design/harness/L1-requirements/screen-requirements.md
---

# HELIX L12 screen operational-test compatibility projection

HELIX V-model reader へ L1 screen projection の pair を接続する非正本 projection である。
業務価値 oracle の正本は `docs/test-design/l12-operational-value-test-design.md`、画面操作の PO 受入観点は
`docs/test-design/l11-user-acceptance-test-design.md` にあり、この文書から pass、freeze、agreement を主張しない。

| HELIX reader ID | WT surface | operational evidence boundary |
| --- | --- | --- |
| PM-01 | WT-UI-01 | 構造変更の参照欠落（G-S2）と JSON 外操作件数を WT-OT-03 へ接続する |
| PM-02 | WT-UI-02 | 尺度崩れ件数（G-T1b / G-T3）を WT-OT-02 へ接続する |
| PM-03 | WT-UI-03 | 破壊域停止件数と誤警告件数を WT-OT-03 へ接続する |
| PM-04 | WT-UI-04 | 記事単位切替の未登録メタ・option 経路件数を WT-OT-01 へ接続する |
| PM-05 | WT-UI-05 | SP 既定・語彙・PR・JSON-LD・A/B variant・SNS share・CV・バナー・タグ / 同意・監査バッジの受け皿有無を WT-OT-02 / 08 / 14 / 15 / 16 / 17 / 18 / 20 / 21 へ接続する |
| PM-06 | WT-UI-06 | SP 積層規約違反、CollectionPage 欠落、画像最適化、feed・資料 DL・バナー・同意状態の公開面確認を WT-OT-02 / 09 / 14 / 15 / 16 / 18 / 21 へ接続する |
| PM-07 | WT-UI-07 | 静的 FAIL=0、SP 操作境界、タグ同意前非発火、性能予算、Lighthouse / Core Web Vitals の blocking gate、実機 invalid=0 の同一 HEAD 束縛率を WT-OT-05 / 10 / 18 / 19 / 21 の前提として記録する |
| PM-08 | WT-UI-08 | manifest 列挙率、SP プレビュー、データ層 / 同意契約、第三者プラグイン領域別検出、差分 API、MCP / REST / WP-CLI の能力集合一致、監査 export、manifest 外指定の拒否率を WT-OT-01 / 03 / 11 / 12 / 16 / 17 / 20 / 22 / 23 へ接続する |
| PM-09 | WT-UI-09 | 実証記録の証跡付き率と他リポ参照 0 を WT-OT-04 へ接続する |
| PM-10 | WT-UI-10 | 設定 JSON の schema 検証違反件数と manifest との不一致 0 を WT-OT-01 / 22 へ、SP 既定・プレビュー、tag slot・データ層・同意、第三者プラグインの領域別既定・警告、A/B・画像・操作ログ・差分・rollback・鍵・CV・バナー・監査の運用値を WT-OT-08 / 09 / 13 / 15 / 16 / 17 / 18 / 20 / 21 / 22 / 23 へ、商品正本の反映率・商品 CTA の計測経路欠落・購入完了のテーマ内扱い 0 を WT-OT-06 へ接続する |
| PM-11 | WT-UI-11 | bot 識別済みクロール指標・保持/非記録・キャッシュ限界を WT-OT-07 へ接続する |
