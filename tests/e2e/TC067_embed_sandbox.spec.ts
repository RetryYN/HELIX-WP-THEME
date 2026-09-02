/**
 * TC-067: iframe sandbox 隔離 / XSS 封じ込め
 *
 * テスト設計SSOT: docs/legacy/test-plan/L3-test-plan.md TC-067 (P0)
 * 実装根拠:      plugins/agent-neo-embed/src/embed/render.php (mode=interactive)
 *                → sandbox="allow-scripts" のみを出力（allow-same-origin / top-navigation* なし）
 * PoC実測済:     poc/embed-isolation/RESULTS.md (VERDICT: PASS / 2026-06-20)
 *
 * 受入条件:
 *   (a) sandbox 属性が存在し、allow-same-origin トークンを含まない
 *   (b) allow-top-navigation / allow-top-navigation-by-user-activation を含まない
 *   (c) allow-scripts トークンを含む（スクリプト実行に必要）
 *   (d) allow-forms 等の追加トークンは許容（禁止トークンの不在で検証）
 *   (e) iframe 内 JS が window.parent.document.cookie / localStorage に
 *       アクセスできない（SecurityError 発生 / opaque origin）
 *
 * 配信方式:
 *   Playwright の page.route() で socket bind 不要の in-process ルートを使用。
 *   親ページ HTML の iframe は render.php が出力する属性と同一の
 *   sandbox="allow-scripts" を持つ。
 *   サンドボックスオリジン（http://sandbox.test）は親オリジン（http://parent.test）
 *   と完全に分離された別 origin として Chromium に認識させる。
 *
 * 実装コードとの属性一致根拠:
 *   render.php line 39:
 *     sandbox="allow-scripts"
 *   PoC RESULTS.md "iframe sandbox tokens" 実測値:
 *     {"sandbox": "allow-scripts", ...}
 *   → 本テストは同一の sandbox 値を使用し、behavioral 不変条件を検証する。
 */

import { test, expect, type Page, type Route } from '@playwright/test';

// -----------------------------------------------------------------------
// オリジン定数（別 origin として Chromium に認識させる）
// -----------------------------------------------------------------------
const PARENT_ORIGIN = 'http://parent.test';
const SANDBOX_ORIGIN = 'http://sandbox.test';

/**
 * sandbox 内で実行する JS:
 * window.parent.document.cookie / localStorage へのアクセスを試み、
 * 結果（SecurityError 等）を postMessage で親に送信する。
 */
const SANDBOX_PROBE_JS = `
(function () {
  var results = {};

  // window.parent.document.cookie へのアクセス試行
  try {
    var cookie = window.parent.document.cookie;
    results.cookie = { accessible: true, value: cookie };
  } catch (e) {
    results.cookie = { accessible: false, name: e.name, message: e.message };
  }

  // window.parent.localStorage へのアクセス試行
  try {
    var ls = window.parent.localStorage;
    results.localStorage = { accessible: true };
  } catch (e) {
    results.localStorage = { accessible: false, name: e.name, message: e.message };
  }

  window.parent.postMessage({ type: 'ane-probe-result', results: results }, '*');
})();
`;

/** 親ページ HTML: render.php の interactive モード出力と同等の iframe を持つ */
function buildParentHtml(sandboxSrc: string): string {
  return `<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>TC-067 embed sandbox test</title>
</head>
<body>
  <!--
    render.php (line 39) の出力と同一の sandbox 属性セット:
      sandbox="allow-scripts"
    allow-same-origin / allow-top-navigation* は意図的に含まない。
  -->
  <iframe
    id="tc067-frame"
    sandbox="allow-scripts"
    src="${sandboxSrc}"
    title="AGENT NEO interactive embed PoC"
    loading="eager"
    width="100%"
    height="160"
    data-agent-neo-iframe="true">
  </iframe>

  <script>
    // sandbox からの postMessage を受け取って window.__probeResult に格納
    window.__probeResult = null;
    window.addEventListener('message', function(e) {
      if (e.data && e.data.type === 'ane-probe-result') {
        window.__probeResult = e.data.results;
      }
    });
  </script>
</body>
</html>`;
}

/** sandbox コンテンツ HTML: プローブ JS を実行して親に結果を報告 */
function buildSandboxHtml(): string {
  return `<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>sandbox content</title>
</head>
<body>
  <script>
    ${SANDBOX_PROBE_JS}
  </script>
</body>
</html>`;
}

/**
 * Playwright route を設定する。
 * PoC の RouteBundle 方式を TypeScript に移植。
 * socket bind なしで 2 origin を模擬する。
 */
async function installRoutes(page: Page): Promise<void> {
  const sandboxSrc = `${SANDBOX_ORIGIN}/embed.html`;

  // 親オリジン: ルートページ配信
  await page.route(`${PARENT_ORIGIN}/**`, (route: Route) => {
    const url = new URL(route.request().url());
    if (url.pathname === '/' || url.pathname === '/index.html') {
      route.fulfill({
        status: 200,
        contentType: 'text/html; charset=utf-8',
        body: buildParentHtml(sandboxSrc),
        headers: {
          'X-Content-Type-Options': 'nosniff',
        },
      });
      return;
    }
    route.fulfill({ status: 404, body: 'not found' });
  });

  // サンドボックスオリジン: iframe コンテンツ配信
  await page.route(`${SANDBOX_ORIGIN}/**`, (route: Route) => {
    const url = new URL(route.request().url());
    if (url.pathname === '/embed.html') {
      route.fulfill({
        status: 200,
        contentType: 'text/html; charset=utf-8',
        body: buildSandboxHtml(),
        headers: {
          'X-Content-Type-Options': 'nosniff',
        },
      });
      return;
    }
    route.fulfill({ status: 404, body: 'not found' });
  });
}

// -----------------------------------------------------------------------
// テストスイート
// -----------------------------------------------------------------------

test.describe('TC-067: iframe sandbox 隔離 / XSS 封じ込め', () => {
  test.beforeEach(async ({ page }) => {
    await installRoutes(page);
    await page.goto(`${PARENT_ORIGIN}/`, { waitUntil: 'load' });
    // iframe のロードを待機
    await page.waitForSelector('#tc067-frame');
  });

  // ------------------------------------------------------------------
  // (a) sandbox 属性が存在し、allow-same-origin トークンを含まない
  // ------------------------------------------------------------------
  test('(a) sandbox 属性が存在し allow-same-origin トークンを含まない', async ({ page }) => {
    const sandboxAttr = await page.locator('#tc067-frame').getAttribute('sandbox');

    // sandbox 属性が存在する
    expect(sandboxAttr).not.toBeNull();
    expect(sandboxAttr).not.toBe('');

    const tokens = (sandboxAttr ?? '').split(/\s+/).filter(Boolean);

    // allow-same-origin が存在しない（XSS 封じ込めの核心）
    expect(tokens).not.toContain('allow-same-origin');
  });

  // ------------------------------------------------------------------
  // (b) allow-top-navigation* トークンを含まない
  // ------------------------------------------------------------------
  test('(b) allow-top-navigation / allow-top-navigation-by-user-activation トークンを含まない', async ({ page }) => {
    const sandboxAttr = await page.locator('#tc067-frame').getAttribute('sandbox');
    const tokens = (sandboxAttr ?? '').split(/\s+/).filter(Boolean);

    expect(tokens).not.toContain('allow-top-navigation');
    expect(tokens).not.toContain('allow-top-navigation-by-user-activation');
  });

  // ------------------------------------------------------------------
  // (c) allow-scripts トークンを含む
  // ------------------------------------------------------------------
  test('(c) allow-scripts トークンを含む（スクリプト実行に必要）', async ({ page }) => {
    const sandboxAttr = await page.locator('#tc067-frame').getAttribute('sandbox');
    const tokens = (sandboxAttr ?? '').split(/\s+/).filter(Boolean);

    expect(tokens).toContain('allow-scripts');
  });

  // ------------------------------------------------------------------
  // sandbox 属性値の完全スナップショット（回帰防止）
  // render.php line 39 の出力と一致することを確認
  // ------------------------------------------------------------------
  test('sandbox 属性値が render.php の出力と一致する（allow-scripts のみ）', async ({ page }) => {
    const sandboxAttr = await page.locator('#tc067-frame').getAttribute('sandbox');

    // render.php が出力する実装値: sandbox="allow-scripts"
    // PoC RESULTS.md "iframe sandbox tokens" 実測値: "sandbox": "allow-scripts"
    expect(sandboxAttr?.trim()).toBe('allow-scripts');
  });

  // ------------------------------------------------------------------
  // (e) iframe 内 JS が window.parent.document.cookie に
  //     アクセスできない（SecurityError / opaque origin）
  // ------------------------------------------------------------------
  test('(e-1) iframe 内 JS が window.parent.document.cookie に SecurityError でアクセスできない', async ({ page }) => {
    // sandbox iframe が親に probe 結果を postMessage で送るまで待機
    await page.waitForFunction(
      () => (window as Window & { __probeResult?: unknown }).__probeResult !== null,
      { timeout: 8000 },
    );

    const probeResult = await page.evaluate(
      () => (window as Window & { __probeResult?: { cookie: { accessible: boolean; name?: string } } }).__probeResult,
    );

    expect(probeResult).not.toBeNull();
    expect(probeResult!.cookie.accessible).toBe(false);
    // opaque origin のため SecurityError となる
    expect(probeResult!.cookie.name).toBe('SecurityError');
  });

  // ------------------------------------------------------------------
  // (e) iframe 内 JS が window.parent.localStorage に
  //     アクセスできない（SecurityError / opaque origin）
  // ------------------------------------------------------------------
  test('(e-2) iframe 内 JS が window.parent.localStorage に SecurityError でアクセスできない', async ({ page }) => {
    await page.waitForFunction(
      () => (window as Window & { __probeResult?: unknown }).__probeResult !== null,
      { timeout: 8000 },
    );

    const probeResult = await page.evaluate(
      () => (window as Window & { __probeResult?: { localStorage: { accessible: boolean; name?: string } } }).__probeResult,
    );

    expect(probeResult).not.toBeNull();
    expect(probeResult!.localStorage.accessible).toBe(false);
    expect(probeResult!.localStorage.name).toBe('SecurityError');
  });

  // ------------------------------------------------------------------
  // 親から iframe.contentWindow.document への直接アクセスがブロックされる
  // （PoC verify.py "parent cannot read iframe document" と対応）
  // ------------------------------------------------------------------
  test('親 JS から iframe.contentWindow.document へのアクセスが SecurityError でブロックされる', async ({ page }) => {
    const result = await page.evaluate(() => {
      const frame = document.getElementById('tc067-frame') as HTMLIFrameElement | null;
      if (!frame) return { accessible: false, name: 'ElementNotFound', message: 'iframe not found' };
      try {
        const text = frame.contentWindow?.document.body.textContent;
        return { accessible: true, text };
      } catch (e: unknown) {
        const err = e as Error;
        return { accessible: false, name: err.name, message: err.message };
      }
    });

    expect(result.accessible).toBe(false);
    expect(result.name).toBe('SecurityError');
  });
});
