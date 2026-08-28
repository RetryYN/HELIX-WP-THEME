# THEME-INV-12: 資産再利用可否台帳（14 件）を本調査の結果で埋める

labels: investigation, ledger, priority:high
depends: THEME-INV-01..11

> **状態: 暫定版**（2026-08-26）／レポート: `../reports/INV-12-asset-reuse-ledger.md`
> 14 行のうち **6 確定 / 5 暫定 / 3 未判定**。REST 34 本を A 群 16（契約付き移植）/
> B 群 9（不採用）/ C 群 4（契約のみ + アダプタ）/ D 群 4（基盤）に確定。
> **残**: 未判定 3 件（MCP/Abilities・dry-run/apply/rollback・idempotency）の精読と、
> GRAPHIX-NEO 側 `docs/references/` への反映（cross-repo・PO 承認後）。

## 背景
`GRAPHIX-NEO/docs/references/helix-wp-theme-reference.md` の再利用候補 **14 件が全て「未判定」**のまま。
判定値は `参照のみ` / `契約付き移植` / `不採用`。本調査（テーマA × テーマB × agent-neo 実測）は
その判定根拠を作るために行われた。

## 調査項目
14 件それぞれについて、本調査の実測を根拠に判定を入れる:
FSE/theme.json・Design Tokens・Gutenberg block/pattern・中間 JSON と決定論 render・REST controller・
MCP/Abilities・dry-run/apply/rollback・idempotency・tracking/AB・HP/LP patterns・
security/SSRF/audit log・WordPress 7 対応知見・embed isolation・test/CI/SBOM

## 完了条件
- [ ] 14 行すべてに判定値と根拠（本調査の節番号 or イシュー番号）が入っている
- [ ] `契約付き移植` としたものは、契約を起こす後続作業が特定されている
- [ ] 台帳の更新が GRAPHIX-NEO 側へ反映されている（PO 承認後）
