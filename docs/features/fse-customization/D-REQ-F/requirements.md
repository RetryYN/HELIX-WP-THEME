# fse-customization — D-REQ-F（機能要件）

## 概要

`fse-customization` は AGENT NEO テーマの FSE（Full Site Editing）カスタマイズ余地を確立する feature である。スタイルバリエーション（Style Variations）による見た目の切り替え、記事専用テンプレートパーツ（post-header / post-footer）の切り出し、および Global Styles・パターン編集を阻害しないテーマ設計を通じて、制作・運営側が AI 生成物を安全に上書き・微調整できる骨格を提供する。

**思想の二分（ADR-028 に準拠）**:
- エンドユーザー向けの分厚い設定パネルは持たない（SWELL の Customizer 331設定の世界 = 戦略的不採用）。
- 制作・運営側が AI 生成物を上書き・微調整する余地は残す（FSE 標準の拡張性を殺さない）。

**AI 再生成との競合回避ルール**: 見た目調整は**テーマ層（Style Variations / Global Styles / 追加CSS）で行う** = 投稿再生成で消えない。ブロック単位インライン上書きは再生成で消えうるため非推奨。

> **2026-06-26 追加 / ADR-028**: FSE カスタマイズ余地設計の方針確定。REQ-F-045 / REQ-F-046 / REQ-NF-026 が正式採番された。

## ID 体系

| 接頭辞 | 対象 |
|---|---|
| `FC-` | fse-customization 機能要件 |

## 機能要件テーブル

| ID | 要件名 | 説明 | 優先度 | 上位 L1 ID |
|---|---|---|---|---|
| FC-001 | styles/light.json（標準バリエーション） | `styles/light.json` を新規作成し、theme.json 本体と同値の8色パレット（base/foreground/primary/secondary/accent/accent-aa/footer-bg/muted）を明示見本として定義する。title は「ライト（標準）」。業種別 variation 複製の起点となる | P0 | REQ-F-045 |
| FC-002 | styles/dark.json（ダークバリエーション） | `styles/dark.json` を新規作成し、暗背景に適した8色パレット（background=#121212 / foreground=#ededed / accent=#ff6b00 維持ほか）を定義する。ボタン文字コントラスト確保のため `styles.elements.button.color.text` を最小 override する。dark バリエーション適用時に WCAG 2.2 AA を満たすこと（axe 実測で確定）| P0 | REQ-F-045 |
| FC-003 | theme.json: templateParts + customFontFamily 追加 | theme.json の `templateParts` 配列に `post-header`（area=uncategorized, title="Post Header"）と `post-footer`（area=uncategorized, title="Post Footer"）を追加する。`typography.customFontFamily: true` を明示化する（現状未指定のため）。`custom:false` 等のロックフラグは一切入れない | P0 | REQ-F-046, REQ-NF-026 |
| FC-004 | parts/post-header.html | `parts/post-header.html` を新規作成する。single.html のパンくずリスト・投稿タイトル（h1）・エントリーメタ（日付/アバター/著者名/カテゴリ/読了時間）をこのパーツに切り出す。area=uncategorized、title="Post Header"。single の singular context を継承して wp:post-* ブロックが正常解決されることを確認する | P0 | REQ-F-046 |
| FC-005 | parts/post-footer.html（パターンラッパー方式） | `parts/post-footer.html` を新規作成する。single.html の an-article-end 内コンテンツ（post-terms/tags・シェアボタンパターン・記事 CTA パターン・著者ボックス・著者プロフィールパターン・関連記事クエリ・前後ナビ）をこのパーツに移す。PHP 動的パターン（share-buttons / author-profile）は `<!-- wp:pattern {"slug":...} /-->` 参照のままラッパーパートとしてラップする（ラッパーパーツ方式）。comments は single.html に残す | P0 | REQ-F-046 |
| FC-006 | single.html の template-part 参照化 | `templates/single.html` の該当インラインブロックを `<!-- wp:template-part {"slug":"post-header",...} /-->` と `<!-- wp:template-part {"slug":"post-footer",...} /-->` 参照に置換する。featured-image と post-content は single.html に残す。page.html / archive.html は今回変更しない | P0 | REQ-F-046 |
| FC-007 | README/docs: 業種別 variation 複製手順 + 制作側カスタマイズ方針 | `themes/agent-neo-theme/README.md` に「制作側カスタマイズ方針」節と「業種別バリエーションの作り方」節を追記する。styles/light.json を複製して palette を変えるだけで新バリエーションが作れること・Global Styles 上書き可・パターン流し込み後編集可・見た目調整はテーマ層で行い投稿再生成と競合させない旨を記載する | P1 | REQ-F-045, REQ-NF-026 |
| FC-008 | bin/check-theme-quality.sh: synced パターン不在チェック（任意） | `bin/check-theme-quality.sh` に patterns/ 内に `<!-- wp:block` 参照や `Synced: yes` の文字列がないことの軽チェックを追加する。WARN のみで gate FAIL にはしない（P2 / 任意追加） | P2 | REQ-NF-026 |

## 設計指針

### Style Variation アーキテクチャ

theme.json 本体の `styles` セクションは全て `var(--wp--preset--color--*)` で palette slug を参照している。バリエーションファイルは**palette の色値のみを override** する。var 参照が新 palette を自動的に指すため、レイアウト/タイポグラフィは再定義不要 = 「複製で業種別が作れる骨格」の核。

- `styles/` に置く `.json` ファイルは全てバリエーションとして自動認識される。雛形を `styles/` に置かない（当初は light.json / dark.json の2本のみ）
- 業種別バリエーションは手順書（FC-007）に従い `styles/light.json` を複製して利用する

### dark.json の必須 styles override

本体の button は `text=var(--wp--preset--color--background)` を参照している。dark バリエーションで background=#121212 になるとボタン文字が暗色になりオレンジ背景との WCAG 2.2 AA を満たさなくなる。dark.json では `styles.elements.button.color.text` を明示指定して AA を確保する。実装時 axe で本文/ボタン/リンク/カテゴリバッジのコントラストを実測し通過する組み合わせを確定する（初期案: accent-aa=#ff7a1a + button text=#121212）。

### templateParts と single.html の改修方針

- `post-header` / `post-footer` は area=uncategorized で登録し、記事テンプレート専用パーツとして扱う
- page.html / archive.html には影響しない
- 改修後の single.html 表示がブレイクダウン前と同等（パンくず / タイトル / メタ / タグ / シェア / CTA / 著者 / 関連 / ナビが欠落しない）であることを検証する

### Global Styles 上書き保持

現状 theme.json は `appearanceTools:true` / `color.custom:true` / `customFontSize:true` 等でロックなし。FC-003 で `typography.customFontFamily:true` を明示追加するのみで、`custom:false` 等のロックは一切入れない（REQ-NF-026）。

### パターン編集可保証

既存パターンはすべて非同期（ファイルベース）であり流し込み後の編集が可能。synced パターン（再利用ブロック / wp:block CPT 参照）を同梱しない方針を維持する。FC-008 の軽チェックで継続監視する。

## L4 実装順（Wave 構成 / fe drive）

| Wave | 担当 | 内容 |
|---|---|---|
| Wave 1 | fe-style | FC-003（theme.json） → FC-001 → FC-002（axe 実測で dark 配色確定） |
| Wave 2 | fe-component | FC-004 → FC-005 → FC-006（single.html 改修・表示同等性確認） |
| Wave 3 | — | FC-007 docs + FC-008（任意） + 検証（check-theme-quality / unit 47 / security 48 / WP 目視） |

Wave 1 / Wave 2 は並列実行可能だが、git 事故回避のため逐次 dispatch を推奨。Wave 3 は両 Wave 完了後。

## 参照

- L1 要件: REQ-F-045, REQ-F-046, REQ-NF-026
- ADR: ADR-028（テーマカスタマイズ余地境界の明文化）
- 設計仕様正本: fse-customization-design-spec.md §4, §5
