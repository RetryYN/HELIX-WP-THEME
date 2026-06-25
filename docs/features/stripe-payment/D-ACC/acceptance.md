# stripe-payment — D-ACC（受入条件）

## 概要

`stripe-payment` の受入条件は、Stripe ホスト型リンク導線を Companion Plugin が安全に提供することを確認する。AC は `SP-ACC-001` 〜 `SP-ACC-005` とし、`AC3` は URL 許可/拒否ケースを明示した境界検証を要求する。

## 受入条件テーブル

| ID | 対応要件 | テスト条件 | 期待結果 | 測定方法 |
|---|---|---|---|---|
| SP-ACC-001 | REQ-F-047, SP-001, SP-002 | エディタに `agent-neo/payment-link` ブロックが登録される | ブロック名、`Payment URL`、`Label`、`Note` の入力項目が表示される | ブロック挿入手順 |
| SP-ACC-002 | REQ-F-047, SP-003 | 記事 / 固定ページに Payment URL とボタン文言を設定して保存 | フロントで `<a href="...">` として Stripe ホスト決済ページへ遷移するボタンが出力される | DOM / 表示確認 |
| SP-ACC-003 | REQ-NF-027, SP-001/003 | URL 許可/拒否の境界値でレンダリングを検証 | 許可: 遷移可能、拒否: 何も出力される（ボタン非表示）。許可/拒否の判定網羅は以下参照 | 単体テスト |
| SP-ACC-004 | REQ-NF-027, SP-003 | `plugins/agent-neo-core/blocks/payment-link/render.php` の出力を確認 | `esc_url`/`esc_html`/`get_block_wrapper_attributes` が使用され、`sk_live`/`sk_test`/`secret` / カード情報 / webhook 実装が混入しない | grep + 監査 |
| SP-ACC-005 | REQ-NF-027-b, REQ-F-047 | VDD 要件に沿って `composer test:unit`（含む SP001）/ `composer test:security` を実行 | 全テスト緑、テーマ非変更領域の `php -l` 及び `check-theme-quality.sh` が失敗しない | テスト実行 |

## AC3 許可 / 拒否ケース一覧

### 許可ケース

- `https://buy.stripe.com/aEU3cd...`
- `https://buy.stripe.com/test_8wM00...`
- `https://checkout.stripe.com/c/pay/cs_test_...`

### 拒否ケース

- 空文字
- `http://buy.stripe.com/x`
- `https://evil.com/x`
- `https://buy.stripe.com.evil.com/x`
- `https://buy.stripe.com@evil.com/x`
- `https://sub.buy.stripe.com/x`
- `javascript:alert(1)`
- `ftp://buy.stripe.com/x`

## 受入条件のカバレッジ

| 要件 | ACC ID |
|---|---|
| REQ-F-047 決済リンク導線 | SP-ACC-001, SP-ACC-002 |
| REQ-NF-027 鍵非保持・URL制限 | SP-ACC-003, SP-ACC-004 |
| 4/4 テスト合格（VDD） | SP-ACC-005 |

## 参照

- L1: REQ-F-047, REQ-NF-027
- ADR: ADR-029
- 設計仕様正本: `stripe-payment-design-spec.md` §3, §4
