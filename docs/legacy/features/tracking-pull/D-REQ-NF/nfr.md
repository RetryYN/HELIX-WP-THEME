# tracking-pull — D-REQ-NF（非機能要件）

## 概要

`tracking-pull` の非機能要件は、計測データ取得の同意制御、PII 非保持、認証境界、鍵運用の明確化を担保する。

## 非機能要件定義

| ID | 要件名 | 観点 | 説明 | 優先度 | 上位 L1 ID |
|---|---|---|---|---|---|
| REQ-NF-028-a | consent-gated 送信 | 同意制御 | `agent_neo_consent_v2.analytics_storage === 'granted'` 以外では tracking.js から送信しない。未同意時はイベント生成・送信のどちらも発火しない。 | P0 | REQ-NF-028, TP-003 |
| REQ-NF-028-b | PII 非保持 | データ最小化 | export では個人特定情報を返さない。`metadata` は CTA/バナー計測に必要な最小属性のみ保持し、メール・IP・UA など利用者識別情報は保存対象外とする（保存経路に存在しないことを実装上担保）。 | P0 | REQ-NF-028, REQ-F-048 |
| REQ-NF-028-c | token/hmac 運用 | 鍵管理境界 | `siteToken` と `hmacKey` は controller 側で生成し、`options` に保存。JS 側では署名用キーをそのまま埋め込む実装仕様（client-side HMAC は秘匿目的ではなく整合性担保の実務上の値）として明記する。 | P0 | TP-002 |

## 補足

- `TP-001` / `TP-002` / `TP-003` の実装は `client` と `server` の責務分離を前提とし、AI 判定ロジックは含めない。
- 受け取り契約は `D-CONTRACT/export-contract.md` で固定し、互換性変更時は `schema_version` 遷移として管理する。
