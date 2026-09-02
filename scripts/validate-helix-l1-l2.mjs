import { spawnSync } from "node:child_process";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";

const root = process.cwd();
const cli = resolve(root, "node_modules/.bin/tsx");
const expectedScreens = [...readFileSync(resolve(root, "docs/requirements/l2/screen-list.md"), "utf8").matchAll(/^\|\s*WT-UI-\d{2}\s*\|/gm)].length;
if (expectedScreens === 0) throw new Error("no WT-UI surfaces in docs/requirements/l2/screen-list.md");
const result = spawnSync(cli, ["node_modules/helix/src/cli.ts", "l1-l2", "gap-check", "--json"], {
  cwd: root,
  encoding: "utf8",
});
if (result.status !== 0) {
  throw new Error(`HELIX L1/L2 gap-check failed (${result.status}): ${result.stderr || result.stdout}`);
}
const packet = JSON.parse(result.stdout);
if (packet.schemaVersion !== "l1-l2-gap-check.v1") throw new Error("unexpected HELIX L1/L2 gap-check schema");
if (packet.consistency?.ok !== true || packet.consistency?.checked !== expectedScreens) {
  throw new Error(`HELIX L1/L2 projection is not connected to all ${expectedScreens} WT screens: ${JSON.stringify(packet.consistency)}`);
}
if (packet.consistency.violations.length !== 0 || !packet.consistency.messages.some((line) => line.includes("mock pair=declared"))) {
  throw new Error(`HELIX L1/L2 structural coverage is not green: ${JSON.stringify(packet.consistency)}`);
}
if (packet.contentReviewRequired !== true || packet.completionClaimAllowed !== false) {
  throw new Error("HELIX L1/L2 human review boundary changed unexpectedly");
}
console.log(`HELIX L1/L2 validation: OK (${expectedScreens} WT screens, bidirectional coverage, mock pair declared)`);
