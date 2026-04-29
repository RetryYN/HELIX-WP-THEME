# 40-OSSライブラリ技術転用候補

## 目的
SWELL/JIN:R/Automation SEOの解析で見えた課題を、AGENT NEOのテーマ本体、Core Plugin、Automation SEO連携、CI/運用品質ゲートに転用できるOSS・公式ツールへ落とす。

評価軸は、WordPress適合性、商用配布時の扱いやすさ、保守性、AIエージェント操作への寄与、導入コスト、検証自動化への寄与とする。

## 採用方針

| 方針 | 内容 |
|---|---|
| WordPress公式優先 | `@wordpress/*`、Theme Handbook、Coding Standards、Theme Checkを基準にする |
| テーマ本体は軽く | 重い計測・生成・外部連携はCore PluginまたはAutomation SEO側へ逃がす |
| 契約を先に作る | REST/OpenAPI、JSON Schema、section/CTA/schema registryを先に固定する |
| GPL境界を守る | 参考テーマのコード、CSS、画像、固有デザインを流用しない |
| AGPL系は慎重 | 商用配布物へ直接組み込む前にライセンス確認を必須にする |

## 候補一覧

| 領域 | 候補 | 用途 | 推奨 | 配置 |
|---|---|---|---|---|
| WP開発環境 | `@wordpress/env` | WPテスト環境の再現、WP/PHP行列の起点 | Adopt now | dev/CI |
| WPビルド | `@wordpress/scripts` | block.json、JS/CSS build、lint、format | Adopt now | Theme/Core Plugin |
| ブロック雛形 | `@wordpress/create-block` | FSE/Blockテーマの初期構造 | PoC | Theme |
| テーマ品質 | Theme Check | WordPressテーマ要件の機械確認 | Adopt now | CI |
| PHP規約 | WordPress Coding Standards | PHP/JS/CSS規約、escape/sanitizeの確認 | Adopt now | CI |
| PHP互換 | PHPCompatibilityWP | PHP 7.4-8.x互換確認 | Adopt now | CI |
| WP操作 | WP-CLI | テーマ切替、更新、設定export/import、smoke test | Adopt now | dev/CI |
| WPテスト | wp-browser | CodeceptionベースのWP統合テスト | PoC | Core Plugin |
| ブラウザE2E | Playwright | 認証済みエディタ、公開DOM、console、network | Adopt now | CI/runtime |
| 速度ゲート | Lighthouse CI | LighthouseをCIで予算化 | Adopt now | CI |
| 視覚差分 | BackstopJS | テーマ更新前後の見た目差分 | PoC | CI |
| HTML検証 | html-validate | 見出し、alt、aria、HTML品質 | PoC | CI |
| a11y補助 | axe-core / Pa11y | Lighthouse外のアクセシビリティ確認 | PoC | CI |
| PHP静的解析 | PHPStan | 型・未定義・到達不能コードの検出 | Adopt now | CI |
| WP型補助 | wordpress-stubs / phpstan-wordpress | WP関数・hook前提の解析補強 | Adopt now | CI |
| 自動改修補助 | Rector | PHPバージョン追随、古い記法の段階移行 | PoC | maintenance |
| JSON契約 | opis/json-schema | PHP側のJSON Schema検証 | Adopt now | Core Plugin |
| JS契約 | Ajv | 管理画面/ビルド時のJSON Schema検証 | Adopt now | build/admin |
| OpenAPI | swagger-php | REST契約のドキュメント生成 | PoC | Core Plugin |
| 構造化データ型 | schema-dts | JSON-LD設計の型参照 | Reference | docs/build |
| 開発時観測 | Query Monitor | DB/フック/テンプレート負荷の開発時確認 | Reference | local only |
| トレース | OpenTelemetry PHP | 将来のRUM/処理追跡 | PoC | Core Plugin/SaaS |
| SEO参考実装 | Yoast SEO | SEOデータモデル、schema graphの参考 | Reference only | design |

## テーマ側に持たせるべきもの

| 項目 | 理由 |
|---|---|
| `theme.json` | FSE/Global Styles/spacing/typography/colorを機械可読にする |
| `block.json` | AIエージェントがブロック仕様を安全に読み取れる |
| `patterns/` | LP/HP/affiliateの再現性あるsection設計に向く |
| `data-agent-section-id` | DOM推測ではなく、section単位で読み書き対象を特定する |
| `data-cta-id` | CTA改善、AB、クリック計測、Automation SEO連携の接点になる |
| `schema_role` | JSON-LDと可視コンテンツの同期を検査できる |
| asset budget | 画像、JS、CSS、third-partyタグの上限をテーマ設定で制御する |

## Core Plugin側に持たせるべきもの

| 項目 | 理由 |
|---|---|
| REST/OpenAPI | AI/外部連携の契約をテーマ表示層から分離する |
| JSON Schema | section、CTA、SEO meta、entity graph、experimentの型を固定する |
| WP-CLI | CI、移行、更新、差分監査をCLIから再現できる |
| audit log | AI/人間の変更履歴を追える |
| dry-run/diff/rollback | 更新系操作を即時反映せず、確認可能にする |
| adapter | SWELL型block.jsonテーマ、JIN:R型classic/customizerテーマの両方を読める |
| conflict detector | SEOプラグイン、キャッシュ、フォーム、計測タグとの重複を検出する |

## Automation SEO側に寄せるべきもの

| 項目 | 理由 |
|---|---|
| keyword/search intent | テーマの責務外。外部データとAI処理が必要 |
| content plan | 生成/更新頻度/競合分析はSaaS側で回収する |
| LLMO audit | answer unit、entity graph、引用候補、AI検索露出は継続観測が必要 |
| field data | CrUX/PSI/RUM、週次差分、アラートは運用サービス向き |
| experiment analysis | AB/CRO/CTA改善の統計処理はテーマに抱え込まない |

## すぐ採用する技術セット

| 優先 | セット | 内容 |
|---|---|---|
| P0 | WP公式ビルドセット | `@wordpress/env`、`@wordpress/scripts`、Theme Check、WPCS、WP-CLI |
| P0 | 実測CIセット | Playwright、Lighthouse CI、HTML/a11y補助、visual diff |
| P0 | 契約検証セット | JSON Schema、OpenAPI、schema registry、section/CTA registry |
| P1 | PHP品質セット | PHPStan、wordpress-stubs、phpstan-wordpress、PHPCompatibilityWP |
| P1 | 運用観測セット | Query Monitor、RUM設計、OpenTelemetry PHP PoC |
| P2 | SEO参考セット | Yoast SEOのschema graph思想、schema-dts、Google/Schema.org validator運用 |

## 採用しない/注意するもの

| 種別 | 判断 |
|---|---|
| 参考テーマのCSS/画像/固有JS | 不採用。設計抽象だけを取り込む |
| 重いページビルダー依存 | 不採用。AIエージェントが構造把握しにくく、速度予算も崩れやすい |
| テーマ本体へのAI生成機能内包 | 不採用。AI原価と安全な運用契約はAutomation SEO/Core Plugin側で扱う |
| SEOプラグイン必須化 | 不採用。共存検出とadapterに留める |
| AGPL系の同梱 | 要確認。SaaS側・開発ツール側に隔離する方が安全 |

## AGENT NEO初期バックログ

1. `theme.json` + `block.json` + `patterns/` を前提にした最小FSEテーマを作る。
2. Core Pluginに `GET /agent-neo/v1/manifest`、`GET /sections`、`GET /seo/profile`、`POST /audit/page` の読み取り系を先に作る。
3. 更新系は最初から `dry_run=true`、diff、rollback token、audit logを必須にする。
4. CIに Theme Check、WPCS、PHPCompatibilityWP、PHPStan、Playwright、Lighthouse CIを入れる。
5. プラグイン寄与分解の測定ジョブを作り、SWELL/JIN:R運用差分を再現できるようにする。
6. WP7 RC/final用の別composeを作り、現行環境を壊さずに行列検証する。

## 参照ソース

| 用途 | URL |
|---|---|
| WordPress Theme Handbook | https://developer.wordpress.org/themes/ |
| `@wordpress/env` | https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/ |
| `@wordpress/scripts` | https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/ |
| Theme Check | https://github.com/WordPress/theme-check |
| WordPress Coding Standards | https://github.com/WordPress/WordPress-Coding-Standards |
| Lighthouse CI | https://github.com/GoogleChrome/lighthouse-ci |
| Playwright | https://github.com/microsoft/playwright |
| PHPStan | https://github.com/phpstan/phpstan |
| opis/json-schema | https://github.com/opis/json-schema |

## Gate

| Gate | Result | Notes |
|---|---|---|
| V-L4 | pass_with_caution | 採用候補は整理済み。ライセンス詳細は導入時に個別確認 |
| RG1 | pass | 解析結果から必要ツールのカテゴリを復元 |
| RG2 | pass | Theme/Core Plugin/Automation SEO/CIへ責務分離 |
| R4 | pass | 39の実測漏れを埋める技術候補として接続 |
