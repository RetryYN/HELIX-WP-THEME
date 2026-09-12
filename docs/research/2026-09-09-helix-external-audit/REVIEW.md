# HELIX consumer 外部監査の再検証

入力は [`../../requirements/discovery/inputs/2026-09-09-helix-external-audit.md`](../../requirements/discovery/inputs/2026-09-09-helix-external-audit.md) へ要求候補として整理した。

監査の主指摘であるremote enforcementとdistribution debtは再現した。GitHub API上で `main` はunprotected、rulesetは0件。lockされたHELIXは `19d0bffd...`、検査時のHELIX mainは `f2695861...` で1228 commits aheadだった。差分量は更新優先度の根拠になるが、直接pin更新の安全性を証明しない。

ローカルにはadmin権限確認後に `harness-check` をrequiredへ設定する [`../../../scripts/setup-branch-protection.sh`](../../../scripts/setup-branch-protection.sh) がある。存在と適用済み状態は別であり、現在は未適用。`test` と `theme-quality-gate` はpath filterがあるため、そのjob名を直接requiredにすると対象外変更でcheck自体が現れずmergeを止める可能性がある。複数laneをrequiredにする場合は常時起動する集約checkを先に設計する。

consumer receiptとpostinstall patchはdraft PR #174にあり、mainにはない。patchは既知before / after digestとatomic preflightでfail-closeだが、consumerがdependency内部を書き換える構造的負債は残る。解消先はHELIX本体のprovider extensionであり、このrepositoryから別repositoryへ実装しない。

Claude→Codex通知のPR #182はcurrent HEAD `f264ad2...` をCodexが独立再検査し、stdin session ID、ID衝突、壊れたentry隔離、atomic marker、marker本文digest一致を含む10検査で既知blocker 0まで収束した。ただしconsumer draft PRであり、HELIX mainへの対称wake導入済みとは扱わない。

追加監査の訂正もHELIX main `f2695861...` とIssue #532 / #563へ照合した。`agent-session-command-center` はagent slotとcontinuationを集約し、sessionを `active / stale / completed / failed / blocked` として観測する。Claude wakeも `OFF / ARMED / CLAIMED / DELIVERED / REVIEWED / TERMINAL / SUPERSEDED` の状態、`receiverSession`、delivery/ACK digest、明示rearmを持つ。このため「双方を検知できない」「claim / ACKがない」という評価は撤回する。

残る不足は配車と仕事の所有権である。Issue #532はClaude→Codexの上流generic wakeが未正規化、Issue #563はPR単位broadcastとsession単位の排他的担当が未解決としてopen。#563の2026-09-08実測では4 Claude sessionが同時稼働しながら一つのPRを複数sessionが確認し、別PRは4時間27分未着手だった。PRコメントでの担当宣言も、読んでいないsessionのsealを拘束しない。配送ACKとreview作業のleaseを分け、obligationをHEADへ束縛し、lease ownerだけがreceiptをsealできるfenceと、timeout後の一度だけの再配車を上流要求候補にする。

監査の「内部機構85%」「end-to-end 65〜70%」は専門家評価として参考になるが、受入台帳の完了数ではない。現行カタログ監査は別の294 ACを単位としており、両数値を換算しない。
