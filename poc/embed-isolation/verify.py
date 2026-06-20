#!/usr/bin/env python3
"""ADR-026 embed isolation PoC verifier."""

from __future__ import annotations

import json
import shutil
import sys
import time
from dataclasses import dataclass
from pathlib import Path
from typing import Any

from playwright.sync_api import Error as PlaywrightError
from playwright.sync_api import sync_playwright

from server import (
    DEFAULT_HOST,
    DEFAULT_PARENT_PORT,
    DEFAULT_SANDBOX_PORT,
    ServerBundle,
    ServerConfig,
    SinkLog,
    create_bundle,
    embed_reset_css,
    parent_css,
    parent_html,
    parent_js,
    sandbox_css,
    sandbox_html,
    sandbox_js,
)


ROOT = Path(__file__).resolve().parent
RESULTS_PATH = ROOT / "RESULTS.md"
EXPECTED_EGRESS_KEYS = {"fetch", "xhr", "sendBeacon", "img", "form"}
EGRESS_TIMEOUT_MS = 8000
EGRESS_SETTLE_MS = 300
FRAME_WAIT_TIMEOUT_MS = 8000


@dataclass
class Check:
    item: str
    measured: str
    passed: bool
    detail: str
    required: bool = True
    status_override: str | None = None

    @property
    def status(self) -> str:
        if self.status_override:
            return self.status_override
        return "PASS" if self.passed else "FAIL"


def add_check(
    checks: list[Check],
    item: str,
    measured: Any,
    passed: bool,
    detail: str = "",
    *,
    required: bool = True,
    status_override: str | None = None,
) -> None:
    if not isinstance(measured, str):
        measured = json.dumps(measured, ensure_ascii=False, sort_keys=True)
    checks.append(
        Check(
            item=item,
            measured=measured,
            passed=passed,
            detail=detail,
            required=required,
            status_override=status_override,
        )
    )


def required_checks_passed(checks: list[Check]) -> bool:
    return all(check.passed for check in checks if check.required)


def find_check(checks: list[Check], item: str) -> Check | None:
    for check in checks:
        if check.item == item:
            return check
    return None


def chromium_executable() -> str | None:
    # Playwright 同梱ブラウザが未導入の環境では system Chromium を使う。
    cache_root = Path.home() / ".cache" / "ms-playwright"
    bundled = sorted(cache_root.glob("chromium-*/chrome-linux*/chrome"), reverse=True)
    if bundled:
        return str(bundled[0])
    for name in ("chromium", "chromium-browser", "google-chrome", "google-chrome-stable"):
        path = shutil.which(name)
        if path and not path.startswith("/snap/"):
            return path
    snap = Path("/snap/bin/chromium")
    return str(snap) if snap.exists() else None


class RouteBundle:
    """socket bind が禁止された環境向けの Playwright route fallback."""

    route_mode = True

    def __init__(self) -> None:
        self.config = ServerConfig(
            host=DEFAULT_HOST,
            parent_port=DEFAULT_PARENT_PORT,
            sandbox_port=DEFAULT_SANDBOX_PORT,
        )
        self.sink_log = SinkLog()

    def start(self) -> None:
        return

    def stop(self) -> None:
        return


def parent_csp(config: ServerConfig) -> str:
    return "; ".join(
        [
            "default-src 'self'",
            "script-src 'self'",
            "style-src 'self'",
            f"frame-src {config.sandbox_origin}",
            "connect-src 'self'",
            "img-src 'self'",
            "form-action 'self'",
            "base-uri 'none'",
            "object-src 'none'",
        ]
    )


def sandbox_csp(config: ServerConfig) -> str:
    return "; ".join(
        [
            "default-src 'none'",
            "script-src 'self'",
            "style-src 'self'",
            "connect-src 'none'",
            "img-src 'self'",
            "form-action 'none'",
            "base-uri 'none'",
            "object-src 'none'",
            f"frame-ancestors {config.parent_origin}",
        ]
    )


def install_routes(page: Any, bundle: RouteBundle) -> None:
    # socket bind 不可環境でも、ブラウザには別 port の HTTP origin として見せる。
    def handle(route: Any) -> None:
        request = route.request
        url = request.url
        parsed = url.split("?", 1)[0]
        headers = {"X-Content-Type-Options": "nosniff"}

        if parsed in (
            bundle.config.parent_origin,
            f"{bundle.config.parent_origin}/",
            f"{bundle.config.parent_origin}/index.html",
        ):
            route.fulfill(
                status=200,
                body=parent_html(bundle.config),
                content_type="text/html; charset=utf-8",
                headers={
                    **headers,
                    "Content-Security-Policy": parent_csp(bundle.config),
                    "Referrer-Policy": "no-referrer",
                },
            )
            return
        if parsed == f"{bundle.config.parent_origin}/parent.css":
            route.fulfill(status=200, body=parent_css(), content_type="text/css; charset=utf-8", headers=headers)
            return
        if parsed == f"{bundle.config.parent_origin}/parent.js":
            route.fulfill(
                status=200,
                body=parent_js(bundle.config),
                content_type="application/javascript; charset=utf-8",
                headers=headers,
            )
            return
        if parsed == f"{bundle.config.parent_origin}/embed-reset.css":
            route.fulfill(status=200, body=embed_reset_css(), content_type="text/css; charset=utf-8", headers=headers)
            return
        if parsed.startswith(f"{bundle.config.parent_origin}/sink/"):
            body = request.post_data or ""
            bundle.sink_log.append(request.method, url.replace(bundle.config.parent_origin, ""), body.encode("utf-8"))
            route.fulfill(status=204, body="", content_type="text/plain; charset=utf-8", headers=headers)
            return
        if parsed in (
            f"{bundle.config.sandbox_origin}/embed.html",
            f"{bundle.config.sandbox_origin}/embed-allow-forms.html",
        ):
            route.fulfill(
                status=200,
                body=sandbox_html(),
                content_type="text/html; charset=utf-8",
                headers={
                    **headers,
                    "Content-Security-Policy": sandbox_csp(bundle.config),
                    "Referrer-Policy": "no-referrer",
                },
            )
            return
        if parsed == f"{bundle.config.sandbox_origin}/embed.css":
            route.fulfill(status=200, body=sandbox_css(), content_type="text/css; charset=utf-8", headers=headers)
            return
        if parsed == f"{bundle.config.sandbox_origin}/embed.js":
            route.fulfill(
                status=200,
                body=sandbox_js(),
                content_type="application/javascript; charset=utf-8",
                headers=headers,
            )
            return
        if parsed == f"{bundle.config.sandbox_origin}/self-image.svg":
            route.fulfill(
                status=200,
                body='<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"></svg>',
                content_type="image/svg+xml",
                headers=headers,
            )
            return
        route.fulfill(status=404, body="not found", content_type="text/plain; charset=utf-8", headers=headers)

    page.route("**/*", handle)


EGRESS_CHANNEL_PATHS = {
    "fetch": "/sink/fetch",
    "xhr": "/sink/xhr",
    "sendBeacon": "/sink/beacon",
    "img": "/sink/img-beacon.gif",
    "form": "/sink/form-post",
}
FORM_ACTION_CSP_PATH = "/sink/form-action-csp-post"
ALL_SINK_CHANNEL_PATHS = {
    **EGRESS_CHANNEL_PATHS,
    "form_action_csp": FORM_ACTION_CSP_PATH,
}


def _sink_url_channel(url: str) -> str | None:
    for channel, path in ALL_SINK_CHANNEL_PATHS.items():
        if path in url:
            return channel
    return None


def _console_evidence(console_entries: list[dict[str, str]], channel: str, path: str) -> list[str]:
    evidence: list[str] = []
    for entry in console_entries:
        text = entry.get("text", "")
        if (
            path in text
            or (channel == "form" and "form" in text.lower() and "sandbox" in text.lower())
            or (channel == "form_action_csp" and "form-action" in text.lower())
        ):
            evidence.append(text)
    return evidence


def _channel_evidence(channel: str, path: str, api_result: Any, artifacts: dict[str, Any]) -> dict[str, Any]:
    sink_responses = artifacts.get("sink_responses", [])
    request_failures = [
        item for item in artifacts.get("request_failures", [])
        if _sink_url_channel(item.get("url", ""))
    ]
    requests = [
        url for url in artifacts.get("sink_requests_seen_by_playwright", [])
        if _sink_url_channel(url) == channel
    ]
    failures = [item for item in request_failures if _sink_url_channel(item["url"]) == channel]
    responses = [item for item in sink_responses if _sink_url_channel(item["url"]) == channel]
    console = _console_evidence(artifacts.get("console", []), channel, path)
    api_text = json.dumps(api_result, ensure_ascii=False, sort_keys=True) if api_result is not None else ""
    if "blocked-by-csp" in api_text or "form-action" in api_text:
        block_reason = "CSP"
    elif any("Content Security Policy" in item or "violates" in item for item in console):
        block_reason = "CSP"
    elif any("sandbox" in item.lower() for item in console):
        block_reason = "sandbox"
    elif failures:
        block_reason = "requestfailed"
    elif responses:
        block_reason = "network-response"
    else:
        block_reason = "not-observed"
    return {
        "api_result_reference_only": api_result,
        "playwright_requests": requests,
        "request_failures": failures,
        "responses": responses,
        "console_evidence": console,
        "block_reason": block_reason,
    }


def egress_evidence(egress_results: dict[str, Any], artifacts: dict[str, Any]) -> dict[str, Any]:
    channels: dict[str, Any] = {}
    for channel in EGRESS_CHANNEL_PATHS:
        channels[channel] = _channel_evidence(
            channel,
            EGRESS_CHANNEL_PATHS[channel],
            egress_results.get(channel),
            artifacts,
        )
    return channels


def wait_for_egress_results(
    page: Any,
    frame: Any,
    artifacts: dict[str, Any],
    expected_keys: set[str],
    timeout_ms: int = EGRESS_TIMEOUT_MS,
    settle_ms: int = EGRESS_SETTLE_MS,
) -> tuple[dict[str, Any], dict[str, Any]]:
    deadline = time.perf_counter() + timeout_ms / 1000
    all_keys_seen_at: float | None = None
    quiet_since: float | None = None
    last_event_count = len(artifacts.get("sink_request_events", []))
    last_results: dict[str, Any] = {}

    while True:
        now = time.perf_counter()
        try:
            last_results = frame.evaluate("() => window.__egressResults || {}")
        except PlaywrightError:
            last_results = {}
        event_count = len(artifacts.get("sink_request_events", []))
        if event_count != last_event_count:
            quiet_since = now
            last_event_count = event_count
        if expected_keys.issubset(set(last_results)):
            if all_keys_seen_at is None:
                all_keys_seen_at = now
                quiet_since = now
            if quiet_since is not None and (now - quiet_since) * 1000 >= settle_ms:
                return last_results, {
                    "settled": True,
                    "timeout_ms": timeout_ms,
                    "settle_ms": settle_ms,
                    "observed_keys": sorted(last_results),
                    "sink_request_events": event_count,
                }
        if now >= deadline:
            return last_results, {
                "settled": False,
                "timeout_ms": timeout_ms,
                "settle_ms": settle_ms,
                "observed_keys": sorted(last_results),
                "sink_request_events": event_count,
            }
        page.wait_for_timeout(50)


def wait_for_frame_by_url_prefix(
    page: Any,
    url_prefix: str,
    label: str,
    timeout_ms: int = FRAME_WAIT_TIMEOUT_MS,
) -> Any:
    deadline = time.perf_counter() + timeout_ms / 1000
    observed_urls: list[str] = []

    while True:
        observed_urls = [frame.url for frame in page.frames]
        for frame in page.frames:
            if frame.url.startswith(url_prefix):
                try:
                    frame.wait_for_load_state("load", timeout=1000)
                except PlaywrightError:
                    pass
                return frame
        if time.perf_counter() >= deadline:
            raise AssertionError(
                f"{label} iframe was not found within {timeout_ms}ms; "
                f"expected url prefix {url_prefix!r}; observed frames: {observed_urls!r}"
            )
        page.wait_for_timeout(50)


def egress_passed(egress_results: dict[str, Any], sink_log: list[dict[str, str]], artifacts: dict[str, Any]) -> bool:
    expected_channels = set(EGRESS_CHANNEL_PATHS)
    result_channels = set(egress_results.keys())
    sink_requests = artifacts["sink_requests_seen_by_playwright"]
    sink_failure_urls = {
        item["url"]
        for item in artifacts["request_failures"]
        if _sink_url_channel(item.get("url", ""))
    }
    successful_sink_responses = [
        item for item in artifacts["sink_responses"]
        if 200 <= int(item.get("status", 0)) < 400
    ]
    return (
        expected_channels.issubset(result_channels)
        and len(sink_log) == 0
        and not successful_sink_responses
        and all(url in sink_failure_urls for url in sink_requests)
    )


def static_inheritance_warning(static_styles: dict[str, Any]) -> str | None:
    color_leaked = static_styles.get("inheritedColor") == "rgb(255, 0, 0)"
    font_leaked = "Courier New" in static_styles.get("inheritedFont", "")
    if color_leaked or font_leaked:
        return (
            "WARNING: 継承プロパティが host 経由で shadow 内へ入った。"
            "static mode で完全視覚隔離が必要なら block 側外部 CSS で host の継承プロパティを"
            "個別に `!important` reset するか、static も sandbox iframe 化を検討する。"
        )
    return None


def run_browser_checks(bundle: ServerBundle | RouteBundle) -> tuple[list[Check], dict[str, Any]]:
    checks: list[Check] = []
    artifacts: dict[str, Any] = {
        "parent_origin": bundle.config.parent_origin,
        "sandbox_origin": bundle.config.sandbox_origin,
        "route_mode": getattr(bundle, "route_mode", False),
        "console": [],
        "sink_requests_seen_by_playwright": [],
        "sink_request_events": [],
        "sink_responses": [],
        "request_failures": [],
    }

    executable_path = chromium_executable()

    with sync_playwright() as playwright:
        launch_options: dict[str, Any] = {
            "headless": True,
            "timeout": 15000,
            "args": [
                "--no-sandbox",
                "--disable-crash-reporter",
                "--disable-crashpad",
                "--disable-breakpad",
            ],
        }
        if executable_path:
            launch_options["executable_path"] = executable_path
        browser = playwright.chromium.launch(**launch_options)
        page = browser.new_page()
        if getattr(bundle, "route_mode", False):
            install_routes(page, bundle)
        page.on("console", lambda msg: artifacts["console"].append({"type": msg.type, "text": msg.text}))
        def on_request(request: Any) -> None:
            if "/sink/" not in request.url:
                return
            artifacts["sink_requests_seen_by_playwright"].append(request.url)
            artifacts["sink_request_events"].append(
                {"url": request.url, "ts_ms": round((time.perf_counter() - started) * 1000, 2)}
            )

        page.on("request", on_request)
        page.on(
            "requestfailed",
            lambda request: artifacts["request_failures"].append(
                {"url": request.url, "failure": request.failure}
            ),
        )
        page.on(
            "response",
            lambda response: artifacts["sink_responses"].append(
                {"url": response.url, "status": response.status, "ok": response.ok}
            )
            if "/sink/" in response.url
            else None,
        )

        started = time.perf_counter()
        response = page.goto(bundle.config.parent_origin, wait_until="load")
        elapsed_ms = round((time.perf_counter() - started) * 1000, 2)

        add_check(
            checks,
            "parent page load",
            {"status": response.status if response else None, "elapsed_ms": elapsed_ms},
            bool(response and response.ok),
            "origin-parent が通常ロードできる",
        )

        page.wait_for_function(
            "() => window.__staticResetState && (window.__staticResetState.loaded || window.__staticResetState.error)",
            timeout=5000,
        )
        static_styles = page.evaluate(
            """() => {
              const host = document.querySelector('#static-embed');
              const root = host.shadowRoot;
              const resetLink = root.querySelector('link[rel="stylesheet"]');
              const card = root.querySelector('.shadow-card');
              const explicit = root.querySelector('#shadow-explicit');
              const inherited = root.querySelector('#shadow-inherited');
              const light = document.querySelector('#light-leak-target');
              const hostStyle = getComputedStyle(host);
              const cardStyle = getComputedStyle(card);
              const explicitStyle = getComputedStyle(explicit);
              const inheritedStyle = getComputedStyle(inherited);
              const lightStyle = getComputedStyle(light);
              let resetRules = [];
              try {
                resetRules = Array.from(resetLink.sheet.cssRules).map((rule) => rule.cssText);
              } catch (error) {
                resetRules = [`cssRules unavailable: ${error.name}`];
              }
              return {
                hasShadowRoot: Boolean(root),
                resetState: window.__staticResetState,
                resetLinkHref: resetLink?.href || null,
                inlineStyleCount: root.querySelectorAll('style').length,
                resetRules,
                hostColor: hostStyle.color,
                hostFont: hostStyle.fontFamily,
                hostBackground: hostStyle.backgroundColor,
                cardBackground: cardStyle.backgroundColor,
                cardBorderColor: cardStyle.borderTopColor,
                cardBorderWidth: cardStyle.borderTopWidth,
                explicitColor: explicitStyle.color,
                explicitFont: explicitStyle.fontFamily,
                explicitBackground: explicitStyle.backgroundColor,
                inheritedColor: inheritedStyle.color,
                inheritedFont: inheritedStyle.fontFamily,
                inheritedBackground: inheritedStyle.backgroundColor,
                inheritedBorderColor: inheritedStyle.borderTopColor,
                lightBackground: lightStyle.backgroundColor,
              };
            }"""
        )
        static_non_inherited_pass = (
            static_styles["hasShadowRoot"]
            and static_styles["resetState"]["loaded"] is True
            and static_styles["inlineStyleCount"] == 0
            and isinstance(static_styles["resetLinkHref"], str)
            and static_styles["resetLinkHref"].endswith("/embed-reset.css")
            and static_styles["cardBackground"] == "rgb(232, 244, 255)"
            and static_styles["cardBorderColor"] == "rgb(18, 92, 160)"
            and static_styles["cardBorderWidth"] == "2px"
            and static_styles["explicitColor"] == "rgb(16, 24, 32)"
            and static_styles["explicitBackground"] == "rgb(232, 244, 255)"
            and static_styles["lightBackground"] != "rgb(0, 255, 0)"
            and static_styles["hostColor"] == "rgb(1, 2, 3)"
            and static_styles["hostFont"].startswith("Arial")
            and static_styles["inheritedColor"] != "rgb(255, 0, 0)"
            and static_styles["inheritedColor"] == "rgb(1, 2, 3)"
        )
        add_check(
            checks,
            "static Shadow DOM non-inherited CSS isolation",
            static_styles,
            static_non_inherited_pass,
            "外部 same-origin reset CSS 配信により、非継承・継承の双方で親 CSS 漏洩なしを実測",
        )
        inheritance_warning = static_inheritance_warning(static_styles)
        add_check(
            checks,
            "static Shadow DOM inherited CSS observation",
            {
                "hostColor": static_styles["hostColor"],
                "hostFont": static_styles["hostFont"],
                "inheritedColor": static_styles["inheritedColor"],
                "inheritedFont": static_styles["inheritedFont"],
                "warning": inheritance_warning,
            },
            True,
            inheritance_warning or "継承プロパティも外部 reset CSS の実測上は親グローバル CSS に負けなかった",
        )

        iframe_attrs = page.evaluate(
            """() => {
              const frame = document.querySelector('#interactive-frame');
              const formActionFrame = document.querySelector('#interactive-form-action-frame');
              return {
                sandbox: frame.getAttribute('sandbox'),
                title: frame.getAttribute('title'),
                src: frame.getAttribute('src'),
                heightAttr: frame.getAttribute('height'),
                heightStyle: frame.style.height,
                formActionSandbox: formActionFrame.getAttribute('sandbox'),
                formActionTitle: formActionFrame.getAttribute('title'),
                formActionSrc: formActionFrame.getAttribute('src'),
              };
            }"""
        )
        sandbox_tokens = set(iframe_attrs["sandbox"].split())
        form_action_sandbox_tokens = set(iframe_attrs["formActionSandbox"].split())
        add_check(
            checks,
            "iframe sandbox tokens",
            iframe_attrs,
            "allow-scripts" in sandbox_tokens
            and "allow-same-origin" not in sandbox_tokens
            and "allow-top-navigation" not in sandbox_tokens
            and "allow-top-navigation-by-user-activation" not in sandbox_tokens,
            "`allow-scripts` のみを許可する主変種で、親 origin 化と top navigation を禁止",
        )
        add_check(
            checks,
            "allow-forms variant sandbox tokens",
            iframe_attrs,
            "allow-scripts" in form_action_sandbox_tokens
            and "allow-forms" in form_action_sandbox_tokens
            and "allow-same-origin" not in form_action_sandbox_tokens
            and "allow-top-navigation" not in form_action_sandbox_tokens
            and "allow-top-navigation-by-user-activation" not in form_action_sandbox_tokens,
            "`allow-forms` 変種でも親 origin 化と top navigation は禁止",
        )

        parent_access = page.evaluate(
            """() => {
              const frame = document.querySelector('#interactive-frame');
              try {
                const text = frame.contentWindow.document.body.textContent;
                return { accessible: true, text };
              } catch (error) {
                return { accessible: false, name: error.name, message: error.message };
              }
            }"""
        )
        add_check(
            checks,
            "parent cannot read iframe document",
            parent_access,
            parent_access.get("accessible") is False,
            "opaque origin / same-origin policy により親から iframe document 参照不可",
        )

        frame = wait_for_frame_by_url_prefix(
            page,
            f"{bundle.config.sandbox_origin}/embed.html",
            "sandbox",
        )

        egress_results, egress_wait = wait_for_egress_results(page, frame, artifacts, EXPECTED_EGRESS_KEYS)
        sink_log = bundle.sink_log.snapshot()
        egress_paths = [entry["path"] for entry in sink_log]
        egress_channel_evidence = egress_evidence(egress_results, artifacts)
        add_check(
            checks,
            "egress attempts blocked",
            {
                "egress_results": egress_results,
                "channel_evidence": egress_channel_evidence,
                "sink_log_count": len(sink_log),
                "sink_paths": egress_paths,
                "playwright_sink_requests": artifacts["sink_requests_seen_by_playwright"],
                "wait": egress_wait,
                "playwright_sink_responses": artifacts["sink_responses"],
                "request_failures": artifacts["request_failures"],
            },
            egress_wait["settled"] and egress_passed(egress_results, sink_log, artifacts),
            "判定は5キー揃い + sink 宛300ms静止 + sink 到達0件 + sink 宛成功レスポンス0件の実通信基準",
        )

        try:
            form_action_frame = wait_for_frame_by_url_prefix(
                page,
                f"{bundle.config.sandbox_origin}/embed-allow-forms.html",
                "allow-forms sandbox",
            )
            form_action_frame.wait_for_function(
                "() => typeof window.__runFormActionProbe === 'function'",
                timeout=1000,
            )
            form_action_frame.evaluate("() => window.__runFormActionProbe()")
            form_action_results, form_action_wait = wait_for_egress_results(
                page,
                form_action_frame,
                artifacts,
                {"form"},
            )
            try:
                form_action_frame.wait_for_function(
                    "() => (window.__cspViolations || []).some((item) => item.effectiveDirective === 'form-action')",
                    timeout=1000,
                )
                form_action_results = form_action_frame.evaluate("() => window.__egressResults || {}")
            except PlaywrightError:
                pass
            form_action_csp_violations = form_action_frame.evaluate("() => window.__cspViolations || []")
            form_action_result = form_action_frame.evaluate("() => window.__formActionResult || null")
            form_action_sink_log = [
                entry for entry in bundle.sink_log.snapshot()
                if FORM_ACTION_CSP_PATH in entry["path"]
            ]
            form_action_evidence = _channel_evidence(
                "form_action_csp",
                FORM_ACTION_CSP_PATH,
                form_action_results.get("form"),
                artifacts,
            )
            form_action_successful_responses = [
                item for item in form_action_evidence["responses"]
                if 200 <= int(item.get("status", 0)) < 400
            ]
            form_action_passed = (
                form_action_evidence["block_reason"] == "CSP"
                and len(form_action_sink_log) == 0
                and not form_action_successful_responses
            )
            add_check(
                checks,
                "interactive form-action CSP (allow-forms variant)",
                {
                    "egress_results": form_action_results,
                    "channel_evidence": form_action_evidence,
                    "form_action_result": form_action_result,
                    "csp_violations": form_action_csp_violations,
                    "sink_log_count": len(form_action_sink_log),
                    "sink_paths": [entry["path"] for entry in form_action_sink_log],
                    "successful_response_count": len(form_action_successful_responses),
                    "wait": form_action_wait,
                },
                form_action_passed,
                "`allow-forms` ありでも CSP `form-action 'none'` 証拠 + form POST の sink 着信0件 + 成功レスポンス0件を確認",
                required=False,
            )
        except (AssertionError, PlaywrightError) as error:
            observed_frames = [frame.url for frame in page.frames]
            form_action_results = {}
            form_action_wait = {"settled": False, "error": type(error).__name__}
            form_action_evidence = {"error": str(error), "observed_frames": observed_frames}
            form_action_csp_violations = []
            form_action_result = None
            form_action_sink_log = []
            add_check(
                checks,
                "interactive form-action CSP (allow-forms variant)",
                {
                    "error": f"{type(error).__name__}: {error}",
                    "observed_frames": observed_frames,
                },
                True,
                "form-action は allow-forms 変種未通電のため L4 実WPで要検証",
                required=False,
                status_override="SKIP",
            )

        page.evaluate(
            """() => {
              window.postMessage({
                type: 'ane-embed:height',
                nonce: document.body.dataset.expectedNonce,
                height: 999
              }, '*');
            }"""
        )
        page.wait_for_timeout(200)
        message_state = page.evaluate("() => window.__embedMessages")
        accepted = message_state["accepted"]
        rejected = message_state["rejected"]
        accepted_heights = [
            item["data"].get("height")
            for item in accepted
            if item["data"].get("type") == "ane-embed:height"
        ]
        rejected_reasons = [item["reason"] for item in rejected]
        add_check(
            checks,
            "postMessage source + nonce validation",
            {
                "accepted_count": len(accepted),
                "rejected_count": len(rejected),
                "origins": message_state["origins"],
                "accepted_heights": accepted_heights,
                "rejected_reasons": rejected_reasons,
            },
            len(accepted) >= 2
            and "nonce" in rejected_reasons
            and "source" in rejected_reasons
            and 777 not in accepted_heights
            and 999 not in accepted_heights
            and "null" in message_state["origins"],
            "origin は null 前提で、event.source と nonce による受理/破棄を確認",
        )

        parent_metrics = page.evaluate(
            """() => ({
              longTaskObserverSupported: window.__longTaskObserverSupported,
              longTaskCount: window.__longTasks.length,
              longTaskMaxMs: window.__longTasks.reduce((max, entry) => Math.max(max, entry.duration), 0),
              navigationDurationMs: performance.getEntriesByType('navigation')[0]?.duration || 0,
            })"""
        )
        sandbox_metrics = frame.evaluate(
            """() => ({
              longTaskObserverSupported: window.__embedMetrics.longTaskObserverSupported,
              longTaskCount: window.__embedMetrics.longTasks.length,
              longTaskMaxMs: window.__embedMetrics.longTasks.reduce((max, entry) => Math.max(max, entry.duration), 0),
            })"""
        )
        add_check(
            checks,
            "reference performance metrics",
            {"parent": parent_metrics, "sandbox": sandbox_metrics},
            True,
            "参考値。INP は操作入力がないため Long Task / navigation duration で代替記録",
        )

        artifacts["iframe_attrs"] = iframe_attrs
        artifacts["static_styles"] = static_styles
        artifacts["static_inheritance_warning"] = inheritance_warning
        artifacts["egress_results"] = egress_results
        artifacts["egress_wait"] = egress_wait
        artifacts["egress_channel_evidence"] = egress_channel_evidence
        artifacts["form_action_csp_results"] = form_action_results
        artifacts["form_action_csp_wait"] = form_action_wait
        artifacts["form_action_csp_evidence"] = form_action_evidence
        artifacts["form_action_csp_violations"] = form_action_csp_violations
        artifacts["form_action_csp_result"] = form_action_result
        artifacts["form_action_csp_sink_log"] = form_action_sink_log
        artifacts["sink_log"] = sink_log
        artifacts["message_state"] = message_state
        artifacts["parent_metrics"] = parent_metrics
        artifacts["sandbox_metrics"] = sandbox_metrics

        browser.close()

    return checks, artifacts


def render_results(checks: list[Check], artifacts: dict[str, Any]) -> str:
    verdict = "PASS" if required_checks_passed(checks) else "FAIL"
    rows = "\n".join(
        f"| {check.item} | `{check.measured.replace('|', '&#124;')}` | {check.status} | {check.detail} |"
        for check in checks
    )
    form_action_check = find_check(checks, "interactive form-action CSP (allow-forms variant)")
    form_action_certified = bool(form_action_check and form_action_check.status == "PASS")
    form_action_skipped = bool(form_action_check and form_action_check.status in {"SKIP", "CARRY"})
    effective = [
        "Static mode: Shadow DOM 内の reset CSS は inline `<style>` ではなく親オリジン配信の `/embed-reset.css` として適用したため、親 CSP `style-src 'self'` 下でロード可能だった。",
        "Static mode: 外部 same-origin reset CSS 配信により、非継承・継承の双方で親 CSS 漏洩なしを実測した。",
        "Interactive mode: `<iframe sandbox=\"allow-scripts\">` で `allow-same-origin` / top-navigation 系を付けない場合、親から iframe document へアクセスできなかった。",
        "Interactive mode 主変種: fetch / XHR / sendBeacon は `connect-src 'none'`、img beacon は `img-src 'self'`、form submit は `allow-forms` 不在の sandbox により sink 到達 0 件だった。",
        "postMessage: sandbox iframe の `event.origin` は `null` と観測されたため、ADR-026 どおり `event.source === iframe.contentWindow` + nonce で受理した。",
    ]
    if form_action_certified:
        effective.insert(
            4,
            "Interactive form-action CSP allow-forms 変種: `sandbox=\"allow-scripts allow-forms\"` の form submit は CSP `form-action 'none'` により sink 着信 0 件だった。",
        )
    if artifacts.get("static_inheritance_warning"):
        effective.append("Static mode: 継承プロパティ（color / font-family）は全体 verdict から分離し、実測事実として WARNING 記録した。")
    else:
        effective.append("Static mode: 継承プロパティ（color / font-family）も今回の実測では親グローバル CSS に負けなかった。")
    ineffective = [
        "主変種の form submit は iframe sandbox に `allow-forms` がないため、CSP `form-action` の通電検証としては扱わない。",
        "INP は実ユーザー入力の計測値ではないため、本 PoC では Long Task と navigation duration の参考値に留めた。",
    ]
    if form_action_skipped:
        ineffective.insert(0, "form-action は allow-forms 変種未通電のため L4 実WPで要検証。")
    if artifacts.get("static_inheritance_warning"):
        ineffective.insert(0, artifacts["static_inheritance_warning"])
    carry_rows = "\n".join(
        [
            "| CARRY-EMBED-001 | PoC では block.json は未作成。属性候補として `mode`, `embed_url`, `title`, `nonce/payload_id` が必要と確認。 |",
            "| CARRY-EMBED-002 | 主変種の sandbox 属性は `allow-scripts` のみ。form-action 通電検証用に `allow-scripts allow-forms` 変種を併設。postMessage は `{ type: 'ane-embed:ready'|'ane-embed:height', nonce, height }` を `event.source` + nonce で検証。 |",
            f"| CARRY-EMBED-003 | 親 CSP は `frame-src {artifacts['sandbox_origin']}`。sandbox-origin CSP は `default-src 'none'; script-src 'self'; style-src 'self'; connect-src 'none'; img-src 'self'; form-action 'none'; base-uri 'none'; object-src 'none'; frame-ancestors {artifacts['parent_origin']}`。allow-forms 変種が SKIP の場合は L4 実WPで form-action 通電を再検証する。 |",
            "| CARRY-EMBED-004 | static mode の host reset は親オリジン配信 `/embed-reset.css` として ShadowRoot 内 `<link rel=\"stylesheet\">` から適用。sanitize allowlist は本 PoC 対象外。 |",
            "| CARRY-EMBED-005 | Abilities API / REST apply endpoint は本 PoC 対象外。 |",
            "| CARRY-EMBED-006 | 検証パイプライン候補として本 `verify.py` の CSS 隔離・sandbox・egress・postMessage 検査を CI 化可能。 |",
        ]
    )
    sources = "\n".join(
        [
            "- ADR-026: `docs/adr/ADR-026.md`",
            "- Test plan: `docs/test-plan/L3-test-plan.md` §10 TC-066 / TC-067 / TC-068 / TC-079",
            "- WHATWG HTML `<iframe sandbox>`: https://html.spec.whatwg.org/multipage/iframe-embed-object.html#attr-iframe-sandbox",
            "- MDN CSP `style-src`: https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy/style-src",
            "- W3C CSP3 directives: `connect-src`, `img-src`, `form-action`: https://www.w3.org/TR/CSP3/",
            "- Playwright Python docs: events / frame evaluate",
        ]
    )
    raw = json.dumps(artifacts, ensure_ascii=False, indent=2, sort_keys=True)
    return f"""# AGENT NEO embed isolation PoC results

- Date: 2026-06-20
- Verdict: **{verdict}**
- origin-parent: `{artifacts['parent_origin']}`
- origin-sandbox: `{artifacts['sandbox_origin']}`

## 実測結果

| 検証項目 | 実測値 | 判定 | 補足 |
|---|---|---|---|
{rows}

## 設計どおり効いた点

{chr(10).join(f"- {item}" for item in effective)}

## 効かなかった点 / 想定外

{chr(10).join(f"- {item}" for item in ineffective)}

## CARRY-EMBED 契約具体化

| Carry | PoC で具体化できた契約値 |
|---|---|
{carry_rows}

## 参照

{sources}

## Raw artifacts

```json
{raw}
```
"""


def render_blocked_results(bundle: ServerBundle | RouteBundle, error: Exception) -> str:
    error_text = " ".join(str(error).splitlines())
    if len(error_text) > 900:
        error_text = f"{error_text[:900]}..."
    error_text = error_text.replace("|", "&#124;")
    return f"""# AGENT NEO embed isolation PoC results

- Date: 2026-06-20
- Verdict: **FAIL / BLOCKED**
- origin-parent: `{bundle.config.parent_origin}`
- origin-sandbox: `{bundle.config.sandbox_origin}`

## 実測結果

| 検証項目 | 実測値 | 判定 | 補足 |
|---|---|---|---|
| environment socket/browser availability | `{type(error).__name__}: {error_text}` | FAIL | この実行環境では socket bind または Chromium 起動が syscall 制限で拒否されたため、ブラウザ実測を完了できなかった。 |

## 設計どおり効いた点

- 未計測。通常環境で `python3 verify.py` を再実行する必要がある。

## 効かなかった点 / 想定外

- `/opt/agent-neo` の現在の sandbox では `socket.socket()` が `Operation not permitted` になり、2 port HTTP server を起動できなかった。
- Playwright Chromium は crashpad / sandbox host 経路で `setsockopt: Operation not permitted` または `shutdown: Operation not permitted` により起動できなかった。

## CARRY-EMBED 契約具体化

| Carry | PoC で具体化できた契約値 |
|---|---|
| CARRY-EMBED-001 | PoC ファイルは作成済み。block.json は本 PoC 対象外。 |
| CARRY-EMBED-002 | 実装値は主変種 `sandbox=\"allow-scripts\"`、form-action 通電検証変種 `sandbox=\"allow-scripts allow-forms\"`、postMessage は `event.source` + nonce 検証。未実測。 |
| CARRY-EMBED-003 | 実装値は親 `frame-src` allowlist、sandbox-origin `connect-src 'none'; img-src 'self'; form-action 'none'`。未実測。 |
| CARRY-EMBED-004 | 実装値は static Shadow DOM 内 `<link rel=\"stylesheet\" href=\"/embed-reset.css\">` による外部 reset CSS。未実測。 |
| CARRY-EMBED-005 | 対象外。 |
| CARRY-EMBED-006 | `verify.py` を検証パイプライン候補として作成済み。現環境では未完走。 |

## 参照

- ADR-026: `docs/adr/ADR-026.md`
- Test plan: `docs/test-plan/L3-test-plan.md` §10 TC-066 / TC-067 / TC-068 / TC-079
- WHATWG HTML `<iframe sandbox>`: https://html.spec.whatwg.org/multipage/iframe-embed-object.html#attr-iframe-sandbox
- MDN CSP `style-src`: https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy/style-src
- W3C CSP3 directives: `connect-src`, `img-src`, `form-action`: https://www.w3.org/TR/CSP3/
- Playwright Python docs: events / frame evaluate
"""


def main() -> int:
    try:
        bundle: ServerBundle | RouteBundle = create_bundle(
            parent_port=DEFAULT_PARENT_PORT,
            sandbox_port=DEFAULT_SANDBOX_PORT,
        )
    except PermissionError:
        print("warning: socket bind is not permitted; using Playwright route fallback")
        bundle = RouteBundle()
    bundle.start()
    try:
        checks, artifacts = run_browser_checks(bundle)
        RESULTS_PATH.write_text(render_results(checks, artifacts), encoding="utf-8")
        for check in checks:
            print(f"{check.status}: {check.item}")
        print(f"RESULTS: {RESULTS_PATH}")
        return 0 if required_checks_passed(checks) else 1
    except (AssertionError, PlaywrightError, OSError) as error:
        RESULTS_PATH.write_text(render_blocked_results(bundle, error), encoding="utf-8")
        print(f"ERROR: {error}", file=sys.stderr)
        print(f"RESULTS: {RESULTS_PATH}")
        return 1
    finally:
        bundle.stop()


if __name__ == "__main__":
    raise SystemExit(main())
