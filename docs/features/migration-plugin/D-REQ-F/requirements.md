# migration-plugin — D-REQ-F（機能要件）

## 概要

`migration-plugin` は既存 WordPress サイト（SWELL / Cocoon / AFFINGER / JIN / Lightning を主要対象）から AGENT NEO の標準構造へコンテンツを移行するプラグインの機能要件を定義する。Plan A（REST 機械変換）と Plan B（AI フル再構築）の2プランを提供し、プレビュー・確認・適用・ロールバックの4ステップを強制する。

Automation SEO の LLMRouter と連携し、Plan B では AI によるセクション再設計と blueprint 生成を行う。移行プラグイン単体では診断・プレビューのみを提供し、apply 操作は AGENT NEO Companion Plugin を必要とする。

## ID 体系

| 接頭辞 | 対象 |
|---|---|
| `MF-` | migration-plugin 機能要件 |

## 対象テーマ別特性

| テーマ | 主な移行難易度 | 特記事項 |
|---|---|---|
| SWELL | 中 | `lp` CPT・ブログパーツ・再利用パーツの変換が必要。REST あり |
| Cocoon | 低〜中 | 無料テーマのため構造がシンプル。section_id 付与が主作業 |
| AFFINGER | 中〜高 | 設定が複雑で capability map が必要。CTA/shortcode 変換が多い |
| JIN / JIN:R | 中 | テーマ内 SEO メタの正規化が必要。classic template 前提 |
| Lightning | 中 | 法人 HP/LP・フォーム・事例ページの CTA/section 変換が必要 |

## 詳細要件

| ID | 要件名 | 説明 | 優先度 | 上位 L1 ID |
|---|---|---|---|---|
| MF-001 | プラン選択 UI | 移行開始時にユーザーが Plan A（REST 機械変換）または Plan B（AI フル再構築）を選択できる画面を提供する。各プランの適用条件・所要時間・リスクを説明する | P1 | REQ-F-008 |
| MF-002 | 移行元 WP REST 抽出 | 移行元サイトの WP REST API（`/wp/v2/posts`, `/wp/v2/pages`, `/wp/v2/media`, `/wp/v2/categories`）から投稿・メディア・分類・メニューを抽出する | P1 | REQ-F-008 |
| MF-003 | Plan A: REST 機械変換 | 抽出した WP 標準コンテンツを AGENT NEO の block.json 準拠のブロック構造に自動変換し、section_id / cta_id を付与する。変換できない要素はリストアップする | P1 | REQ-F-008 |
| MF-004 | Plan B: AI フル再構築 | Automation SEO LLMRouter に抽出コンテンツを渡し、ページ目的・ペルソナ・SEO 意図を分析して LP/HP/BLP blueprint を生成する。LLMRouter との通信契約を定義する | P2 | REQ-F-008, REQ-F-007 |
| MF-005 | 変換プレビュー | Plan A/B の変換結果を apply 前に確認できるプレビュー画面を提供する。元コンテンツと変換後の diff ビュー・未変換要素のリスト・section_id マッピングを表示する | P1 | REQ-F-008 |
| MF-006 | apply 実行 | プレビュー確認後に「適用する」ボタンで AGENT NEO Companion Plugin の `POST /jobs` を呼び、移行ジョブを非同期実行する。apply は AGENT NEO がある場合のみ有効 | P1 | REQ-F-008 |
| MF-007 | ロールバック | apply 前の状態を rollback point として保存し、`POST /rollback/{id}` で元のコンテンツ構造に戻せる。ロールバック対象範囲（全体/ページ別）を選択できる | P1 | REQ-F-008 |
| MF-008 | 進捗表示 | 移行ジョブの進捗（extract/transform/preview/apply の各ステップ）・処理済み件数・エラー件数をリアルタイムで表示する | P1 | REQ-F-008 |
| MF-009 | 検証ケース順序 | SWELL → Cocoon → AFFINGER → JIN → Lightning の順序で移行シナリオを検証し、テーマ別の変換品質マトリクスを作成する | P1 | REQ-F-008 |
| MF-010 | SEO メタ正規化 | 移行元テーマの SEO メタ（Yoast/Rank Math/テーマ内 SEO）を SEO Meta Normalizer で正規化し、AGENT NEO SEO Core 形式に変換する | P1 | REQ-F-007, REQ-F-008 |
| MF-011 | CTA/Offer 推定 | 移行元ページのリンク・ボタン・shortcode から CTA を推定し、`cta_id` と `offer_id` を付与する。confidence スコアを表示し、低スコアは manual review をマークする | P1 | REQ-F-008 |
| MF-012 | 単体動作モード | AGENT NEO Theme/Companion Plugin がない環境でも診断・抽出・プレビューが実行できる。apply ボタンは「AGENT NEO が必要」と表示する | P1 | REQ-F-008 |
| MF-013 | 段階的移行 | ページ全体を一括移行するだけでなく、特定投稿タイプ・カテゴリ・個別ページを選択して部分移行できる | P2 | REQ-F-008 |
| MF-014 | LLMRouter 連携契約 | Plan B で使用する LLMRouter API の入力（抽出コンテンツ + 指示 prompt）・出力（blueprint JSON + section 配置）・エラーハンドリングを contract として定義する | P2 | REQ-F-007, REQ-F-008 |

## 補足・設計指針

**SWELL → Cocoon → AFFINGER → JIN → Lightning の検証順序の意味**: SWELL は REST API と CPT を持つため変換候補が豊富で最初に検証しやすい。Cocoon は構造がシンプルでベースラインの確立に適している。AFFINGER/JIN は設定の複雑さの検証。Lightning は法人 LP 構造の変換検証に使う。

**Plan A と Plan B の判断基準**: ページの目的・元構造の整理度・予算・スピードによって選択する。Plan A は変換率が高く高速。Plan B は AI 再設計が入るため時間とコストがかかるが、AGENT NEO の LP/HP 標準構造に最適化される。

**Automation SEO LLMRouter 連携の責務分離**: migration-plugin は LLMRouter に対して「コンテンツ抽出結果と目的指示」を送信し、「blueprint JSON」を受け取る。LLMRouter の内部プロンプトや LLM 選択は migration-plugin の外部仕様とする。

**wp.org 申請品質**: 移行プラグインは wp.org 申請を前提とした品質水準とする。README.txt・ライセンスヘッダー・uninstall cleanup・エスケープ処理を必須化する。

## 移行フローの概要

```
1. 移行元 WP REST 抽出
   ↓ extract ジョブ
2. テーマ Adapter による変換（Plan A: 機械変換 / Plan B: LLMRouter）
   ↓ transform ジョブ
3. プレビュー画面（diff / 未変換要素 / confidence）
   ↓ ユーザー確認
4. apply（AGENT NEO Companion Plugin POST /jobs 経由）
   ↓ apply ジョブ
5. 検証（変換率レポート / SEO チェック）
   ↓ 問題あれば
6. ロールバック（rollback point から復旧）
```

## Plan A と Plan B の比較

| 観点 | Plan A（REST 機械変換） | Plan B（AI フル再構築） |
|---|---|---|
| 変換対象 | WP 標準ブロック・投稿・メディア | ページ目的・LP 構造・SEO 意図 |
| 所要時間 | 短時間（100件で数分） | 長時間（ページ分析と AI 生成） |
| 変換品質 | 元構造を維持。AGENT NEO 形式に自動マッピング | AGENT NEO 標準 LP/HP 構造に最適化 |
| コスト | 無料（REST 処理のみ） | LLMRouter トークンコスト発生 |
| 適用条件 | 既存構造が整理されている場合 | 抜本的な再設計が必要な場合 |

## 参照

- L1: REQ-F-007, REQ-F-008, REQ-NF-008, REQ-NF-014, REQ-NF-019, REQ-NF-020
- 解析レポート: 28-共通強化プラグイン（§1. 各テーマを強化できる共通プラグイン機能, §5. テーマ別に強化できるポイント）
