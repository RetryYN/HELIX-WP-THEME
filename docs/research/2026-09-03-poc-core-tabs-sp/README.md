# PoC: コア Tabs ブロック + block style で SP をアコーディオン化できるか（CSS のみ）

- 対象の問い: WT-Q-VOCAB-05 の PoC 課題「コア Tabs + block style で SP はアコーディオン化が成立するか」
- 実施日: 2026-09-03
- 環境: ローカル Docker の WordPress 7.1、有効テーマは `agent-neo-themes/agent-neo-theme`、Playwright（Chromium）
- 終了時状態は `results/cleanup-state.txt`（mu-plugin 削除、テスト投稿削除、有効テーマ `agent-neo-themes/agent-neo-theme`）。`themes/` `plugins/` は変更していない。

## 結論

**部分成立**（CSS だけで「見た目のアコーディオン」は成立、「挙動のアコーディオン」は Tabs のまま）。

- WP 7.1 core に `core/tabs` / `core/tab-list` / `core/tab-panels` / `core/tab-panel` が登録済み（Gutenberg プラグイン不要。`results/block-registry.json`）。
- 390px で、タブボタンと対応パネルが縦に交互に並び（ボタン1 → ボタン2 → [開いたパネル2] → ボタン3）、
  選択中のパネルだけが表示される。1280px では通常の横タブ。`screenshots/tabs-390-tab2.png` / `tabs-1280-tab2.png`。
- JS は追加していない。開閉は core の Interactivity API がそのまま担う（`aria-selected` / `hidden` の同期）。
- ARIA は core が出す `role=tablist / tab / tabpanel`、`aria-selected`、`aria-controls`、`aria-labelledby`、`tabindex` roving を保持。
  `display: contents` を当てた後も Chromium の accessibility tree に tablist / tab / tabpanel が残った（`results/results.json` の `aria_snapshot`）。
- キーボード: 矢印キーはフォーカス移動のみ（manual activation）、Enter / Space で切替、Tab キーで開いているパネルへ進む。390px でも同じ。
- 成立しない部分:
  - 「複数パネルを同時に開く」「もう一度押して閉じる」というアコーディオン固有の挙動は Tabs のモデルに無く、CSS では作れない。
  - スクリーンリーダーには引き続き「タブ」と読まれる（アコーディオンとして読ませたいなら別ブロック）。
  - `nth-child` による order 指定は上限（本 PoC は 6 タブ）を持つ。

## 手順

1. `scripts/tabs-editor-create.mjs`: エディタ内で `wp.blocks.createBlock` から Tabs（3 タブ、各タブに段落 + 表）を組み立てて保存。
2. `scripts/tabs-editor-resave.mjs`: 再読込して `isValid` を集計し再保存（全 12 ブロック valid、`results/editor-validation.json`、`screenshots/editor-1280-reloaded.png`）。
3. block style `is-style-sp-accordion` を投稿の Tabs に付与（`scripts/tabs-post-content.html` が最終の保存 HTML）。
4. `scripts/zz-wt-tabs-sp.php`（mu-plugin、コンテナ内のみ）: `register_block_style('core/tabs', 'sp-accordion')` と、
   `wp_add_inline_style('wp-block-tabs', ...)` で `@media (max-width: 480px)` の CSS を注入。
   - `.wp-block-tabs` を flex 縦、`.wp-block-tab-list` と `.wp-block-tab-panels` を `display: contents` にしてボタンとパネルを同じ flex の子にする
   - `button:nth-child(n)` に order 2n-1、`.wp-block-tab-panel:nth-child(n)` に order 2n を与えて交互配置
   - `[aria-selected="true"]` で ▲ / ▼ を切替（装飾のみ）
   - core の `.wp-block-tab-panel[hidden]{display:none!important}` はそのまま（選択パネルのみ表示）
5. `scripts/tabs-frontend.mjs`: 390 / 1280 でスクリーンショット、クリック・矢印・Enter・Space・Tab の各操作後の
   `aria-*` と表示パネル、`getBoundingClientRect` の並び、`ariaSnapshot` を記録（`results/frontend-checks.json`、要約は `results/results.json`）。

## 再現手順

前提:

- `docker compose` で WordPress 7.1 が起動済み（`http://localhost:8086`）。
- Playwright（Chromium）が別ディレクトリに導入済み。
- 管理ユーザーは `admin`。

環境変数（`scripts/*.mjs` の実参照名）:

- `BASE_URL`: WordPress のベース URL。未設定時の既定値は `http://localhost:8086`。`WP_BASE_URL` はスクリプトが参照しないため、その名前で渡す場合はスクリプト内の定数を書き換える。
- `WP_ADMIN_USER`、`WP_ADMIN_PASS`: 管理画面ログイン用。値は記録せず環境変数で渡す。
- `S`: Playwright の `package.json` があるディレクトリ。各スクリプトは `${S}/package.json` から `playwright` を解決する。
- `NODE_PATH`: Playwright の `node_modules`。`.mjs` から直接は参照されない。実際のモジュール解決には上記 `S` が使われる。
- `OUT`: JSON とエディタ画像の出力先。`WT_OUT_DIR` はスクリプトが参照しないため、その名前で渡す場合はスクリプト内の定数を書き換える。
- `POST_ID`: `tabs-editor-resave.mjs` / `tabs-editor-validate.mjs` が開く投稿 ID。
- `URL`: `tabs-frontend.mjs` が検証する公開ページ URL。
- `SHOTS`: `tabs-frontend.mjs` がスクリーンショットを書き出すディレクトリ。

```text
export BASE_URL=http://localhost:8086
export WP_ADMIN_USER=admin
: "${WP_ADMIN_PASS:?WP_ADMIN_PASS を環境変数で指定する}"
export S=<Playwright の package.json があるディレクトリ>
export NODE_PATH=<Playwright の node_modules>
export OUT=<出力先>
export SHOTS=<スクリーンショット出力先>
export URL=<対象ページ URL>
```

mu-plugin を投入する。

```text
docker cp scripts/zz-wt-tabs-sp.php agent-neo-wp:/var/www/html/wp-content/mu-plugins/zz-wt-tabs-sp.php
```

`.mjs` は次の順で実行する。`tabs-editor-create.mjs` の出力に含まれる ID を `<ID>` に置き換える。

```text
node scripts/tabs-editor-create.mjs
POST_ID=<ID> node scripts/tabs-editor-resave.mjs
docker compose run --rm -T wpcli --user=admin post update <ID> < scripts/tabs-post-content.html
node scripts/tabs-frontend.mjs
```

初回の無効 HTML を検証する場合だけ、create の後、resave の前に次を任意で実行する。

```text
POST_ID=<ID> node scripts/tabs-editor-validate.mjs
```

撤去する。

```text
docker exec agent-neo-wp rm -f /var/www/html/wp-content/mu-plugins/zz-wt-tabs-sp.php
docker compose run --rm -T wpcli post delete <ID> --force
docker compose run --rm -T wpcli theme status
```

最後に `results/cleanup-state.txt` と `docker compose run --rm -T wpcli theme status` 等で、mu-plugin・テスト投稿・有効テーマを確認する。

## 想定と違った点

1. 手書きの `<!-- wp:tab-list /-->` は無効（editor の save 形式は `div[role=tablist] > button[role=tab]`）。canonical な保存 HTML はエディタ経由で作る必要がある。
2. エディタで挿入直後に保存すると `tab-list` の `tabs` 属性が空のまま保存され、ボタンが保存されない。再読込後の再保存で解消（`editor-validation.json` の `first_open.dirty: true` が証跡）。
3. WP-CLI の `post update` をユーザーなしで実行すると kses で `<button>` が落ちる。`--user=admin` が必要。
4. core の tabs は矢印キーで選択が切り替わらない（フォーカス移動のみ、Enter / Space で確定）。

## VOCAB-01 への示唆

- 「SP ではアコーディオン」を語彙として持つなら、(a) Tabs の block style として「見た目だけ」の変種を許す、
  (b) 別語彙（本物のアコーディオン、例えば details/summary ベース）に振り分ける、の 2 系統を区別して定義する必要がある。
  CSS のみでは (a) までで、(b) は別ブロックか JS が要る。
- (a) を採る場合、パネル数上限・`display: contents` の支援技術差・フォーカス順（ボタン列が分断される）を制約として語彙に書く。

## 証跡ファイル

- `scripts/`: tabs-editor-create.mjs, tabs-editor-resave.mjs, tabs-editor-validate.mjs（初回の無効 HTML 検証用）, tabs-frontend.mjs, zz-wt-tabs-sp.php, tabs-post-content.html
- `results/`: block-registry.json, core-block-sources.txt, editor-validation.json, frontend-checks.json, results.json, cleanup-state.txt
- `screenshots/`: editor-1280-reloaded.png, tabs-390-initial.png, tabs-390-tab2.png, tabs-390-tab3-keyboard.png, tabs-1280-*.png, page-390.png, page-1280.png

## 出典

- Tabs ブロック — WordPress 7.1 field guide のタグ一覧（2026-09-03 参照）: https://make.wordpress.org/core/tag/7-1/
- Core block reference（2026-09-03 参照）: https://developer.wordpress.org/block-editor/reference-guides/core-blocks/
- Interactivity API（2026-09-03 参照）: https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/
- Block validation（2026-09-03 参照）: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#validation
- `register_block_style`（2026-09-03 参照）: https://developer.wordpress.org/reference/functions/register_block_style/
