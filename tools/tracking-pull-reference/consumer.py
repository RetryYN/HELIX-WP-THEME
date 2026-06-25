"""
tracking-pull-reference / consumer.py

Automation SEO 再設計側が採用する「消費者リファレンス実装」。
依存ゼロ（Python 標準ライブラリのみ）。

【責務境界】
  純粋関数（テスト対象）: map_event_type / transform_export_to_ingest /
                          next_after_cursor / dedup_by_event_id
  薄い IO シェル（実運用例）: pull_once / run_pull_loop

【対応 contract】
  docs/features/tracking-pull/D-CONTRACT/export-contract.md
  schema_version: 1
"""

from __future__ import annotations

import json
import math
import urllib.error
import urllib.parse
import urllib.request
from typing import TYPE_CHECKING

if TYPE_CHECKING:
    pass

# ---------------------------------------------------------------------------
# event_type → event_kind 変換マップ
# ---------------------------------------------------------------------------
_EVENT_KIND_MAP: dict[str, str] = {
    "ad_impression": "engagement",
    "viewable_impression": "engagement",
    "view_time": "engagement",
    "impression": "engagement",
    "affiliate_click": "click",
    "click": "click",
    "scroll_depth": "scroll",
    "conversion": "conversion",
}

# 未知 event_type に適用するフォールバック
_FALLBACK_KIND = "engagement"

# export 契約で定義された既知 schema_version
_SUPPORTED_SCHEMA_VERSIONS = {1}

# 1 バッチあたりの最大件数（backend ingest 側制約に揃える）
_BATCH_SIZE = 100


# ---------------------------------------------------------------------------
# 純粋関数（テスト対象・ネットワーク不要）
# ---------------------------------------------------------------------------


def map_event_type(event_type: str) -> str:
    """AGENT-NEO の event_type を backend event_kind に変換する。

    変換マップ:
      ad_impression        → engagement
      viewable_impression  → engagement
      view_time            → engagement
      impression           → engagement
      affiliate_click      → click
      click                → click
      scroll_depth         → scroll
      conversion           → conversion
      <未知>               → engagement （fail-safe）

    Args:
        event_type: AGENT-NEO が export する event_type 文字列。

    Returns:
        backend が受け付ける event_kind 文字列。
    """
    return _EVENT_KIND_MAP.get(event_type, _FALLBACK_KIND)


def transform_export_to_ingest(export_response: dict) -> list[dict]:
    """export レスポンス dict を backend ingest 形リスト（バッチ分割済み）に変換する。

    出力形式（バッチ 1 件分）:
    {
        "events": [
            {
                "event_kind":    str,   # map_event_type で変換
                "canonical_url": str,
                "occurred_at":   str,  # ISO8601
                "payload": {
                    "section_id":          str,
                    "cta_id":              str,
                    "variant_id":          str | None,
                    "metadata":            dict,
                    "original_event_type": str,
                    "event_id":            str | int,
                }
            },
            ...
        ]
    }

    Args:
        export_response: GET /agent-neo/v1/tracking/export のレスポンス dict。

    Returns:
        バッチ単位のリスト。各要素が 1 回の ingest 呼び出しに対応する。
        100 件超の場合は自動で複数バッチに分割する。

    Raises:
        ValueError: schema_version が未知（未サポート）の場合。
        ValueError: events フィールドが配列でない場合。
    """
    schema_version = export_response.get("schema_version")
    if schema_version not in _SUPPORTED_SCHEMA_VERSIONS:
        raise ValueError(
            f"未サポートの schema_version: {schema_version!r}。"
            f"サポート対象: {sorted(_SUPPORTED_SCHEMA_VERSIONS)}"
        )

    raw_events = export_response.get("events", [])
    if not isinstance(raw_events, list):
        raise ValueError(
            f"events は配列である必要があります。実際の型: {type(raw_events).__name__}"
        )

    # 各イベントを ingest 形に変換
    converted: list[dict] = []
    for evt in raw_events:
        event_type = evt.get("event_type", "")
        converted.append(
            {
                "event_kind": map_event_type(event_type),
                "canonical_url": evt.get("canonical_url", ""),
                "occurred_at": evt.get("occurred_at", ""),
                "payload": {
                    "section_id": evt.get("section_id", ""),
                    "cta_id": evt.get("cta_id", ""),
                    "variant_id": evt.get("variant_id"),
                    "metadata": evt.get("metadata", {}),
                    "original_event_type": event_type,
                    "event_id": evt.get("event_id", ""),
                },
            }
        )

    # 100 件単位でバッチ分割
    if not converted:
        return [{"events": []}]

    batch_count = math.ceil(len(converted) / _BATCH_SIZE)
    batches: list[dict] = []
    for i in range(batch_count):
        start = i * _BATCH_SIZE
        end = start + _BATCH_SIZE
        batches.append({"events": converted[start:end]})

    return batches


def next_after_cursor(export_response: dict) -> "str | None":
    """増分 pull 用に次回 after パラメータとして渡すカーソル値を返す。

    next_cursor が存在し、かつ count == limit の場合のみ継続あり。
    count < limit の場合は末尾に到達しているため None を返す。

    Args:
        export_response: GET /agent-neo/v1/tracking/export のレスポンス dict。

    Returns:
        次回 after に渡す文字列、または None（これ以上取得なし）。
    """
    count = export_response.get("count", 0)
    limit = export_response.get("limit", _BATCH_SIZE)
    next_cursor = export_response.get("next_cursor")

    # count < limit: 末尾まで取得済み
    if count < limit:
        return None

    # next_cursor が None または空の場合も終端
    if next_cursor is None or str(next_cursor).strip() == "":
        return None

    return str(next_cursor)


def dedup_by_event_id(events: list, seen: set) -> list:
    """event_id の重複を除去して冪等性を保証する。

    seen セットを in-place で更新し、呼び出し元で次回の seen として再利用できる。

    Args:
        events: ingest 変換前の生 event dict リスト（export の events 配列）。
        seen:   既処理の event_id セット（呼び出し側で保持・渡す）。

    Returns:
        重複を除いた events リスト。
    """
    result: list = []
    for evt in events:
        event_id = evt.get("event_id")
        if event_id is None:
            # event_id なし → 除去対象としない（pass-through）
            result.append(evt)
            continue
        key = str(event_id)
        if key not in seen:
            seen.add(key)
            result.append(evt)
    return result


# ---------------------------------------------------------------------------
# 薄い IO シェル（テスト対象外・実運用例）
# ---------------------------------------------------------------------------


def pull_once(
    base_url: str,
    after: "str | None" = None,
    limit: int = 100,
    event_type: "str | None" = None,
    auth: "tuple[str, str] | None" = None,
) -> dict:
    """エクスポートエンドポイントを 1 回 GET して dict を返す。

    【認証】
    Automation SEO の既存サイト接続（inspect / ai-query と同じ接続トークン）で
    `edit_posts` 権限を持つ WordPress ユーザーとして認証する。
    auth=("username", "application_password") を渡すと Basic 認証ヘッダを付与する。
    実運用では Automation SEO の接続 DB からトークンを取得して渡す（本コードは参照実装のみ）。

    Args:
        base_url:   WordPress サイト URL（末尾スラッシュなし）。
                    例: "https://example.com"
        after:      前回レスポンスの next_cursor 文字列（増分取得）。
        limit:      1〜100。
        event_type: カンマ区切り CSV（例: "affiliate_click,scroll_depth"）。
        auth:       ("wp_username", "app_password") タプル。

    Returns:
        レスポンス JSON を dict に変換したもの。

    Raises:
        urllib.error.URLError: ネットワーク・HTTP エラー。
        ValueError: レスポンスが JSON でない場合。
    """
    endpoint = f"{base_url.rstrip('/')}/wp-json/agent-neo/v1/tracking/export"

    params: dict[str, str] = {"limit": str(min(max(1, limit), 100))}
    if after:
        params["after"] = after
    if event_type:
        params["event_type"] = event_type

    url = endpoint + "?" + urllib.parse.urlencode(params)

    req = urllib.request.Request(url, method="GET")
    req.add_header("Accept", "application/json")
    req.add_header("Content-Type", "application/json")

    if auth:
        import base64

        token = base64.b64encode(f"{auth[0]}:{auth[1]}".encode()).decode()
        req.add_header("Authorization", f"Basic {token}")

    with urllib.request.urlopen(req, timeout=30) as resp:
        body = resp.read().decode("utf-8")

    return json.loads(body)


def run_pull_loop(
    base_url: str,
    auth: "tuple[str, str] | None" = None,
    event_type: "str | None" = None,
    limit: int = 100,
) -> None:
    """全件 pull → transform → dedup → ingest（ループ）の実行例。

    ingest 送信は stub（コメント）にしてある。
    再設計側の Automation SEO が本関数を参照して実装する。

    Args:
        base_url:   WordPress サイト URL。
        auth:       認証情報 ("username", "app_password")。
        event_type: event_type フィルタ（任意）。
        limit:      1 回に取得する最大件数。
    """
    after: "str | None" = None
    seen: set = set()
    total_ingested = 0

    while True:
        # 1. pull
        resp = pull_once(base_url, after=after, limit=limit, event_type=event_type, auth=auth)

        raw_events: list = resp.get("events", [])

        # 2. dedup（event_id で冪等除去）
        deduped = dedup_by_event_id(raw_events, seen)

        # 3. transform（export → ingest 形）
        if deduped:
            partial_resp = {
                "schema_version": resp.get("schema_version", 1),
                "events": deduped,
            }
            batches = transform_export_to_ingest(partial_resp)

            for batch in batches:
                # 4. ingest 送信（stub）
                # ここで Automation SEO の POST /api/v1/tracking/ingest を呼ぶ。
                # 例:
                #   ingest_client.post(batch)
                #
                # 【再設計側への注記】
                # - テナント（site_id）は接続 DB の site_id で確定する。
                # - consent は AGENT-NEO 収集側（tracking-controller）で担保済み。
                # - リトライは呼び出し側（Automation SEO ジョブ基盤）が管理する。
                total_ingested += len(batch["events"])

        # 5. 次カーソルを確認
        cursor = next_after_cursor(resp)
        if cursor is None:
            break
        after = cursor

    print(f"[run_pull_loop] 完了: ingest 対象 {total_ingested} 件")
