# 学習ナビゲーションの所有と公開境界

WT-AC-LEARN-01Dの現行 `helix-wt` 実装・検証。学習ページの階層ナビと前後レッスンを、それぞれ独立して共通継承 `site`、学習面専用設定 `own`、非表示 `off` へ接続した。旧テーマとpluginsは変更していない。

## 表示設定

| 対象 | 所有軸 | 共通設定 | 学習面専用設定 |
|---|---|---|---|
| 階層 | `content_learning_hierarchy` | `learning_hierarchy_style` | `own_content_learning_hierarchy_style` |
| 前後 | `content_learning_sequence` | `learning_sequence_style` | `own_content_learning_sequence_style` |

階層は `trail / panel`、前後は `split / cards` を選べる。`site` と `own` は別の値を解決し、`off` は該当する `nav` だけを描画しない。既存の公開済み親子データ、前後端、DOM順、`aria-label`、現在ページの `aria-current=page` は維持する。

階層をラベル付き `nav` とリストで表し、現在ページを末尾へ置く構造は[WAI Breadcrumb Pattern](https://www.w3.org/WAI/ARIA/apg/patterns/breadcrumb/)と[WCAG Technique G65](https://www.w3.org/WAI/WCAG21/Techniques/general/G65)を参照した。WordPressの表示構造は既存の専用block templateを維持し、[Theme Handbookのtemplate hierarchy](https://developer.wordpress.org/themes/templates/template-hierarchy/)に別テンプレートを増やしていない。

## 実測

```sh
node docs/research/2026-09-13-learning-navigation-ownership/probe.mjs
node scripts/verify-learning-faces.mjs
```

専用probeは一時講座、公開3レッスン、下書き1件、パスワード保護1件を作成し、finallyで作成IDとslugを照合して削除する。

- 階層3状態 × 前後3状態 × 390/1440px × JS有効/無効 = 36表示条件。
- 組合せごとにownerとstyleの解決、現在位置、中央レッスンの前後リンク、横overflowなしを確認。
- `site` は明示した共通設定、`own` は別の学習面専用設定を反映。`off` は対象navが0件。
- 全状態で公開本文を表示し、下書きは講座一覧へ混入しない。
- 下書きURLは4条件すべて404で本文・題名を非表示。パスワード保護URLは4条件すべてpassword formだけを表示し本文を非表示。
- データ供給層の `wtcf_learning_display()` も下書き・パスワード保護へ `null` を返す。
- 44条件成功。既存学習検査も252件、14画像で成功。
- `verify.json` はテーマ3ファイルと認可境界を担う既存plugin `learning.php` のSHA-256を保持する。

`site/own/off-{390,1440}.png` は3状態の代表画像。JS無効も構造計測済みだが、同一描画の画像を重複保存していない。

## 限界

表示軸はqueryと既存theme_mod解決経路で検証した。Site Editor専用UIは未実装。公開範囲の実測は匿名閲覧の下書き・パスワード保護であり、全role/capability行列、ログイン後のパスワードcookie、外部会員機構は対象外。`off` は要求どおり補助ナビを隠すため、別の移動手段が必要な運用での採用判断は残る。
