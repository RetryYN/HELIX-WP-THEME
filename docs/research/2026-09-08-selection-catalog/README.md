# 要求・候補の機械可読インデックス

独立したHTMLカタログ画面はPO指示で破棄した。パーツPoCの表示・操作確認は各PoCのWordPress環境で行う。このディレクトリは、既存PoC/調査画像、要求ID、受入条件、証拠状態の機械可読な対応データだけを保持する。画像の対応付けは要求の達成証拠ではない。

## 正本と生成物

- 要求と受入条件: `docs/requirements/l3/requirements-ir.json` / `acceptance-cases.json`
- 候補と関連画像の投影: `catalog-data.json`
- 要求・候補対応の投影: `selection-index.json`
- 受入証跡の正本と監査: `acceptance-evidence.json` / `acceptance-audit.json`
- 未完了条件と次の検証: `completion-backlog.md` / `.json`

生成器は画像・候補・受入証跡の参照を更新する。WordPress PoCを実装・表示する代わりにはならない。

## 過去の比較証跡と現行ソース

過去の baseline / before capture は、その時点で計測した `sourceDigests` を保持する。現行ファイルと異なることだけを理由に過去証跡を書き換えたり、現行合格とみなしたりしない。`historical-artifact-snapshots.json` は履歴として扱うファイルを個別列挙し、各JSON自身のSHA-256を固定する。固定後の履歴JSON改変は失敗し、それ以外の source-bound artifact は現在ソースとのdigest一致を要求する。

```sh
node scripts/audit-catalog-evidence.mjs
node scripts/build-selection-catalog.mjs
```

## 受入証跡の更新

束縛元を変えたときは `npm run catalog-evidence:rebind -- --case <AC-ID>` で影響範囲を確認する。変更したソースに依存する全 AC と、再生成する proof を共有する全 AC を選ぶ。proof ごとの再生成コマンドは `package.json` の `catalogOracles` または `config/catalog-admission-oracles.json` の `commands` に宣言する。

`--apply` では選択した全 proof の正規コマンドを `--command-json '["npm","run","<script>"]'` などの argv 配列で渡す。同じ実行内で proof の書き直し・完了状態・指定した成功行・source digest を検査し、実際に変わった digest だけを更新する。`acceptance-rebind-log.json` に transaction が追記され、`npm run catalog-evidence:rebind-check` が base 版からの chain を照合する。digest JSON の直接編集や、過去の比較証跡を現行ソースへ合わせ直す操作はしない。

<!-- catalog-current:start -->
現在の生成結果: 711候補 / 1293画像 / 135要求 / 303受入条件。PoC確認33・部分確認104・証跡未対応166・再検証0。全要求完了ではない。
<!-- catalog-current:end -->
