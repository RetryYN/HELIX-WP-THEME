"""
tracking-pull-reference / test_consumer.py

依存ゼロ（標準ライブラリのみ）・pytest 不要の自己実行テスト。
$ python3 tools/tracking-pull-reference/test_consumer.py

全テスト成功 → 標準出力に "ALL PASS"
失敗 → sys.exit(1)
"""

import sys
from consumer import (
    dedup_by_event_id,
    map_event_type,
    next_after_cursor,
    transform_export_to_ingest,
)

# ---------------------------------------------------------------------------
# テスト用 fixture
# ---------------------------------------------------------------------------

# export レスポンスの代表フィクスチャ（affiliate_click / scroll_depth / ad_impression 含む）
FIXTURE_EXPORT_RESPONSE: dict = {
    "schema_version": 1,
    "events": [
        {
            "event_id": 1001,
            "event_type": "affiliate_click",
            "section_id": "hero",
            "cta_id": "cta-affiliate-01",
            "variant_id": "var-a",
            "occurred_at": "2026-06-26T10:00:00+00:00",
            "canonical_url": "https://example.com/article/affiliate-guide",
            "metadata": {"link_text": "公式サイトへ", "position": "hero"},
        },
        {
            "event_id": 1002,
            "event_type": "scroll_depth",
            "section_id": "body",
            "cta_id": "",
            "variant_id": None,
            "occurred_at": "2026-06-26T10:01:00+00:00",
            "canonical_url": "https://example.com/article/affiliate-guide",
            "metadata": {"scroll_depth": 75, "viewport_ratio": 0.8},
        },
        {
            "event_id": 1003,
            "event_type": "ad_impression",
            "section_id": "sidebar",
            "cta_id": "banner-001",
            "variant_id": "default",
            "occurred_at": "2026-06-26T10:02:00+00:00",
            "canonical_url": "https://example.com/article/seo-tips",
            "metadata": {"viewport_ratio": 1.0, "ad_slot": "sidebar-top"},
        },
    ],
    "next_cursor": 1003,
    "count": 3,
}

# ---------------------------------------------------------------------------
# テストランナー（シンプルな assert ベース）
# ---------------------------------------------------------------------------

_PASS: list[str] = []
_FAIL: list[str] = []


def _run(name: str, fn) -> None:  # type: ignore[type-arg]
    try:
        fn()
        _PASS.append(name)
        print(f"  PASS  {name}")
    except Exception as exc:
        _FAIL.append(name)
        print(f"  FAIL  {name}: {exc}", file=sys.stderr)


# ---------------------------------------------------------------------------
# 1. map_event_type 全ケース
# ---------------------------------------------------------------------------


def test_map_event_type_ad_impression() -> None:
    assert map_event_type("ad_impression") == "engagement"


def test_map_event_type_viewable_impression() -> None:
    assert map_event_type("viewable_impression") == "engagement"


def test_map_event_type_view_time() -> None:
    assert map_event_type("view_time") == "engagement"


def test_map_event_type_impression() -> None:
    assert map_event_type("impression") == "engagement"


def test_map_event_type_affiliate_click() -> None:
    assert map_event_type("affiliate_click") == "click"


def test_map_event_type_click() -> None:
    assert map_event_type("click") == "click"


def test_map_event_type_scroll_depth() -> None:
    assert map_event_type("scroll_depth") == "scroll"


def test_map_event_type_conversion() -> None:
    assert map_event_type("conversion") == "conversion"


def test_map_event_type_unknown_falls_back_to_engagement() -> None:
    assert map_event_type("completely_unknown_event") == "engagement"


def test_map_event_type_empty_string_falls_back_to_engagement() -> None:
    assert map_event_type("") == "engagement"


# ---------------------------------------------------------------------------
# 2. transform_export_to_ingest
# ---------------------------------------------------------------------------


def test_transform_event_kind_affiliate_click() -> None:
    """affiliate_click → click に変換されること"""
    batches = transform_export_to_ingest(FIXTURE_EXPORT_RESPONSE)
    events = batches[0]["events"]
    affiliate = next(
        e for e in events if e["payload"]["original_event_type"] == "affiliate_click"
    )
    assert affiliate["event_kind"] == "click"


def test_transform_event_kind_scroll_depth() -> None:
    """scroll_depth → scroll に変換されること"""
    batches = transform_export_to_ingest(FIXTURE_EXPORT_RESPONSE)
    events = batches[0]["events"]
    scroll = next(
        e for e in events if e["payload"]["original_event_type"] == "scroll_depth"
    )
    assert scroll["event_kind"] == "scroll"


def test_transform_event_kind_ad_impression() -> None:
    """ad_impression → engagement に変換されること"""
    batches = transform_export_to_ingest(FIXTURE_EXPORT_RESPONSE)
    events = batches[0]["events"]
    ad = next(
        e for e in events if e["payload"]["original_event_type"] == "ad_impression"
    )
    assert ad["event_kind"] == "engagement"


def test_transform_canonical_url_preserved() -> None:
    """canonical_url がそのまま引き継がれること"""
    batches = transform_export_to_ingest(FIXTURE_EXPORT_RESPONSE)
    events = batches[0]["events"]
    assert events[0]["canonical_url"] == "https://example.com/article/affiliate-guide"


def test_transform_occurred_at_preserved() -> None:
    """occurred_at がそのまま引き継がれること"""
    batches = transform_export_to_ingest(FIXTURE_EXPORT_RESPONSE)
    events = batches[0]["events"]
    assert events[0]["occurred_at"] == "2026-06-26T10:00:00+00:00"


def test_transform_payload_contains_cta_id() -> None:
    """payload に cta_id が含まれること"""
    batches = transform_export_to_ingest(FIXTURE_EXPORT_RESPONSE)
    events = batches[0]["events"]
    affiliate = next(
        e for e in events if e["payload"]["original_event_type"] == "affiliate_click"
    )
    assert affiliate["payload"]["cta_id"] == "cta-affiliate-01"


def test_transform_payload_contains_section_id() -> None:
    """payload に section_id が含まれること"""
    batches = transform_export_to_ingest(FIXTURE_EXPORT_RESPONSE)
    events = batches[0]["events"]
    assert events[0]["payload"]["section_id"] == "hero"


def test_transform_payload_contains_variant_id() -> None:
    """payload に variant_id が含まれること（null も許容）"""
    batches = transform_export_to_ingest(FIXTURE_EXPORT_RESPONSE)
    events = batches[0]["events"]
    affiliate = next(
        e for e in events if e["payload"]["original_event_type"] == "affiliate_click"
    )
    assert affiliate["payload"]["variant_id"] == "var-a"
    # scroll_depth は variant_id=None
    scroll = next(
        e for e in events if e["payload"]["original_event_type"] == "scroll_depth"
    )
    assert scroll["payload"]["variant_id"] is None


def test_transform_payload_contains_metadata() -> None:
    """payload に metadata が含まれること"""
    batches = transform_export_to_ingest(FIXTURE_EXPORT_RESPONSE)
    events = batches[0]["events"]
    scroll = next(
        e for e in events if e["payload"]["original_event_type"] == "scroll_depth"
    )
    assert scroll["payload"]["metadata"]["scroll_depth"] == 75


def test_transform_payload_contains_original_event_type() -> None:
    """payload に original_event_type が格納されること"""
    batches = transform_export_to_ingest(FIXTURE_EXPORT_RESPONSE)
    events = batches[0]["events"]
    assert events[0]["payload"]["original_event_type"] == "affiliate_click"


def test_transform_payload_contains_event_id() -> None:
    """payload に event_id が格納されること"""
    batches = transform_export_to_ingest(FIXTURE_EXPORT_RESPONSE)
    events = batches[0]["events"]
    assert events[0]["payload"]["event_id"] == 1001


def test_transform_invalid_schema_version_raises_value_error() -> None:
    """schema_version が未知の場合 ValueError を送出すること"""
    bad_resp = {
        "schema_version": 99,
        "events": [],
        "next_cursor": None,
        "count": 0,
    }
    try:
        transform_export_to_ingest(bad_resp)
        raise AssertionError("ValueError が送出されなかった")
    except ValueError as exc:
        assert "99" in str(exc)


def test_transform_100_events_single_batch() -> None:
    """100 件ちょうどは 1 バッチになること"""
    events = [
        {
            "event_id": i,
            "event_type": "impression",
            "section_id": "body",
            "cta_id": "",
            "variant_id": None,
            "occurred_at": "2026-06-26T00:00:00+00:00",
            "canonical_url": f"https://example.com/article/{i}",
            "metadata": {},
        }
        for i in range(1, 101)
    ]
    resp = {"schema_version": 1, "events": events, "next_cursor": 100, "count": 100}
    batches = transform_export_to_ingest(resp)
    assert len(batches) == 1
    assert len(batches[0]["events"]) == 100


def test_transform_101_events_two_batches() -> None:
    """101 件は 2 バッチに分割されること"""
    events = [
        {
            "event_id": i,
            "event_type": "impression",
            "section_id": "body",
            "cta_id": "",
            "variant_id": None,
            "occurred_at": "2026-06-26T00:00:00+00:00",
            "canonical_url": f"https://example.com/article/{i}",
            "metadata": {},
        }
        for i in range(1, 102)
    ]
    resp = {"schema_version": 1, "events": events, "next_cursor": 101, "count": 101}
    batches = transform_export_to_ingest(resp)
    assert len(batches) == 2
    assert len(batches[0]["events"]) == 100
    assert len(batches[1]["events"]) == 1


def test_transform_250_events_three_batches() -> None:
    """250 件は 3 バッチ（100+100+50）に分割されること"""
    events = [
        {
            "event_id": i,
            "event_type": "conversion",
            "section_id": "footer",
            "cta_id": "cta-cvr",
            "variant_id": None,
            "occurred_at": "2026-06-26T00:00:00+00:00",
            "canonical_url": f"https://example.com/article/{i}",
            "metadata": {},
        }
        for i in range(1, 251)
    ]
    resp = {"schema_version": 1, "events": events, "next_cursor": 250, "count": 250}
    batches = transform_export_to_ingest(resp)
    assert len(batches) == 3
    total = sum(len(b["events"]) for b in batches)
    assert total == 250


# ---------------------------------------------------------------------------
# 3. next_after_cursor
# ---------------------------------------------------------------------------


def test_next_after_cursor_count_less_than_limit_returns_none() -> None:
    """count < limit の場合 None を返すこと（末尾到達）"""
    resp = {
        "schema_version": 1,
        "events": [],
        "next_cursor": 999,
        "count": 3,
        "limit": 100,
    }
    assert next_after_cursor(resp) is None


def test_next_after_cursor_count_equals_limit_returns_cursor() -> None:
    """count == limit の場合 next_cursor 文字列を返すこと"""
    resp = {
        "schema_version": 1,
        "events": [],
        "next_cursor": 1003,
        "count": 100,
        "limit": 100,
    }
    result = next_after_cursor(resp)
    assert result == "1003"


def test_next_after_cursor_none_cursor_returns_none() -> None:
    """next_cursor が None の場合 None を返すこと"""
    resp = {
        "schema_version": 1,
        "events": [],
        "next_cursor": None,
        "count": 100,
        "limit": 100,
    }
    assert next_after_cursor(resp) is None


def test_next_after_cursor_empty_string_cursor_returns_none() -> None:
    """next_cursor が空文字の場合 None を返すこと"""
    resp = {
        "schema_version": 1,
        "events": [],
        "next_cursor": "",
        "count": 100,
        "limit": 100,
    }
    assert next_after_cursor(resp) is None


# ---------------------------------------------------------------------------
# 4. dedup_by_event_id
# ---------------------------------------------------------------------------


def test_dedup_removes_duplicate_event_id() -> None:
    """同一 event_id のイベントを重複除去すること"""
    events = [
        {"event_id": 1, "event_type": "impression"},
        {"event_id": 2, "event_type": "click"},
        {"event_id": 1, "event_type": "impression"},  # 重複
    ]
    seen: set = set()
    result = dedup_by_event_id(events, seen)
    assert len(result) == 2
    ids = [e["event_id"] for e in result]
    assert ids == [1, 2]


def test_dedup_seen_set_is_updated() -> None:
    """seen セットが in-place で更新されること"""
    events = [{"event_id": 10, "event_type": "scroll"}]
    seen: set = set()
    dedup_by_event_id(events, seen)
    assert "10" in seen


def test_dedup_across_multiple_calls() -> None:
    """複数回呼び出しをまたいで重複除去されること（増分 pull のシナリオ）"""
    seen: set = set()
    batch1 = [{"event_id": 1}, {"event_id": 2}]
    batch2 = [{"event_id": 2}, {"event_id": 3}]  # event_id=2 は重複

    r1 = dedup_by_event_id(batch1, seen)
    r2 = dedup_by_event_id(batch2, seen)

    assert len(r1) == 2
    assert len(r2) == 1
    assert r2[0]["event_id"] == 3


def test_dedup_no_event_id_passes_through() -> None:
    """event_id が存在しないイベントは除去せずそのまま通すこと"""
    events = [{"event_type": "unknown_no_id"}]
    seen: set = set()
    result = dedup_by_event_id(events, seen)
    assert len(result) == 1


def test_dedup_all_unique() -> None:
    """全て一意の場合は除去なし"""
    events = [{"event_id": i} for i in range(5)]
    seen: set = set()
    result = dedup_by_event_id(events, seen)
    assert len(result) == 5


# ---------------------------------------------------------------------------
# メインランナー
# ---------------------------------------------------------------------------

if __name__ == "__main__":
    print("=" * 60)
    print("tracking-pull-reference / test_consumer.py")
    print("=" * 60)

    test_cases = [
        # map_event_type
        ("map: ad_impression→engagement", test_map_event_type_ad_impression),
        ("map: viewable_impression→engagement", test_map_event_type_viewable_impression),
        ("map: view_time→engagement", test_map_event_type_view_time),
        ("map: impression→engagement", test_map_event_type_impression),
        ("map: affiliate_click→click", test_map_event_type_affiliate_click),
        ("map: click→click", test_map_event_type_click),
        ("map: scroll_depth→scroll", test_map_event_type_scroll_depth),
        ("map: conversion→conversion", test_map_event_type_conversion),
        ("map: unknown→engagement", test_map_event_type_unknown_falls_back_to_engagement),
        ("map: empty→engagement", test_map_event_type_empty_string_falls_back_to_engagement),
        # transform
        ("transform: affiliate_click→click", test_transform_event_kind_affiliate_click),
        ("transform: scroll_depth→scroll", test_transform_event_kind_scroll_depth),
        ("transform: ad_impression→engagement", test_transform_event_kind_ad_impression),
        ("transform: canonical_url 保持", test_transform_canonical_url_preserved),
        ("transform: occurred_at 保持", test_transform_occurred_at_preserved),
        ("transform: payload.cta_id 含む", test_transform_payload_contains_cta_id),
        ("transform: payload.section_id 含む", test_transform_payload_contains_section_id),
        ("transform: payload.variant_id 含む", test_transform_payload_contains_variant_id),
        ("transform: payload.metadata 含む", test_transform_payload_contains_metadata),
        ("transform: payload.original_event_type 含む", test_transform_payload_contains_original_event_type),
        ("transform: payload.event_id 含む", test_transform_payload_contains_event_id),
        ("transform: schema_version 不正→ValueError", test_transform_invalid_schema_version_raises_value_error),
        ("transform: 100件→1バッチ", test_transform_100_events_single_batch),
        ("transform: 101件→2バッチ", test_transform_101_events_two_batches),
        ("transform: 250件→3バッチ", test_transform_250_events_three_batches),
        # next_after_cursor
        ("cursor: count<limit→None", test_next_after_cursor_count_less_than_limit_returns_none),
        ("cursor: count==limit→cursor返す", test_next_after_cursor_count_equals_limit_returns_cursor),
        ("cursor: next_cursor=None→None", test_next_after_cursor_none_cursor_returns_none),
        ("cursor: next_cursor=''→None", test_next_after_cursor_empty_string_cursor_returns_none),
        # dedup
        ("dedup: 重複除去", test_dedup_removes_duplicate_event_id),
        ("dedup: seen更新", test_dedup_seen_set_is_updated),
        ("dedup: 複数呼び出し間で除去", test_dedup_across_multiple_calls),
        ("dedup: event_id なし→通過", test_dedup_no_event_id_passes_through),
        ("dedup: 全一意→除去なし", test_dedup_all_unique),
    ]

    print()
    for name, fn in test_cases:
        _run(name, fn)

    print()
    print(f"結果: {len(_PASS)} PASS / {len(_FAIL)} FAIL")
    print()

    if _FAIL:
        print(f"FAILED: {_FAIL}", file=sys.stderr)
        sys.exit(1)

    print("ALL PASS")
