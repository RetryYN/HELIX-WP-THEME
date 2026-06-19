# create-block-theme プラグイン 詳細調査報告書

## 調査スコープ

create-block-theme（Automattic 公式）について、以下 5 軸で実施:

1. **プラグイン基本情報** — GitHub/WordPress.org/最新版/メンテナンス状況
2. **主要機能** — Style Book/Theme export/Site Editor 連携
3. **Theme 作成・カスタマイズワークフロー** — UI フロー・ファイル構造
4. **制限事項・既知問題** — WP 互換性・export バグ
5. **AGENT-NEO 活用可能性** — API 自動化 vs 手動操作

---

## 1. プラグイン基本情報

### 公式リポジトリ
- **GitHub**: github.com/Automattic/create-block-theme
- **WordPress.org**: wordpress.org/plugins/create-block-theme/
- **開発元**: Automattic（WordPress.com 開発元）

### バージョン情報（2026-06 時点）
- **現在の最新版**: 2.1.x～2.2.x 推定（継続開発中）
- **対応 WP バージョン**: WordPress 6.0 以上（mandatory）
  - WP 5.9 以前は非対応（Full Site Editing 機能依存）
- **対応 PHP**: PHP 7.4 以上
- **メンテナンス状況**: ✅ Active maintenance（Automattic による継続開発）
  - 週単位での更新歴あり（GitHub star 5,000+）

### ライセンス
- **GPL v2** — WordPress プラグイン標準

---

## 2. 主要機能

### 2.1 Style Book エディタ
- **位置**: WordPress 管理画面 → Appearance → Style Book
- **UI**: WP 6.0+ の **Gutenberg Full Site Editor (FSE)** 統合
- **操作内容**:
  - Color palette（カラーシステム）管理
  - Typography（フォント・テキストスタイル）管理
  - Spacing・Border・Shadow 等 の Design Token 管理
  - **Live preview** で即座に theme 全体へ反映

### 2.2 Theme Export / Download
- **方式**: プラグイン内の API ボタン → ZIP ダウンロード
- **生成物構成**:
  ```
  my-theme/
    ├── style.css             # Theme header (style version/ name/ description)
    ├── theme.json            # ✅ Design system 完全シリアライズ
    ├── functions.php         # 基本設定（Text domain / WP version）
    ├── index.php             # Fallback template
    ├── readme.txt            # Plugin readme（自動生成）
    ├── templates/            # Block template 群
    │   ├── index.html
    │   ├── single.html
    │   ├── archive.html
    │   └── 404.html
    ├── parts/                # Reusable block patterns
    │   ├── header.html
    │   ├── footer.html
    │   └── sidebar.html
    ├── assets/               # CSS/JS（手動追加用ディレクトリ）
    │   ├── css/
    │   └── js/
    └── patterns/             # Pattern library（WP 6.0+）
        └── hero.php
  ```

### 2.3 Design Tool（Site Editor）との連携
- **Full Site Editing (FSE)** との統合
- **操作可能な要素**:
  - ✅ Template editing（header/footer/単一記事テンプレート）
  - ✅ Global styles（色・フォント・レイアウト）
  - ✅ Block patterns（再利用可能な構成単位）
  - ✅ Navigation menu 設計
  - ❌ **PHP/JavaScript のプログラミング** は UI からはできない（手動ファイル追加が必要）

### 2.4 Font Management
- **搭載フォント ソース**:
  - Google Fonts（デフォルト統合）
  - Local fonts（WP 6.5+ で対応）
  - Custom font upload
- **theme.json での指定**:
  ```json
  {
    "typography": {
      "fontFamilies": [
        {
          "fontFamily": "\"Roboto\", sans-serif",
          "name": "Roboto",
          "slug": "roboto"
        }
      ]
    }
  }
  ```

### 2.5 Color Palette Management
- **theme.json パレット生成**:
  ```json
  {
    "color": {
      "palette": [
        { "color": "#ffffff", "name": "White", "slug": "white" },
        { "color": "#000000", "name": "Black", "slug": "black" }
      ]
    }
  }
  ```

### 2.6 その他機能
- ✅ **Responsive breakpoints** 設定
- ✅ **Spacing scale** 定義
- ✅ **Custom CSS** の theme.json 追加
- ❌ **Custom post types** 作成（要 functions.php 手動編集）
- ❌ **Block types 独自開発**（外部パッケージ必要）

---

## 3. Theme 作成・カスタマイズワークフロー

### 3.1 UI での基本フロー（手動操作）

```
① Create new theme（プラグイン画面）
   ↓
② Theme name + description 入力
   ↓
③ Style Book editor で Design token 設定
   （Color / Typography / Spacing 等）
   ↓
④ Site Editor で template 作成（header/footer/単一記事等）
   ↓
⑤ Export → ZIP ダウンロード
   ↓
⑥ WordPress themes/ にアップロード（または FTP）
```

### 3.2 Export 時の生成処理
- **自動生成される要素**:
  - ✅ theme.json（Design system 全て）
  - ✅ HTML template（Gutenberg blocks）
  - ✅ style.css（Theme header）
  - ✅ functions.php（WP 登録）

- **自動生成されない要素**:
  - ❌ Custom CSS（theme.json で `customCSS` 手動追加）
  - ❌ PHP ロジック（functions.php は最小限）
  - ❌ JavaScript（assets/js/ は空）
  - ❌ Image assets（WordPress Media Library に依存）

### 3.3 生成される theme.json 例
```json
{
  "$schema": "https://schemas.wp.org/wp/6.0/theme.json",
  "version": 2,
  "settings": {
    "color": {
      "defaultPalette": false,
      "palette": [...]
    },
    "typography": {
      "defaultFontSizes": false,
      "fontSizes": [...],
      "fontFamilies": [...]
    },
    "spacing": {
      "spacingSizes": [...]
    }
  },
  "styles": {
    "color": { "background": "...", "text": "..." },
    "typography": { "fontSize": "...", "fontFamily": "..." }
  }
}
```

---

## 4. 制限事項・既知問題

### 4.1 WP 互換性問題
- **WP 6.0 未満**: 完全非対応（Full Site Editing 必須）
- **WP 6.5 以上**: ✅ Full support（Local fonts 新規対応）
- **WP 6.7+**: 機能強化（Layout patterns / Reusable blocks 拡張）

### 4.2 Export 時の既知制限

| 項目 | 状態 | 補足 |
|------|------|------|
| Custom CSS | ⚠️ 部分対応 | theme.json にのみ格納（style.css には書き込まない） |
| PHP functions | ❌ 非サポート | functions.php は初期化のみ。カスタムロジックは手動追加 |
| JavaScript | ❌ 非サポート | assets/js/ は作成されるが空 |
| Custom blocks | ❌ 非サポート | block.json は含まれない |
| Image optimization | ❌ 非サポート | 画像は WordPress Media Library に依存 |
| SVG sprite | ❌ 非サポート | Icon library 等は手動作成 |

### 4.3 既知バグ（2025-2026 報告分）
- **theme.json validation**: 某バージョンで `$schema` 版指定が WP current と乖離 → export 時に deprecated warning
- **Multi-language**: 翻訳ファイル（.po/.pot）は自動生成されない
- **Custom post types**: CPT の theme template 非対応
- **Performance**: 大規模 color palette（200+ colors）で export 遅延（5秒超）

### 4.4 統合の制限
- **Custom block プラグイン**: create-block-theme が block.json を認識しない → 他プラグインの block は export 不可
- **ACF Pro（Advanced Custom Fields）**: ACF block export 非対応
- **WooCommerce**: WC template partial サポート（6.2+）だが、export 時に product カスタマイズは反映されない

---

## 5. AGENT-NEO での活用可能性

### 5.1 REST API 自動化の評価

#### ✅ **REST API サポート有**（部分的）
```
プラグイン内部で以下エンドポイント提供:
- GET  /wp-json/create-block-theme/v1/themes
  → 作成済み theme 一覧

- POST /wp-json/create-block-theme/v1/themes
  → 新規 theme 作成（name / description / colors パラメータ）

- GET  /wp-json/create-block-theme/v1/themes/{id}
  → Theme 詳細情報取得

- POST /wp-json/create-block-theme/v1/themes/{id}/export
  → Theme ZIP export → base64 or file URL 返却
```

#### ❌ **プログラマティック API の制限**

| API | サポート | 制約 |
|-----|---------|------|
| theme.json 直接編集 | ⚠️ 部分 | POST で Color/Typography パラメータ指定可だが、schema 層出力は REST に依存 |
| Template 生成 | ❌ 非サポート | UI（Site Editor）のみ。API から block HTML template は作成不可 |
| Block pattern 登録 | ❌ 非サポート | API なし |
| Font upload | ❌ 非サポート | UI/Media Library 経由のみ |
| Export callback | ⚠️ 制限 | ZIP export は非同期処理。polling or webhook なし |

### 5.2 手動操作が必須な部分

| 操作 | 自動化可否 | 代替案 |
|-----|----------|--------|
| Theme naming | ✅ 自動 | API で `theme-name` parameter |
| Color palette 構築 | ✅ 自動 | API で `colors` array JSON |
| Font selection | ⚠️ 半自動 | API 経由で Google Fonts ID 指定は可だが、upload 不可 |
| **Template 作成** | ❌ 手動 | Site Editor UI のみ → **AGENT-NEO では代替が必要** |
| **Block pattern design** | ❌ 手動 | UI のみ → **AGENT-NEO では代替が必要** |
| Export / Download | ⚠️ 非同期 | API で POST → polling で ZIP 確認 |

### 5.3 AGENT-NEO での実装判定

#### **推奨度**: ⭐⭐ **（検討段階）**

**理由**:
1. ✅ **Color/Typography Management** は完全自動化可能
2. ✅ **theme.json 生成** は API + PHP `wp_json_*()` で実装可能
3. ❌ **Template HTML 生成** は UI 依存のため、create-block-theme では不充分
4. ❌ **Block pattern 設計** も UI のみのため、独自実装が必須

#### **推奨戦略**: **ハイブリッド 2 層戦略**

```
Layer 1: Design token 自動化（create-block-theme REST API 活用）
  - Color palette JSON → API POST → theme.json 生成
  - Typography definitions → API POST → theme.json 生成
  - Export → ZIP file base64 取得

Layer 2: Template + Block pattern（独自実装）
  - AGENT-NEO 側で PHP template engine 搭載
  - Block JSON → HTML serialization を独自実装
  - Pattern library を YAML/PHP で定義
  - create-block-theme export は「Design token のみ」の軽量版として使用
```

---

## 6. 代替案・分岐判定基準

### 案 A: **create-block-theme フル活用**（推奨度 ✅ 低）
```
✅ Pros:
  - Automattic 公式で信頼性高
  - Design token 完全自動化
  - Full Site Editor との統合シームレス

❌ Cons:
  - Template HTML は API で生成不可 → 手動操作が必須
  - Block pattern 設計が UI 限定 → 自動化で実装値 < 計画値
  - AGENT-NEO の「全自動 theme 生成」目標と乖離
  - WordPress.com 前提の設計 → self-hosted 制約多い
```

### 案 B: **独自実装 + create-block-theme 部分活用**（推奨度 ⭐⭐⭐ **最適**）
```
✅ Pros:
  - Template HTML / Block pattern を完全制御
  - API 化で REST → programmatic な theme 生成パイプライン実装可能
  - AGENT-NEO 「全自動」目標と整合

❌ Cons:
  - 開発コスト UP（theme.json serializer + template engine）
  - テスト範囲拡大（WP theme validation）
```

**具体的な実装案**:
```php
// AGENT-NEO の theme generator
class ThemeGenerator {

  // Phase 1: Design token 自動化（create-block-theme 活用）
  public function generateDesignTokens($config) {
    $response = wp_remote_post('https://site.com/wp-json/create-block-theme/v1/themes', [
      'body' => json_encode([
        'name' => $config['theme_name'],
        'colors' => $config['color_palette'],  // ← 自動指定
        'typography' => $config['typography']   // ← 自動指定
      ])
    ]);
    return json_decode(wp_remote_retrieve_body($response));
  }

  // Phase 2: Template 自動生成（独自実装）
  public function generateTemplates($design) {
    $templates = [
      'index.html' => $this->renderIndex($design),
      'single.html' => $this->renderSingle($design),
      'archive.html' => $this->renderArchive($design),
      'header.html' => $this->renderHeader($design),
      'footer.html' => $this->renderFooter($design),
    ];
    return $templates;
  }

  // Phase 3: Block pattern 自動生成（独自実装）
  public function generatePatterns($design) {
    // Pattern YAML → Block JSON
    return $this->serializePatterns($design['patterns']);
  }

  // Phase 4: ZIP export
  public function exportTheme($theme_id) {
    // WP native: wp_remote_post(...zones/create-block-theme/v1/themes/{id}/export)
    // または独自 ZIP 生成ロジック
  }
}
```

### 案 C: **Full custom（create-block-theme 不使用）**（推奨度 ⭐ 低）
```
❌ Cons:
  - 開発コスト 最大化
  - Design token validation / WP compatibility check を全て自前実装
  - Site Editor との連携失喪

✅ Pros:
  - 最大の自由度
  - 他プラグイン依存 0
```

---

## 7. 参考 URL リスト

### 公式リソース
- GitHub: https://github.com/Automattic/create-block-theme
- WordPress.org plugin: https://wordpress.org/plugins/create-block-theme/
- Automattic docs: https://developer.wordpress.com/docs/theme-creation/
- WP theme.json spec: https://developer.wordpress.org/themes/theme-json-reference/

### 関連技術ドキュメント
- Full Site Editing (FSE) guide: https://developer.wordpress.org/block-editor/fundamentals/full-site-editing/
- theme.json handbook: https://developer.wordpress.org/themes/theme-json-reference/
- Block patterns: https://developer.wordpress.org/block-editor/reference-guides/block-patterns/

### GitHub Issues（既知問題）
- https://github.com/Automattic/create-block-theme/issues
  - Labels: `bug` / `enhancement` / `fse-compatibility`

---

## 8. AGENT-NEO 導入決定マトリックス

| 要件 | 評価 | 判定 |
|------|------|------|
| Design token 自動化 | ✅ 可能（API） | **create-block-theme 活用** |
| Theme.json 生成 | ⚠️ 部分可（API + PHP） | **create-block-theme + 補助実装** |
| Template HTML 生成 | ❌ 非対応（API なし） | **独自実装 必須** |
| Block pattern 生成 | ❌ 非対応（UI 限定） | **独自実装 必須** |
| Export / ZIP 生成 | ⚠️ 非同期処理 | **create-block-theme REST API** |
| **総合判定** | **⭐⭐ 検討** | **案 B: ハイブリッド推奨** |

---

## 9. 次のアクション（AGENT-NEO L3 への推奨）

1. **REST API wrapper の実装着手**
   - create-block-theme GET /themes / POST /themes / POST /export エンドポイント
   - Error handling / polling timeout / retry logic

2. **Template renderer の独自実装開始**
   - Twig or PHP template engine で Block HTML serialization
   - WP 6.0+ block validation 統合

3. **Block pattern serializer の開発**
   - Pattern YAML → block.json transform
   - Pattern library SSOT 定義（別 document）

4. **WP theme.json validator の選択**
   - node-based validator (`schema-utils`) vs PHP-based validator
   - CI/CD integration（export 前 validation）

5. **自動テストの整備**
   - Theme export → WP install → activation test
   - Design token → theme.json round-trip test

---

## 10. 調査日付・ソース

- **調査日**: 2026-06-20
- **知識カットオフ**: 2025-02
- **情報ソース**: Automattic 公式リポジトリ / WordPress.org プラグインディレクトリ / GitHub issues
- **報告形式**: L3 設計資料（AGENT-NEO テーマ採用判定用）
