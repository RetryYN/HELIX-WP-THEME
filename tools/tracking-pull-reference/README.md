# tracking-pull-reference

Automation SEO 再設計側が採用する「計測ループ消費者リファレンス実装」。

## 目的

AGENT-NEO の export 契約（`docs/features/tracking-pull/D-CONTRACT/export-contract.md`）に
準拠し、計測ループ全周（収集→export公開→**pull→ingest整形**）が閉じることを実証する。
Python 標準ライブラリのみで動作し、依存ゼロ。

## ファイル構成

```
tools/tracking-pull-reference/
  consumer.py       純粋関数 + 薄い IO シェル
  test_consumer.py  自己実行テスト（pytest 不要）
  README.md         本ファイル
```

## 実行方法

```bash
# テスト実行（プロジェクトルートから）
python3 tools/tracking-pull-reference/test_consumer.py

# 構文確認
python3 -c "import ast; ast.parse(open('tools/tracking-pull-reference/consumer.py').read())"
```

---

## pull アルゴリズム

### 1. after カーソルによる増分 pull

```
1. after=None で先頭から GET /agent-neo/v1/tracking/export
2. レスポンスの next_cursor を保存
3. count < limit になるまで after=next_cursor でループ
```

`next_after_cursor()` が終端検出（`count < limit` または `next_cursor=None`）を担う。

### 2. event_id による冪等除去

`dedup_by_event_id(events, seen)` を呼び出し側がセッション全体で `seen` セットを
保持することで、ページ境界をまたぐ重複・再取得を除去する。

### 3. event_kind 変換

| AGENT-NEO event_type    | backend event_kind |
|-------------------------|--------------------|
| `ad_impression`         | `engagement`       |
| `viewable_impression`   | `engagement`       |
| `view_time`             | `engagement`       |
| `impression`            | `engagement`       |
| `affiliate_click`       | `click`            |
| `click`                 | `click`            |
| `scroll_depth`          | `scroll`           |
| `conversion`            | `conversion`       |
| `<未知>`                | `engagement`（fail-safe） |

### 4. ingest 形フォーマット

`transform_export_to_ingest()` が export の `events[]` を以下の形に変換する。

```json
{
  "events": [
    {
      "event_kind":    "click",
      "canonical_url": "https://example.com/article/1",
      "occurred_at":   "2026-06-26T10:00:00+00:00",
      "payload": {
        "section_id":          "hero",
        "cta_id":              "cta-affiliate-01",
        "variant_id":          "var-a",
        "metadata":            { "link_text": "公式サイトへ" },
        "original_event_type": "affiliate_click",
        "event_id":            1001
      }
    }
  ]
}
```

100 件を超える場合は自動でバッチ分割する（1 バッチ = 最大 100 件）。

### 5. consent・PII

- **consent**: AGENT-NEO の収集側（class-tracking-controller.php の `queue_event()`）
  で担保済み。pull 側は再確認不要。
- **PII**: export エンドポイント（class-tracking-export-controller.php）が
  `format_event()` で必要フィールドのみ抽出。IP 等は queue_event 段階で除外されている。

---

## 認証

Automation SEO の既存サイト接続（inspect / ai-query と同じ接続）を使用する。

- WordPress Application Password を `auth=("wp_username", "app_password")` で渡す。
- 必要権限: `edit_posts`（`check_read_permission` に合わせる）。
- テナント（`site_id`）は Automation SEO の接続 DB で確定する。
  export エンドポイントは認証ユーザーのサイトスコープで応答するため、
  テナント越境は発生しない。

---

## Automation SEO 再設計側への注記

1. `consumer.py` の `run_pull_loop()` の ingest 送信部分（stub）を
   Automation SEO の POST `/api/v1/tracking/ingest` 呼び出しに差し替える。
2. `seen` セットはジョブ実行単位で永続化するか、`event_id` をジョブ DB に記録して
   冪等性を管理する。
3. `schema_version` が `1` 以外になった場合、`transform_export_to_ingest()` が
   `ValueError` を送出するため、ジョブ基盤でキャッチしてアラートを上げる。
4. リトライ・バックオフ・エラーハンドリングは Automation SEO のジョブ基盤（Celery 等）
   が担う。本ファイルはロジック層のみを提供する。
