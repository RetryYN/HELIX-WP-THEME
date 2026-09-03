# デザイン試作 01（2026-09-03）— ローカル WP 7.1 での描画証跡

## 目的

WT-FR-LOOK-03（サイトパターン別の見た目）と記事内語彙の見え方を PO に見せるため、
ローカル Docker の WordPress 7.1 上でテーマ本体（`themes/agent-neo-theme`）を実際に描画し、
既存 style variation 9 本 + 試作 variation 3 本 × 3 ページ × 2 幅のスクリーンショットを生成した。
これは PoC 証跡であり、要求・設計の確定ではない。

## 厳守した境界

- `themes/` 配下（テーマ本体）は編集していない。試作物はすべて子テーマ `wt-proto` として
  **コンテナ内のみ**に置いた（`/var/www/html/wp-content/themes/wt-proto`、`docker cp` で投入）。
  本ディレクトリの `wt-proto/` はその複製（再現用）で、テーマとしては読み込まれない。
- 終了時にテーマは `agent-neo-themes/agent-neo-theme` を再有効化したが、投稿 475 / 476、`wp_global_styles` 投稿 479、コンテナ内子テーマ `wt-proto` の 4 点は意図的に残置している（未復元項目。理由・撤去方法は「終了時状態と未復元項目」を参照）。
- git commit / push はしていない。DB は消していない。既存の `ge1-*` 下書きは触っていない。
- 画像は生成していない（サムネイル等は CSS グラデーションのプレースホルダ）。
- サンプル本文は架空（商品 A / B / C、価格はサンプル値）。第三者製品名・実サイト名は含めない。
- スクリーンショット本体（PNG 85 枚、約 50MB）はリポジトリに入れず、作業用ディレクトリに置いた
  （`index.json` が一覧・主要色・検証結果を持つ。本 README 末尾に要約を転記）。

## 作ったもの（ローカル WP、http://localhost:8086）

| 種別 | ID | slug / URL | テンプレート | 備考 |
|---|---|---|---|---|
| 記事（post、公開） | 475 | `/design-proto-article/` | single | H2×4・H3×4、記事内語彙一式 |
| LP（page、公開） | 476 | `/design-proto-lp/` | `page-lp-sample` | 本文は空。テンプレートが lp-* パターン 12 本を並べる |
| トップ | — | `/` | `front-page` | show_on_front=posts のまま front-page.html が適用される。home-* パターン 7 本 |
| 子テーマ | — | `wt-proto`（コンテナ内） | Template: agent-neo-themes/agent-neo-theme | styles/ に試作 variation 3 本、style.css に試作 block style |
| user global styles | 479 | `wp_global_styles`（wt-proto 用） | — | variation 切替の書き込み先。477 は term 未付与で生成された空投稿（削除済み） |

記事内語彙（記事 475 の本文）: PR 表記（先頭）、目次（手動 list、`is-style-wt-toc`）、
囲み 3 種（`core/group` block style `wt-box-info / wt-box-warn / wt-box-point`）、
リンクカード（`wt-link-card`）、比較表（`core/table`、列 = 商品 A/B/C）、商品カード（`core/columns` + CSS プレースホルダ + button）、
ボタン 3 種（塗り / `is-style-outline` / 試作 `is-style-wt-ghost`）、手順（番号リスト）、レビュー（`wt-review`、星はテキスト）、
FAQ（`core/details` ×3）、CTA 束（`wt-cta-bundle` = buttons + 補足文）。

LP の順序: hero → problem → agitation → solution → feature → benefit → use-case → proof → comparison → pricing → faq → final-cta
（テンプレート `page-lp-sample` 固定。指示の「hero → 課題 → 解決 → 特徴 → 比較 → 料金 → FAQ → 最終 CTA」を含む上位集合）。

## 試作 variation 3 本（LOOK-02 の写像規則: 8 色スラッグの値差し替え + fontFamilies 差し替え）

| slug | 想定サイトパターン | 色の方針 | フォント（自己ホストなし、fallback スタックのみ） | 追加 CSS（variation 内 `styles.css`） |
|---|---|---|---|---|
| `compare` | 比較・ランキング | 信頼感の青（primary #0b3d91）+ 強調オレンジ（accent #f28c00 / accent-aa #b85f00） | body / heading とも `system-ui, -apple-system, 'Segoe UI', sans-serif` | H2 左ボーダー |
| `corporate` | コーポレート | ネイビー #1f2a44 / グレー、accent は落ち着いた金茶 #8a6d3b | heading `'Hiragino Mincho ProN', 'Yu Mincho', 'Noto Serif JP', serif`（明朝系 fallback）、body system-ui | 本文段落 `text-indent:1em`（字下げ）、H2 下線、ボタン角丸 0 |
| `service-lp` | サービス LP | 高彩度単色（primary #0d9488）+ 白、accent #ff4d6d | system-ui 系 | ボタン pill 形・太字 800・下影 |

### 12 variation の 8 色（`set-variation.php` が書き込んだ値 = index.json の colors）

| variation | primary | secondary | accent | accent-aa | background | foreground | footer-bg | muted |
|---|---|---|---|---|---|---|---|---|
| light | #1a1a1a | #f0f0f0 | #ff6b00 | #bf5200 | #ffffff | #1a1a1a | #111111 | #767676 |
| dark | #f5f5f5 | #262626 | #ff6b00 | #ff7a1a | #121212 | #ededed | #000000 | #9a9a9a |
| business | #1b2a41 | #f3f5f7 | #0b7285 | #095c6b | #ffffff | #1f2933 | #1b2a41 | #616e7c |
| depth | #0f172a | #ffffff | #f26b1d | #c4530f | #eef1f5 | #1e293b | #0b1220 | #64748b |
| editorial | #14161a | #eceff3 | #1f4fd8 | #1a3fae | #f7f7f5 | #1c1e22 | #14161a | #5f636b |
| mono | #111111 | #e7e7e7 | #333333 | #111111 | #ffffff | #111111 | #000000 | #666666 |
| night-contrast | #f6f8ff | #17213d | #b8ef4a | #83b82a | #0b1020 | #eef3ff | #050813 | #a9b6d6 |
| vivid | #2d1154 | #fff0f8 | #ff4d91 | #bd1d61 | #fffaff | #251630 | #21103c | #735676 |
| warm | #4a1f1b | #fff2e6 | #d9784a | #9f4e2d | #fffaf5 | #2f1e1a | #30151a | #73584e |
| compare | #0b3d91 | #eef3fb | #f28c00 | #b85f00 | #ffffff | #1c2833 | #082b66 | #5b6b7c |
| corporate | #1f2a44 | #f2f3f5 | #8a6d3b | #6e5327 | #fbfbfc | #2b2f36 | #1a2238 | #6b7280 |
| service-lp | #0d9488 | #ecfdfa | #ff4d6d | #c8173a | #ffffff | #0f172a | #0f766e | #64748b |

## 再現手順

前提: `docker compose` で WP 7.1 が起動済み（テーマリポ root で実行）、Playwright（chromium）が別ディレクトリに導入済み。

1. 子テーマを投入して有効化
   ```
   docker cp <本ディレクトリ>/wt-proto agent-neo-wp:/var/www/html/wp-content/themes/wt-proto
   docker cp <本ディレクトリ>/scripts/set-variation.php agent-neo-wp:/var/www/html/wp-content/themes/wt-proto/set-variation.php
   docker exec agent-neo-wp chown -R www-data:www-data /var/www/html/wp-content/themes/wt-proto
   docker compose run --rm -T wpcli theme activate wt-proto
   ```
2. 記事と LP を作る
   ```
   docker compose run --rm -T wpcli post create - --post_type=post --post_status=publish \
     --post_title="【比較】在宅ワーク用チェア 商品 A・B・C を徹底比較（デザイン試作）" --post_name=design-proto-article < content/article.html
   docker compose run --rm -T wpcli post create --post_type=page --post_status=publish \
     --post_title="サービス LP（デザイン試作）" --post_name=design-proto-lp --page_template=page-lp-sample
   ```
3. スクリーンショット生成（variation 切替は `set-variation.php` を `wp --user=admin eval-file` で呼び、
   user global styles 投稿へ variation JSON を書き込む。切替後にページの `--wp--preset--color--accent / --primary` の
   計算済み値と JSON の値をスクリプト内で照合し、不一致は `index.json` の `problems` に記録する）
   ```
   WT_THEME_DIR=<テーマリポ root> WT_OUT_DIR=<出力先> NODE_PATH=<playwright の node_modules> node scripts/shoot.js
   ```
## 終了時状態と未復元項目

終了時状態（2026-09-03）:

- 有効テーマ: `agent-neo-themes/agent-neo-theme`
- option `wt_poc_site_selection`: 存在しない
- `mu-plugins`: 空

意図的残置（未復元）4 点:

- 投稿 475（post、slug `design-proto-article`、公開）
- 投稿 476（page、slug `design-proto-lp`、公開）
- `wp_global_styles` 投稿 479（`wt-proto` 用）
- コンテナ内子テーマ `wt-proto`
- 理由: デザイン反復のため（PO 提示用の試作ページ）。

削除済み:

- 空の global styles 投稿 477（term 未付与で生成されたもの）。

PO が撤去を判断したとき用の撤去コマンド列:

```text
docker compose run --rm -T wpcli post delete 475 476 479 --force
docker exec agent-neo-wp rm -rf /var/www/html/wp-content/themes/wt-proto
docker compose run --rm -T wpcli theme activate agent-neo-themes/agent-neo-theme
```

終了時状態と未復元項目の記録は `results/cleanup-state.txt` を参照。

## 出力

- フルページ: `<page>-<variation>-<sp|pc>.png`（page = article / lp / home、幅 390px / 1280px）= 3 × 12 × 2 = 72 枚
- 語彙別（記事、SP 幅 390px、DPR 2、variation = compare）: `vocab-<name>-sp.png`
  name = box, box-warn, box-point, buttons, link-card, steps, table, faq, review, product-card, cta, toc, pr-notice = 13 枚
- `index.json`: 全 85 枚の page / variation / width / path / 8 色 / 計算済み（body 背景・body フォント・見出しフォント・見出し色）/ verified
- 検証結果: 72 枚すべて palette 一致（`problems` は 0 件）、pageerror なし
- フルページ PNG 85 枚（約 50MB）と `index.json` が指す `path` の実体はリポジトリ外（作業用ディレクトリ）にある。`index.json` の `path` はプレースホルダ扱いであり、ホストの絶対パスは記録していない。
- `results/` に収載した画像（webp 3 枚）:
  - `results/vocab-faq-sp.webp`: FAQ 無装飾
  - `results/article-dark-sp.webp`: Cookie バナーが dark で浮く
  - `results/article-compare-sp.webp`: compare variation の記事全体・フォント fallback

## うまくいかなかったこと・注意点（正直に）

- **variation 切替の落とし穴**: wp-cli を `--user` なしで動かすと `tax_input` が捨てられ、生成された
  `wp_global_styles` 投稿に `wp_theme` term が付かず、書き込んでもフロントに反映されない。
  `set-variation.php` 内で `wp_set_object_terms` を補い、`--user=admin` で実行して解決。
  この過程で生成された term 未付与の投稿 ID 477 は削除済み。
- **Cookie 同意バナー**: テーマ同梱の固定バナーが SP では画面の大半を覆う。スクリーンショット前に
  「拒否する」をクリックして閉じている（スクリプトの `dismissConsent`）。バナーは variation の色を受けず白固定で、
  dark / night-contrast では浮く（`results/article-dark-sp.webp` 参照。Cookie バナーが dark で浮く所見の根拠）。
- **記事全体（compare variation）**: SP の記事全体とフォント fallback は `results/article-compare-sp.webp` を参照。
- **目次**（作業時観察・画像未収載）: テーマに自動目次の仕組みはない。記事では手動の番号リスト + anchor で代用した。
- **FAQ（core/details）**: テーマ側にスタイルがなく、境界線・背景のない素の `<details>` として描画される
  （`results/vocab-faq-sp.webp` 参照。FAQ 無装飾の所見の根拠）。囲みとの一体感がない。
- **block validation**: フロント描画には影響しないが、`core/html` でプレースホルダ `<div>` を置いたため、
  エディタで開くと HTML ブロックとして扱われる（validation エラーではない）。エディタでの検証は未実施。
- **トップの「最新の記事」**（作業時観察・画像未収載）: DB に残っている他 PoC の投稿（poc-core-* 系）がそのまま並ぶ。今回の記事も混ざる。
- **記事のカテゴリ**（作業時観察・画像未収載）: 「Uncategorized」表示のまま。
- **見出し明朝（corporate）**（作業時観察・画像未収載）: ローカル chromium に日本語明朝フォントがない場合は環境の serif に fallback する。
  スクリーンショットは生成環境のフォントに依存する。
- 試作 variation の `styles.css` は variation JSON 内のカスタム CSS で、LOOK-02 の「8 色 + fontFamilies の差し替えのみ」から
  はみ出す。色・フォントだけで足りない差（字下げ・ボタン形状）を示すために意図的に入れたもので、採否は PO 判断。

## 出典

- テーマ本体: `themes/agent-neo-theme`（theme.json v3、styles/ 9 本、patterns/ lp-* / home-*、templates/）
- 要求参照: WT-FR-LOOK-02 / WT-FR-LOOK-03（サイトパターン向け variation）
- WP: 7.1（docker）、wp-cli、Playwright chromium
- theme.json（style variations / styles ディレクトリ）（2026-09-03 参照）: https://developer.wordpress.org/themes/global-settings-and-styles/style-variations/
- theme.json 全般（2026-09-03 参照）: https://developer.wordpress.org/themes/global-settings-and-styles/
- global styles（ユーザー global styles / `wp_global_styles`）（2026-09-03 参照）: https://developer.wordpress.org/block-editor/how-to-guides/themes/global-settings-and-styles/
- `register_block_style`（2026-09-03 参照）: https://developer.wordpress.org/reference/functions/register_block_style/
- 子テーマ（2026-09-03 参照）: https://developer.wordpress.org/themes/advanced-topics/child-themes/
