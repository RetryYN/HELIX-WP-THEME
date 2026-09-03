# PoC: Abilities API の 3 面（WP-CLI / REST / MCP）が同じ能力集合を返すか

- 対象の問い: WT-Q-AGENT-03 の PoC 課題「パック定義 JSON から登録して MCP・REST・CLI の 3 面が同じ能力集合を返す」
- 実施日: 2026-09-03
- 環境: ローカル Docker の WordPress 7.1（PHP 8.3）、WP-CLI、WordPress 公式 MCP Adapter v0.6.1（GitHub release zip をホストで取得して `docker cp` で投入）
- 参照した公式ドキュメント（2026-09-03 確認）: https://developer.wordpress.org/apis/abilities-api/
  （`wp_register_ability()` を `wp_abilities_api_init` で呼ぶ、`meta.show_in_rest`。annotations / `public` / `mcp.public` の扱いは
  コンテナ内 `wp-includes/abilities-api/class-wp-ability.php` と MCP Adapter の `McpAbilityExposure` で確認）
- 終了時状態は `results/cleanup-state.txt`（mu-plugin 3 ファイル削除、Application Password `wt-poc` 削除、MCP Adapter プラグイン撤去、option `wt_poc_site_selection` 削除、有効テーマ `agent-neo-themes/agent-neo-theme`）。本リポの `themes/` `plugins/` は変更していない。

## 結論

**成立**。pack.json（3 ability）を mu-plugin が読み `wp_register_ability()` で登録すると、
WP-CLI（`wp eval` で `wp_get_abilities()`）・REST（`/wp-abilities/v1/abilities`）・MCP（Adapter の `tools/list`）の
3 面で同じ 3 ability が、同じ label / input_schema / output_schema / annotations で見えた（`results/results.json` の `match` は
1 点を除きすべて true。例外は下記「想定と違った点」1）。

destructive ability（`wt/site-selection-apply`）は receipt なしの execute を 3 面すべてで拒否した
（CLI: `wt_receipt_required` WP_Error、REST: HTTP 400 同コード、MCP: `isError:true`）。
認証なしでは REST 一覧・実行が 401、MCP は匿名の `tools/list` が 401（証跡 `results/face-c-mcp-wt-pack-tools-list-anon.json`）。他メソッドの匿名呼び出しは未検証。

## パック定義と mu-plugin

- `scripts/pack.json`: category `wt` と 3 ability
  - `wt/site-selection-read`（readonly / idempotent。現在の header パーツ案とテンプレ変種を返す。入力なし）
  - `wt/site-selection-dry-run`（readonly。差分と receipt を返すだけで副作用なし）
  - `wt/site-selection-apply`（destructive / idempotent。`manage_options` 必須。dry-run の receipt が無いか不一致なら WP_Error）
  - 各 ability の `meta`: `annotations{readonly,destructive,idempotent}` / `public` / `show_in_rest` / `mcp{public,type}`
- `scripts/zz-wt-pack.php`: pack.json を読んで `wp_abilities_api_categories_init` / `wp_abilities_api_init` で登録。
  execute_callback は ability 名で PHP 側に対応表を持つ（JSON にはコールバックを書けない）。
  さらに `mcp_adapter_init` でパック専用 MCP サーバー（route `mcp/wt-pack`）を作り、3 ability を tool として直接公開。
- `scripts/zz-wt-poc-env.php`: ローカル限定。HTTP のままでは Application Password が無効化されるため
  `wp_is_application_passwords_available` を true にする（本番へ持ち込まない）。
- `scripts/compare-faces.py`: 3 面の結果を突き合わせて `results/results.json` を出す。

## 再現手順

前提: `docker compose` の WordPress 7.1（`http://localhost:8086`）が起動済みで、管理ユーザーは `admin`。

### MCP Adapter の取得・設置

WordPress 公式 MCP Adapter（GitHub release v0.6.1）の zip をホストで取得し、コンテナ内の plugins ディレクトリへ展開する。実際に使った有効化 slug は scripts / results から特定できないため、ここでは `<mcp-adapter-slug>` と記す。

```text
curl -L <WordPress 公式 MCP Adapter v0.6.1 の GitHub release zip URL> -o <出力先>/mcp-adapter-v0.6.1.zip
docker cp <出力先>/mcp-adapter-v0.6.1.zip agent-neo-wp:/var/www/html/wp-content/plugins/mcp-adapter-v0.6.1.zip
docker exec agent-neo-wp sh -c 'unzip -q /var/www/html/wp-content/plugins/mcp-adapter-v0.6.1.zip -d /var/www/html/wp-content/plugins/ && rm -f /var/www/html/wp-content/plugins/mcp-adapter-v0.6.1.zip'
docker compose run --rm -T wpcli plugin activate <mcp-adapter-slug>
```

### mu-plugin と pack の投入

`zz-wt-pack.php` は `__DIR__ . '/wt-pack.json'` を読むため、`scripts/pack.json` はコンテナ内で `wt-pack.json` という名前にする。

```text
docker cp scripts/zz-wt-pack.php agent-neo-wp:/var/www/html/wp-content/mu-plugins/zz-wt-pack.php
docker cp scripts/zz-wt-poc-env.php agent-neo-wp:/var/www/html/wp-content/mu-plugins/zz-wt-poc-env.php
docker cp scripts/pack.json agent-neo-wp:/var/www/html/wp-content/mu-plugins/wt-pack.json
```

### Application Password

値は記録せず、発行コマンドの出力を環境変数 `WP_APP_PASS` に渡す。

```text
export WP_APP_PASS="$(docker compose run --rm -T wpcli user application-password create admin wt-poc --porcelain)"
```

### 3 面の呼び出し

面 A（WP-CLI）は `wp eval` で列挙する。

```text
docker compose run --rm -T wpcli --user=admin eval 'echo wp_json_encode( wp_get_abilities(), JSON_UNESCAPED_SLASHES ), PHP_EOL;'
```

面 B（REST）は Application Password で一覧と実行を呼び出す。`<redacted-receipt>` は dry-run の結果を渡す位置のプレースホルダ。

```text
export WP_BASE_URL=http://localhost:8086
curl -u "admin:$WP_APP_PASS" "$WP_BASE_URL/wp-json/wp-abilities/v1/abilities"
curl -u "admin:$WP_APP_PASS" "$WP_BASE_URL/wp-json/wp-abilities/v1/abilities/wt/site-selection-read/run"
curl -u "admin:$WP_APP_PASS" -X DELETE "$WP_BASE_URL/wp-json/wp-abilities/v1/abilities/wt/site-selection-apply/run" \
  -H 'Content-Type: application/json' \
  --data '{"header_part":"header-b","receipt":"<redacted-receipt>"}'
```

面 C（MCP HTTP）は `/wp-json/mcp/wt-pack` へ `initialize` → `Mcp-Session-Id` 取得 → `tools/list` → `tools/call` の順で呼び出す。

```text
curl -i -u "admin:$WP_APP_PASS" -X POST "$WP_BASE_URL/wp-json/mcp/wt-pack" \
  -H 'Content-Type: application/json' \
  --data '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"wt-poc","version":"0.1.0"}}}'
export MCP_SESSION_ID=<initialize 応答の Mcp-Session-Id>
curl -u "admin:$WP_APP_PASS" -X POST "$WP_BASE_URL/wp-json/mcp/wt-pack" \
  -H "Mcp-Session-Id: $MCP_SESSION_ID" -H 'Content-Type: application/json' \
  --data '{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}'
curl -u "admin:$WP_APP_PASS" -X POST "$WP_BASE_URL/wp-json/mcp/wt-pack" \
  -H "Mcp-Session-Id: $MCP_SESSION_ID" -H 'Content-Type: application/json' \
  --data '{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"wt-site-selection-read","arguments":{}}}'
```

STDIO は次のサーバーを起動し、同じ JSON-RPC の `initialize` / `tools/list` / `tools/call` を流す。

```text
wp mcp-adapter serve --server=wt-pack-server --user=admin
```

3 面の突合結果を作る。

```text
python scripts/compare-faces.py
```

### 撤去

```text
docker exec agent-neo-wp rm -f /var/www/html/wp-content/mu-plugins/zz-wt-pack.php /var/www/html/wp-content/mu-plugins/zz-wt-poc-env.php /var/www/html/wp-content/mu-plugins/wt-pack.json
docker compose run --rm -T wpcli user application-password delete admin --all
docker compose run --rm -T wpcli plugin deactivate <mcp-adapter-slug>
docker compose run --rm -T wpcli plugin delete <mcp-adapter-slug>
docker compose run --rm -T wpcli option delete wt_poc_site_selection
docker compose run --rm -T wpcli theme status
docker compose run --rm -T wpcli plugin list
docker compose run --rm -T wpcli option get wt_poc_site_selection
```

最後の `option get` は option が存在しないことを確認するためのもの。`results/cleanup-state.txt` と合わせて削除を確認する。

## 各面の手順と結果

### 面 A: WP-CLI

- `wp ability` サブコマンドは WP 7.1 の WP-CLI には無い（`'ability' is not a registered wp command`）。`wp eval` で列挙した。
- `results/face-a-cli-abilities.json`: 全 ability（core 3 + wt 3 + MCP Adapter 自身の 3）の label / schema / meta。
- `results/face-a-cli-execute.json`（`--user=admin`）: read → dry-run → receipt なし apply（`wt_receipt_required`）→
  偽 receipt（`wt_receipt_mismatch`）→ 正 receipt で apply 成功 → read で反映確認。
- `results/face-a-cli-apply-anon.json`: ユーザーなしの apply は `ability_invalid_permissions`（permission_callback が効く）。
- `results/face-c-cli-servers.json`: `wp mcp-adapter list` で 2 サーバー（default と wt-pack）を確認。

### 面 B: REST（Application Password 認証）

- ルートは REST index で確認: `/wp-abilities/v1/abilities`、`/abilities/<name>`、`/abilities/<name>/run`、`/categories`。
- 一覧: 認証なし 401（`results/face-b-rest-abilities-anon.json`）、認証あり 200 で 6 件（core 3 + wt 3。
  MCP Adapter の 3 は `show_in_rest` が無いため出ない）。
- 実行は annotations で HTTP メソッドが決まる（core の `validate_request_method`）:
  readonly → GET（入力は `?input[key]=` のクエリ）、destructive かつ idempotent → DELETE、それ以外 → POST。
  POST で apply を叩くと 405（`face-b-rest-run-apply-anon-post.json`）。
- `face-b-rest-run-read-*.json` / `-dryrun-auth.json` / `-apply-noreceipt-auth.json`（400）/ `-apply-bogus-auth.json`（409）/
  `-apply-receipt-auth.json`（200, applied）/ `-read-after.json`（反映）。認証なしの read / apply は 401。

### 面 C: MCP（WordPress 公式 MCP Adapter v0.6.1）

- HTTP transport のルートは `/wp-json/mcp/<server_route>`。`initialize` で `Mcp-Session-Id` ヘッダーが返り、
  以降は同ヘッダー必須（無いと `-32600 Missing Mcp-Session-Id header`）。匿名の `tools/list` は 401（`results/face-c-mcp-wt-pack-tools-list-anon.json`）。`initialize` / `tools/call` 等の他メソッドの匿名呼び出しは未検証。
- default サーバー（`mcp/mcp-adapter-default-server`）の `tools/list` は Adapter の meta tool 3 つ
  （discover-abilities / get-ability-info / execute-ability）だけを返す。パックの ability は
  `discover-abilities` の結果（`face-c-mcp-default-call-discover.json`）に 6 件として現れ、
  `execute-ability`（引数は `ability_name` と `parameters`）で apply を receipt なしで呼ぶと拒否された。
- パック専用サーバー（`mcp/wt-pack`）の `tools/list` は 3 tool を直接返す（`face-c-mcp-wt-pack-tools-list.json`）。
  tool 名は `/` が `-` に置換される（`wt-site-selection-read`）。annotations は
  `readOnlyHint / destructiveHint / idempotentHint` に写像され、`outputSchema` も伝搬。
  `tools/call` で read / dry-run / receipt なし apply（isError）/ receipt あり apply（成功）を確認。
- STDIO 経路: `wp mcp-adapter serve --server=wt-pack-server --user=admin` に JSON-RPC を流すと
  同じ 3 tool が返る（`results/face-c-stdio-wt-pack.jsonl`）。

## 想定と違った点

1. 入力なしの ability に `input_schema: {type: object}` を付けると、REST GET は input が null なので
   「input is not of type object」で 400 になる。入力なしなら input_schema を書かない（pack.json を修正）。
   MCP 側は input_schema 不在時に `{type: object, properties: {}}` を補うため、この 1 点だけ CLI と MCP の
   input_schema が文字通りには一致しない（意味は同じ）。
2. REST の実行メソッドが annotations で固定される（readonly=GET、destructive+idempotent=DELETE）。
   パック定義の annotations は「説明」ではなく REST の契約になる。
3. MCP default サーバーは ability を tool として直接並べない（meta tool 経由）。「3 面が同じ集合」を
   `tools/list` レベルで満たすには、パック側でサーバーを作る（本 PoC の `mcp_adapter_init`）か
   `mcp_adapter_default_server_config` フィルタで tools を差し替える必要がある。
4. HTTP のみのローカル環境では Application Password が無効。PoC 限定でフィルタを入れた。
5. `wp ability` コマンドは存在しない。CLI 面は `wp eval` か MCP Adapter の STDIO。
6. `execute-ability` の引数名は `parameters`（`input` ではない）。

## WT-Q-AGENT-03 への示唆

- パック定義 JSON → 登録 → 3 面露出は WP 7.1 core + 公式 Adapter で成立する。ただし callback は PHP 側に必要で、
  JSON は「宣言」（名前・schema・annotations・露出フラグ・要求 capability）に留まる。
- annotations は REST メソッドと MCP hint を同時に決めるため、パック定義の必須項目として扱うべき。
- dry-run → receipt → apply の受け渡しは 3 面で同じ WP_Error が返り、エージェント側の運転手順に依存しない。

## 証跡ファイル

- `results/` 内の receipt 実値（`wt-dryrun-` で始まる HMAC 文字列）はローカル salt 由来のため `<redacted-receipt>` に伏せた。
- `scripts/`: pack.json, zz-wt-pack.php, zz-wt-poc-env.php, compare-faces.py
- `results/`: face-a-*（CLI）, face-b-*（REST）, face-c-*（MCP HTTP / STDIO / CLI list）, results.json（突合）, cleanup-state.txt

## 出典

- Abilities API（2026-09-03 参照）: https://developer.wordpress.org/apis/abilities-api/
- MCP Adapter（2026-09-03 参照）: https://github.com/WordPress/mcp-adapter
