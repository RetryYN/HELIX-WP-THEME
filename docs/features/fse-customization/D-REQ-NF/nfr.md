# fse-customization — D-REQ-NF（非機能要件）

## 概要

`fse-customization` の非機能要件は、FSE カスタマイズ余地の確立が品質・互換性・アクセシビリティ・保守性の基準を満たすことを保証する。特に「制作側の上書き余地を残す」という設計方針が、将来の更新やコンテンツ再生成によって失われないこと（Editability Preservation）を重点とする。

## 非機能要件の分類

| 観点 | 要件 ID | 件数 |
|---|---|---|
| アクセシビリティ | REQ-NF-026-a | 1 |
| 編集可能性保証 | REQ-NF-026-b | 1 |
| パターン整合性 | REQ-NF-026-c | 1 |

## 詳細要件

| ID | 要件名 | 観点 | 説明 | 優先度 | 上位 L1 ID |
|---|---|---|---|---|---|
| REQ-NF-026-a | dark バリエーション WCAG 2.2 AA 必達 | アクセシビリティ | dark.json 適用時の本文・ボタン・リンク・カテゴリバッジのコントラスト比が WCAG 2.2 AA 基準（通常テキスト 4.5:1 以上 / 大テキスト 3:1 以上）を満たすこと。axe-core で critical / serious ゼロを確認する | P0 | REQ-NF-026, REQ-NF-005 |
| REQ-NF-026-b | Global Styles 上書き保持（Editability Preservation） | 編集可能性保証 | theme.json は値を「初期値」として提供し、`custom:false` 等のロックフラグを一切使用しない。サイトエディタの Global Styles パネルで色・タイポグラフィ・余白を上書きした変更が前面に反映されること。追加 CSS も阻害しないこと | P0 | REQ-NF-026 |
| REQ-NF-026-c | 同梱パターンの synced 禁止 | パターン整合性 | patterns/ 配下の全パターンファイルが非同期（ファイルベース）であり、synced パターン（`<!-- wp:block` 参照 / `Synced: yes` 記述）を含まないこと。bin/check-theme-quality.sh で静的に確認可能にする（FC-008 / P2） | P0 | REQ-NF-026 |

## Global Styles 上書き保持の詳細設計指針

**現状と追加対応**:

| 設定項目 | 現状 | FC-003 での対応 |
|---|---|---|
| `appearanceTools` | `true`（ロックなし） | 維持 |
| `color.custom` | `true` | 維持 |
| `color.customDuotone` | `false` 据え置き可 | 変更なし |
| `typography.customFontSize` | `true` | 維持 |
| `typography.customFontFamily` | 未指定（暗黙 false 相当） | **`true` を明示追加** |

theme.json のいかなる設定も `custom:false` や `locked:true` でユーザーの上書きを禁じない。

**上書き経路の保証**:

1. **Global Styles（サイトエディタ）**: 色 / タイポグラフィ / 余白の変更 → テーマ更新で消えない（WP core が `theme_mods` に保存）
2. **追加 CSS**: 管理画面「外観 > カスタマイズ > 追加 CSS」での上書き → 保持
3. **Style Variation 複製**: `styles/light.json` を複製して palette を変えた業種別 variation → サイトエディタで選択可
4. **同梱パターンの編集**: 流し込み後のブロックを直接編集 → 非同期パターンなので保持

## パターン編集可能性の背景

WordPress の FSE パターンには「非同期（unsynced）」と「同期（synced / 再利用ブロック）」の2種がある。

- **非同期パターン**: ファイルベースで配信されるひな型。挿入後は独立したブロックに変換される → **流し込み後の編集が保持される**
- **同期パターン（再利用ブロック）**: CPT（`wp:block`）として DB に保存し全挿入箇所に同期される → **一箇所の変更が全サイトに波及、個別編集不可**

AGENT NEO は AI が記事を流し込む際にパターンを使用する。synced パターンを同梱すると AI の再生成が全サイトのパターン内容を上書きするリスクがある。このため、同梱パターンは全て非同期とし（REQ-NF-026-c）、制作側が局所的な編集を安全に行える設計を維持する。

## AI 再生成との競合回避

見た目の調整は必ずテーマ層（Style Variations / Global Styles / 追加 CSS）で行う。ブロック単位のインライン上書き（エディタ上の直接スタイル変更）は AI が記事を再生成した際に消えうるため非推奨とする。この方針を docs（FC-007 / README）に明記する。

## 検証計画サマリー

| 要件 | 検証方法 | 実行タイミング |
|---|---|---|
| REQ-NF-026-a dark WCAG AA | axe-core（dark 切替後 AC2 実測） | Wave 1 完了後 |
| REQ-NF-026-b Global Styles | サイトエディタで accent 色変更 → 前面反映確認（AC5） | Wave 1 完了後 |
| REQ-NF-026-c synced 禁止 | `bin/check-theme-quality.sh` PASS（FAIL 0） | Wave 3 |

## 参照

- L1 非機能要件: REQ-NF-026（Editability Preservation）、REQ-NF-005（アクセシビリティ）、REQ-NF-008（配布/機能境界）
- ADR-028: テーマカスタマイズ余地境界の明文化
- 設計仕様正本: fse-customization-design-spec.md §4.6, §4.7
