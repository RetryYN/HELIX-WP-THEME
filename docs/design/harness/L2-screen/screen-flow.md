---
layer: L2
sub_doc: screen-flow
status: candidate_projection
source_authority: docs/requirements/l2/screen-flow.md
source_sha256: a59023fe171b4952be56d117fdd5e4534d529748ba28fb1314a7d5cd89902f49
pair_artifact: docs/design/harness/L2-screen/wireframe.md
---

# HELIX L2 screen-flow compatibility projection

正本 flow は `docs/requirements/l2/screen-flow.md`。次の対応だけを HELIX reader へ投影する。

`PM-01 → PM-07 → PM-05 / PM-06` は構造変更、ゲート、公開面確認の順路を表す。`PM-08 → PM-07 → PM-05 / PM-06` は AI 経路の同じ順路。
`PM-03 → PM-07` は値の判定からゲートへの接続、`PM-02 → PM-07` はスタイル変更のゲート、`PM-04 → PM-05` は記事単位切替の確認、
`PM-07 → PM-09` は証跡付きの実証記録台帳への記録、`PM-10 → PM-05 / PM-06` はサイト既定の設定から公開面確認への順路を表す。テーマ独自メニューは PM-10 の 1 つだけ。failure、cancel、timeout の状態契約は WT 正本から変更しない。
