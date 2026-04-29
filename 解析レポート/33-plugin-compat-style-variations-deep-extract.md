# プラグイン互換 + ブロックスタイル variation + パターン 深掘り

> 解析日: 2026-04-30 / 対象: SWELL 2.16.0 + JIN:R 親テーマ
> 解析コンテキスト: PM Opus 観点で Codex 解析の情報密度を上げる目的の追加抽出

## 領域 A: プラグイン互換性ロジック

### SWELL の実装

| 場所 | 検知方式 | 対象プラグイン | 振る舞い |
|---|---|---|---|
| `classes/Theme_Data.php` L435-445 | `is_plugin_active()` | 有効プラグイン全体 | 全プラグインのリストをデータとして記録（診断・サポート用） |
| `classes/Json_Ld.php` L16-25 | `class_exists('\SSP_Output')` | **Schema Simple Plugin (SSP)** | 検出時、JSON-LD 出力を SSP メタデータ最優先に切替（深い統合） |
| `lib/load/block_assets.php` L58 | `function_exists('wp_set_script_translations')` | WP Core v5.0+ | 翻訳ファイル読み込み判定 |

### JIN:R の実装

| 場所 | 検知方式 | 対象 | 振る舞い |
|---|---|---|---|
| `include/custom-functions.php` | `file_exists()` | ローカルリソースのみ | プラグイン依存性が極めて低く、独立志向 |

### 重要発見

- **Yoast SEO / RankMath / All in One SEO への明示的なチェックは両テーマとも未実装**
- SWELL は SSP（Schema Simple Plugin）にのみ深く統合
- JIN:R は SEO プラグインとの統合をあえて避け、自前でほぼ完結
- WooCommerce / Contact Form 7 / WP Rocket / Autoptimize に対する明示的な分岐コードも確認できず

---

## 領域 B: ブロックスタイル Variation

### SWELL の実装パターン

| ブロック | 制御属性 | 実装方式 |
|---------|---------|--------|
| button | color, fontSize, btnSize | 属性ベース CSS マッピング |
| balloon | balloonType, balloonBorder, balloonShape, balloonCol | multi-dimensional variation |
| box-menu | boxStyle | 単一スタイル属性 |
| step | numShape, numLayout, stepClass | 複合属性 |

### 重要発見

**SWELL の `block.json` には `styles` フィールドが存在しない。**

代わりに:
- attributes 内で variant 系プロパティを定義
- CSS 側で `.swell-block-button.blue_` のような class selector で表現
- `register_block_style()` も使わない属性ベース統一

### 検出された色 variation

- balloon: blue, red, orange, green, purple, gray
- button: blue, black, white, orange

### JIN:R

- ブロックカスタマイズなし
- Core block のみ
- style variations 未実装

---

## 領域 C: Block Pattern / Pattern Category

### SWELL の実装

| ファイル | パターン数 | Category |
|---------|----------|----------|
| `lib/gutenberg/block_pattern/common.php` | 7+ | `swell-patterns`（汎用） |
| `lib/gutenberg/block_pattern/page.php` | 4+ | `swell-page-patterns`（ページ） |
| `lib/gutenberg/block_pattern/table.php` | 3+ | `swell-table-patterns`（テーブル） |
| Dynamic（blog_parts CPT） | 無制限 | `swell-custom-patterns` |

### 革新的機能: blog_parts CPT → Pattern 自動登録

`lib/gutenberg/block_patterns.php` L31-64 で、ユーザーが「ブログパーツ」として保存したコンテンツを自動的に再利用可能 block pattern として登録するメカニズムを実装。

### 代表パターン例

- `swell-pattern/button-with-microcopy` — マイクロコピー付きボタン
- `swell-pattern/list-border` — 枠線付きリスト
- `swell-pattern/media-text-double-card` — カード型メディアレイアウト

### JIN:R

- Pattern 機能なし
- デザイン調整は全てカスタマイザー UI

---

## 主要発見（AGENT NEO への含意）

### 1. プラグイン互換性は「段階的フォールバック戦略」を採用すべき

SWELL（SSP 深い統合）と JIN:R（独立志向）という対照的なアプローチが見えた。AGENT NEO はハイブリッド戦略が妥当:

```
Tier 1: seo-tool-connector（既存・最優先）
Tier 2: Yoast / RankMath（adapter 経由でセカンダリ）
Tier 3: AGENT NEO Theme Default（フォールバック・常時動作）
```

これにより、外部プラグイン依存度を低く保ちながら互換性を確保できる。`adapter-capability-map.json` でこの 3 ティアを明示する。

### 2. ブロックスタイル variation は「属性ベース」が高拡張性

SWELL の `block.json` が `styles` フィールドを持たず、属性で全制御する設計は WordPress 慣習からは外れるものの、保守・拡張に優れている。

ただし AGENT NEO は **AI 操作前提** なので、機械可読性重視で `styles` フィールド + `register_block_style()` の標準パターンを採用すべき。AI が JSON で variation を選びやすい:

```json
{
  "name": "agent-neo/cta-button",
  "styles": [
    { "name": "default", "label": "Default" },
    { "name": "filled", "label": "Filled" },
    { "name": "outline", "label": "Outline" }
  ],
  "attributes": {
    "variant": { "type": "string", "default": "default" },
    "color": { "type": "string", "default": "primary" },
    "size": { "type": "string", "default": "md" }
  }
}
```

複合属性は `[data-variant="filled"][data-color="primary"]` のように data 属性で出力し、AI が DOM から確実に状態を読めるようにする。

### 3. Pattern ライブラリは「ユーザー生成 CPT 連携」で資産化

SWELL の blog_parts → Pattern 自動登録メカニズムは秀逸。AGENT NEO の `reusable-part` CPT も同様の設計とすべき:

```
1. agent-neo/reusable-part (CPT) 登録
2. parts_use taxonomy で「pattern」「cta」「section」分類
3. init フックで register_block_pattern() を自動実行
4. 法人版は service_id で service-aware なパターン分類
```

これによりユーザー（および AI）が作成したコンテンツが自動的に再利用可能パターンになり、パターンライブラリが運用しながら充実する。法人版の場合は service_id でフィルタリングして「サービス A 用 CTA」「サービス B 用 Hero」のように整理できる。

### 4. プラグイン非統合は「機能が薄い」のではなく「設計判断」

SWELL/JIN:R が外部 SEO プラグインを明示検出していないのは、競合との差別化を意図的に保つため。AGENT NEO も同様にすべきだが、ただし `seo-tool-connector` は自社 first-party なので深い統合が前提。これは方針として一貫性がある。

---

**レポート作成**: 2026-04-30 / Explore subagent 抽出 / PM Opus 検証
**分析対象**: SWELL Theme v2.16.0 親 + JIN:R 親テーマ
**抽出対象ファイル数**: 47+（SWELL）/ 8+（JIN:R）
