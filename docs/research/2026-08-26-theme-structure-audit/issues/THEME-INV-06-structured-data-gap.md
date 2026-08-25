# THEME-INV-06: 構造化データ出力の差分を埋める

labels: investigation, seo, structured-data, priority:medium
depends: なし

## 背景（実測）
| テーマ | 実装 | 出力 @type |
|---|---|---|
| JIN:R | `include/json-ld.php`（344 行） | Organization / Person / ListItem / ImageObject |
| SWELL | `classes/Json_Ld.php`（472 行） | **Article / WebSite / WebPage / CollectionPage / BreadcrumbList / SearchAction** / Organization / Person / ImageObject / ListItem |
| agent-neo | `inc/seo/class-structured-data.php` | BlogPosting / WebPage / WebSite / BreadcrumbList / Organization / Person / ImageObject / ListItem |

SWELL のみ `CollectionPage`（アーカイブ）と `SearchAction`（サイト内検索）を出力。
JIN:R は記事本体の型（Article/BlogPosting）を出していない可能性があり、要確認。

## 調査項目
1. 3 実装の出力を**実ページで採取**して比較する（読み取り専用の HTTP 取得）
2. JIN:R が記事型を出していないのが実装差か設定差かを判定（プラグイン `website-llms-txt` `google-sitemap-generator` の関与も確認）
3. `CollectionPage` / `SearchAction` / FAQPage / HowTo 等、agent-neo に無い型の要否を SEO 観点で判定
4. AIO / LLMO 向けの追加型（`website-llms-txt` が両サイトで active）との整合を確認

## 完了条件
- [ ] 3 実装 × 主要ページ種別（記事 / アーカイブ / 検索 / トップ）の実出力が採取されている
- [ ] agent-neo に不足する型が優先度付きで列挙されている
- [ ] プラグイン由来の出力と二重化しない方針が決まっている
