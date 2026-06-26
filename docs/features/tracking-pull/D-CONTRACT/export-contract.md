# tracking export contract（`GET /agent-neo/v1/tracking/export`）

## endpoint

- Method: `GET`
- Path: `/agent-neo/v1/tracking/export`
- 説明: AGENT-NEO が内部保持する計測イベントを、Automation SEO 側が pull で取得するための公開 read API。
- 契約バージョン: `schema_version=1`（前方互換）

## 認証・権限

- 認証: WordPress REST `check_read_permission`
- 条件: `is_user_logged_in() && current_user_can('edit_posts')`
- 既存の `read` 接続で取得できる権限に揃える。

## クエリパラメータ

| パラメータ | 型 | 必須 | 説明 |
|---|---|---|---|
| `after` | integer | 任意 | 直前レスポンスの `next_cursor` を再取得するためのカーソル。未指定時は先頭から取得する。
| `limit` | integer | 任意 | 1〜100 を許容。省略時は `100`。
| `event_type` | string | 任意 | カンマ区切り CSV で複数指定可。例: `ad_impression,viewable_impression,affiliate_click,scroll_depth`。完全一致でフィルタ。
| `since` | string (datetime) | 任意 | 受理タイム（`accepted_at`）の下限 ISO8601（例 `2026-06-01T00:00:00Z`）。指定より新しいイベントを返す。

## response schema

```json
{
  "schema_version": 1,
  "events": [
    {
      "event_id": 123456,
      "event_type": "ad_impression",
      "section_id": "hero",
      "cta_id": "cta-01",
      "variant_id": "default",
      "occurred_at": "2026-06-26T10:00:00+00:00",
      "canonical_url": "https://example.com/article/1",
      "metadata": {
        "viewport_ratio": 1.0,
        "scroll_depth": 100
      }
    }
  ],
  "next_cursor": 123456,
  "count": 1
}
```

### フィールド定義

| フィールド | 型 | 説明 |
|---|---|---|
| `schema_version` | integer | 互換性キー。現行固定値 `1`。変更時は version を上げる |
| `events` | array | 計測イベント配列 |
| `events[].event_id` | integer | イベント単位の自動採番 ID |
| `events[].event_type` | string | イベント種別 |
| `events[].section_id` | string | 計測対象セクション ID |
| `events[].cta_id` | string | 対象 CTA/Banner ID |
| `events[].variant_id` | string / null | 配信 variant の識別子 |
| `events[].occurred_at` | string | 発生時刻（ISO8601） |
| `events[].canonical_url` | string | 対象コンテンツ URL |
| `events[].metadata` | object | 追加情報（ページ種別・表示率・スクロール深度など）。consumer は未知キーを無視して前方互換を維持すること |
| `events[].metadata.page_type` | string / null | ページ種別。`home`（トップ）/ `lp`（ランディングページ）/ `page`（固定ページ）/ `post`（記事）/ `other`（アーカイブ・検索・404 等）のいずれか。クライアント JS が agentNeoTracking.pageType を受信できなかった場合はキー欠損となる（null 相当）。schema_version は据え置き（metadata 内追加のため version 変更不要）|
| `next_cursor` | integer | 次ページ取得時の `after` へ渡す値 |
| `count` | integer | 当該件数 |

> **セキュリティ注記（consumer 責務 / G6 carry-003）**: `metadata` 内の URL 系フィールド（例: `affiliate_click` の `metadata.href`）はサーバ側で `sanitize_text_field` を通すのみで URL スキーム検証は行わない。`javascript:` 等のスキームが格納され得るため、**consumer（Automation SEO）は href 等の URL 値を DOM／リンクとして展開する前に必ず `esc_url()` 相当のサニタイズを適用すること**。export は `edit_posts` 権限の認証下でのみ取得可能で、AGENT-NEO フロント側に当該値をレンダリングする経路は存在しない。

## event_type 取りうる値（実装観点）

- `ad_impression`
- `viewable_impression`
- `affiliate_click`
- `scroll_depth`
- 将来追加値は `schema_version` 互換性方針に従って追加する。

## pagination / カーソル仕様

- `after=0` または未指定: 先頭から取得。
- `next_cursor` を次回 `after` に渡すと継続取得。
- `count <= limit`。
- `event_type`・`since` フィルタは `event_queue` と `metadata` を加味して適用する。

## 再設計連携

- 今後の Automation SEO 側実装は本契約に合わせて pull 取得し、`schema_version` と `events` を解釈して処理する。
- REQ-NF-028 により PII 非保持を維持し、テナント確定は接続側（Automation SEO）で行う。
- 本契約変更は `D-PLUGIN-CONTRACT` 更新時と同時に追従し、上位契約と一致させる。
