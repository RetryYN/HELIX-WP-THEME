# HELIX consumer 外部監査入力（2026-09-09）

## 扱い

外部監査官から共有された評価を、GitHub API・current checkout・依存 lock と照合した discovery input。採点値は意見であり、要求完了率や G3 承認の証拠には使わない。確認済みの機械的欠落だけを要求候補へ送る。

## 再検証した事実

| ID | 事実 | 2026-09-09 の証拠 | 判定 |
| --- | --- | --- | --- |
| HX-AUD-01 | `main` の remote 強制境界 | GitHub branches API: `protected=false`、repository rulesets API: `[]` | confirmed gap |
| HX-AUD-02 | HELIX dependency pin | `package-lock.json`: `19d0bffd44fc51ba13922d953dd73f9d47af8dff` | confirmed |
| HX-AUD-03 | pin と HELIX main の距離 | GitHub compare API: main `f2695861e22812444e7b22d4fc74ff5c69e00546`、`ahead_by=1228`、`behind_by=0` | confirmed gap; commit数だけで互換性は判断しない |
| HX-AUD-04 | consumer receipt の所在 | GitHub: `main=e76e7c7b...`、draft PR #174=`e7d17989...` | main未反映 |
| HX-AUD-05 | consumer patch | `package.json` postinstall と `scripts/patch-helix-*.mjs` が installed HELIX を既知digestに限って変更 | confirmed debt; fail-close実装は維持 |
| HX-AUD-06 | Claude→Codex bridge | draft PR #182 current HEAD `f264ad2116a71eab3858e03ca6953a3de358811a`。Codex独立再検査10/10、既知blocker 0 | consumer PRでは収束、HELIX main未反映 |
| HX-AUD-07 | session / wake 観測能力 | HELIX main `f2695861...`: `agent-session-command-center` が `active / stale / completed / failed / blocked` と continuation を投影。Claude wake は `OFF / ARMED / CLAIMED / DELIVERED / REVIEWED / TERMINAL / SUPERSEDED`、`receiverSession`、delivery/ACK digest、明示rearmを実装 | confirmed capability。存在検知・claim・ACKの欠落とは扱わない |
| HX-AUD-08 | review workの配車・所有権 | HELIX Issue #532 / #563 はopen。#563追記では4 Claude session稼働中に同一PRの重複reviewと別PRの4時間27分未着手を実測。PRコメントの担当宣言は別sessionを拘束しなかった | confirmed gap。観測ではなく排他的ownership・lease timeout・再配車・seal fenceの不足 |

## 要求への投影

| Candidate | 優先度 | 投影先 | 要求候補 |
| --- | --- | --- | --- |
| HX-REQ-ENFORCE-01 | P0 | `WT-NFR-GATE-01` 改定候補 | exact HEADの静的ゲート・実機G-E1・独立review receiptを同一HEADへ束縛し、GitHub `main` protectionでrequired checkとして強制する。remote protectionが無い状態を完了扱いしない。 |
| HX-REQ-PIN-01 | P0 operations | consumer lifecycle（製品UI要求とは分離） | HELIX pinは candidate pin → clean install → consumer compatibility → CI terminal → exact-HEAD cross-author review → promotion の順で更新する。commit距離だけで直接mainへ更新しない。rollback pinを保持する。 |
| HX-REQ-EXT-01 | P1 upstream candidate | HELIX本体への提案。別repositoryへは書かない | core / consumer / project-specific receipt providerを正式extension pointにし、consumerの `node_modules` patchを廃止可能にする。 |
| HX-REQ-CANARY-01 | P1 upstream candidate | HELIX本体への提案。別repositoryへは書かない | clean consumer fixtureでinstall、doctor、receipt再構築、notification round tripを定期実行するcompatibility suiteを持つ。 |
| HX-REQ-INBOX-01 | P0 operations | PR #182 + HELIX Issue #532 | consumer bridgeのID衝突、entry隔離、atomic marker、marker検証、session provenance、再配送を負例で拘束し、上流ではClaude/Codexの対称wakeへ一般化する。consumer側の収束を上流導入済みとみなさない。 |
| HX-REQ-DISPATCH-01 | P0 upstream candidate | HELIX Issue #563。本repositoryから別repositoryへは書かない | review workごとに担当sessionを一意claimし、leaseを持つsessionだけがreview receiptをsealできるfenceを設ける。未ACK・lease timeout・stale/failed/blocked sessionを検知したら、同じHEAD・CI generation・obligationを保ったまま別の適格sessionへ自動再配車する。重複実行と無担当滞留をterminal理由付きで監査できる。 |

## 受入条件候補

1. `main` protectionのAPI応答でrequired status checkに常時起動する集約checkが含まれ、force push・deletionが禁止される。
2. required checkが欠落、別HEAD、pending、failure、またはreview receiptが別HEADならpromotionを拒否する。
3. candidate HELIX pinのclean `npm ci` 後にconsumer doctor、要求検査、receipt再構築、projection / checkpoint replayが通る。
4. candidate失敗時はlockfileを旧pinへ戻して同じconsumer suiteが再度通る。
5. inbox markerはatomicに生成し、空・破損・別ID・digest不一致を未配送として再試行する。
6. 同一review workを複数sessionが同時claimした場合、1 sessionだけがleaseを取得し、非owner sessionのreceipt sealをfence token不一致で拒否する。
7. 未ACKまたはlease更新なしが閾値を超えた場合、session command centerの状態と照合してstale ownerを解放し、同一obligationを別sessionへ一度だけ再配車する。元ownerの遅延sealは拒否する。
8. 配車履歴から、依頼・claim owner・lease期限・ACK・rearm/reassignment・review・terminal reasonをHEAD単位で再構築できる。

## 境界

- `WT-NFR-GATE-01` の正式改定、AC追加、L3 compile、G3承認は別工程。過去承認をこの入力へ自動適用しない。
- branch protectionの実適用はGitHub remote writeであり、本入力の記録やローカル検査と同一視しない。
- HELIX本体のextension / canary / generic inboxは上流候補として記録するだけで、別repositoryを変更しない。
- 現行Claude wakeのclaim / ACKは配送受領の証拠であり、review workの排他的な担当権やreceipt seal権を証明しない。
- PR #174 / #182がdraftの間はmain導入済みと表現しない。
