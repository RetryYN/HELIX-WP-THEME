import fs from 'node:fs';
import { spawnSync } from 'node:child_process';

export function runProbeProcess({ command, args = [], cwd, env, reportPath }) {
  fs.rmSync(reportPath, { force: true });
  const child = spawnSync(command, args, { cwd, env, encoding: 'utf8', maxBuffer: 32 * 1024 * 1024 });
  let report = null;
  if (child.status === 0) {
    try {
      const parsed = JSON.parse(fs.readFileSync(reportPath, 'utf8'));
      if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) report = parsed;
    } catch {}
  }
  return {
    completed: child.status === 0 && report?.completed === true,
    exitCode: Number.isInteger(child.status) ? child.status : null,
    reportCreated: fs.existsSync(reportPath),
    report,
    stdout: child.stdout,
    stderr: child.stderr,
  };
}
