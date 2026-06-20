#!/usr/bin/env python3
"""ADR-026 embed isolation PoC server.

2 つの HTTP origin を同一プロセス内の別ポートで起動する。
origin-parent はホスト記事、origin-sandbox は AI 生成 HTML 配信元を模す。
"""

from __future__ import annotations

import argparse
import json
import signal
import threading
import time
from dataclasses import dataclass
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from typing import Callable
from urllib.parse import parse_qs, quote, unquote, urlparse


DEFAULT_PARENT_PORT = 18080
DEFAULT_SANDBOX_PORT = 18090
DEFAULT_HOST = "127.0.0.1"


class SinkLog:
    """egress 到達をプロセス内で記録する簡易 sink."""

    def __init__(self) -> None:
        self._lock = threading.Lock()
        self._entries: list[dict[str, str]] = []

    def append(self, method: str, path: str, body: bytes) -> None:
        with self._lock:
            self._entries.append(
                {
                    "method": method,
                    "path": path,
                    "body": body.decode("utf-8", errors="replace"),
                    "ts": f"{time.time():.6f}",
                }
            )

    def clear(self) -> None:
        with self._lock:
            self._entries.clear()

    def snapshot(self) -> list[dict[str, str]]:
        with self._lock:
            return list(self._entries)


@dataclass(frozen=True)
class ServerConfig:
    host: str
    parent_port: int
    sandbox_port: int

    @property
    def parent_origin(self) -> str:
        return f"http://{self.host}:{self.parent_port}"

    @property
    def sandbox_origin(self) -> str:
        return f"http://{self.host}:{self.sandbox_port}"

    @property
    def nonce(self) -> str:
        return "ane-poc-nonce-20260620"


class QuietHandler(BaseHTTPRequestHandler):
    server_version = "AgentNeoEmbedPoC/1.0"

    def log_message(self, fmt: str, *args: object) -> None:
        # 検証ログは verify.py 側で集約するため HTTP access log は抑制する。
        return

    def send_text(
        self,
        status: int,
        body: str,
        content_type: str,
        headers: dict[str, str] | None = None,
    ) -> None:
        data = body.encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", content_type)
        self.send_header("Content-Length", str(len(data)))
        self.send_header("X-Content-Type-Options", "nosniff")
        if headers:
            for key, value in headers.items():
                self.send_header(key, value)
        self.end_headers()
        self.wfile.write(data)

    def send_json(self, status: int, body: object) -> None:
        self.send_text(status, json.dumps(body, indent=2), "application/json; charset=utf-8")


def make_parent_handler(config: ServerConfig, sink_log: SinkLog) -> type[QuietHandler]:
    class ParentHandler(QuietHandler):
        def _csp(self) -> str:
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

        def do_GET(self) -> None:
            path = urlparse(self.path).path
            if path in ("/", "/index.html"):
                self.send_text(
                    200,
                    parent_html(config),
                    "text/html; charset=utf-8",
                    {"Content-Security-Policy": self._csp(), "Referrer-Policy": "no-referrer"},
                )
                return
            if path == "/parent.css":
                self.send_text(200, parent_css(), "text/css; charset=utf-8")
                return
            if path == "/parent.js":
                self.send_text(200, parent_js(config), "application/javascript; charset=utf-8")
                return
            if path == "/embed-reset.css":
                self.send_text(200, embed_reset_css(), "text/css; charset=utf-8")
                return
            if path == "/__sink-log":
                self.send_json(200, sink_log.snapshot())
                return
            if path == "/__reset-sink":
                sink_log.clear()
                self.send_json(200, {"ok": True})
                return
            if path.startswith("/sink/"):
                sink_log.append("GET", self.path, b"")
                self.send_text(204, "", "text/plain; charset=utf-8")
                return
            self.send_text(404, "not found", "text/plain; charset=utf-8")

        def do_POST(self) -> None:
            path = urlparse(self.path).path
            body = self.rfile.read(int(self.headers.get("Content-Length", "0")))
            if path.startswith("/sink/"):
                sink_log.append("POST", self.path, body)
                self.send_text(204, "", "text/plain; charset=utf-8")
                return
            self.send_text(404, "not found", "text/plain; charset=utf-8")

    return ParentHandler


def make_sandbox_handler(config: ServerConfig) -> type[QuietHandler]:
    class SandboxHandler(QuietHandler):
        def _csp(self) -> str:
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

        def do_GET(self) -> None:
            path = urlparse(self.path).path
            if path in ("/embed.html", "/embed-allow-forms.html"):
                self.send_text(
                    200,
                    sandbox_html(),
                    "text/html; charset=utf-8",
                    {
                        "Content-Security-Policy": self._csp(),
                        "Referrer-Policy": "no-referrer",
                    },
                )
                return
            if path == "/embed.css":
                self.send_text(200, sandbox_css(), "text/css; charset=utf-8")
                return
            if path == "/embed.js":
                self.send_text(200, sandbox_js(), "application/javascript; charset=utf-8")
                return
            if path == "/self-image.svg":
                self.send_text(
                    200,
                    '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"></svg>',
                    "image/svg+xml",
                )
                return
            self.send_text(404, "not found", "text/plain; charset=utf-8")

        def do_POST(self) -> None:
            self.send_text(404, "not found", "text/plain; charset=utf-8")

    return SandboxHandler


def parent_html(config: ServerConfig) -> str:
    sandbox_src = (
        f"{config.sandbox_origin}/embed.html"
        f"?nonce={quote(config.nonce)}"
        f"&sink={quote(config.parent_origin + '/sink')}"
    )
    sandbox_form_action_src = (
        f"{config.sandbox_origin}/embed-allow-forms.html"
        f"?nonce={quote(config.nonce)}"
        f"&sink={quote(config.parent_origin + '/sink')}"
        f"&probe=form-only"
        f"&formPath=form-action-csp-post"
    )
    return f"""<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AGENT NEO embed isolation PoC</title>
  <link rel="stylesheet" href="/parent.css">
  <script defer src="/parent.js"></script>
</head>
<body data-expected-nonce="{config.nonce}">
  <main>
    <h1>AGENT NEO embed isolation PoC</h1>
    <section aria-labelledby="static-title">
      <h2 id="static-title">Static mode</h2>
      <p id="light-leak-target" class="light-leak-target">Light DOM leak sentinel</p>
      <div id="static-embed" class="embed-shell" data-mode="static"></div>
    </section>
    <section aria-labelledby="interactive-title">
      <h2 id="interactive-title">Interactive mode</h2>
      <iframe
        id="interactive-frame"
        title="AGENT NEO interactive embed PoC"
        src="{sandbox_src}"
        sandbox="allow-scripts"
        loading="eager"
        width="100%"
        height="160"></iframe>
    </section>
    <section aria-labelledby="interactive-form-action-title">
      <h2 id="interactive-form-action-title">Interactive form-action CSP variant</h2>
      <iframe
        id="interactive-form-action-frame"
        title="AGENT NEO interactive form-action CSP PoC"
        src="{sandbox_form_action_src}"
        sandbox="allow-scripts allow-forms"
        loading="eager"
        width="100%"
        height="120"></iframe>
    </section>
    <pre id="message-log" aria-live="polite"></pre>
  </main>
</body>
</html>
"""


def parent_css() -> str:
    return """* {
  color: rgb(255, 0, 0) !important;
  font-family: "Courier New", monospace !important;
}

body {
  margin: 0;
  background: rgb(255, 245, 245);
}

main {
  max-width: 880px;
  margin: 0 auto;
  padding: 24px;
}

.embed-shell {
  border: 6px solid rgb(255, 0, 0) !important;
  background: rgb(255, 230, 230) !important;
}

.light-leak-target {
  background: rgb(250, 250, 250) !important;
  padding: 8px;
}

iframe {
  border: 3px solid rgb(255, 0, 0) !important;
  display: block;
}
"""


def parent_js(config: ServerConfig) -> str:
    sandbox_origin = json.dumps(config.sandbox_origin)
    return f"""(() => {{
  const sandboxOrigin = {sandbox_origin};
  const expectedNonce = document.body.dataset.expectedNonce;
  const frame = document.getElementById('interactive-frame');
  const logNode = document.getElementById('message-log');

  window.__embedMessages = {{
    accepted: [],
    rejected: [],
    origins: [],
    sandboxOrigin,
  }};
  window.__longTasks = [];

  if ('PerformanceObserver' in window) {{
    try {{
      const observer = new PerformanceObserver((list) => {{
        for (const entry of list.getEntries()) {{
          window.__longTasks.push({{ name: entry.name, duration: entry.duration }});
        }}
      }});
      observer.observe({{ type: 'longtask', buffered: true }});
      window.__longTaskObserverSupported = true;
    }} catch (error) {{
      window.__longTaskObserverSupported = false;
    }}
  }} else {{
    window.__longTaskObserverSupported = false;
  }}

  const staticHost = document.getElementById('static-embed');
  const shadow = staticHost.attachShadow({{ mode: 'open' }});
  window.__staticResetState = {{
    href: new URL('/embed-reset.css', window.location.href).href,
    loaded: false,
    error: false,
  }};
  window.__staticResetReady = new Promise((resolve) => {{
    const resetLink = document.createElement('link');
    resetLink.rel = 'stylesheet';
    resetLink.href = '/embed-reset.css';
    resetLink.addEventListener('load', () => {{
      window.__staticResetState.loaded = true;
      resolve(window.__staticResetState);
    }});
    resetLink.addEventListener('error', () => {{
      window.__staticResetState.error = true;
      resolve(window.__staticResetState);
    }});
    shadow.append(resetLink);
  }});

  const article = document.createElement('article');
  article.className = 'shadow-card';
  article.innerHTML = `
    <h3 id="shadow-explicit">Shadow explicit probe</h3>
    <p id="shadow-inherited">Shadow inherited probe</p>
  `;
  shadow.append(article);

  window.addEventListener('message', (event) => {{
    const data = event.data || {{}};
    window.__embedMessages.origins.push(event.origin);

    if (event.source !== frame.contentWindow) {{
      window.__embedMessages.rejected.push({{ reason: 'source', origin: event.origin, data }});
      return;
    }}
    if (data.nonce !== expectedNonce) {{
      window.__embedMessages.rejected.push({{ reason: 'nonce', origin: event.origin, data }});
      return;
    }}
    if (data.type !== 'ane-embed:ready' && data.type !== 'ane-embed:height') {{
      window.__embedMessages.rejected.push({{ reason: 'type', origin: event.origin, data }});
      return;
    }}

    window.__embedMessages.accepted.push({{ origin: event.origin, data }});
    if (data.type === 'ane-embed:height') {{
      frame.style.height = `${{Math.max(80, Math.min(600, Number(data.height) || 0))}}px`;
    }}
    logNode.textContent = JSON.stringify(window.__embedMessages, null, 2);
  }});
}})();
"""


def embed_reset_css() -> str:
    return """:host {
  all: initial !important;
  display: block !important;
  box-sizing: border-box !important;
  padding: 12px !important;
  color: rgb(1, 2, 3) !important;
  font-family: Arial, sans-serif !important;
  line-height: 1.5 !important;
  background: rgb(238, 247, 255) !important;
}

:host *,
:host *::before,
:host *::after {
  box-sizing: border-box;
}

.shadow-card {
  border: 2px solid rgb(18, 92, 160);
  padding: 12px;
  background: rgb(232, 244, 255);
}

#shadow-explicit {
  color: rgb(16, 24, 32);
  font-family: Arial, sans-serif;
  background: rgb(232, 244, 255);
  border-color: rgb(18, 92, 160);
}

#shadow-inherited {
  color: inherit;
  font-family: inherit;
  background: rgb(232, 244, 255);
  border: 1px solid rgb(18, 92, 160);
}

.light-leak-target {
  background: rgb(0, 255, 0) !important;
}
"""


def sandbox_html() -> str:
    return """<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sandbox embed</title>
  <link rel="stylesheet" href="/embed.css">
  <script defer src="/embed.js"></script>
</head>
<body>
  <main>
    <h1 id="sandbox-probe">Sandbox interactive embed</h1>
    <p>egress attempts run automatically.</p>
    <img src="/self-image.svg" width="1" height="1" alt="">
  </main>
</body>
</html>
"""


def sandbox_css() -> str:
    return """body {
  margin: 0;
  color: rgb(20, 30, 40);
  font-family: Arial, sans-serif;
  background: rgb(238, 255, 242);
}

main {
  padding: 12px;
}

#sandbox-probe {
  color: rgb(20, 30, 40);
}
"""


def sandbox_js() -> str:
    return """(() => {
  const params = new URLSearchParams(window.location.search);
  const nonce = params.get('nonce') || '';
  const sink = params.get('sink') || '';
  const probe = params.get('probe') || 'all';
  const formPath = params.get('formPath') || 'form-post';

  window.__egressResults = {};
  window.__formActionResult = null;
  window.__embedMetrics = { longTasks: [], longTaskObserverSupported: false };
  window.__cspViolations = [];

  if ('PerformanceObserver' in window) {
    try {
      const observer = new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
          window.__embedMetrics.longTasks.push({ name: entry.name, duration: entry.duration });
        }
      });
      observer.observe({ type: 'longtask', buffered: true });
      window.__embedMetrics.longTaskObserverSupported = true;
    } catch (error) {
      window.__embedMetrics.longTaskObserverSupported = false;
    }
  }

  function record(name, status, detail) {
    window.__egressResults[name] = { status, detail: String(detail || '') };
  }

  document.addEventListener('securitypolicyviolation', (event) => {
    const violation = {
      effectiveDirective: event.effectiveDirective,
      violatedDirective: event.violatedDirective,
      blockedURI: event.blockedURI,
    };
    window.__cspViolations.push(violation);
    if (event.effectiveDirective === 'form-action') {
      window.__formActionResult = {
        attempted: true,
        submittedCallReturned: true,
        status: 'blocked-by-csp',
        detail: `${event.effectiveDirective}:${event.blockedURI}`,
      };
      record('form', 'blocked-by-csp', `${event.effectiveDirective}:${event.blockedURI}`);
    }
  });

  async function tryFetch() {
    try {
      await fetch(`${sink}/fetch`, { method: 'POST', mode: 'no-cors', body: 'fetch-body' });
      record('fetch', 'unexpected-resolved', 'fetch resolved');
    } catch (error) {
      record('fetch', 'blocked', error.name);
    }
  }

  function tryXhr() {
    return new Promise((resolve) => {
      try {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', `${sink}/xhr`, true);
        xhr.timeout = 1000;
        xhr.onload = () => {
          record('xhr', 'unexpected-load', xhr.status);
          resolve();
        };
        xhr.onerror = () => {
          record('xhr', 'blocked', 'error');
          resolve();
        };
        xhr.ontimeout = () => {
          record('xhr', 'blocked', 'timeout');
          resolve();
        };
        xhr.send('xhr-body');
      } catch (error) {
        record('xhr', 'blocked', error.name);
        resolve();
      }
    });
  }

  function tryBeacon() {
    try {
      const accepted = navigator.sendBeacon(`${sink}/beacon`, 'beacon-body');
      record('sendBeacon', accepted ? 'queued-by-api' : 'blocked', `return=${accepted}`);
    } catch (error) {
      record('sendBeacon', 'blocked', error.name);
    }
  }

  function tryImage() {
    return new Promise((resolve) => {
      const img = new Image();
      img.onload = () => {
        record('img', 'unexpected-load', 'loaded');
        resolve();
      };
      img.onerror = () => {
        record('img', 'blocked', 'error');
        resolve();
      };
      img.src = `${sink}/img-beacon.gif?ts=${Date.now()}`;
      setTimeout(() => {
        if (!window.__egressResults.img) {
          record('img', 'blocked', 'timeout');
          resolve();
        }
      }, 1000);
    });
  }

  function tryForm() {
    try {
      window.__formActionResult = {
        attempted: true,
        submittedCallReturned: false,
        status: 'submitting',
        detail: '',
      };
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = `${sink}/${formPath}`;
      form.target = '_self';
      const input = document.createElement('input');
      input.name = 'secret';
      input.value = 'diagnostic-answer';
      form.append(input);
      document.body.append(form);
      form.submit();
      window.__formActionResult.submittedCallReturned = true;
      if (probe === 'form-only') {
        window.setTimeout(() => {
          if (!window.__egressResults.form) {
            window.__formActionResult = {
              ...window.__formActionResult,
              status: 'submitted-without-csp-event',
              detail: 'CSP form-action event was not observed before timeout',
            };
            record('form', 'submitted-without-csp-event', 'CSP form-action event was not observed before timeout');
          }
        }, 1500);
      } else {
        window.__formActionResult.status = 'submitted-call-returned';
        window.__formActionResult.detail = 'sandbox should prevent network before CSP';
        record('form', 'submitted-call-returned', 'sandbox should prevent network before CSP');
      }
    } catch (error) {
      window.__formActionResult = {
        attempted: true,
        submittedCallReturned: false,
        status: 'blocked',
        detail: error.name,
      };
      record('form', 'blocked', error.name);
    }
  }

  window.__runFormActionProbe = tryForm;

  async function run() {
    parent.postMessage({ type: 'ane-embed:ready', nonce, height: 160 }, '*');
    parent.postMessage({ type: 'ane-embed:height', nonce: 'bad-nonce', height: 777 }, '*');
    parent.postMessage({ type: 'ane-embed:height', nonce, height: document.body.scrollHeight + 24 }, '*');
    if (probe === 'all') {
      await tryFetch();
      await tryXhr();
      tryBeacon();
      await tryImage();
      tryForm();
    }
    parent.postMessage({ type: 'ane-embed:height', nonce, height: document.body.scrollHeight + 24 }, '*');
  }

  window.addEventListener('load', () => {
    window.setTimeout(run, 50);
  });
})();
"""


def bind_server(
    host: str,
    preferred_port: int,
    handler_factory: Callable[[int], type[QuietHandler]],
) -> tuple[ThreadingHTTPServer, int, bool]:
    for port, fallback in ((preferred_port, False), (0, True)):
        try:
            server = ThreadingHTTPServer((host, port), handler_factory(port))
            actual_port = int(server.server_address[1])
            return server, actual_port, fallback
        except OSError:
            if port == 0:
                raise
    raise RuntimeError("unreachable")


class ServerBundle:
    def __init__(self, config: ServerConfig, parent: ThreadingHTTPServer, sandbox: ThreadingHTTPServer, sink_log: SinkLog) -> None:
        self.config = config
        self.parent = parent
        self.sandbox = sandbox
        self.sink_log = sink_log
        self._threads: list[threading.Thread] = []

    def start(self) -> None:
        for server in (self.parent, self.sandbox):
            thread = threading.Thread(target=server.serve_forever, daemon=True)
            thread.start()
            self._threads.append(thread)

    def stop(self) -> None:
        for server in (self.parent, self.sandbox):
            server.shutdown()
            server.server_close()
        for thread in self._threads:
            thread.join(timeout=2)


def create_bundle(host: str = DEFAULT_HOST, parent_port: int = DEFAULT_PARENT_PORT, sandbox_port: int = DEFAULT_SANDBOX_PORT) -> ServerBundle:
    sink_log = SinkLog()
    temp_config = ServerConfig(host=host, parent_port=parent_port, sandbox_port=sandbox_port)

    parent_server, actual_parent_port, parent_fallback = bind_server(
        host,
        parent_port,
        lambda _port: make_parent_handler(temp_config, sink_log),
    )
    config = ServerConfig(host=host, parent_port=actual_parent_port, sandbox_port=sandbox_port)
    if parent_fallback:
        parent_server.RequestHandlerClass = make_parent_handler(config, sink_log)

    sandbox_server, actual_sandbox_port, sandbox_fallback = bind_server(
        host,
        sandbox_port,
        lambda _port: make_sandbox_handler(config),
    )
    config = ServerConfig(host=host, parent_port=actual_parent_port, sandbox_port=actual_sandbox_port)
    parent_server.RequestHandlerClass = make_parent_handler(config, sink_log)
    sandbox_server.RequestHandlerClass = make_sandbox_handler(config)

    if parent_fallback:
        print(f"warning: parent port {parent_port} was busy; using {actual_parent_port}")
    if sandbox_fallback:
        print(f"warning: sandbox port {sandbox_port} was busy; using {actual_sandbox_port}")

    return ServerBundle(config, parent_server, sandbox_server, sink_log)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--host", default=DEFAULT_HOST)
    parser.add_argument("--parent-port", type=int, default=DEFAULT_PARENT_PORT)
    parser.add_argument("--sandbox-port", type=int, default=DEFAULT_SANDBOX_PORT)
    args = parser.parse_args()

    bundle = create_bundle(args.host, args.parent_port, args.sandbox_port)
    bundle.start()
    print(f"origin-parent:  {bundle.config.parent_origin}")
    print(f"origin-sandbox: {bundle.config.sandbox_origin}")
    print("press Ctrl+C to stop")

    stop = threading.Event()

    def handle_stop(_signum: int, _frame: object) -> None:
        stop.set()

    signal.signal(signal.SIGINT, handle_stop)
    signal.signal(signal.SIGTERM, handle_stop)

    try:
        while not stop.is_set():
            time.sleep(0.2)
    finally:
        bundle.stop()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
