# 要求

正本入口は [`authority.md`](./authority.md)。起点は [`docs/planning/L0-agent-controlled-variety.md`](../planning/L0-agent-controlled-variety.md)。
L1 の 5 sub-doc、L2 discovery / prototype（デザイン系受入条件ドラフトは [`l2/prototype-03-design-acceptance-draft.md`](./l2/prototype-03-design-acceptance-draft.md)、PO 反応待ち・要求正本ではない）、L3 strict JSON IR（precompile inventory、compile は iteration 2 待ち）、L10〜L12 test design を分離している。

PO への問いは `discovery/candidate-projection.json` の `unresolved`（2 件: G3 承認と開発スタイル。WT-Q-* は総数 81 件、うち採用 77 件・reject 2 件）。通常の問いは「X ができる。採用するか」、直接反映分は「反映内容一覧の確認」の形。

```bash
npm run requirements:validate        # 正本の不変条件
npm run requirements:helix-l1-l2     # HELIX 本体 l1-l2 gap-check
npm run requirements:helix-vmodel    # HELIX 本体 vmodel lint（pair-freeze）
```

要求は append-only event から candidate projection を再構築する。PO 確認は対象 revision 付き agreement event へ
記録し、L3 compile と G1 / G3 承認を経るまで frozen としない。2026-09-05 に WT-AGREE-01（G1 承認・G2 合意）を記録した。compile は RDJ-FR-007 の直近 2 iteration 条件が未達のため backflow_required とし、iteration 2 で再実行する。G3 は未実施。
