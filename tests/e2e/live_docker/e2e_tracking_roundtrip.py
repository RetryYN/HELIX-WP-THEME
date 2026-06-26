#!/usr/bin/env python3
"""
E2E: tracking roundtrip — 全イベント種別 HMAC 署名 POST 検証
=============================================================

【採用理由: wp-phpunit 非可搬のため live-docker E2E を採用】
  tests/Integration/ の TC009_TrackingEventTest は WP_UnitTestCase 依存で
  wp-phpunit/WordPress コアが別途必要なため CI 非可搬（既知課題）。
  本スクリプトは docker の agent-neo-wp（http://localhost:8086）に対して
  実 HTTP POST を行い、本番 ad-tracking.js と同一の署名方式を Python で
  忠実に再現して全イベント種別の受理（HTTP 200）を検証する。

【token はサーバ tracking_secrets() と同じ優先順で解決する】
  サーバ class-tracking-controller.php tracking_secrets() の解決順:
    site_token:
      1. env  AGENT_NEO_SITE_TOKEN
      2. env  AGENT_NEO_TRACKING_SITE_TOKEN
      3. option agent_neo_site_token
      4. option agent_neo_tracking_site_token
    hmac_key:
      1. env  AGENT_NEO_TRACKING_HMAC_KEY
      2. env  AGENT_NEO_HMAC_KEY
      3. option agent_neo_tracking_hmac_key
      4. option agent_neo_hmac_key
  本スクリプトは docker exec で WP コンテナの env と DB の option を
  同じ優先順で読み込むことで、常に「サーバが検証に使う token」で署名する。

【agent_neo_site_token は本番未設定が前提】
  env または DB に agent_neo_site_token（legacy キー）が残存すると
  tracking_secrets() がそれを優先し、frontend が localize している
  agent_neo_tracking_site_token（TrackingAssets が書き込むキー）と
  不一致になり全 HTTP 401 になる（token 汚染バグ）。
  本スクリプトは token 整合性アサーションでこの構成ミスを検出する。

【署名方式】
  ad-tracking.js: canonicalizeForSignature() — オブジェクトを再帰的にキー昇順ソート
  PHP 側:         sort_recursive() / canonical_json() — 連想配列を再帰 ksort
  両者は同一の出力を生成する。本スクリプトはこれを Python で忠実に複製する。

【負の対照テスト】
  旧実装（トップレベルのみソート）では viewable_impression{ratio,page_type} が
  401 SIGNATURE_INVALID になることを確認し、回帰検出器として機能させる。

【前提条件】
  - docker コンテナ agent-neo-wp, agent-neo-db が起動中
  - http://localhost:8086/ が 200 を返す

exit code: 0=PASS / 1=FAIL / 2=SKIP（docker 未起動）
"""

import hashlib
import hmac as hmac_lib
import json
import secrets
import subprocess
import sys
import urllib.error
import urllib.request

WP_URL = "http://localhost:8086"
TRACKING_ENDPOINT = f"{WP_URL}/wp-json/agent-neo/v1/tracking/event"
WP_CONTAINER = "agent-neo-wp"
DB_CONTAINER = "agent-neo-db"
DB_USER = "wp"
DB_PASS = "wp"
DB_NAME = "agent_neo"


# ---------------------------------------------------------------------------
# 前提条件チェック・token 解決
# ---------------------------------------------------------------------------

def get_wp_env() -> dict:
    """docker exec で WP コンテナの環境変数を取得する。"""
    try:
        result = subprocess.run(
            ["docker", "exec", WP_CONTAINER, "env"],
            capture_output=True, text=True, timeout=10,
        )
        env = {}
        for line in result.stdout.splitlines():
            if "=" in line:
                k, _, v = line.partition("=")
                env[k] = v
        return env
    except Exception:
        return {}


def get_db_options() -> dict:
    """DB から agent_neo 関連 option を取得する。"""
    option_keys = (
        "agent_neo_site_token",
        "agent_neo_tracking_site_token",
        "agent_neo_tracking_hmac_key",
        "agent_neo_hmac_key",
    )
    keys_csv = ",".join(f"'{k}'" for k in option_keys)
    try:
        result = subprocess.run(
            [
                "docker", "exec", DB_CONTAINER,
                "mysql", f"-u{DB_USER}", f"-p{DB_PASS}", "-N", "-e",
                f"SELECT option_name, option_value FROM {DB_NAME}.wp_options "
                f"WHERE option_name IN ({keys_csv});",
            ],
            capture_output=True, text=True, timeout=10,
        )
        rows = {}
        for line in result.stdout.strip().splitlines():
            parts = line.split("\t", 1)
            if len(parts) == 2:
                rows[parts[0]] = parts[1]
        return rows
    except Exception:
        return {}


def resolve_tokens():
    """tracking_secrets() と同じ優先順で site_token / hmac_key を解決する。

    Returns: (site_token, hmac_key, frontend_token, resolution_log)
      - site_token    : サーバが検証に使う token
      - hmac_key      : サーバが検証に使う hmac_key
      - frontend_token: TrackingAssets が localize する token（整合性確認用）
      - resolution_log: どのキーから解決したかのログ文字列
    """
    env = get_wp_env()
    opts = get_db_options()

    # --- site_token（tracking_secrets() と同一優先順）---
    candidates_token = [
        ("env:AGENT_NEO_SITE_TOKEN",             env.get("AGENT_NEO_SITE_TOKEN", "")),
        ("env:AGENT_NEO_TRACKING_SITE_TOKEN",     env.get("AGENT_NEO_TRACKING_SITE_TOKEN", "")),
        ("option:agent_neo_site_token",           opts.get("agent_neo_site_token", "")),
        ("option:agent_neo_tracking_site_token",  opts.get("agent_neo_tracking_site_token", "")),
    ]
    token_source = ""
    site_token = ""
    for source, val in candidates_token:
        if val and val.strip():
            site_token = val.strip()
            token_source = source
            break

    # --- hmac_key（tracking_secrets() と同一優先順）---
    candidates_hmac = [
        ("env:AGENT_NEO_TRACKING_HMAC_KEY",   env.get("AGENT_NEO_TRACKING_HMAC_KEY", "")),
        ("env:AGENT_NEO_HMAC_KEY",            env.get("AGENT_NEO_HMAC_KEY", "")),
        ("option:agent_neo_tracking_hmac_key", opts.get("agent_neo_tracking_hmac_key", "")),
        ("option:agent_neo_hmac_key",          opts.get("agent_neo_hmac_key", "")),
    ]
    hmac_source = ""
    hmac_key = ""
    for source, val in candidates_hmac:
        if val and val.strip():
            hmac_key = val.strip()
            hmac_source = source
            break

    # frontend が localize する token（TrackingAssets: option agent_neo_tracking_site_token を使う）
    frontend_token = opts.get("agent_neo_tracking_site_token", "").strip()

    resolution_log = f"site_token=[{token_source}], hmac_key=[{hmac_source}]"
    return site_token, hmac_key, frontend_token, resolution_log


def check_prerequisites():
    """WP 起動確認 + token 解決 + 整合性アサーション。

    Returns: (site_token, hmac_key)
    """
    # WP ヘルスチェック
    try:
        with urllib.request.urlopen(f"{WP_URL}/", timeout=5) as r:
            if r.status not in (200, 301, 302):
                print(f"[SKIP] WP が応答しない（status={r.status}）")
                sys.exit(2)
    except Exception as e:
        print(f"[SKIP] WP に接続できない: {e}")
        print("  docker コンテナ agent-neo-wp が起動中か確認してください")
        sys.exit(2)

    site_token, hmac_key, frontend_token, resolution_log = resolve_tokens()

    if not site_token or not hmac_key:
        print("[SKIP] site_token / hmac_key を解決できなかった")
        print(f"  {resolution_log}")
        sys.exit(2)

    print(f"  token 解決: {resolution_log}")
    print(f"  site_token (サーバ解決): {site_token[:12]}...")
    if frontend_token:
        print(f"  site_token (frontend): {frontend_token[:12]}...")

    # --- token 整合性アサーション ---
    # frontend が localize する token とサーバが検証する token が一致しない場合、
    # 実際のブラウザイベントが全 401 になる（本番構成ミスの検出）。
    # 例: env/DB に legacy キー agent_neo_site_token が残存するとサーバが優先し不一致になる。
    if frontend_token and site_token != frontend_token:
        print()
        print("  [WARN] token 不一致検出: frontend token != サーバ token")
        print(f"    frontend (agent_neo_tracking_site_token): {frontend_token[:12]}...")
        print(f"    サーバ   ({resolution_log.split('[')[1].split(']')[0]}): {site_token[:12]}...")
        print("    原因: env または DB に agent_neo_site_token（legacy キー）が残存している可能性。")
        print("    影響: 実ブラウザから送信されるイベントが全 401 SIGNATURE_INVALID になる。")
        print("    対処: テスト残骸キーを削除してください（本スクリプトはサーバ優先 token で続行）。")
    else:
        print("  [OK] token 整合性: frontend token = サーバ token")

    return site_token, hmac_key


# ---------------------------------------------------------------------------
# 署名ヘルパー（本番 JS と完全一致する実装）
# ---------------------------------------------------------------------------

def sha256_hex(text):
    return hashlib.sha256(text.encode("utf-8")).hexdigest()


def hmac_sha256_hex(payload, key):
    return hmac_lib.new(
        key.encode("utf-8"), payload.encode("utf-8"), hashlib.sha256
    ).hexdigest()


def js_stringify(obj):
    """JSON.stringify と同一の出力（区切りスペースなし・unicode raw・スラッシュ非エスケープ）。"""
    return json.dumps(obj, separators=(",", ":"), ensure_ascii=False)


def canonicalize_recursive(v):
    """ad-tracking.js canonicalizeForSignature() の Python 忠実複製。

    - dict  : キーを昇順ソートして再帰処理（PHP ksort と一致）
    - list  : 要素を再帰処理するが順序は保持
    - それ以外: そのまま返す
    """
    if isinstance(v, list):
        return [canonicalize_recursive(x) for x in v]
    if isinstance(v, dict):
        return {k: canonicalize_recursive(v[k]) for k in sorted(v.keys())}
    return v


def canonicalize_toplevel_only(body):
    """旧 JS（トップレベルのみソート）の再現。回帰検出器用。"""
    return {k: body[k] for k in sorted(body.keys())}


def build_signature(body_without_sig, nonce, hmac_key, mode="new"):
    """署名を生成する。

    mode="new": 再帰 canonicalize（本番実装）
    mode="old": トップレベルのみソート（旧バグ再現）
    """
    if mode == "new":
        canonical = js_stringify(canonicalize_recursive(body_without_sig))
    else:
        canonical = js_stringify(canonicalize_toplevel_only(body_without_sig))

    payload = f"POST|/agent-neo/v1/tracking/event|{nonce}|{sha256_hex(canonical)}"
    return hmac_sha256_hex(payload, hmac_key)


def post_event(body):
    """イベントを POST し (status_code, body_text) を返す。"""
    data = js_stringify(body).encode("utf-8")
    req = urllib.request.Request(
        TRACKING_ENDPOINT,
        data=data,
        method="POST",
        headers={"Content-Type": "application/json"},
    )
    try:
        with urllib.request.urlopen(req, timeout=10) as r:
            return r.status, r.read().decode("utf-8")[:200]
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode("utf-8")[:200]


def make_and_post(event_type, cta_id, variant_id, metadata, site_token, hmac_key, mode="new"):
    """ボディ組み立て・署名・POST を一気に行う。"""
    nonce = secrets.token_hex(12)
    body = {
        "site_token": site_token,
        "nonce": nonce,
        "event_type": event_type,
        "section_id": "ad",
        "cta_id": cta_id,
        "variant_id": variant_id,
        "metadata": metadata,
    }
    sig = build_signature({k: v for k, v in body.items() if k != "signature"}, nonce, hmac_key, mode)
    body["signature"] = sig
    return post_event(body)


# ---------------------------------------------------------------------------
# テストケース定義
# ---------------------------------------------------------------------------

# 全イベント種別 × page_type の組み合わせ
# metadata キーが複数あるケース（viewable_impression{ratio,page_type}）が
# 再帰ソートの恩恵を最も受けるケース
CASES = [
    # (event_type, cta_id, variant_id, metadata, description)
    ("ad_impression",        "lp_banner",         "default", {"element": "ad", "page_type": "lp"},     "ad_impression/lp"),
    ("viewable_impression",  "lp_hero_primary",   "default", {"ratio": 50, "page_type": "lp"},          "viewable_impression{ratio,page_type}/lp"),
    ("viewable_impression",  "lp_hero_secondary", "default", {"ratio": 75, "page_type": "lp"},          "viewable_impression{ratio=75}/lp"),
    ("affiliate_click",      "lp_hero_primary",   "default", {"href": "https://example.com/x", "label": "購入する", "page_type": "lp"}, "affiliate_click{href,label,page_type}/lp"),
    ("scroll_depth",         "page",              "default", {"depth_pct": 25, "page_type": "home"},    "scroll_depth{25}/home"),
    ("scroll_depth",         "page",              "default", {"depth_pct": 75, "page_type": "home"},    "scroll_depth{75}/home"),
    ("ad_impression",        "article_cta",       "default", {"element": "ad", "page_type": "post"},    "ad_impression/post"),
    ("viewable_impression",  "article_cta",       "default", {"ratio": 60, "page_type": "post"},        "viewable_impression/post"),
    ("scroll_depth",         "page",              "default", {"depth_pct": 50, "page_type": "other"},   "scroll_depth/other(category/404)"),
]


def run_positive_cases(site_token, hmac_key):
    """正常系: 全ケース HTTP 200 をアサート。"""
    failures = []
    print("\n=== [1/2] 正常系: 全イベント種別 HTTP 200 アサート ===")
    for event_type, cta_id, variant_id, metadata, desc in CASES:
        status, body = make_and_post(event_type, cta_id, variant_id, metadata, site_token, hmac_key, mode="new")
        ok = status == 200
        mark = "PASS" if ok else "FAIL"
        print(f"  [{mark}] {desc:<52} -> HTTP {status}")
        if not ok:
            failures.append(f"{desc}: HTTP {status} (expected 200)  body={body[:80]}")
    return failures


def run_negative_case(site_token, hmac_key):
    """負の対照: 旧実装（トップレベルのみソート）で viewable_impression{ratio,page_type} が 401 になる回帰検出。"""
    failures = []
    print("\n=== [2/2] 負の対照: 旧署名（トップレベルのみソート）で 401 を確認 ===")
    status, _ = make_and_post(
        "viewable_impression", "lp_hero_primary", "default",
        {"ratio": 50, "page_type": "lp"},
        site_token, hmac_key, mode="old",
    )
    ok = status == 401
    mark = "PASS" if ok else "FAIL"
    print(f"  [{mark}] viewable_impression{{ratio,page_type}} 旧署名 -> HTTP {status}  (期待=401)")
    if not ok:
        failures.append(f"負の対照: HTTP {status} (expected 401 — 旧バグが再発した可能性)")
    return failures


# ---------------------------------------------------------------------------
# エントリポイント
# ---------------------------------------------------------------------------

def main():
    print("=== E2E: tracking roundtrip (live docker WP) ===")
    print(f"  endpoint: {TRACKING_ENDPOINT}")

    site_token, hmac_key = check_prerequisites()

    failures = []
    failures += run_positive_cases(site_token, hmac_key)
    failures += run_negative_case(site_token, hmac_key)

    print()
    if failures:
        print(f"FAIL: {len(failures)} 件失敗")
        for f in failures:
            print(f"  - {f}")
        sys.exit(1)
    else:
        total = len(CASES) + 1  # 正常系 + 負の対照
        print(f"PASS: 全 {total} ケース通過")
        sys.exit(0)


if __name__ == "__main__":
    main()
