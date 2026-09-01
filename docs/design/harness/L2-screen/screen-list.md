---
layer: L2
sub_doc: screen-list
status: candidate_projection
parent_doc: docs/design/harness/L1-requirements/screen-requirements.md
source_authority: docs/requirements/l2/screen-list.md
source_sha256: f4cfff4c0d558e11f43539fcdaa8ec92c0cbac752152e21d5296b3502ced9725
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
| PM-02 | WT-UI-02 | WT-SCR-02 | `/wp-admin/site-editor.php?path=/wp_global_styles` | `docs/requirements/l2/screen-list.md` |
| PM-03 | WT-UI-03 | WT-SCR-03 | `/wp-admin/site-editor.php?path=/wp_template_part` | `docs/requirements/l2/screen-list.md` |
| PM-04 | WT-UI-04 | WT-SCR-04 | `/wp-admin/post.php?action=edit` | `docs/requirements/l2/screen-list.md` |
| PM-05 | WT-UI-05 | WT-SCR-05 | `/{post}` | `docs/requirements/l2/screen-list.md` |
| PM-06 | WT-UI-06 | WT-SCR-06 | `/` | `docs/requirements/l2/screen-list.md` |
| PM-07 | WT-UI-07 | WT-SCR-07 | `bin/check-design-consistency.sh` | `docs/requirements/l2/screen-list.md` |
| PM-08 | WT-UI-08 | WT-SCR-08 | `docs/intake/ledger.json` | `docs/requirements/l2/screen-list.md` |
