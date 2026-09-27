import fs from 'node:fs/promises';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { launch } from 'chrome-launcher';
import lighthouse from 'lighthouse';
import { PERFORMANCE_GATE } from '../validate-performance-evidence.mjs';

const deviceSettings = Object.freeze({
  home: {
    formFactor: 'mobile',
    screenEmulation: { mobile: true, width: 412, height: 823, deviceScaleFactor: 1.75, disabled: false },
  },
  sp: {
    formFactor: 'mobile',
    screenEmulation: { mobile: true, width: 390, height: 844, deviceScaleFactor: 3, disabled: false },
  },
  pc: {
    formFactor: 'desktop',
    screenEmulation: { mobile: false, width: 1440, height: 900, deviceScaleFactor: 1, disabled: false },
  },
});

export async function collectLighthouseRuns({ url, coverageId, device, reportDir }) {
  const settings = deviceSettings[device];
  const isHomeRegression = coverageId === 'home-regression' && device === 'home';
  if (!settings || (!isHomeRegression && !expectedCoverageId(coverageId))) throw new Error('invalid Lighthouse coverage key');
  const chromePath = process.env.PERF_GATE_CHROME_PATH;
  if (!chromePath) throw new Error('PERF_GATE_CHROME_PATH is required');
  const chromeVersion = execFileSync(chromePath, ['--version'], { encoding: 'utf8' }).trim();
  if (!new RegExp(`${PERFORMANCE_GATE.chromeVersion.replaceAll('.', '\\.')}(?:\\s|$)`, 'u').test(chromeVersion)) {
    throw new Error('Chrome binary version does not match the pinned performance-gate version');
  }
  process.env.CHROME_PATH = chromePath;
  const runs = [];
  for (let index = 1; index <= PERFORMANCE_GATE.runsPerCondition; index += 1) {
    const chrome = await launch({
      chromePath,
      port: 0,
      logLevel: 'error',
      chromeFlags: ['--headless', '--no-sandbox', '--disable-dev-shm-usage'],
    });
    let lhr;
    try {
      ({ lhr } = await lighthouse(url, {
        port: chrome.port,
        output: 'json',
        logLevel: 'error',
      }, {
        extends: 'lighthouse:default',
        settings: {
          ...settings,
          onlyCategories: ['performance'],
          throttlingMethod: 'simulate',
        },
      }));
    } finally {
      await chrome.kill();
    }
    const file = `${coverageId.replace(':', '-')}-run-${index}.json`;
    const outputPath = path.join(reportDir, 'lighthouse', file);
    await fs.mkdir(path.dirname(outputPath), { recursive: true });
    const json = `${JSON.stringify(lhr, null, 2)}\n`;
    await fs.writeFile(outputPath, json);
    runs.push({
      index,
      path: path.relative(reportDir, outputPath).split(path.sep).join('/'),
      sha256: createHash('sha256').update(json).digest('hex'),
      lighthouseVersion: lhr.lighthouseVersion,
      chromeVersion,
      userAgent: lhr.userAgent,
    });
  }
  return runs;
}

function expectedCoverageId(value) {
  return PERFORMANCE_GATE.pageTypes.some(page => PERFORMANCE_GATE.devices.includes(value.split(':')[1])
    && `${page}:${value.split(':')[1]}` === value);
}
