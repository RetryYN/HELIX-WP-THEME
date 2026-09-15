# 内包HELIXのconsumerレビュー記録修正

POの変更境界: このリポジトリに入っている開発ツールとしてのHELIXは修正可。別リポジトリは変更禁止。対象は内包パッケージと、このリポジトリで再適用できる修正・検査に限定する。

## 問題の実測

#179のNode宣言不足はf8e6124で修正し、Claude側でもauthority通過を確認した。しかしapprove時にconsumerルートの本体専用policy/sourceを読むためENOENTになる。

内包コードのpolicy/source読込だけを実行中モジュール基準へ修正し、実際のconsumerを対象に2回のメモリDB再構築を実行した。ENOENTは解消。artifact_registry=188、descent_obligations/plan_registry/review_evidence_registry=0、finding_count=0、unexpected_unstable_columns=0。ただし本体向けcheckpoint人口条件がfalseでconverged=false。調査中の未コミット2件もworkspace.clean=falseで正しく検出された。

`scripts/patch-helix-receipt.mjs`は現時点で参照先修正のみ。元コードと適用後のSHA-256を固定し、二重適用は何もしない。未知バージョンには失敗する。package.jsonのpostinstallへ接続済み。パッチ単体の再適用は確認済みだが、依存全体の再インストール検証は未実施。

## consumer実入力での診断

`./node_modules/.bin/tsx scripts/probe-consumer-receipt.ts`は、実行cwdのHEAD/treeを取得し、実際のconsumer doctorとcanonical DBの2回再構築を実行してから試作判定へ渡す。doctor成功を定数で代用しない。開始・終了でHEAD/treeが変わった場合も拒否する。診断結果は承認receiptではなく、投稿もACKもしない。

86dbdb8上の作業差分8件で実行した結果、doctor_passed=true、両再構築のartifact_registry=188、他3表=0。consumer_projection_failuresはdirty_workspaceのみ、終了コード1。本体のcheckpoint_population_validとconvergedはfalseを維持した。clean状態での成功、生成・受理経路への接続はまだ証明していない。

パッチ検査とconsumer判定の26テストは成功。不正なトップレベル入力、非正整数・安全整数範囲外のschema revisionも拒否する。`npm test`の要求検査・Vモデル検査・consumer doctorも成功した。これらは正式receipt発行や全要求の完了を証明しない。

## 修正契約

- 検査対象のHEAD・tree・workspace・DB再構築はconsumer自身。方針と検証コードの出所は実行中の内包HELIXパッケージ。検査対象をパッケージへすり替えない。
- 本体向けG3 bootstrap v2の人口条件を維持する。consumerの承認を本体G3完了の証明にしない。
- consumer向けreceiptは別schema/profileとして区別し、レビュー生成と読取・merge admissionの双方で扱う。未知profile、schema/profile不一致を拒否する。
- consumer判定にはセットアップ契約・consumer doctor成功・Git追跡対象の実在を必要とする。ファイル欠落を理由にconsumerへ自動降格しない。
- consumerでもclean workspace、同じHEAD/tree、2回の再構築一致、stale/orphan/findingの拒否、非空の対象artifact、証拠digest照合を維持する。空テーブルを無条件に成功へ変換しない。
- reviewerの本人性、別レーン、現HEAD、CI完了後の時刻、CI generation、コメントURL、ACKの条件は維持する。CodexがClaudeの承認receiptを代理生成しない。

## 必要な検査

1. policy/sourceがconsumerルートにない実fixtureで、正しい内包資産を解決する。
2. consumerのHEAD/treeがreceiptに結び付く。本体のHEADへの置換を拒否する。
3. 正常consumerと本体profileを別々に通す。未知profile、consumer判定不足、dirty、入力破損、projection不一致、空artifact、stale/orphanを拒否する。
4. schema/profile/digest改ざんを生成・読取・merge admissionで拒否する。
5. 再インストール時に修正を再適用し、再適用の冪等性と未知パッケージ版の拒否を確認する。
6. 現HEADのCI成功後にClaudeへ再通知し、本人による正式receiptとACKを確認する。コメントだけで完了扱いしない。

## 接続状況

`scripts/lib/create-consumer-review-receipt.ts`は専用schema `helix-consumer-review-db-receipt.v1`を生成する。元の本体receiptを全体として保持し、consumer用のconvergedと失敗理由、生成コード・判定コード・doctorのdigestを外側のdigestへ含める。保存済み証拠の受理は実行cwdから再生成し、全体一致を要求する。

`scripts/patch-helix-consumer-review.mjs`で、同梱CLIの`pr-review-receipt --db-profile consumer-review-projection.v1`、共通レビューreceipt読取、DB admissionへ接続した。未指定はcoreであり、未知profileは拒否する。runtime本人性・別レーン・CI generation・投稿・ACK処理は変更していない。既存core用のDB検証分岐も維持している。

パッチはpostinstallへ接続。3ファイルすべての適用前または適用済みSHA-256を確認してから書き込み、未知版の場合は書き込み開始前に停止する。freshなソースへの適用、冪等性、最後のファイルが未知版の場合に先行ファイルも変更しない検査が成功。関連30テスト、postinstall再実行、CLI help、npm testが成功した。

CLIはinput.headShaをconsumer生成へ渡し、実行cwdのHEADと不一致ならdoctor/DB生成前に拒否する。この拒否を追加した関連31テストが成功した。

## clean consumerと再インストールの実証

同一テーマリポジトリの86dbdb8から一時detached worktreeを作り、作業中の検証モジュールを使ってcleanなconsumerを検査した。対象HEADは86dbdb8のままであり、未コミットの検証コードがそのHEADに含まれるとは扱わない。

| 実測 | 結果 |
| --- | --- |
| consumer doctor | true |
| consumer失敗理由 | 空配列 |
| consumer converged | true |
| 元の本体G3 converged | false |
| 保存済み証拠の再生成照合 | true |
| canonicalLogicalDbReceiptValidによるadmission | true |
| 別HEADのadmission | 拒否 |
| source_treeを書き換えた証拠の読取 | 拒否 |

別の同一リポジトリ一時worktreeへ作業中のpackage.json・パッチ・検証コード・テストをコピーし、npm ci --no-audit --no-fundを実行した。postinstallを含め終了0、その新規依存環境でnpm run test:helix-patchとnpm testも終了0。これは再インストールの検証であり、コピー後のworktreeをcleanとは扱わない。両一時worktreeは検証終了後に撤去済み。元の作業差分と他のworktreeは維持した。

未検証: reviewer本人性・CIを含む正式レビューreceiptからadmissionまでの実往復、Claude本人によるreceipt・ACK。公開前には全差分のレビューと公開情報検査を行い、更新HEADのCI後に再通知する。別リポジトリの変更は行っていない。

本計画は実装・検証途中であり、現時点では正式receipt発行の問題は未解決。
