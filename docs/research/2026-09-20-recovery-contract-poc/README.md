# 変更回復契約 PoC

`WT-NFR-REC-01` の受入条件を、視覚デザインの実装とは分離した静的契約で検証する。

`contract.mjs` は構造・スタイル・値・ゾーンを同じリソース形として持ち、`dryRun` が変更前/変更後の digest と patch digest を発行する。`apply` は receipt の基底・patch・目標 digest を照合し、`rollback` は target digest が一致するときだけ保存した snapshot を戻す。stale receipt、patch不一致、rollback中の別変更、未知path、空patchは拒否する。

WordPressではRESTの更新・権限・revisionが実保存を担う。公式仕様上、REST routeには `permission_callback` が必要で、更新は認証済み権限で行う。revisionは復元対象を提供するが、このPoCはそこへ接続していない。

- https://developer.wordpress.org/rest-api/extending-the-rest-api/routes-and-endpoints/
- https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
- https://developer.wordpress.org/rest-api/reference/post-revisions/

## 実行

```bash
node scripts/verify-recovery-contract-poc.mjs
```

2受入条件は `partial`。この証跡はローカルfixtureと静的ブラウザ表示のみで、実WP 7.2、永続DB、権限、MCP/CLI、同時更新競合は未検証である。
