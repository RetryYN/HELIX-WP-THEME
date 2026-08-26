# THEME-INV-09: サイト設定の正本と移管方式を決める

labels: investigation, migration, settings, priority:medium
depends: THEME-INV-05

> **状態: 一次完了**（2026-08-26）／レポート: `../reports/INV-09-settings-authority.md`
> 分類軸を **①サイト固有 / ②見た目 / ③内部状態 / ④副作用既定値**に確定。
> アクセサ 707 の**約 75% が「見た目」**で、移管必須は 60〜80 キー（全体の 5〜7%）。
> **判別不能問題は実害なし**（副作用の 5 キーは全て②で移管対象外）。移管手順 5 段を定義。
> **残**: 実在キーの列挙と 4 分類（`wp option list --search='themeA_*'`）。

## 背景（実測）
| テーマ | 保持方式 | 規模 |
|---|---|---|
| テーマA | `themeA_*` **個別オプション 1,225 キー** + カスタマイザ 162 ファイル | 巨大・分散 |
| テーマB | 単一配列 `themeB_options`（既定 **540 キー**）+ 独自管理画面タブ 8 枚 | 集約 |
| agent-neo | theme.json + `config/*.json` 7 本（section-registry / theme-manifest / asset-policy / third-party-tags / schema-reference / i18n-profile / web-vitals-budget） | 宣言的 |

既存サイトを移管する場合、**1,225 / 540 のうち何が「サイトの意味」で何が「テーマの都合」か**を
仕分けないと、移管がそのまま旧構造の持ち込みになる。

## 調査項目
1. `themeA_*` 1,225 キーを分類（サイト固有の意味 / 見た目 / テーマ内部状態 / 廃棄可）
2. `themeB_options` 540 既定キーを同じ軸で分類
3. 「サイトの意味」に該当するものだけを対象に、宣言的 JSON への写像可能性を判定
4. 移管対象データ（プロフィール・SNS・CV ボタン・カテゴリ設定・トラッキング ID 等）の最小集合を定義

## 完了条件
- [ ] 1,765 キーの分類表が存在する（機械分類 + 手動確認）
- [ ] 移管必須の最小集合が列挙されている
- [ ] 宣言的 JSON への写像方式が決まっている
