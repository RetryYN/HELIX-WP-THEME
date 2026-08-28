# THEME-INV-08: エージェント接点（REST / フック）の差を評価する

labels: investigation, api, agent-interface, priority:medium
depends: なし

> **状態: 一次完了**（2026-08-26）／レポート: `../reports/INV-08-agent-interface-gap.md`
> テーマA 操作の現実解は **コア REST（記事）+ WP-CLI / ブラウザ（設定）**。
> option 直接書き換えはスキーマが無いため非推奨。**名前空間はコアに相乗りしない**と決定。
> AGENT NEO の 34 コントローラを A 群 16（契約付き移植）/ B 群 9（不採用）/
> C 群 4（契約のみ + アダプタ）/ D 群 4（基盤）に仕分け。
> **残**: テーマB 14 ルートの契約精読、`_themeA_*` の `register_meta` 有無の確認。

## 背景（実測）
| テーマ | REST | 自前フック |
|---|---|---|
| テーマA | **0 本** | `do_action` 3 / `apply_filters` **1** |
| テーマB | 14 本（**`wp/v2` 名前空間に相乗り**: `/themeB-block-settings` `/themeB-term-list` `/themeB-balloon*` `/themeB-ct-*` `/themeB-reset-*` ほか） | `apply_filters` **79** / `do_action` 5 |
| agent-neo | **34 コントローラ**（`agent-neo/v1`）+ MCP + CLI | — |

テーマA は外部からの介入点が実質存在せず、**操作経路がオプション書き換えしか無い**。
これは「既存 2 サイトをハーネスから機械操作する」計画に直接効く制約。

## 調査項目
1. テーマB 14 ルートの入出力契約を採取し、機械操作で使えるものを特定（読み取り専用で検証）
2. テーマA を機械操作する現実的経路を列挙（WP コア REST / WP-CLI / オプション直接 / 不可）と、それぞれのリスク
3. `wp/v2` 相乗りの是非（名前空間衝突リスク）を評価し、Graphix NEO 側の名前空間方針を決める
4. agent-neo の 34 コントローラのうち、実運用テーマに対しても意味を持つものを仕分ける

## 完了条件
- [ ] テーマB 14 ルートの契約表が存在する
- [ ] テーマA サイトへの操作経路が可否付きで列挙されている
- [ ] Graphix NEO の REST 名前空間方針が決まっている
