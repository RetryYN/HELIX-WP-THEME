---
layer: L2
sub_doc: screen-list
status: candidate_projection
parent_doc: docs/design/harness/L1-requirements/screen-requirements.md
source_authority: docs/requirements/l2/screen-list.md
source_sha256: 2a65b838d0a1f77296311dc5daefcb90837b3b8433c8e6b6027412c3265a4619
pair_artifact: docs/design/harness/L2-screen/wireframe.md
next_pair_freeze: L10
implemented_screens: ""
---

# HELIX L2 screen-list compatibility projection

HELIX reader 用の非正本 ID 対応表。route と状態契約の正本は `docs/requirements/l2/screen-list.md` であり、
この projection は G2 freeze または実装完了を表さない。

| HELIX reader ID | WT surface ID | WT source ID | route | authority |
| --- | --- | --- | --- | --- |
| PM-01 | WT-UI-01 | WT-SCR-01 | `/wp-admin/site-editor.php?path=/patterns` | `docs/requirements/l2/screen-list.md` |
| PM-02 | WT-UI-02 | WT-SCR-02 | `/wp-admin/site-editor.php?path=/styles` | `docs/requirements/l2/screen-list.md` |
| PM-03 | WT-UI-03 | WT-SCR-03 | `/wp-admin/post.php?action=edit (block inspector)` | `docs/requirements/l2/screen-list.md` |
| PM-04 | WT-UI-04 | WT-SCR-04 | `/wp-admin/post.php?action=edit (document panel)` | `docs/requirements/l2/screen-list.md` |
| PM-05 | WT-UI-05 | WT-SCR-05 | `/{{post}}/` | `docs/requirements/l2/screen-list.md` |
| PM-06 | WT-UI-06 | WT-SCR-06 | `/ , /lp/ , /category/{{term}}/` | `docs/requirements/l2/screen-list.md` |
| PM-07 | WT-UI-07 | WT-SCR-07 | `cli: bin/check-design-consistency.sh, ge1-local.mjs` | `docs/requirements/l2/screen-list.md` |
| PM-08 | WT-UI-08 | WT-SCR-08 | `/wp-json/{{ns}}/v1/capabilities , mcp abilities` | `docs/requirements/l2/screen-list.md` |
| PM-09 | WT-UI-09 | WT-SCR-09 | `docs/evidence/ledger.jsonl` | `docs/requirements/l2/screen-list.md` |
| PM-10 | WT-UI-10 | WT-SCR-10 | `/wp-admin/admin.php?page=theme-settings` | `docs/requirements/l2/screen-list.md` |
| PM-11 | WT-UI-11 | WT-SCR-11 | `/wp-admin/admin.php?page=theme-crawl` | `docs/requirements/l2/screen-list.md` |

## S3 projection note

S3 は既存 11 画面の追加タブ・要素へ投影し、新規 route を作らない。
