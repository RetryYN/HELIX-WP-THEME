# THEME-INV-09 レポート — サイト設定の正本と移管方式

- 対象イシュー: `issues/THEME-INV-09-settings-authority.md`
- 状態: **分類軸・移管手順・判別可否の結論まで確定 /
  ①②（1,225 + 540 キーの全量分類）は全量取得が要る**
- 調査日: 2026-08-26
- 手段: ホスティング SSH 読み取り専用
- 一次証跡: `evidence/re-themeA-accessors.txt`（アクセサのファイル別本数・`set_theme_mod` 出現）・
  `evidence/theme-features-raw.txt`（`get_option` キーの出現頻度）・
  `evidence/re-themeB-detail.txt`（`Theme_Data` の DB 定義）・`evidence/re-themeA-boot.txt`

## 1. 設定の保持構造（確定）

| | テーマA | テーマB | AGENT NEO |
|---|---|---|---|
| 格納 | `themeA_*` **個別 option 1,225 種** + theme_mod（`themeA__*`） | 配列 **4 グループ**（`themeB_customizer` / `themeB_options` / `themeB_editors` / `themeB_others`）+ 独自テーブル `themeB_balloon` | `theme.json` + `config/*.json` 7 本 |
| 既定値の所在 | **707 個のアクセサ関数に散在**（17 ファイル） | `classes/Data/Default_Settings.php` **1 ファイル・540 キー** | JSON に宣言 |
| 読み出し | アクセサ関数 `themeA__*()` | 起動時に確定した静的プロパティ | `Config_Loader`（**fail-fast schema 検証つき**） |
| post meta | `_themeA_*` **27 種** | `lib/post_meta/` 5 ファイル | — |
| term meta | — | `themeB_term_meta_display_parts` ほか | — |
| **目録の作成可否** | **不可** | **可**（既定値ファイルが目録） | **可**（JSON） |

### 1.1 アクセサ 707 個の分布（`evidence/re-themeA-accessors.txt`）

| ファイル | アクセサ数 | 内容 |
|---|---|---|
| `button-design-setting.php` | 181 | ボタンの見た目 |
| `fukidashi-setting.php` | 121 | 吹き出しの見た目 |
| `main-visual-setting.php` | 80 | メインビジュアル |
| `spmenu-setting.php` | 75 | SP メニュー |
| `site-design-setting.php` | 53 | サイト全体のデザイン |
| `box-design-setting.php` | 49 | ボックスの見た目 |
| `color-setting.php` | 47 | 色 |
| `sns-setting.php` | 29 | SNS |
| `information-setting.php` | 20 | お知らせバー |
| `representation-act-setting.php` | 10 | **PR 表記（ステマ規制対応）** |
| `thumbnails-setting.php` | 9 | サムネイル |
| `profile-setting.php` / `others-setting.php` | 各 8 | プロフィール / その他 |
| `animation-setting.php` | 5 | アニメーション |
| `headline-design-setting.php` | 4 | 見出し |
| `site-setting.php` / `themeA-setting.php` | 3 / 3 | サイト基本 |
| `design-preset-setting.php` / `custom-functions.php` | 各 1 | プリセット |

**構成の性格が読み取れる**: 707 のうち **約 530（75%）が「見た目」**
（ボタン 181・吹き出し 121・メインビジュアル 80・SP メニュー 75・サイトデザイン 53・
ボックス 49・見出し 4）。**サイトの意味に属する設定はごく少数**。

## 2. 分類軸（確定）

各キーを 4 分類する。

| 分類 | 定義 | 移管 | 例 |
|---|---|---|---|
| **① サイト固有の意味** | そのサイトが何であるかを規定する。人が決めた事実 | **必須** | サイト名・プロフィール・SNS URL・お問い合わせ URL・PR 表記文言・カテゴリ割り当て・トラッキング ID |
| **② 見た目の選択** | デザイン上の選択。移管先のデザイン体系で作り直す | **不要**（参考） | 色・余白・ボタン形状・吹き出し意匠・見出し装飾 |
| **③ テーマ内部状態** | テーマが自分の都合で持つ値 | **破棄** | バージョン・アクティベート済みフラグ・キャッシュ・デモインポート履歴 |
| **④ 副作用で入った既定値** | 描画パスの `set_theme_mod()` が書いたもの（INV-16） | **破棄**（ただし判別困難） | `themeA__theme_color` `themeA__header_bg_color` `themeA__header_menu_color` `themeA__text_color` `themeA__bg_color` |

**①と②の境界が本レポートの要点**。「テーマカラー」は一見①に見えるが、
移管先で意匠を作り直すなら②。逆に「PR 表記の文言」は見た目に見えて、
**法令対応の文言**なので①（そのまま持ち込む必要がある）。

## 3. 移管必須の最小集合（現時点の候補）

`evidence/theme-features-raw.txt` の `get_option` 頻度と、アクセサのファイル名から特定。

| 領域 | キー / 所在 | 分類 | 備考 |
|---|---|---|---|
| PR 表記 | `representation-act-setting.php`（10 アクセサ）・`themeA__representations_pr_text_*` / `themeA__representations_none_display_ids` | **①** | ステマ規制対応。文言と除外カテゴリ ID |
| プロフィール | `profile-setting.php`（8）・`themeA__profile_name` / `_job` / `_introduction` / `_image_url` / `_button_link` / `_label_text` | **①** | `THEMEA_VAR` 経由でブロックにも渡る |
| SNS | `sns-setting.php`（29）・`themeA__tw_url` / `_fb_url` / `_youtube_url` / `_insta_url` / `_line_url` | **①** | |
| 問い合わせ | `themeA__contact_url` | **①** | |
| CV ボタン | `themeA__spcv_all_color` / `_category1〜3_color` ほか | **①/②** | **色は②だが「カテゴリ別に CV を出し分ける」構造は①** |
| 広告 | `themeA_h2_ads_code` / `_sponsor_text` / `themeA_choise_category_1〜4` / `themeA_h2_sp_display` | **①** | INV-03 のゾーン定義へ写す。**コードは credential 相当**（値をリポジトリへ残さない） |
| SEO | `themeA_top_next_noindex` / `themeA_tag_noindex` / `themeA_tag_next_noindex` / `themeA_image_page_noindex` / `themeA_separation_title` | **①** | インデックス制御は意味に属する |
| カテゴリ | `themeA_choise_category_*`（9 出現） | **①** | 広告のカテゴリ割り当て |
| 有料記事 | `themeA_paidpost_secret_key` / `_subscription_check` | **①（credential）** | **値を取得しない**。INV-11 でスコープ外を推奨 |
| 記事表示 | post meta `_themeA_*` 27 種 | **①** | 記事単位の制御。REST 経路で移管できる（INV-08 §3） |
| デザイン一式 | `button-design` / `fukidashi` / `box-design` / `color` / `headline` ほか約 530 | **②** | 移管しない。**意匠は Graphix NEO で作り直す** |
| プリセット/内部 | `preset_data`（5 出現）・`themeA__design_style` ほか | **③** | 破棄 |

**移管必須はおよそ 60〜80 キー**と見積もる（全 1,225 の 5〜7%）。
残りの大半は②（見た目）と③（内部状態）。

## 4. 判別可否の結論（イシューの受入条件）

### 4.1 「人が決めた設定」と「副作用で入った既定値」は**値だけでは判別できない**

`reports/INV-16-themeA-render-side-effects.md` で確定したとおり、
`wp_head` の CSS 生成関数が 5 キーに既定値を書き込む。
既定値（`#407FED` / `#22327a` / `#555555` / `#f7faff`）と一致していても、
人が同じ色を選んだ可能性を排除できない。

**ただし実害は限定的**: 該当 5 キーはすべて**②（見た目）**に分類されるため、
移管必須の最小集合には入らない。**①のキーには `set_theme_mod()` の副作用が及ばない**
（副作用の対象は色 5 つのみ）。

→ **結論: 判別は不可能だが、移管作業には影響しない。**
   ただし「移管前のスナップショット」は別の理由（作業中の変化の検知）で必要。

### 4.2 目録の作成可否

| | 目録を作れるか | 方法 |
|---|---|---|
| テーマB | **可** | `Default_Settings.php`（540 キー）が既定値の目録。実値との差分＝人が変えた設定 |
| テーマA | **不可（機械的には）** | 既定値がアクセサ関数の中に散在。**707 関数を 1 つずつ読む**以外に目録化の道が無い |
| AGENT NEO | **可** | JSON 宣言 + schema |

**テーマA の現実的な代替手段**:
1. `wp option list --search='themeA_*'` で**実際に DB に存在するキー**を列挙する（1,225 は
   コード上の参照数であり、実サイトに存在する数とは別）。
2. 実在キーだけを対象に、キー名から §2 の 4 分類へ振り分ける（機械分類 + 目視確認）。
3. 分類が付かないキーだけアクセサ関数を読む。

**この順序なら全 707 関数を読まずに済む。** 実在キーは大幅に少ないと見込まれる
（未設定のキーは DB に無い）。

## 5. 移管手順（定義）

```
[0] 事前スナップショット                      ← INV-16 の要求
    wp option get theme_mods_themeA --format=json      > snapshot/theme_mods.json
    wp option list --search='themeA_*' --format=json   > snapshot/options.json
    wp post meta list <id> …（記事単位）              > snapshot/post_meta.json
    ※ credential 系（*_secret_key / *_ads_code）は**値を保存せず有無だけ記録**

[1] 実在キーの列挙と分類
    → ①サイト固有 / ②見た目 / ③内部状態 / ④副作用既定値 の 4 分類

[2] ①のみを抽出し、Graphix NEO の設定スキーマ（config/*.json）へ写像
    → 写像先が無いものは「新設が要る設定」としてリスト化

[3] post meta（_themeA_* 27 種）は記事移行と同時に REST 経由で移す
    ※ register_meta の有無を先に確認（INV-08 §6 の未了項目）

[4] 移管後の差分検証
    → [0] のスナップショットと突き合わせ、①が漏れなく写っているかを機械検証
```

**credential の扱い**（統合層 CLAUDE.md 規律 2）:
`themeA_paidpost_secret_key` / `themeA_h2_ads_code`（アフィリエイト ID を含みうる）は
**値をリポジトリ・ログに残さない**。スナップショットは有無と長さのみ。
実値の移送が要る場合は PO が直接行う。

## 6. 未了項目

- [ ] **① 実在する `themeA_*` キーの列挙**（`wp option list --search='themeA_*'`）と 4 分類
- [ ] **② `themeB_options` の実値と `Default_Settings.php` の差分抽出**
- [ ] `theme_mods_themeA` の実値取得（INV-16 と共通）
- [ ] `_themeA_*` post meta が `register_meta` されているか（REST 露出の有無）
- [ ] `preset_data`（5 出現）の中身 — デザインプリセットの実体
- [ ] RTOC プラグインの `rtoc_*` 設定の棚卸し（目次の配置情報。INV-07 §3.4）

## 7. 証跡ファイル

| 内容 | 場所 |
|---|---|
| アクセサのファイル別本数（707 の内訳） | `evidence/re-themeA-accessors.txt` |
| `get_option` キーの出現頻度（テーマA / テーマB） | `evidence/theme-features-raw.txt` |
| `Theme_Data` の DB 定義（4 グループ + 独自テーブル） | `evidence/re-themeB-detail.txt` |
| `set_theme_mod` の 5 箇所 | `evidence/re-themeA-boot.txt` L264-295 |
| post meta `_themeA_*` 27 種 | `reports/INV-08-agent-interface-gap.md` §3 |

## 2026-08-27 実測の反映

`evidence/option-key-classification.tsv` により、実在する `themeA*` option を 179 件確認し、
値を記録せずに 5 軸で全件分類した。

| 軸 | 件数 | 比率 | 扱い |
|---|---:|---:|---|
| A サイト固有の意味 | 152 | 84% | 移管必須 |
| B 見た目の選択 | 23 | 12% | 任意移管 |
| C テーマ内部状態 | 2 | 1% | 移管しない |
| X credential | 2 | 1% | 値を読まず、移管せず、ログに出さない |
| D 保留 | 0 | 0% | なし |

したがって §3 の「移管必須 60〜80 キー」は option 側の実測には適用せず、
152 件を現時点の確定値とする。`theme_mods_themeA` は 235 キーで、
型は str 210 / bool 23 / int 1 / dict 1 だった。
同 235 キーの同種分類は値の読み取りを伴うため、未了の PO 判断として残す。

### theme_mods 235 キーの分類（2026-08-31・PO 許可のもと値を読み取り、値は記録せず）

`evidence/theme-mods-key-classification.tsv`（キー名・値の型・軸・理由のみ）。

| 軸 | 件数 | 比率 | 扱い |
|---|---:|---:|---|
| A サイト固有の意味 | 51 | 22% | 移管必須（表示文言・URL・SNS 48〔うち値が空 6〕/ 記事・分類の選択 1 / WP コア 2） |
| B 見た目の選択 | 176 | 75% | 任意移管（色・フォント・レイアウト 167 / 表示 ON-OFF 9） |
| C テーマ内部状態 | 1 | 0% | 異常キー（キー名 "0"）・移管しない |
| X credential | 0 | 0% | なし（credential は option 側の 2 キーのみ） |
| D 保留 | 6 | 3% | ④ 副作用既定値候補 5（INV-16 の 5 キー。値は既定値と相違＝人が設定）+ キー名から判別できない 1（アクセサ要確認） |

option（サイト固有 84%）と theme_mods（見た目 75%）の性質の違いは §2026-08-27 の見立てどおり。
移管必須の確定値は option 152 + theme_mods 51 = **203 キー**、保留 6 はアクセサ読解で確定させる（Sol レビュー 2026-08-31 で文言 3 件を A へ、意匠 9 件と ON/OFF 3 件を B へ訂正）。
実運用サイトへの対処（`.user.ini` 等）は PO 判断（2026-08-31）により**運用段階に入るまで行わず**、検証は PoC 環境で行う。
