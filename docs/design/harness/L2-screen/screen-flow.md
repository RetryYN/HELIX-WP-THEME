---
layer: L2
sub_doc: screen-flow
status: candidate_projection
source_authority: docs/requirements/l2/screen-flow.md
source_sha256: 3574d9c72e92cd3a92951f300436f4e7f57bd2e89e508811a35560e70b54fec1
pair_artifact: docs/design/harness/L2-screen/wireframe.md
---

# HELIX L2 screen-flow compatibility projection

正本 flow は `docs/requirements/l2/screen-flow.md`。次の対応だけを HELIX reader へ投影する。

`PM-01 → PM-07 → PM-05 / PM-06` は構造変更、ゲート、公開面確認の順路を表す。
`PM-04 → PM-07` は値の判定からゲートへの接続、`PM-02 / PM-03 → PM-07` はスタイル・パーツ変更のゲート、
`PM-07 → PM-08` は証跡付きの取り込み台帳への記録を表す。failure、cancel、timeout の状態契約は WT 正本から変更しない。
