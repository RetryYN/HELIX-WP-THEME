# stripe-payment — D-REQ-F（機能要件）

## 概要

`stripe-payment` は AGENT NEO の決済導線を提供する feature である。MVP は Stripe ホスト型 Payment Link / Checkout へのリンク埋め込みに限定し、決済処理そのものは Stripe 側へ完全委任する。テーマ本体には実装せず、`plugin` が責務を持つ（PF-006 準拠）。

> **2026-06-26 / ADR-029**: Stripe ホスト型決済を Companion Plugin のブロックで実装し、API 鍵やカード情報、checkout session/webhook の自前実装は採らない方針を採択。

## ID 体系

| 接頭辞 | 対象 |
|---|---|
| `SP-` | stripe-payment 機能要件 |

## 機能要件テーブル

| ID | 要件名 | 説明 | 優先度 | 上位 L1 ID |
|---|---|---|---|---|
| SP-001 | URL バリデータ（純 PHP） | `plugins/agent-neo-core/inc/payment-link.php` に `agent_neo_core_is_stripe_payment_url( string $url ): bool` を追加し、`parse_url` ベースで https / user/pass 無し / host 完全一致 (`buy.stripe.com`,`checkout.stripe.com`) を検証する。WP 依存を持たず純 PHP で unit test 可能にする。 | P1 | REQ-F-047 |
| SP-002 | payment-link ブロック定義 | `plugins/agent-neo-core/blocks/payment-link/block.json` を追加し、`apiVersion`/`name`/`title`/`category`、属性（`paymentUrl`/`label`/`note`）、`render: file:./render.php` を定義する。 | P1 | REQ-F-047 |
| SP-003 | server-side render | `plugins/agent-neo-core/blocks/payment-link/render.php` で決済 URL 検証結果が true の場合のみボタンを出力する。`href` は `esc_url`、文言は `esc_html` を用い、`rel="noopener nofollow"` を付与する。非許可 URL は何も出力しない。 | P1 | REQ-F-047 |
| SP-004 | スタイル定義 | `plugins/agent-neo-core/blocks/payment-link/style.css` で決済ボタン・補助文言（ノート）の最小見た目を定義する。既存テーマのアクセント系に馴染む配色・論理プロパティを用い、保守しやすい最小スタイルとする。 | P2 | REQ-F-047 |

## 設計指針（仕様書 §0 / §2）

### §0（意思決定）への追従

- MVP は **Stripe ホスト型リンク埋め込みのみ**（`Payment Link` / `Checkout URL`）。
- **シークレット鍵・カード情報・決済 API・Webhook はテーマ/プラグインに持たない。** Stripe 側（buy/checkout）で完結。
- `PF-006`（Theme Core/Companion Plugin 分離）に合わせ、ブロック所有は Companion Plugin 側。
- `REQ-NF-025`（AI ロジック分離）を守るため、判定ロジックは「URL 形式の静的検証」に限定。

### §2（実装構成）への追従

- ブロック定義は `block.json` 登録。編集 JS は追加実装しない。
- サーバレンドリングは `render.php` で完結し、`block.json` の `render` を使用する。
- URL 検証は純関数化してテストしやすくし、`plugins/agent-neo-core` 単体で検証可能にする。

## 参照

- L1: REQ-F-047 / REQ-NF-027
- ADR: ADR-029
- 設計仕様正本: `stripe-payment-design-spec.md` §3, §4
