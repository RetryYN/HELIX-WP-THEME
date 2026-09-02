# tracking-pull — D-REQ-F（機能要件）

## 概要

`tracking-pull` は、CTA / バナーの計測イベントを**問い合わせ型（PULL）**で公開する feature である。

従来の push / relay 構成ではなく、計測イベントは AGENT-NEO 側で蓄積し、Automation SEO 側が `GET /agent-neo/v1/tracking/export` を定期的に pull して取得する。これによりバックエンドの push 認証・本番 relay を不要化し、テナント境界の確定を Automation SEO 側で行う契約を採用する。

> **2026-06-26 追記**: ADR-030 で PULL 採用を承認。REQ-F-048 / REQ-NF-028 を成立させる計測公開基盤を提供する。

## ID 体系

| 接頭辞 | 対象 |
|---|---|
| `TP-` | tracking-pull 機能要件 |

## 機能要件

| ID | 要件名 | 説明 | 優先度 | 上位 L1 ID |
|---|---|---|---|---|
| TP-001 | CTA 計装（render_block） | `class-cta-instrumenter.php` により CTA/Banner ブロックの render 時に `data-agent-neo-*` 属性を付与する。`data-agent-neo-affiliate` / `data-cta-id` / `data-variant-id` / `data-agent-neo-ad` / `data-ad-type` を埋め、`section_id` とイベント識別に必要な `cta_id`, `variant_id` を付ける。 | P0 | REQ-F-048 |
| TP-002 | enqueue + token 配備 | front で tracking JS を enqueue し、`endpoint`, `siteToken`, `hmacKey`, `sectionId`, `consentKey` をローカライズする。`siteToken` と `hmacKey` は option 未設定時に生成保存する。 | P0 | REQ-F-048 |
| TP-003 | consent gate / 同意制御 | tracking.js は `agent_neo_consent_v2` を参照し、`consent.analytics_storage === 'granted'` の場合のみ送信する。`granted` 以外は送信しない。 | P0 | REQ-NF-028 |
| TP-004 | tracking export read 口 | `GET /agent-neo/v1/tracking/export` を公開し、`after / limit / event_type / since` を受けて `schema_version`, `events`, `next_cursor`, `count` を返却する。Automation SEO は queryable な read 契約として pull 取得する。 | P0 | REQ-F-048, REQ-NF-028 |

## 設計指針

- **PULL を既定化**: AGENT-NEO は集約・保持を担い、export が公開インターフェース。消費者（Automation SEO）は pull でスキーマを解釈し、テナント決定は消費者側で実施する。
- **契約優先**: tracking/export のペイロード・ページング・フィルタは実装クラスから抽出した仕様で固定し、上位契約（`D-CONTRACT/export-contract.md`）に一本化する。
- **PII を送らない**: 計測イベントは `event_id / event_type / section_id / cta_id / variant_id / occurred_at / canonical_url / metadata` のみを対象とする。
- **既存契約整合**: AI 判定（variant 選択、CTR 判定、統計判定）は Automation SEO 側の責務。AGENT-NEO の計測 feature は配信・保持・read 契約の公開に限定する。

## 参照

- L1: REQ-F-048, REQ-NF-028
- ADR: ADR-030
- 設計仕様: tracking-pull-loop-spec.md, class-tracking-export-controller.php, class-cta-instrumenter.php, ad-tracking.js
