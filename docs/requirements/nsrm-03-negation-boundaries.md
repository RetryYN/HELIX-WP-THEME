# NSRM-03 否定境界（Negation Boundaries）

> 根拠: L0-planning.md / L1-requirements.md §5.2 対象外 / 各 REQ-F の制約記述
> 作成日: 2026-04-30
> ステータス: L1 Draft 段階の抽出。憶測が入る箇所は「※推定」を付記。

「AGENT NEO がやらないこと・なれないもの」を明示する。要件追加の検討時にこのリストを参照し、スコープ拡大を防ぐ。

---

## NEG-001: AGENT NEO は CMS ではない

```
対象: Drupal / Strapi / Contentful 等のヘッドレス CMS 機能
理由: WP テーマとして WordPress エコシステムの上に載る。CMS 機能は WordPress 自体が担う
関連 REQ-F: なし（暗黙）
根拠: L0 §1「WP テーマをAIエージェントが安全に操作できるようにする商用テーマ基盤」
関連 OPEN QUESTIONS: なし
```

## NEG-002: テーマ単体では AI を内蔵しない（Phase 1）

```
対象: 独自 LLM ホスティング / 内蔵 AI クレジットシステム
理由: LLM 原価をテーマ買い切り価格に抱え込まない（ADR-003）。Phase 1 では BYOK + Automation SEO + S1 のみ
関連 REQ-F: なし（Q-009 として open）
根拠: L1 §5.2「初版での独自 LLM 基盤開発」を対象外と明記。L0 §Q-009
関連 OPEN QUESTIONS: Q-009（Phase 2 で再評価）
```

## NEG-003: 外部 AI エディタからの直接書き込みを許可しない（デフォルト）

```
対象: Claude Computer Use / Codex CLI / Cursor / Cline / Continue 等の外部 AI エディタが wp/v2 等を経由して直接書き込む行為
理由: slot 制約 / page_type 予算 / 検証パイプラインを認識しない外部ツールによる構造破壊を防ぐ。CV 防衛・セキュリティ・持続可能性（REQ-F-042）
関連 REQ-F: REQ-F-042
根拠: L1 REQ-F-042「許可経路は agent-neo/v1 と aseo/v1 の 2 経路のみ」
関連 OPEN QUESTIONS: Q-010（Open Editor Bridge Plugin 月額価格・対応エディタ優先順位）
```

## NEG-004: 記事へのサンドボックスは必須化しない

```
対象: 記事 / BLP コンテンツへのプレビュー承認フロー必須化
理由: 記事は低ステークス・高頻度更新のため、サンドボックスを通さない軽量経路（WP 標準エディタ / agent-neo/v1 直接 PATCH / aseo/v1 経由）が設計原則（REQ-F-041）
関連 REQ-F: REQ-F-038, REQ-F-039, REQ-F-041
根拠: L0 §1.6「HP/LP/固定ページに限ってサンドボックス必須化、記事は軽量経路で済ませる」
関連 OPEN QUESTIONS: なし
```

## NEG-005: 参照テーマ（ThemeB / テーマA 等）のコード・画像・CSS・固有文言をコピーしない

```
対象: 既存 WP テーマの実装コード・スタイルシート・画像・固有 UI 文言の流用
理由: GPL 互換・著作権遵守（REQ-NF-003 / CR-009）。設計思想・情報設計の抽象化は許容するが、コード・見た目のコピーは禁止
関連 REQ-F: REQ-NF-003
根拠: L1 §5.2「参照テーマのコード、画像、CSS、固有デザインの流用」を対象外と明記。L2 ADR-004
関連 OPEN QUESTIONS: なし
```

## NEG-006: 完全な MA / CRM を初版で内蔵しない

```
対象: HubSpot / Salesforce 等と同等の MA 機能・CRM エンジンの内蔵
理由: テーマの責務範囲を超える。外部 CRM/MA との Webhook + REST 連携・Zapier adapter として扱う（REQ-F-015 は P1 別課金候補）
関連 REQ-F: REQ-F-015
根拠: L1 §5.2「初版での完全なMA/CRM内蔵」を対象外と明記
関連 OPEN QUESTIONS: なし
```

## NEG-007: WordPress.com（マルチサイト・Automattic 環境）への動作保証をしない

```
対象: WordPress.com ホスティング環境での動作保証
理由: FSE / theme.json / Companion Plugin の REST API 構成が WordPress.com の制限と整合しない可能性
関連 REQ-F: REQ-F-001
根拠: L1 §5.2「WordPress.com向け保証」を対象外と明記。L2 §1.2
関連 OPEN QUESTIONS: なし
```

## NEG-008: Theme Core は REST API・CPT・計測保存を持たない

```
対象: Theme Core（agent-neo-theme）が REST ルート・CPT・SEO 保存・計測保存・A/B を直接持つ構成
理由: WordPress.org Theme Review ポリシー違反回避・データ移植性・保守性。これらは Companion Plugin の責務（REQ-NF-008 / ADR-008）
関連 REQ-F: REQ-NF-008
根拠: L2 §2.4「Theme Core は FSE 表示層に限定」、L1 §6.2「機能境界」
関連 OPEN QUESTIONS: なし
```

## NEG-009: 個人版は HP / LP ブループリント操作を持たない

```
対象: 個人版ユーザーが HP / LP ブループリント API を使ってサイト構造を変更する行為
理由: 個人版は HP/sidebar/archive/single の固定テンプレート 1 セットのみ。構造変更は法人版のみ（REQ-F-016）。AI 操作スコープも記事 CRUD のみ（REQ-F-010）
関連 REQ-F: REQ-F-010, REQ-F-016
根拠: L1 REQ-F-016「ユーザー（および AI）はテンプレ構造を変更できない」
関連 OPEN QUESTIONS: Q-001（個人→法人アップグレード方式）
```

## NEG-010: フリーフォームブロックのページ本体コンテキストで未サニタイズ inline script を実行させない

```
対象: AI フリーフォーム HTML/CSS ブロックへのページ本体（light DOM / Shadow DOM static）コンテキストでの未サニタイズ inline <script> / on*= 属性記述・実行
理由: XSS・プロンプトインジェクション・未管理の外部スクリプト実行を防ぐ。
      JS 自体は禁止ではない。正規 JS はコンテキストにより以下の通り規律を分離する:
      【ページ本体コンテキスト JS】（light DOM ブロック・親側 postMessage リスナー等）:
        REQ-NF-001e（defer/async 必須・メインスレッドブロック禁止・minify+tree-shake 済み・1ブロック≤5KB目安）
        + セキュリティ隔離（別オリジン sandbox iframe / ADR-026 mode=interactive）の条件下で許可する。
      【mode=interactive の別オリジン iframe payload JS（sandbox-origin 配信）】:
        別オリジン iframe 内スクリプトは独立したドキュメント内で実行され、page 本体の defer/async と切り離される。
        REQ-NF-001e の defer/async 必須は親ページ JS に適用し、iframe 内スクリプトにはここに適用しない。
        代わりに「別オリジン iframe 隔離（allow-same-origin 不含 sandbox 属性）+ frame-src allowlist +
        loading="lazy" + page_type 性能予算カウント + INP / Long Task 実測（親ページ性能劣化が許容閾値内）+
        実行時間制限（requestIdleCallback / Web Worker 化 / タイムアウト設定）」で非ブロッキング契約を担保する。
        防御境界: 別オリジン iframe 隔離 + HTTP CSP（sandbox-origin サーバー配信）+ 親 frame-src allowlist +
        sandbox 属性（allow-same-origin を含まない）。allow-same-origin を含まないため event.origin は opaque（"null"）になり
        特定 origin 一致照合は機能しない。postMessage 検証主軸は event.source === iframe.contentWindow + nonce/payload-id（ADR-026 §postMessage）。
        cross-origin src の利点は HTTP レスポンスヘッダによる CSP 分離（親 CSP 非継承）であり origin が opaque でも有効。
        ※ 旧設計（srcdoc 属性 + CSP meta prepend）は廃止済み。
      （= 無駄JS禁止・JS絶対禁止ではない）
関連 REQ-F: REQ-F-036
根拠: L0 §1.4「フリーフォームブロック内の page 本体コンテキストで未サニタイズ inline script / on*= 禁止（JS 自体は性能規律+sandbox下で許可）」、L1 REQ-F-036「(7) JS 性能規律（REQ-NF-001e 準拠 + 別オリジン sandbox iframe 隔離下で許可 / 無駄JS禁止であってJS絶対禁止ではない）」
関連 OPEN QUESTIONS: なし
```

## NEG-011: GIF アニメーションを WebP / AVIF に変換しない

```
対象: アニメーション GIF の自動変換
理由: アニメーション性を保持するため。変換せず警告通知を出す（REQ-F-017）
関連 REQ-F: REQ-F-017
根拠: L1 REQ-F-017「GIF アニメは変換せず警告通知」、L0 §1.1「GIF: 禁止（アニメーション必要時は WebM / MP4）」
関連 OPEN QUESTIONS: なし
```

## NEG-012: Automation SEO（外部システム）が AI 自律最適化のオーケストレーションを担う

```
対象: AGENT NEO テーマ自体が AI 自律 A/B テストの LLM 呼び出し・統計判定・variant 生成を実行する
理由: AI 原価の分離。AGENT NEO は「実行基盤」を提供し、「最適化の判断」は Automation SEO の LLM が担う（ADR-003）
関連 REQ-F: REQ-F-024
根拠: L0 §1.3「AGENT NEO はこの自律ループの実行基盤を提供。実際の最適化自体は Automation SEO の LLM が判断」
関連 OPEN QUESTIONS: Q-009（Phase 2 の内蔵 AI クレジット）
```

## NEG-013: Automation SEO Theme Bridge Plugin は既存テーマへの深い自動書き込みをしない

```
対象: ThemeB / テーマA / テーマC 等の既存テーマへの構造的書き換え・safe apply 実行
理由: 既存テーマの DOM / CSS / SEO メタは安定 API ではない。診断・正規化・移行入口として preview-only に限定（REQ-NF-020）
関連 REQ-F: REQ-NF-020
根拠: L2 ADR-019「既存テーマでは原則 preview-only、AGENT NEO Core Plugin だけを safe apply の第一級書き込み先にする」
関連 OPEN QUESTIONS: なし
```

## NEG-014: テーマコア（agent-neo-theme）はデータを永続化しない

```
対象: テーマが CPT / post_meta / custom table にデータを保存すること
理由: テーマ無効化でデータが失われる設計を防ぐ。全永続化は Companion Plugin が担う（REQ-NF-008 / CR-010）
関連 REQ-F: REQ-NF-008
根拠: L1 §6.2「Companion Plugin は REST/MCP/WP CLI/CPT/SEO/計測/A-B/LP-HP Blueprint」、L2 CR-010
関連 OPEN QUESTIONS: なし
```

## NEG-015: 外部プラグイン（Yoast / CF7 / Elementor 等）を必須依存にしない

```
対象: 外部 SEO プラグイン / フォームプラグイン / キャッシュプラグインを必須依存とすること
理由: 依存度の最小化・プラグイン競合リスク低減（REQ-NF-010）。検出・共存・adapter 連携の対象として扱う
関連 REQ-F: REQ-NF-010
根拠: L1 REQ-NF-010「必須依存を AGENT NEO Theme + Core Plugin に限定」、L2 §2.5
関連 OPEN QUESTIONS: なし
```

## NEG-016: 個人版アフィリエイターの記事以外の構造（LP / HP 等）は AI が変更できない

```
対象: 個人版環境での AI による HP テンプレート / LP / design-tokens の変更
理由: 個人版の AI 操作スコープは「記事 CRUD のみ」に制限（REQ-F-010 / REQ-F-016）。保守コスト・テスト範囲を最小化する設計
関連 REQ-F: REQ-F-010, REQ-F-016
根拠: L1 §7「AI 操作スコープ: 個人版 = 記事 CRUD のみ」
関連 OPEN QUESTIONS: Q-001（個人→法人アップグレード）
```

## NEG-017: jQuery をデフォルトで読み込まない

```
対象: フロントエンドへの jQuery 標準 enqueue
理由: 第一原理「無駄な JavaScript を組まない」（REQ-NF-001a）。互換機能が必要な場合のみ feature flag で条件付き読み込み
関連 REQ-F: REQ-NF-001a
根拠: L2 §8.1「フロント jQuery は初期状態で無効」、CR-007
関連 OPEN QUESTIONS: なし
```

## NEG-018: 初版でマーケットプレイス / テーマプリセット販売を提供しない

```
対象: AGENT NEO 専用マーケットプレイス / 第三者テーマプリセット販売機能
理由: 初版スコープ外（将来対応）
関連 REQ-F: なし（暗黙）
根拠: L1 §5.3「将来対応: マーケットプレイス / テーマプリセット販売」
関連 OPEN QUESTIONS: なし（将来検討事項）
```

## NEG-019: Facebook / Pinterest / YouTube / TikTok を Phase 1 で必須 SNS 対応しない

```
対象: Facebook / Pinterest / YouTube / TikTok のシェア / 自動投稿 / 深い統合
理由: Phase 1 の必須 SNS は X / Instagram / Threads / LINE の 4 つ。上記は adapter 経由で Phase 2 対応（REQ-F-018）
関連 REQ-F: REQ-F-018
根拠: L1 REQ-F-018「Facebook / Pinterest / YouTube / TikTok は adapter で Phase 2 対応」
関連 OPEN QUESTIONS: なし
```

## NEG-020: ページスピードを犠牲にする機能追加を許容しない

```
対象: LCP / INP / CLS 予算を超過する機能追加。特に記事ページへの LP 向け JS 波及
理由: 第一原理「ページスピード最優先」（REQ-NF-001b）。ページタイプ別 JS 予算（REQ-NF-001f）を構造的に守る
関連 REQ-F: REQ-NF-001, REQ-NF-001b, REQ-NF-001f
根拠: L0 §1.1.1「記事だけ < 15KB 保証を CI で強制」、L1「機能追加時は CV への寄与を必須提示」
関連 OPEN QUESTIONS: なし
```

## NEG-021: SEO 効果を保証する表現を販売文言で使わない

```
対象: 「このテーマで検索順位が上がる」等の SEO 成果保証表現
理由: 景表法・薬機法遵守（REQ-NF-009）。SEO Core は手段を提供するが結果を保証しない
関連 REQ-F: REQ-NF-009
根拠: L2 §7.3「SEO保証表現禁止をレビュー対象にする」、L1 REQ-NF-009
関連 OPEN QUESTIONS: なし
```

## NEG-022: 個人情報を直接収集する計測設計をしない

```
対象: 氏名・メールアドレス・電話番号等の PII を計測イベントに含める設計
理由: データ保護（REQ-NF-004）。計測データは必要最小限・PII 非収集が基本設計
関連 REQ-F: REQ-NF-004
根拠: L1 REQ-NF-004「計測データは必要最小限とし、個人情報を直接収集しない設計を基本」
関連 OPEN QUESTIONS: なし
```

---

## NEG 件数サマリ

| ID | タイトル | 主根拠 |
|----|---------|--------|
| NEG-001 | CMS ではない | L0 §1 |
| NEG-002 | Phase 1 で AI 内蔵しない | L1 §5.2, Q-009 |
| NEG-003 | 外部 AI エディタの直接書き込みを拒否 | REQ-F-042 |
| NEG-004 | 記事サンドボックス必須化しない | REQ-F-041 |
| NEG-005 | 参照テーマのコード流用禁止 | REQ-NF-003, L1 §5.2 |
| NEG-006 | 完全 MA / CRM を初版で内蔵しない | L1 §5.2 |
| NEG-007 | WordPress.com 保証なし | L1 §5.2 |
| NEG-008 | Theme Core は REST / CPT / 計測を持たない | REQ-NF-008 |
| NEG-009 | 個人版は LP / HP 操作なし | REQ-F-010, REQ-F-016 |
| NEG-010 | フリーフォームブロックのページ本体コンテキストで未サニタイズ inline script 禁止（JS自体は性能規律+sandbox下で許可） | REQ-F-036 |
| NEG-011 | GIF アニメを変換しない | REQ-F-017 |
| NEG-012 | AI 自律最適化のオーケストレーションは Automation SEO | REQ-F-024 |
| NEG-013 | Theme Bridge は既存テーマへの自動書き込みなし | REQ-NF-020 |
| NEG-014 | Theme Core はデータを永続化しない | REQ-NF-008 |
| NEG-015 | 外部プラグインを必須依存にしない | REQ-NF-010 |
| NEG-016 | 個人版 AI は記事 CRUD のみ | REQ-F-010, REQ-F-016 |
| NEG-017 | jQuery はデフォルト無効 | REQ-NF-001a |
| NEG-018 | 初版でマーケットプレイスを提供しない | L1 §5.3 |
| NEG-019 | Facebook 等 SNS は Phase 2 | REQ-F-018 |
| NEG-020 | スピード予算を超える機能追加を許容しない | REQ-NF-001 |
| NEG-021 | SEO 効果保証表現を使わない | REQ-NF-009 |
| NEG-022 | PII を計測に含めない | REQ-NF-004 |

**合計: 22 件**
