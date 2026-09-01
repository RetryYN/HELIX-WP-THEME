---
layer: L1
sub_doc: technical
status: confirmed_input
pair_artifact: docs/test-design/l12-operational-value-test-design.md
authority: docs/requirements/authority.md
---

# L1 Technical Requirements

| ID | 技術境界 |
| --- | --- |
| WT-TRL1-01 | 4 層モデル（トークン → 骨格 → パーツ → 内容）を採り、theme.json v3 が尺度の唯一の所有者である |
| WT-TRL1-02 | 変更可能データは JSON とし、中間 JSON は参照 ID・解決に使った版・digest を持つ |
| WT-TRL1-03 | 一貫性ゲートは静的（G-T1 / T1b / T2 / T3 / S1 / S2）と実機（G-E1 / G-S3）に分け、実機は docker WP で走る |
| WT-TRL1-04 | REST 名前空間はコアに相乗りせず、外部 AI からの write を受け付けない |
| WT-TRL1-05 | テーマ語彙は意図ノードへ展開し、プラグイン語彙は不透明ノードで原文を保持する |
| WT-TRL1-06 | 外部デザインツールからの取り込み経路を持たない |
| WT-TRL1-07 | credential・実運用サイトの接続情報・固有名対応表はリポジトリ外に置く |

PoC で成立した経路は `docs/poc/wt-poc-inventory.json` へ digest 束縛する。PoC 未検証の一般化は行わない。
