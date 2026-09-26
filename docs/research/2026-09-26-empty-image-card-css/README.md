# 画像なしカードの共有 CSS

Issue #345 / PR #344 follow-up。現行main `09dc07c` を基点とする。`category.html` の Custom HTML style を既存 `theme.css` のモバイルカード定義へ移す。条件は `max-width:599px`、`body.wt-cat-list-grid`、主一覧内の `.wt-cat-card`、直接の featured-image 子要素なしに限定する。

`archive.html` は日付をカード直下へ出すため、category の meta wrapper と異なる。未定義の `date` grid area が暗黙列を生成することを現行mainの実機で確認した。同じ画像なし条件の直下日付だけを `meta` area へ配置する。画像ありカード、他の一覧型、600px以上の定義は変更しない。

WordPress の [Template Hierarchy](https://developer.wordpress.org/themes/templates/template-hierarchy/) では保存済みテンプレートがテーマ同梱テンプレートより優先される。共有 stylesheet に置くことで、テンプレートへ Custom HTML style を持たせず、category と archive の同じカード構造へ適用する。

## 実機確認

専用DB・volumeを持つ隔離WordPress 7.1.2へ現checkoutをread-only mountし、架空のarticle-purpose seedと画像あり・なしfixtureを投入した。category / tag / date / author の4経路を390 / 599 / 600 / 768 / 1440pxでbefore/after比較した。tag / date / author は `archive.html` の実描画。

`verification.json` の21検査が成功した。実行環境の WordPress 版数は `wordpressVersion` に記録する。全20条件で横overflowはない。390pxの画像なしタイトル幅はtag / date / authorで104–135pxから332pxへ改善。categoryの既存332pxは維持。画像ありの104px画像列も維持した。600px以上の全カード、categoryの全幅、モバイル画像ありカードの合計55カードで、カード・タイトル・日付の矩形がbefore/after一致した。PC/SPの画面を目視確認した。

画像・生の矩形比較はignored `local-evidence/issue345/` に保管し公開証拠には含めない。`verify.mjs` は `HELIX_ARTICLE_BASE_URL` で隔離インスタンスを指定し、配信中CSSのSHA-256とcheckoutのCSSの一致も検査する。再実行しても投稿や設定は変更しない。

再現時は `2026-09-26-news-column-purpose-gap/compose.yaml` が要求する `HELIX_ARTICLE_DB_PASSWORD`、`HELIX_ARTICLE_ROOT_PASSWORD`、`HELIX_ARTICLE_PORT` をローカル専用の環境ファイルに設定する。共有ラボとの衝突を避けるため一意なCompose project名とloopback専用portを指定し、専用DB/volumeで起動する。以下はport `18145`、project名 `helix-wp-theme-issue345-20260927` の例。`<local-env-file>` はローカルにだけ置き、公開しない。

```sh
docker compose -p helix-wp-theme-issue345-20260927 --env-file <local-env-file> -f docs/research/2026-09-26-news-column-purpose-gap/compose.yaml up -d
docker exec -i helix-wp-theme-issue345-20260927-wordpress-1 php < docs/research/2026-09-26-news-column-purpose-gap/seed.php
docker exec -i helix-wp-theme-issue345-20260927-wordpress-1 php < docs/research/2026-09-26-empty-image-card-css/fixture.php
HELIX_ARTICLE_BASE_URL=http://127.0.0.1:18145 node docs/research/2026-09-26-empty-image-card-css/verify.mjs
php -l docs/research/2026-09-26-empty-image-card-css/fixture.php
```

## CSS impact と検証境界

変更は共通 `theme.css` と `templates/category.html`。現mainのacceptance registryから両ソース依存と共有proofの閉包を計算すると36 AC / 34 proofとなる。静的な `2026-09-19-toc-settings/revalidation-impact.json` の30 AC / 31 proofだけでは現行範囲を尽くさない。正規oracleは `package.json` の `catalogOracles` と `config/catalog-admission-oracles.json` に宣言されている。

共通 `theme.css` と `category.html` の影響閉包にある36 AC / 34 proofは、このPRで再生成・正式rebindし、受入監査を実行した。99 source-digest changesを含む記録は `docs/research/2026-09-08-selection-catalog/acceptance-rebind-log.json` にある。このカード実測だけで共通CSS全体の視覚品質を証明したとは扱わない。画像ありarchiveカードには既存の日付レイアウトが残るため、画像ありカード全体の視覚品質改善を証明するものではない。
