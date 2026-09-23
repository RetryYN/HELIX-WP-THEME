# 旧 AGENT NEO 資産

このディレクトリのテーマと2つのプラグインは、旧 AGENT NEO の参照用アーカイブである。現行の WordPress テーマ PoC は `docs/research/2026-09-05-design-prototype-03/theme/helix-wt/` にある。旧資産の9 style variationや旧 REST 実装を、現行 PoC の実装・合格証拠として扱わない。

| 内容 | 位置 | 扱い |
| --- | --- | --- |
| 旧 FSE テーマ | `themes/agent-neo-theme/` | 参照・履歴比較 |
| 旧 Core プラグイン | `plugins/agent-neo-core/` | 参照・履歴比較 |
| 旧 Embed プラグイン | `plugins/agent-neo-embed/` | 参照・履歴比較 |

旧資産を対象にした `bin/` の検査と PHPUnit テストは、旧実装の回帰や比較のために残している。`docker-compose.yml` の旧テーマ・プラグイン mount は読み取り専用である。現行 PoC の受入判定は現行 PoC のソースと証跡を使う。

リポジトリ直下の `themes/agent-neo-theme` と `plugins/agent-neo-*` は、既存の調査文書や受入証跡に記録されたパスを解決するための互換 symlink である。実体はこのアーカイブにのみ置く。互換パスから旧資産を現行テーマと判定しない。
