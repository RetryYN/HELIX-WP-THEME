# 要求

正本入口は [authority.md](authority.md)。現在の要求・実績・残り・次の着手順は [最新要求と現在の足並み](current-alignment.md) から読む。

- [全131候補](l3/g3-approval-summary.md): 旧123件を保持、6件改定・8件追加。282 AC / 131 test ID。追加分P1は暫定。
- [ページ種別台帳](discovery/page-type-ledger.md): 9系統は上限のない索引。元7分類との対応、出所・採否・証拠状態・着手順を分離。
- [デザイン受入条件ドラフト](l2/prototype-03-design-acceptance-draft.md): 段13時点の補正と9月5日の履歴を区別。
- L1の5文書は9月5日の承認済み基準。L2 events / projectionとL3候補へ後続のPO判断を反映し、最新revisionの合意なしに再承認・凍結を主張しない。

正式判断の保留は WT-Q-G3-01 / WT-Q-STYLE-01 の2件。これとは別にIRの pending_resolution 3件（VOCAB-01 / VOCAB-03 / PAGE-01）と種別台帳の未検証が残る。問いの集計は candidate-projection.json を参照し、旧81件の数値を現在の全要求件数と混同しない。

```bash
npm run requirements:validate
npm run requirements:helix-l1-l2
npm run requirements:helix-vmodel
npm run helix -- status
```

eventsはappend-only。最新整理は WT-EVT-0304。WT-AGREE-01（G1 / G2）は event head 0231 に限定し、追加・改定を自動承認しない。フロント先行中はG3を保留し、pending_resolution、最新合意、優先度安定を確認して再compileする。検査成功は文書整合の証拠であり、試作・本実装や全体完了の判定ではない。
