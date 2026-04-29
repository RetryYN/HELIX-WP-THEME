# JSON操作基盤設計

## 目的

AIエージェントがWPテーマを安全に解析・生成・変更できるように、操作対象をJSON契約で固定する。AIに自由なファイル編集をさせるのではなく、許可されたaction、対象path、schema、dryRun、diffReviewを必須にする。

## JSONファイル構成

| ファイル | 役割 |
|---|---|
| `theme-manifest.json` | テーマ名、対応WP/PHP、package、feature flags |
| `package.matrix.json` | Core/個人/法人の有効機能 |
| `design-tokens.json` | 色、フォント、余白、影、角丸、ブレークポイント |
| `layout-registry.json` | header/footer/single/archive/lpの構成 |
| `block-registry.json` | ブロック名、属性、package、render方式 |
| `component-contracts.json` | テンプレート部品の入力/出力 |
| `agent-actions.schema.json` | AIが実行できる操作契約 |
| `analysis-report.json` | 解析結果の機械可読サマリ |

## theme-manifest.json案

```json
{
  "schemaVersion": "0.1.0",
  "theme": {
    "name": "agent-neo-theme",
    "wpMinVersion": "6.5",
    "phpMinVersion": "8.1",
    "textDomain": "agent-neo"
  },
  "package": "personal_affiliate",
  "features": {
    "affiliate": true,
    "corporateLp": false,
    "tracking": true,
    "reusableParts": true,
    "rolePermissions": false
  }
}
```

## block-registry.json案

```json
{
  "blocks": [
    {
      "name": "agent-neo/faq",
      "package": ["core", "personal_affiliate", "corporate_lp"],
      "attributes": {
        "question": { "type": "string", "required": true },
        "answer": { "type": "string", "required": true },
        "schemaEnabled": { "type": "boolean", "default": true }
      }
    },
    {
      "name": "agent-neo/product-card",
      "package": ["personal_affiliate", "corporate_lp"],
      "attributes": {
        "title": { "type": "string", "required": true },
        "description": { "type": "string" },
        "imageId": { "type": "number" },
        "ctaLabel": { "type": "string" },
        "ctaUrl": { "type": "string", "format": "uri" }
      }
    }
  ]
}
```

## agent-actions.schema.json案

```json
{
  "actions": [
    {
      "name": "analyze_theme",
      "allowedTargets": ["theme/**"],
      "writesFiles": false,
      "requiresDryRun": false
    },
    {
      "name": "generate_block",
      "allowedTargets": ["blocks/**"],
      "writesFiles": true,
      "requiresDryRun": true
    },
    {
      "name": "update_design_tokens",
      "allowedTargets": ["config/design-tokens.json"],
      "writesFiles": true,
      "requiresDryRun": true
    },
    {
      "name": "compose_lp_template",
      "allowedTargets": ["templates/lp/**", "config/layout-registry.json"],
      "writesFiles": true,
      "requiresDryRun": true
    }
  ]
}
```

## 操作フロー

```text
User Request
  -> intent分類
  -> agent action JSON生成
  -> schema validation
  -> dryRun
  -> diff生成
  -> human/agent review
  -> apply
  -> test/verification
  -> report JSON更新
```

## 許可アクション候補

| action | 内容 | 初期対応 |
|---|---|---|
| `analyze_theme` | 既存テーマ構造を読む | yes |
| `compare_themes` | 親/子/別テーマを比較 | yes |
| `extract_design_tokens` | CSS/設定からトークン候補抽出 | yes |
| `generate_block` | block.json/PHP/render/CSSを生成 | yes |
| `generate_template_part` | header/footer/single/lp部品を生成 | yes |
| `compose_lp_template` | LPセクションをJSONから構成 | yes |
| `update_package_matrix` | package.matrixを更新 | yes |
| `run_verification` | lint/test/schema checkを実行 | yes |
| `delete_file` | ファイル削除 | 初期はno |
| `modify_rest_endpoint` | RESTの追加/変更 | yes。ただしsecurity review必須 |

## 安全制約

| 制約 | 内容 |
|---|---|
| path allowlist | `theme/` 配下など許可範囲だけ操作 |
| dryRun必須 | 書き込み系actionは事前差分を必ず出す |
| schema validation | JSON契約に合わない操作は拒否 |
| capability check | WP管理操作は権限確認必須 |
| nonce | REST更新系はnonce必須 |
| rate limit | 計測POSTは濫用対策必須 |
| audit log | 法人向けは設定変更履歴を保存 |

## 設計に生かすポイント

| ポイント | 内容 |
|---|---|
| block.json中心 | AIが属性を読んでブロック生成しやすい |
| tokens中心 | 見た目を直接CSS編集せずJSONで制御 |
| package matrix | 個人/法人の差分を条件分岐ではなく宣言で管理 |
| layout registry | LPや記事テンプレートをJSONから再構成 |
| report JSON | 解析結果をAIが再利用できる |

