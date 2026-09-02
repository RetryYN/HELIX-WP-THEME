---
layer: L2
sub_doc: screen-list
status: candidate
pair_artifact: docs/test-design/l11-user-acceptance-test-design.md
---

# L2 Screen List

| surface ID | route | source | priority | normal/cancel/failure/timeout |
| --- | --- | --- | --- | --- |
| WT-UI-01 | `/wp-admin/site-editor.php?path=/patterns` | WT-SCR-01 | P0 | normal/cancel/failure/N/A |
| WT-UI-02 | `/wp-admin/site-editor.php?path=/styles` | WT-SCR-02 | P1 | normal/cancel/failure/N/A |
| WT-UI-03 | `/wp-admin/post.php?action=edit (block inspector)` | WT-SCR-03 | P0 | normal/cancel/failure/N/A |
| WT-UI-04 | `/wp-admin/post.php?action=edit (document panel)` | WT-SCR-04 | P1 | normal/cancel/failure/N/A |
| WT-UI-05 | `/{{post}}/` | WT-SCR-05 | P0 | normal/N/A/failure/N/A |
| WT-UI-06 | `/ , /lp/ , /category/{{term}}/` | WT-SCR-06 | P1 | normal/N/A/failure/N/A |
| WT-UI-07 | `cli: bin/check-design-consistency.sh, ge1-local.mjs` | WT-SCR-07 | P0 | normal/N/A/failure/timeout |
| WT-UI-08 | `/wp-json/{{ns}}/v1/capabilities , mcp abilities` | WT-SCR-08 | P0 | normal/cancel/failure/timeout |
| WT-UI-09 | `docs/evidence/ledger.jsonl` | WT-SCR-09 | P2 | normal/N/A/failure/N/A |
| WT-UI-10 | `/wp-admin/admin.php?page=theme-settings` | WT-SCR-10 | P0 | normal/cancel/failure/N/A |

`N/A` は公開面（read-only）に取消がなく、編集面には timeout を伴う外部呼び出しがないため。CLI ゲートは docker 起動待ち、制御面は dry-run の timeout を持つ。
