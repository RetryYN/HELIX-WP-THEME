# THEME-INV-03: 広告 / CV ゾーン仕様を 3 テーマ横断で確定する

labels: investigation, monetization, zones, priority:high
depends: なし

> **状態: 一次完了**（2026-08-26）／レポート: `../reports/INV-03-ad-cv-zones.md`
> ゾーンを意味で **23 種に正規化**。`ad-zone.schema.json` の差分 3 点を特定
> （`category_override` はゾーンではなく上書き規則／20 ゾーンが語彙に無い／条件表示のモデルが無い）。
> `creative_ref` を参照にし `overrides` を first-match-wins 配列にする改訂案を提示。
> **残**: 本番の `sidebars_widgets` 読み取りによる実配置の確定。

## 背景（実測）
| テーマ | ゾーン数 | 主なゾーン |
|---|---|---|
| テーマA | 11 ウィジェットエリア | post-top / post-start / post-end / post-bottom / relatedpost-bottom / **sidebar-tracking**（追尾）/ hamburger / toppage / footer |
| テーマB | 24 ウィジェットエリア | single_top / single_bottom / **single_cta** / before_related / after_related / **fix_sidebar** / fix_bottom_menu / head_box / footer_box1-3 |
| agent-neo | **0** | parts は header/footer/post-header/post-footer の 4 つのみ |

agent-neo の `plugins/agent-neo-core/schema/ad-zone.schema.json` には既に
「**CARRY-A2-001: テーマA の 4 ゾーン（h2 前挿入 / 記事終 / 関連上 / カテゴリ別上書き）に対応する静的管理**」
と書かれている。実測はこの 4 ゾーンを超える面（追尾サイドバー・固定ボトム・CTA 枠）を示した。

## 調査項目
1. 11 + 24 のゾーンを**位置の意味**で正規化する（記事前 / h2 前 / 記事後 / 関連前後 / 追尾 / 固定ボトム / ヘッダ / フッタ）
2. `ad-zone.schema.json` の `zone_id` 語彙が正規化結果を表現できるか（不足プロパティの洗い出し）
3. カテゴリ別上書き・条件表示（ログイン / デバイス / 記事タイプ）の実装実態を両テーマで確認
4. 実際に何が入っているか（本番のウィジェット設定）を読み取りで採取し、**使われているゾーンだけ**を第一級にする

## 完了条件
- [ ] 正規化ゾーン一覧（意味 × テーマA 名 × テーマB 名 × 実使用有無）が存在する
- [ ] `ad-zone.schema.json` の拡張差分が提案されている
- [ ] 第一級ゾーンと後回しゾーンが実使用証跡で切り分けられている
