---
layer: L2
sub_doc: screen-flow
status: candidate_projection
source_authority: docs/requirements/l2/screen-flow.md
source_sha256: a4e871fda531e09f5323fcafd3ecff3ea04c68d514e909c1ed5fc34c1e66a9aa
pair_artifact: docs/design/harness/L2-screen/wireframe.md
---

# HELIX L2 screen-flow compatibility projection

正本 flow は `docs/requirements/l2/screen-flow.md`。次の対応だけを HELIX reader へ投影する。

`PM-01 → PM-07 → PM-05 / PM-06` は構造変更、ゲート、公開面確認の順路を表す。`PM-08 → PM-07 → PM-05 / PM-06` は AI 経路の同じ順路。`PM-10 → PM-05 / PM-06 → PM-07` は SP 既定・語彙挙動・プレビューとタグ / 同意の設定を公開面・ゲートで確認する順路、`PM-08 → PM-10` は第三者プラグインの検出・領域別既定・警告を manifest と設定へ反映する順路を表す。
`PM-03 → PM-07` は値の判定からゲートへの接続、`PM-02 → PM-07` はスタイル変更のゲート、`PM-04 → PM-05` は記事単位切替の確認、
`PM-07 → PM-09` は証跡付きの実証記録台帳への記録、`PM-10 → PM-05 / PM-06` はサイト既定の設定から公開面確認への順路を表す。`PM-10` の A/B は variant 選択 → 固定配信 → variant ID 計測 → 承認・停止・rollback、画像は dry-run → 非同期進行確認、タグは slot 選択 → データ層契約 → 同意前非発火確認、プラグインは領域検出 → 既定 / 警告 → 人の選択、運用はログ / 差分レビュー / rollback / 鍵管理、CV・バナー・監査は正本選択 → 公開面確認へ接続する。`PM-08` は差分 API、データ層 / 同意契約、第三者プラグイン capability、MCP / REST / WP-CLI の能力集合および監査結果を確認する。`PM-11` はクローラー指標の確認と robots.txt・AI クローラー設定の保存を行う管理面で、MCP / REST へ同じ設定 JSON を投影する。テーマ独自メニューは PM-10 の 1 つだけ。failure、cancel、timeout の状態契約は WT 正本から変更しない。
