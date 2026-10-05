import fs from 'node:fs';
import path from 'node:path';
import vm from 'node:vm';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { mockWp } from './fixture-lifecycle-mock.mjs';

// 実スクリプトの制御フローを隔離実行する。DB/ブラウザ/証跡出力だけを差し替え、
// テーマ描画の成否はこの mock の受入対象にしない。
const [script, rawConfig] = process.argv.slice(2);
const config = JSON.parse(rawConfig);
const fake = mockWp(config.initial, {
  ...config,
  fail: (stage, args, count) => stage === config.failStage && (!config.failCount || count === config.failCount),
});
const runnerProcess = { argv: ['node', script, ...(config.args || [])], env: {}, exitCode: undefined };
const reports = [];
const logs = [];
const browserCalls = [];
const formValues = { name: '検証用の名前', email: 'reader@example.com', message: '確認用の本文\n改行も保持する' };
const isEvent = path.basename(script).startsWith('verify-event-');
const currentFixture = () => [...fake.posts.values()].find(post => post.fixture)?.fixture;

function pageMock() {
  let url = 'http://127.0.0.1:8098/';
  const locator = selector => {
    const value = {
      locator: child => locator(selector + ' ' + child),
      getByRole: () => locator(selector + ' role'),
      first: () => value, last: () => value, nth: () => value,
      count: async () => {
        if (selector.includes('input[type=file][name]')) return 0;
        if (selector.includes('input:not(')) return 0;
        if (selector === '[aria-invalid=true]') return 4;
        if (isEvent && selector === '.wt-form__form') return currentFixture()?.expected === '受付中' ? 1 : 0;
        if (!isEvent && selector === '.wt-form__form' && script.includes('form-slots') && !new URL(url).search) return 0;
        return 1;
      },
      evaluateAll: async () => {
        if (selector === '.wt-event-status') return [currentFixture()?.expected || '受付終了'];
        if (selector === '[data-wt-event-state]') return true;
        if (selector === '.wt-form__row') return new URL(url).search.includes('event_apply')
          ? ['name', 'email', 'tel', 'subject-radio', 'people-count', 'message', 'captcha', 'consent']
          : ['name', 'name-kana', 'company', 'email', 'tel', 'subject-select', 'message', 'captcha', 'consent'];
        return [];
      },
      evaluate: async action => String(action).includes('FormData') ? [] : undefined,
      click: async () => {
        if (selector.includes('a[href="#apply"]')) url = new URL('#apply', url).href;
        if (selector.includes('.wt-form__confirm') && selector.includes('button[type=submit]')
          && /form-(slots|steps)/.test(script)) url = new URL('/thanks/', url).href;
      },
      fill: async value => { const key = selector.replace('#wt-f-', ''); formValues[key] = value; },
      inputValue: async () => selector.includes('nonce') ? 'fixture-nonce' : formValues[selector.replace('#wt-f-', '')],
      innerText: async () => '検証用の名前 reader@example.com 修正した本文 有効期限',
      getAttribute: async () => 'true',
      check: async () => {}, selectOption: async () => {}, scrollIntoViewIfNeeded: async () => {}, isVisible: async () => true,
    };
    return value;
  };
  return {
    goto: async value => { url = value; }, url: () => url, locator,
    close: async () => {}, screenshot: async () => {}, on: () => {},
    evaluate: async () => true, waitForSelector: async () => {}, waitForLoadState: async () => {},
  };
}

const browser = {
  newPage: async () => {
    if (config.bodyFails) throw new Error('Injected browser body failure');
    return pageMock();
  },
  newContext: async () => {
    if (config.bodyFails) throw new Error('Injected browser body failure');
    return {
      newPage: async () => pageMock(), route: async () => {},
      close: async () => { if (config.contextCloseFails) throw new Error('Injected context close failure'); },
      request: { post: async () => ({
        ok: () => true,
        text: async () => isEvent
          ? (currentFixture()?.expected === '受付中' ? 'data-wt-thanks="apply"' : '')
          : 'class="wt-form__confirm" role="alert" reader@example.com',
      }) },
    };
  },
  close: async () => {
    browserCalls.push('close');
    if (config.closeFails) throw new Error('Injected browser close failure');
  },
};
const chromium = { launch: async () => {
  browserCalls.push('launch');
  if (config.launchFails) throw new Error('Injected browser launch failure');
  return browser;
} };
const fakeFs = {
  ...fs,
  mkdirSync: () => { if (config.mkdirFails) throw new Error('Injected output initialization failure'); },
  writeFileSync: (filename, body) => {
    if (config.reportFails) throw new Error('Injected report write failure');
    reports.push(JSON.parse(body));
  },
};
const context = vm.createContext({
  process: runnerProcess, Buffer, URL, URLSearchParams,
  console: { log: value => logs.push(value) },
});
const modules = new Map();
async function moduleFor(identifier) {
  if (modules.has(identifier)) return modules.get(identifier);
  let module;
  if (identifier === 'node:child_process') {
    module = new vm.SyntheticModule(['execFileSync'], function () {
      this.setExport('execFileSync', (cmd, args) => {
        assertDocker(cmd, args);
        return fake.wp(args.slice(args.indexOf('wp') + 1));
      });
    }, { context, identifier });
  } else if (identifier === 'playwright') {
    module = new vm.SyntheticModule(['chromium'], function () { this.setExport('chromium', chromium); }, { context, identifier });
  } else if (identifier.startsWith('node:')) {
    const namespace = await import(identifier);
    module = new vm.SyntheticModule(Object.keys(namespace), function () {
      for (const key of Object.keys(namespace)) this.setExport(key, identifier === 'node:fs' && key === 'default' ? fakeFs : namespace[key]);
    }, { context, identifier });
  } else {
    module = new vm.SourceTextModule(fs.readFileSync(fileURLToPath(identifier), 'utf8'), {
      context, identifier, initializeImportMeta: meta => { meta.url = identifier; },
    });
  }
  modules.set(identifier, module);
  return module;
}
function assertDocker(cmd, args) {
  if (cmd !== 'docker' || !args.includes('wp')) throw new Error('Unexpected external command');
}
let error;
try {
  const main = await moduleFor(pathToFileURL(script).href);
  await main.link((specifier, reference) => moduleFor(specifier.startsWith('.') ? new URL(specifier, reference.identifier).href : specifier));
  await main.evaluate();
} catch (failure) {
  error = failure.message;
}
process.stdout.write(JSON.stringify({
  error, calls: fake.calls, stages: fake.stages, posts: [...fake.posts.entries()], option: fake.option,
  exitCode: runnerProcess.exitCode || 0, reports, browserCalls, logs,
}));
