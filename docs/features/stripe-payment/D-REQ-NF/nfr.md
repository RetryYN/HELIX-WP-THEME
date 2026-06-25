# stripe-payment — D-REQ-NF（非機能要件）

## 概要

`stripe-payment` の非機能要件は、Stripe ホスト型決済導線の最小限で安全な実装を保証する。重点は 4 点。

- REQ-NF-027 の鍵非保持・PCI SAQ-A 境界維持
- URL 限定検証（`buy.stripe.com`/`checkout.stripe.com` のみ許可）
- `PF-006`（Theme Core / Companion Plugin 境界）整合
- `REQ-NF-025`（AI ロジック分離）整合

## 非機能要件の分類

| 観点 | 要件 ID | 件数 |
|---|---|---|
| セキュリティ | REQ-NF-027-a | 1 |
| 運用品質 | REQ-NF-027-b | 1 |
| 契約遵守 | REQ-NF-027-c | 1 |

## 詳細要件

| ID | 要件名 | 観点 | 説明 | 優先度 | 上位 L1 ID |
|---|---|---|---|---|---|
| REQ-NF-027-a | URL 許可ドメイン限定 | セキュリティ | `https://buy.stripe.com` と `https://checkout.stripe.com` のみ許可し、http、subdomain、userinfo 混入、`javascript:`、空文字、他ドメインを拒否する。`buy.stripe.com.evil.com`、`buy.stripe.com@evil.com`、`sub.buy.stripe.com` は拒否対象。 | P0 | REQ-NF-027 |
| REQ-NF-027-b | 鍵・カード・API 情報非保持 | 運用品質 | `plugins/agent-neo-core` に `sk_live` / `sk_test` / API シークレットキー、`stripe.api` 呼び出し、カード入力フォーム、Webhook 受け口を持たせない。シークレット情報の検索ヒットゼロとし、PCI スコープを SAQ-A 想定に維持する。 | P0 | REQ-NF-027 |
| REQ-NF-027-c | 責務境界維持 | 契約遵守 | Stripe 決済表示ブロックは Companion Plugin が所有し、Theme Core は表示/配置に限定。`block.json` + `render.php` 以外の Payment/請求ロジックは theme 配下に持たない。 | P0 | REQ-F-047 |

## 検証方針

| 要件 | 検証方法 |
|---|---|
| URL 検証 | 単体テスト（許可 / 拒否ケース）を `SP001_PaymentLinkUrlTest.php` で実行 |
| 鍵非保持 | grep / grep -Ri / `grep -riE 'sk_live|sk_test|stripe.*api' plugins/agent-neo-core/blocks/payment-link` が空であること |
| 境界維持 | `PF-006` 準拠レビュー（Theme Core と Companion Plugin 責務の分離） |

## 参照

- L1: REQ-NF-027, REQ-NF-025, REQ-NF-008
- ADR: ADR-029
- 設計仕様正本: `stripe-payment-design-spec.md` §0, §3
