# HELIX consumer 外部監査の再検証

入力は [`../../requirements/discovery/inputs/2026-09-09-helix-external-audit.md`](../../requirements/discovery/inputs/2026-09-09-helix-external-audit.md) へ要求候補として整理した。

監査の主指摘であるremote enforcementとdistribution debtは再現した。GitHub API上で `main` はunprotected、rulesetは0件。lockされたHELIXは `19d0bffd...`、検査時のHELIX mainは `f2695861...` で1228 commits aheadだった。差分量は更新優先度の根拠になるが、直接pin更新の安全性を証明しない。

ローカルにはadmin権限確認後に `harness-check` をrequiredへ設定する [`../../../scripts/setup-branch-protection.sh`](../../../scripts/setup-branch-protection.sh) がある。存在と適用済み状態は別であり、現在は未適用。`test` と `theme-quality-gate` はpath filterがあるため、そのjob名を直接requiredにすると対象外変更でcheck自体が現れずmergeを止める可能性がある。複数laneをrequiredにする場合は常時起動する集約checkを先に設計する。

consumer receiptとpostinstall patchはdraft PR #174にあり、mainにはない。patchは既知before / after digestとatomic preflightでfail-closeだが、consumerがdependency内部を書き換える構造的負債は残る。解消先はHELIX本体のprovider extensionであり、このrepositoryから別repositoryへ実装しない。

Claude→Codex通知のPR #182は外部監査後も収束中。Codexがcurrent HEAD `0d7d11e...` を再検証し、初回3 blockerの解消を確認した一方、空のdelivered markerが存在するだけで配送済みになる新規blockerを返した。main導入済みとは扱わない。

監査の「内部機構85%」「end-to-end 65〜70%」は専門家評価として参考になるが、受入台帳の完了数ではない。現行カタログ監査は別の294 ACを単位としており、両数値を換算しない。
