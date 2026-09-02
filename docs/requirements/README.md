# 要求

正本入口は [`authority.md`](./authority.md)。L1 の 5 sub-doc、L2 discovery / prototype、L3 precompile inventory、
L10〜L12 test design を分離している。旧 AGENT NEO 時代の要求・設計書は PO 判断（2026-09-02）で削除済み（git 履歴 2904aea 以前）。継承した点は `l1/functional.md` 末尾と `authority.md` に記す。

```bash
npm run requirements:validate        # 正本の不変条件
npm run requirements:helix-l1-l2     # HELIX 本体 l1-l2 gap-check
npm run requirements:helix-vmodel    # HELIX 本体 vmodel lint（pair-freeze）
```

要求は append-only event から candidate projection を再構築する。PO 確認は対象 revision 付き agreement event へ
記録し、L3 compile と G1/G3 承認を経るまで frozen としない。
