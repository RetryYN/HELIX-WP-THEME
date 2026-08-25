# THEME-INV-08: エージェント接点（REST / フック）の差を評価する

labels: investigation, api, agent-interface, priority:medium
depends: なし

## 背景（実測）
| テーマ | REST | 自前フック |
|---|---|---|
| JIN:R | **0 本** | `do_action` 3 / `apply_filters` **1** |
| SWELL | 14 本（**`wp/v2` 名前空間に相乗り**: `/swell-block-settings` `/swell-term-list` `/swell-balloon*` `/swell-ct-*` `/swell-reset-*` ほか） | `apply_filters` **79** / `do_action` 5 |
| agent-neo | **34 コントローラ**（`agent-neo/v1`）+ MCP + CLI | — |

JIN:R は外部からの介入点が実質存在せず、**操作経路がオプション書き換えしか無い**。
これは「既存 2 サイトをハーネスから機械操作する」計画に直接効く制約。

## 調査項目
1. SWELL 14 ルートの入出力契約を採取し、機械操作で使えるものを特定（読み取り専用で検証）
2. JIN:R を機械操作する現実的経路を列挙（WP コア REST / WP-CLI / オプション直接 / 不可）と、それぞれのリスク
3. `wp/v2` 相乗りの是非（名前空間衝突リスク）を評価し、Graphix NEO 側の名前空間方針を決める
4. agent-neo の 34 コントローラのうち、実運用テーマに対しても意味を持つものを仕分ける

## 完了条件
- [ ] SWELL 14 ルートの契約表が存在する
- [ ] JIN:R サイトへの操作経路が可否付きで列挙されている
- [ ] Graphix NEO の REST 名前空間方針が決まっている
