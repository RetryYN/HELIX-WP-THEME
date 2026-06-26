#!/usr/bin/env python3
"""
E2E: a11y 検証 — 主要ページ axe serious/critical=0 アサート
=============================================================

【採用理由: wp-phpunit 非可搬のため live-docker E2E を採用】
  WP_UnitTestCase は DOM を描画しないため unit/integration テストでは
  実描画の a11y 違反を検出できない。本スクリプトは docker WP の実ページを
  Playwright でレンダリングし axe-core で WCAG2AA 検証を行う。

【検証対象】
  light モード（デフォルト CSS）での主要ページ:
    / (home)
    /lp-sample/ (LP: page-lp テンプレート)
    /?p=256 (記事単一)
    /category/seo-strategy/ (カテゴリアーカイブ)
    /no-such-page-xyz-e2e-test/ (404)

  アサート基準: serious / critical の violations が 0 件

【dark モードについて】
  dark モードは theme.json の palette を手動で差し替えて
  WordPress のグローバルスタイルとして適用する必要があるため、
  自動テストでは安定した再現が難しい（Playwright の CSS 上書きでは
  theme.json 由来の CSS 変数が全て置き換わるわけではない）。
  dark モードの a11y 検証は以下の手順で手動実施すること:
    1. WP 管理画面 外観 > エディタ > スタイル でダークバリエーションを選択
    2. 本スクリプトを再実行（URLs を dark variant に変更可能）
    3. または scratchpad/axe_detail.py を直接実行

【前提条件】
  - docker コンテナ agent-neo-wp が起動中（http://localhost:8086）
  - playwright がインストール済み（pip install playwright && playwright install chromium）
  - /opt/agent-neo/bin/vendor/axe.min.js が存在する

exit code: 0=PASS / 1=FAIL / 2=SKIP（docker 未起動 / playwright 非対応）
"""

import sys
import urllib.request

WP_URL = "http://localhost:8086"
AXE_JS_PATH = "/opt/agent-neo/bin/vendor/axe.min.js"

# 検証対象ページ: (URL, 説明)
PAGES = [
    (f"{WP_URL}/",                              "home / トップページ"),
    (f"{WP_URL}/lp-sample/",                    "lp / LP固定ページ"),
    (f"{WP_URL}/?p=256",                        "post / 記事単一"),
    (f"{WP_URL}/category/seo-strategy/",        "other / カテゴリアーカイブ"),
    (f"{WP_URL}/no-such-page-xyz-e2e-test/",    "other / 404"),
]

IMPACT_FILTER = {"serious", "critical"}

# ---------------------------------------------------------------------------
# 既知の意図的例外（WCAG 除外リスト）
# ---------------------------------------------------------------------------
# SNS シェアボタンの公式ブランドカラーは WCAG 1.4.3 の意図的例外として
# style.css に記録されている（themes/agent-neo-theme/style.css §SNS share buttons）。
# Facebook (#1877f2/白=4.23:1)・LINE (#06c755/白=2.25:1)・はてな (#00a4de/白=2.85:1) は
# 各 SNS プラットフォームの公式ブランドカラーガイドラインに準拠しているため除外する。
KNOWN_EXCEPTIONS = {
    # (axe_rule_id, css_selector_fragment): 理由
    ("color-contrast", ".an-share-btn--facebook"): "SNS ブランドカラー意図的例外 (style.css)",
    ("color-contrast", ".an-share-btn--line"):     "SNS ブランドカラー意図的例外 (style.css)",
    ("color-contrast", ".an-share-btn--hatena"):   "SNS ブランドカラー意図的例外 (style.css)",
}


def is_known_exception(rule_id: str, node: dict) -> bool:
    """既知の意図的例外ノードかどうかを判定する。"""
    targets = node.get("target", [])
    for tgt in targets:
        for (ex_rule, ex_sel), _ in KNOWN_EXCEPTIONS.items():
            if rule_id == ex_rule and ex_sel in str(tgt):
                return True
    return False


def check_prerequisites():
    """前提条件チェック。"""
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

    # axe.min.js の存在確認
    import os
    if not os.path.exists(AXE_JS_PATH):
        print(f"[SKIP] axe.min.js が見つからない: {AXE_JS_PATH}")
        sys.exit(2)

    # playwright の確認
    try:
        from playwright.sync_api import sync_playwright  # noqa: F401
    except ImportError:
        print("[SKIP] playwright がインストールされていない")
        print("  pip install playwright && playwright install chromium")
        sys.exit(2)


def run_axe(page, axe_js):
    """axe を実行し violations を返す。"""
    page.evaluate(axe_js)
    result = page.evaluate(
        "() => axe.run(document, {"
        "  runOnly: ['wcag2a', 'wcag2aa', 'best-practice'],"
        "  resultTypes: ['violations']"
        "}).then(r => ({violations: r.violations}))"
    )
    return result.get("violations", [])


def main():
    check_prerequisites()

    with open(AXE_JS_PATH) as f:
        axe_js = f.read()

    print("=== E2E: a11y 検証 (light モード / live docker WP) ===")
    print(f"  endpoint: {WP_URL}")
    print(f"  axe.js: {AXE_JS_PATH}")
    print(f"  対象: {len(PAGES)} ページ  フィルタ: {sorted(IMPACT_FILTER)}")
    print()

    from playwright.sync_api import sync_playwright

    total_pass = 0
    total_fail = 0
    failures = []

    with sync_playwright() as p:
        browser = p.chromium.launch()

        for url, desc in PAGES:
            page = browser.new_page()
            try:
                page.goto(url, wait_until="networkidle", timeout=20000)
                violations = run_axe(page, axe_js)

                # serious / critical のみ抽出し、既知の意図的例外を除く
                serious_raw = [v for v in violations if v.get("impact") in IMPACT_FILTER]
                serious = []
                for v in serious_raw:
                    rule_id = v.get("id", "")
                    nodes_after_filter = [
                        n for n in v.get("nodes", [])
                        if not is_known_exception(rule_id, n)
                    ]
                    if nodes_after_filter:
                        v_copy = dict(v)
                        v_copy["nodes"] = nodes_after_filter
                        serious.append(v_copy)

                if not serious:
                    print(f"  [PASS] {desc}")
                    total_pass += 1
                else:
                    print(f"  [FAIL] {desc}  ({len(serious)} 件の serious/critical)")
                    total_fail += 1
                    for v in serious:
                        impact = v.get("impact", "?")
                        vid = v.get("id", "?")
                        help_text = v.get("help", "?")
                        nodes = v.get("nodes", [])
                        print(f"         [{impact}] {vid}: {help_text}  (nodes={len(nodes)})")
                        for n in nodes[:3]:
                            tgt = n.get("target", ["?"])[0] if n.get("target") else "?"
                            print(f"           - {tgt}")
                    failures.append(f"{desc}: {len(serious)} serious/critical violations")

            except Exception as e:
                print(f"  [FAIL] {desc}: Playwright エラー: {e}")
                total_fail += 1
                failures.append(f"{desc}: Playwright エラー: {e}")
            finally:
                page.close()

        browser.close()

    print()
    total = total_pass + total_fail
    if total_fail == 0:
        print(f"PASS: 全 {total} ページ serious/critical=0")
        sys.exit(0)
    else:
        print(f"FAIL: {total_fail}/{total} ページで serious/critical 違反あり")
        for f in failures:
            print(f"  - {f}")
        sys.exit(1)


if __name__ == "__main__":
    main()
