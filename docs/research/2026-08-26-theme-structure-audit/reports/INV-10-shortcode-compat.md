# THEME-INV-10 レポート — ショートコード後方互換の扱い

- 対象イシュー: `issues/THEME-INV-10-shortcode-compat.md`
- 状態: **②③④ 判定完了 / ①（`[themeA_fukidashi]` 186 回の由来）は仮説まで。検証手順を定義**
- 調査日: 2026-08-26
- 手段: ホスティング SSH 読み取り専用
- 一次証跡: `evidence/theme-features-raw.txt`（両テーマの `add_shortcode` 抽出）・
  `evidence/usage-raw.txt`（本文中のショートコード実使用）・`evidence/probe3-raw.txt`（ブロック登録）

## 1. 登録と実使用の対照

### 1.1 テーマA — 6 種登録・実使用は 4 種

登録（`include/shortcode.php`・368 行）:
```
themeA_button / themeA_fukidashi / themeA_heading_iconbox / themeA_profile / themeA_simple_iconbox / message
```

本文中の実使用（`evidence/usage-raw.txt`、公開記事 59 + 固定 10）:

| ショートコード | 回数 | 備考 |
|---|---|---|
| `[themeA_fukidashi` | **186** | 同名ブロック `themeA-blocks/fukidashi` の使用回数と**完全一致** |
| `[smartslider` | 1 | プラグイン `smart-slider-3` 由来 |
| `[themeA_profile` | 1 | |
| `[themeA_heading_iconbox` | 1 | |
| `[contact` | 1 | プラグイン `contact-form-7` 由来 |

**未使用**: `themeA_button` / `themeA_simple_iconbox` / `message`

### 1.2 テーマB — 20 種登録・実使用 0

```
ad / ad_tag / blog_parts / custom_banner / full_wide_content / html / icon / only_login /
only_logout / pcbr / spbr / post_link / post_list / pr_notation / review_stars /
speech_balloon / themeB_toc
+ 日本語別名: ふきだし / アイコン / カスタムバナー / ブログパーツ
```

本文中の実使用: **0**（site-B は公開記事 7 本のみ。母数が小さい点に注意）。

**日本語別名の存在**が示す設計意図: 非技術者が本文へ直接書くことを想定している。
テーマB は同じ機能をブロック・ショートコードの両方で提供し、
`Pre_Parse_Blocks::check_content_str()` が**ショートコード記法も文字列検査で拾う**
（`[ふきだし` / `[speech_balloon` → `themeB/balloon`）ことからも、
両方が実運用で使われる前提の実装になっている（`reports/INV-15-themeB-pipeline-transfer.md` §1.3）。

## 2. ① `[themeA_fukidashi]` 186 回の由来 — 仮説

**観測**: `themeA-blocks/fukidashi`（ブロック）186 回 と `[themeA_fukidashi`（ショートコード）186 回が**完全一致**。

**確定している事実**:
- `themeA-blocks/fukidashi` は**静的ブロック**（`render_callback` を持たない。`evidence/probe3-raw.txt`）。
  つまり保存時に save 出力が `post_content` へ焼き込まれる。
- `themeA_fukidashi` ショートコードは `include/shortcode.php` に登録されている。

**仮説**: **ブロックの save 出力がショートコード文字列を含んでいる。**
静的ブロックのマークアップとして `[themeA_fukidashi ...]` を出力し、
表示時に `do_shortcode` で展開する二段構えになっている可能性が高い。
数が完全一致していることがこの仮説を支持する。

**対立仮説**: 編集者が「ブロックを挿入し、その中にショートコードを手書きした」。
186 回すべてでそれが起きる確率は低く、可能性は小さい。

**確度高（未了は本番 `wp db query` 1 本のみ / §6）**。傍証: `usage-raw.txt` の使用集計と
ブロック数の完全一致、`re-themeB-blocks.txt` L151-152 が `parse_blocks(do_shortcode($content))`
の二段構え（ブロック save 出力にショートコードを含み表示時に展開）を実装している事実は
仮説（ブロック由来）を強く支持する。本文 SELECT による最終確認だけが残るが、
中間 JSON 方針（テーマ語彙は展開して意図ノード化）はどちらの仮説でも不変のため、
**INV-10 の要求出力は本照会の結果に依存しない**。

## 3. ② 中間 JSON における表現方式 — 判定

| ケース | 中間 JSON での扱い | 理由 |
|---|---|---|
| **ブロック由来のショートコード**（仮説どおりの場合） | **意図ノードへ正規化**（`{ "type": "balloon", … }`）。ショートコード文字列は残さない | 元々ブロックの内部実装であって編集意図ではない |
| **手書きショートコード（テーマ由来）** | **意図ノードへ変換**。属性はショートコードの引数から取る | 編集者の意図は「ふきだしを置く」であってショートコード記法ではない |
| **プラグイン由来**（`[smartslider]` `[contact]`） | **不透明ノードとして原文保持**（`{ "type": "opaque_shortcode", "raw": "[contact-form-7 id=...]" }`） | テーマの語彙で意味を解釈できない。展開すると再編集不能になる |
| **未使用のショートコード** | 対応不要 | テーマA 3 種・テーマB 20 種は実データ 0 |

**原則**: **テーマ語彙のショートコードは展開して意図ノードへ、プラグイン語彙は原文のまま持つ。**
「展開」を選ぶと再編集できなくなり、「原文保持」を選ぶと意味が扱えない。
テーマ語彙は意味が分かるので展開でき、プラグイン語彙は分からないので保持するしかない。

## 4. ③ プラグイン由来ショートコードの扱い — 判定

実使用は 2 種のみ（`[smartslider]` 1 / `[contact]` 1）。いずれも**不透明ノードとして原文保持**。

追加の規約:
- レンダラは不透明ノードを**そのまま出力**し、`do_shortcode` の判断は表示レイヤに委ねる
- 不透明ノードは**エビデンスゲートの対象外**（内容を検証できないため）
- 移行時に「不透明ノードが何個あるか」を記録し、手動確認の対象リストにする

## 5. ④ 日本語別名の設計意図を要求として拾うか — 判定

**拾わない（Graphix NEO の要求にしない）。**

理由:
- 日本語別名は「本文へ直接書く」運用を前提にした後方互換機構。
  中間 JSON では**そもそも本文へ記法を書かない**（エディタ or 生成器が意図ノードを作る）ため、
  別名の必要がない。
- ただし**設計意図としては重要**: 「非技術者が扱える語彙にする」という要求は、
  中間 JSON の**意図語彙の命名**（INV-01）に反映すべき。
  `themeB/cap-block` のような実装由来の名前ではなく、`balloon` / `caption_box` のように
  意味が分かる名前を採る根拠になる。

## 6. 検証手順（①の確定用・シェル復帰後に実行）

```
# ブロック由来かどうかの判定：ブロックコメントとショートコードの位置関係を見る
wp db query "SELECT post_content FROM wp_posts
             WHERE post_status='publish' AND post_type IN ('post','page')" --skip-column-names \
  | grep -o '<!-- wp:themeA-blocks/fukidashi.\{0,400\}' | head -3
```

- 出力に `[themeA_fukidashi` がブロックコメント直後に現れれば **ブロック由来（仮説どおり）**
- 現れなければ手書き。その場合はショートコード単独の出現箇所を別途数える

あわせて `include/shortcode.php` の `themeA_fukidashi` 実装（368 行のうち該当部）を読み、
出力マークアップを確定させる（INV-01 の対応表に必要）。

## 7. 未了項目

- [ ] ①の検証（上記コマンド）
- [ ] `include/shortcode.php` の 6 実装の出力マークアップ採取
- [ ] `[themeA_profile` / `[themeA_heading_iconbox`（各 1 回）の実記事特定 — 少数なので個別対応でよい
- [ ] テーマB 側の母数不足の補正 — site-B の記事が増えたら再計測

## 8. 証跡ファイル

| 内容 | 場所 |
|---|---|
| 両テーマの `add_shortcode` 抽出 | `evidence/theme-features-raw.txt` |
| 本文中のショートコード実使用回数 | `evidence/usage-raw.txt` |
| `fukidashi` が静的ブロックであることの確認 | `evidence/probe3-raw.txt` |
| テーマB の文字列検査によるショートコード補完 | `evidence/re-themeB-blocks.txt` |
