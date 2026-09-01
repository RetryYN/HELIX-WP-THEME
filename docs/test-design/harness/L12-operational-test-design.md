---
layer: L12
sub_doc: operational-test-design
status: candidate_projection
source_authority: docs/test-design/l12-operational-value-test-design.md
source_sha256: 449823463076ab1ccfed7883ce54697362bec0937d2410ca0eb06c9c76d5d909
pair_artifact: docs/design/harness/L1-requirements/screen-requirements.md
---

# HELIX L12 screen operational-test compatibility projection

HELIX V-model reader へ L1 screen projection の pair を接続する非正本 projection である。
業務価値 oracle の正本は `docs/test-design/l12-operational-value-test-design.md`、画面操作の PO 受入観点は
`docs/test-design/l11-user-acceptance-test-design.md` にあり、この文書から pass、freeze、agreement を主張しない。

| HELIX reader ID | WT surface | operational evidence boundary |
| --- | --- | --- |
| PM-01 | WT-UI-01 | 構造変更の権限エラー件数を WT-OT-02 へ接続する |
| PM-02 | WT-UI-02 | 尺度崩れ件数（G-T1b / G-T3）を WT-OT-05 へ接続する |
| PM-03 | WT-UI-03 | パーツ差し替えの参照欠落（G-S2）を WT-OT-05 へ接続する |
| PM-04 | WT-UI-04 | 破壊域停止件数と誤警告件数を WT-OT-02 へ接続する |
| PM-05 | WT-UI-05 | 欠落面の受け皿有無と JSON-LD 出力を WT-OT-03 へ接続する |
| PM-06 | WT-UI-06 | hero / sticky / 同意バーの積層規約違反を WT-OT-03 へ接続する |
| PM-07 | WT-UI-07 | 静的 FAIL=0 と実機 invalid=0 の同一 HEAD 束縛率を WT-OT-05 へ接続する |
| PM-08 | WT-UI-08 | 台帳の証跡付き率と逆方向取り込み 0 を WT-OT-01 へ接続する |
