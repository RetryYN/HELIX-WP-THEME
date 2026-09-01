---
layer: L2
sub_doc: screen-list
status: candidate
pair_artifact: docs/test-design/l11-user-acceptance-test-design.md
---

# L2 Screen List

| surface ID | route | source | priority | normal/cancel/failure/timeout |
| --- | --- | --- | --- | --- |
| WT-UI-01 | `/wp-admin/site-editor.php?path=/patterns` | WT-SCR-01 | P0 | defined/defined/defined/N/A |
| WT-UI-02 | `/wp-admin/site-editor.php?path=/wp_global_styles` | WT-SCR-02 | P0 | defined/defined/defined/N/A |
| WT-UI-03 | `/wp-admin/site-editor.php?path=/wp_template_part` | WT-SCR-03 | P1 | defined/defined/defined/N/A |
| WT-UI-04 | `/wp-admin/post.php?action=edit` | WT-SCR-04 | P0 | defined/defined/defined/N/A |
| WT-UI-05 | `/{post}` | WT-SCR-05 | P0 | defined/N/A/defined/N/A |
| WT-UI-06 | `/` | WT-SCR-06 | P1 | defined/N/A/defined/N/A |
| WT-UI-07 | `bin/check-design-consistency.sh` | WT-SCR-07 | P0 | defined/N/A/defined/defined |
| WT-UI-08 | `docs/intake/ledger.json` | WT-SCR-08 | P1 | defined/defined/defined/N/A |

`N/A` は公開面（read-only）に取消がなく、編集面には timeout を伴う外部呼び出しがないため。CLI ゲートは docker 起動待ちの timeout を持つ。
