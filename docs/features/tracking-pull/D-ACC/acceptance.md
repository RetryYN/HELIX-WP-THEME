# tracking-pull — D-ACC（受入条件）

## 概要

`tracking-pull` の受入条件は、CTA/Banner の `data-*` 付与、consent-gated の送信制御、`tracking/export` の read 契約、既存接続権限での取得性を検証する。

## 受入条件

| ID | 対応要件 | テスト条件 | 期待結果 | 測定方法 |
|---|---|---|---|---|
| TP-ACC-001 | TP-001 | CTA/バナーを含む記事を表示し DOM を取得 | 計測対象要素に `data-agent-neo-affiliate` または `data-agent-neo-ad` が付与され、`data-cta-id` が section/slot 関係と整合している | フロントDOM検証 |
| TP-ACC-002 | TP-003 | `agent_neo_consent_v2.analytics_storage=denied` でページを開いてイベント送信を観測 | 送信が 0 件、ネットワーク送信が発生しない | network panel + JS テスト |
| TP-ACC-003 | TP-003, TP-004 | `agent_neo_consent_v2.analytics_storage=granted` でページを開きイベント発生（impression / click / scroll） | consent true 時のみ送信実行され、`event_type` ごとの payload が export 対象仕様を満たす形で enqueue / 保持される | 実ページ観測 + export 取得確認 |
| TP-ACC-004 | TP-004 | `GET /agent-neo/v1/tracking/export?after=0&limit=20` を既存接続権限で実行 | `schema_version=1`、`events[]`、`next_cursor`、`count` が返る。after/since/event_type 指定で結果が変化する | REST API 検証 |
| TP-ACC-005 | REQ-F-048 | `GET /agent-neo/v1/tracking/export` を read 接続権限で呼ぶ | `edit_posts` 所有ユーザーは成功し、未認証/権限不足では 401/403 系を返す | 権限テスト |

## 受入条件のカバレッジ

| 要件 | ACC ID |
|---|---|
| REQ-F-048 | TP-ACC-001, TP-ACC-004, TP-ACC-005 |
| REQ-NF-028 | TP-ACC-002, TP-ACC-003 |
| TP-001 | TP-ACC-001 |
| TP-002 | TP-ACC-003 |
| TP-004 | TP-ACC-004 |
