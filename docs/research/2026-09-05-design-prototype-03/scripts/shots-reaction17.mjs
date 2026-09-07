// 段 11（2026-09-07 WT-EVT-0289 / 0301）: フォーム面の撮影。/contact/（helix-wt/form）の 11 軸と遷移（確認 / 見直し / 完了 / エラー / 形式検査）、/thanks/。
// 方式は reaction7〜16 と同じ（一時 dir → 予定集合の照合 → 退避 → 配置 → INDEX を原子的置換 → 失敗時復元 → finally 清掃）。同名置換なし（すべて新規）、stale なし。
// 実行: scripts/ から `NODE_PATH=... node shots-reaction17.mjs`（--out ../results）。verify と同時に走らせない。
//
//
import fs from "node:fs";
import path from "node:path";
import { execFileSync } from "node:child_process";
import { createRequire } from "node:module";
const require = createRequire(process.env.NODE_PATH ? path.join(process.env.NODE_PATH, "x.js") : import.meta.url);
const { chromium } = require("playwright");

const args = Object.fromEntries(process.argv.slice(2).map((a, i, arr) => a.startsWith("--") ? [a.slice(2), arr[i + 1]] : null).filter(Boolean));
const BASE = args.base || "http://localhost:8086";
const OUT = path.resolve(args.out || "../results");
const CATEGORY = "/category/topic-index/";
const EVENT = "/event/";
fs.mkdirSync(OUT, { recursive: true });
const index = [];
const SP = { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true };
const PC = { viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 };
const wt = (q) => "?wt=" + q;
function toJpeg(png, jpg) {
  execFileSync("ffmpeg", ["-y", "-loglevel", "error", "-i", png, "-vf", "scale='if(gte(iw,ih),min(1600,iw),-2)':'if(gte(iw,ih),-2,min(1600,ih))'", "-q:v", "5", jpg]);
  fs.unlinkSync(png);
}
const LOCK = path.join(OUT, ".reaction17.lock");
let lockFd = null;
try { lockFd = fs.openSync(LOCK, "wx"); fs.writeSync(lockFd, String(process.pid)); } catch (e) { throw new Error(`ロックファイルが存在します（別の撮影が実行中か、前回が強制終了）。退避 dir を確認してから削除してください: ${LOCK}`); }
const TMP = fs.mkdtempSync(path.join(OUT, ".reaction17-capture-"));
const waitImgs = async (page, sel) => {
  const bad = await page.evaluate(async (sel) => {
    const root = sel ? document.querySelector(sel) : document; const imgs = Array.from(root ? root.querySelectorAll("img") : []).filter((i) => i.getBoundingClientRect().width > 0);
    imgs.forEach((i) => { i.loading = "eager"; });
    const results = await Promise.all(imgs.map((i) => i.complete ? Promise.resolve(i.naturalWidth > 0 ? "ok" : "error") : new Promise((r) => { i.onload = () => r("ok"); i.onerror = () => r("error"); setTimeout(() => r("timeout"), 3000); })));
    return results.map((state, idx) => ({ state, src: (imgs[idx].currentSrc || imgs[idx].src || "").split("/").pop() })).filter((x) => x.state !== "ok");
  }, sel || null);
  if (bad.length) throw new Error(`画像の読込に失敗または待機超過: ${bad.map((b) => `${b.src}(${b.state})`).join(", ")}`);
};
async function save(page, name, meta, opts = {}) {
  const png = path.join(TMP, name + ".png"), jpg = path.join(TMP, name + ".jpg");
  if (opts.selector) {
    const el = page.locator(opts.selector).first();
    await el.waitFor({ state: "visible", timeout: 8000 });
    await el.scrollIntoViewIfNeeded();
    await waitImgs(page, opts.selector);
    await page.waitForTimeout(200);
    await el.screenshot({ path: png });
  } else if (opts.clipSelector) { // 既存 stage3 と同じ: 要素の上端から clipHeight まで
    const el = page.locator(opts.clipSelector).first(); await el.waitFor({ state: "visible", timeout: 8000 }); await el.scrollIntoViewIfNeeded(); await waitImgs(page, opts.clipSelector); await page.waitForTimeout(200);
    const box = await el.boundingBox(); const y = await page.evaluate(() => scrollY); await page.screenshot({ path: png, clip: { x: 0, y: box.y + y, width: page.viewportSize().width, height: Math.min(box.height, opts.clipHeight) }, fullPage: true });
  } else { await waitImgs(page, null); await page.screenshot({ path: png }); }
  toJpeg(png, jpg);
  if (!fs.statSync(jpg).size) throw new Error(`空の JPEG: ${jpg}`);
  index.push({ file: name + ".jpg", ...meta });
  console.log("shot", name);
}
async function open(ctx, url) {
  const page = await ctx.newPage();
  await page.goto(BASE + url, { waitUntil: "networkidle" });
  await page.waitForTimeout(300);
  return page;
}
const assertBody = async (p, cls) => { if (!(await p.evaluate((c) => document.body.classList.contains(c), cls))) throw new Error(`body.${cls} が付いていません`); };
const assertVisible = async (p, sel) => { if (!(await p.evaluate((s) => { const el = document.querySelector(s); if (!el) return false; const r = el.getBoundingClientRect(); return r.width > 0 && r.height > 0 && getComputedStyle(el).display !== "none"; }, sel))) throw new Error(`${sel} が表示されていません`); };
// 全長を viewport 分割で撮る（reaction9 方式）。分割数は撮影前に高さから決め、予定集合の照合に使う
const CHUNKS = {};
// 全長を viewport 分割で撮る（reaction9 方式）。rootSel の矩形を分割し、表示区間数（expectSections）を撮影前に照合する
async function chunked(browser, cfg, dev, url, key, meta, rootSel, sectionSel, expectSections) {
  const CHUNK = dev === "sp" ? 4000 : 1600;
  const ctx = await browser.newContext({ ...cfg, deviceScaleFactor: 1, viewport: { width: cfg.viewport.width, height: CHUNK } });
  const p = await open(ctx, url);
  const m = await p.evaluate(([sel, ssel]) => { const root = document.querySelector(sel); const r = root.getBoundingClientRect(); const vis = (el) => { const b = el.getBoundingClientRect(); return b.width > 0 && b.height > 0 && getComputedStyle(el).display !== "none"; }; return { top: r.top + scrollY, height: r.height, shown: ssel ? Array.from(root.querySelectorAll(ssel)).filter(vis).length : null }; }, [rootSel, sectionSel]);
  if (expectSections !== null && m.shown !== expectSections) throw new Error(`${key}: 表示区間が ${m.shown}（${expectSections} を期待）`);
  const chunks = Math.ceil(m.height / CHUNK); CHUNKS[`${key}-${dev}`] = chunks;
  for (let i = 0; i < chunks; i++) {
    await p.evaluate(([y]) => scrollTo(0, y), [m.top + i * CHUNK]); await p.waitForTimeout(250);
    await save(p, `${key}-${i + 1}-${dev}`, { face: meta.face, part: meta.part, variant: `${meta.variant} ${i + 1}/${chunks}`, dev });
  }
  await p.close(); await ctx.close();
}
const HOME = "/";
const PARTS = "/parts/";
const FORM = "/contact/", THANKS = "/thanks/";
const KINDS = [["contact", "お問い合わせ（既定。台帳 43%）"], ["apply", "参加申込（Claude 案。台帳の語彙にあるが本体観察 0）"], ["download", "資料ダウンロード（台帳 19%）"], ["reservation", "ご予約（第 1〜3 希望日時。台帳 12%）"], ["newsletter", "メールマガジン登録（台帳 5%）"], ["recruit", "採用エントリー（添付あり。台帳 10%）"], ["quote", "お見積り依頼（業種・予算・添付。台帳 5%）"], ["trial", "無料トライアル（台帳 5%）"], ["diagnosis", "無料診断（yes / no の多段。台帳 other:diagnosis 2%）"]];
const FIELDS = [["minimal", "最小（名前・メール・本文）"], ["standard", "標準（+ 会社・電話・種別）"], ["full", "全部（+ かな・部署・確認メール・郵便番号・住所・URL・添付・きっかけ・メルマガ）"]];
const LAYOUTS = [["2col", "2 列（PC。長文・同意は全幅）"], ["label-left", "ラベル左（PC）"], ["placeholder-only", "placeholder だけ（ラベルは読み上げ専用）"], ["steps", "段階（基本情報 → 内容 → 確認・同意）"]];
const CONSENT = [["link-only", "リンク文だけ（チェックなし）"], ["in-submit", "送信ボタン文言に含める"]];
const SUBMIT = [["send", "送信する"], ["confirm", "確認画面へ"], ["apply", "申し込む"], ["register", "登録する"], ["download", "ダウンロード"], ["next", "次へ進む"]];
const ERRORS = [["inline", "項目下（既定）"], ["top-summary", "上部まとめ（項目へのリンク）"], ["both", "両方"]];
const CAPTCHA = [["question", "簡易な質問型（3 + 4 = ?）"], ["external-slot", "外部認証の枠（描画のみ）"]];
const SIDES = [["none", "なし"], ["email", "メール"], ["chat", "チャット"], ["messaging-app", "メッセージアプリ"]];
const F = (q) => FORM + wt(q);
const fill = async (p) => { for (const [sel, v] of [["#wt-f-name", "山田 太郎"], ["#wt-f-name-kana", "やまだ たろう"], ["#wt-f-company", "株式会社サンプル"], ["#wt-f-email", "taro@example.com"], ["#wt-f-tel", "03-1234-5678"], ["#wt-f-message", "サービスの導入について相談したいです。"]]) if (await p.$(sel)) await p.fill(sel, v); await p.selectOption("#wt-f-subject-select", { index: 2 }); await p.check("#wt-f-consent"); };
const submit = async (p) => { await p.click(".wt-form__form .wt-form__submit"); await p.waitForTimeout(600); };
const shotForm = async (p, name, meta) => { await assertVisible(p, ".wt-form"); await save(p, name, meta, { selector: ".wt-form" }); };
let browser = null;
async function main() {
try {
for (const [dev, cfg] of [["sp", SP], ["pc", PC]]) {
  if (browser) await browser.close();
  browser = await chromium.launch();
  const ctx = await browser.newContext(cfg);
  let p;
  for (const [v, label] of KINDS) { p = await open(ctx, F(`form_kind:${v}`)); await assertBody(p, `wt-form-kind-${v}`); await shotForm(p, `form-kind-${v}-${dev}`, { face: "form", part: "form-kind", variant: `${v} ${label}`, dev }); await p.close(); }
  // 確認画面 → 完了（separate）
  p = await open(ctx, F("form_confirm:yes")); await fill(p); await submit(p); await assertVisible(p, ".wt-form__confirm"); await shotForm(p, `form-confirm-yes-${dev}`, { face: "form", part: "form-confirm", variant: "yes 確認画面（既定。台帳 90%）", dev });
  await p.click(".wt-form__confirm .wt-form__submit"); await p.waitForTimeout(800); await assertVisible(p, ".wt-form-thanks"); await save(p, `form-thanks-separate-${dev}`, { face: "form", part: "form-thanks", variant: "separate 完了ページ /thanks/（既定）", dev }, { selector: ".wt-form-thanks" }); await p.close();
  p = await open(ctx, F("form_error:inline")); await submit(p); await shotForm(p, `form-error-inline-${dev}`, { face: "form", part: "form-error", variant: "inline 項目下（既定）", dev }); await p.close();
  if (dev === "pc") {
    for (const [v, label] of FIELDS) { p = await open(ctx, F(`form_fields:${v}`)); await assertBody(p, `wt-form-fields-${v}`); await shotForm(p, `form-fields-${v}-pc`, { face: "form", part: "form-fields", variant: `${v} ${label}`, dev }); await p.close(); }
    p = await open(ctx, F("form_required:label")); await assertBody(p, "wt-form-required-label"); await shotForm(p, "form-required-label-pc", { face: "form", part: "form-required", variant: "label 「必須」バッジ（台帳 19%。既定は asterisk 81%）", dev }); await p.close();
    for (const [v, label] of LAYOUTS) { p = await open(ctx, F(`form_layout:${v}`)); await assertBody(p, `wt-form-layout-${v}`); await shotForm(p, `form-layout-${v}-pc`, { face: "form", part: "form-layout", variant: `${v} ${label}`, dev }); if (v === "steps") { await p.fill("#wt-f-name", "山田 太郎"); await p.fill("#wt-f-name-kana", "やまだ たろう"); await p.fill("#wt-f-email", "taro@example.com"); await p.click(".wt-form__next"); await p.waitForTimeout(300); await shotForm(p, "form-layout-steps-2-pc", { face: "form", part: "form-layout", variant: "steps 段 2（内容）。段 1 は済み", dev }); } await p.close(); }
    for (const [v, label] of CONSENT) { p = await open(ctx, F(`form_consent:${v}`)); await assertBody(p, `wt-form-consent-${v}`); await shotForm(p, `form-consent-${v}-pc`, { face: "form", part: "form-consent", variant: `${v} ${label}`, dev }); await p.close(); }
    for (const [v, label] of SUBMIT) { p = await open(ctx, F(`form_submit:${v}`)); await assertBody(p, `wt-form-submit-${v}`); await save(p, `form-submit-${v}-pc`, { face: "form", part: "form-submit", variant: `${v} 「${label}」`, dev }, { selector: ".wt-form__actions" }); await p.close(); }
    for (const [v, label] of ERRORS.slice(1)) { p = await open(ctx, F(`form_error:${v}`)); await submit(p); await assertVisible(p, "#wt-form-summary"); await shotForm(p, `form-error-${v}-pc`, { face: "form", part: "form-error", variant: `${v} ${label}`, dev }); await p.close(); }
    for (const [v, label] of CAPTCHA) { p = await open(ctx, F(`form_captcha:${v}`)); await assertBody(p, `wt-form-captcha-${v}`); await shotForm(p, `form-captcha-${v}-pc`, { face: "form", part: "form-captcha", variant: `${v} ${label}`, dev }); await p.close(); }
    for (const [v, label] of SIDES) { p = await open(ctx, F(`form_side:${v}`)); await assertBody(p, `wt-form-side-${v}`); await shotForm(p, `form-side-${v}-pc`, { face: "form", part: "form-side", variant: `${v} ${label}`, dev }); await p.close(); }
    p = await open(ctx, F("form_confirm:inline-review")); await fill(p); await submit(p); await assertVisible(p, ".wt-form__inline-review"); await shotForm(p, "form-confirm-inline-review-pc", { face: "form", part: "form-confirm", variant: "inline-review 同一ページで見直し（Claude 案）", dev }); await p.close();
    p = await open(ctx, F("form_confirm:no,form_thanks:inline")); await fill(p); await submit(p); await assertVisible(p, ".wt-form-thanks--inline"); await shotForm(p, "form-thanks-inline-pc", { face: "form", part: "form-thanks", variant: "inline 同じページに完了表示（form_confirm:no と組合せ）", dev }); await p.close();
    p = await open(ctx, F("form_fields:full,form_captcha:question")); for (const [sel, v] of [["#wt-f-name", "山田"], ["#wt-f-name-kana", "ヤマダ"], ["#wt-f-email", "bad"], ["#wt-f-email-confirm", "bad2@example.com"], ["#wt-f-tel", "12"], ["#wt-f-postal", "12"], ["#wt-f-url", "example.com"], ["#wt-f-message", "x"], ["#wt-f-captcha", "8"]]) await p.fill(sel, v); await p.selectOption("#wt-f-subject-select", { index: 1 }); await p.check("#wt-f-consent"); await submit(p); await shotForm(p, "form-validate-pc", { face: "form", part: "form-validate", variant: "形式検査（かな・メール・確認メール・電話・郵便番号・URL・captcha）", dev }); await p.close();
  }
  await ctx.close();
}
} finally { if (browser) await browser.close(); }

const catalogFile = path.join(OUT, "..", "CATALOG-INDEX.json");
const existing = JSON.parse(fs.readFileSync(catalogFile, "utf8"));
const perDev = (dev) => [
  ...KINDS.map(([v]) => `form-kind-${v}`), "form-confirm-yes", "form-thanks-separate", "form-error-inline",
  ...(dev === "pc" ? [...FIELDS.map(([v]) => `form-fields-${v}`), "form-required-label", ...LAYOUTS.map(([v]) => `form-layout-${v}`), "form-layout-steps-2", ...CONSENT.map(([v]) => `form-consent-${v}`), ...SUBMIT.map(([v]) => `form-submit-${v}`), ...ERRORS.slice(1).map(([v]) => `form-error-${v}`), ...CAPTCHA.map(([v]) => `form-captcha-${v}`), ...SIDES.map(([v]) => `form-side-${v}`), "form-confirm-inline-review", "form-thanks-inline", "form-validate"] : []),
].map((k) => `${k}-${dev}.jpg`);
const NEW_FILES = [...perDev("sp"), ...perDev("pc")];
const planned = new Set(NEW_FILES);
if (planned.size !== NEW_FILES.length) throw new Error("予定集合に重複があります");
const captured = new Set(index.map((e) => e.file));
const missing = [...planned].filter((f) => !captured.has(f)); const unknown = [...captured].filter((f) => !planned.has(f));
if (missing.length || unknown.length || captured.size !== index.length) throw new Error(`撮影集合が予定集合と一致しません（予定 ${planned.size} / 撮影 ${captured.size}）。欠落: ${missing.join(", ") || "なし"} / 予定外: ${unknown.join(", ") || "なし"}`);
for (const e of index) { const t = path.join(TMP, e.file); if (!fs.existsSync(t) || !fs.statSync(t).size) throw new Error(`一時 JPEG が無いか空: ${t}`); }
const known = new Set(existing.map((e) => e.file));
const backupDir = fs.mkdtempSync(path.join(OUT, ".reaction17-backup-"));
const backedUp = new Set(); const placed = []; let keepBackup = false;
const replacement = new Map(index.map((e) => [e.file, e]));
const added = index.filter((e) => !known.has(e.file));
const stale = []; // 段 11 は新規のみ
const nextIndex = JSON.stringify(existing.filter((e) => !stale.includes(e.file)).map((e) => replacement.get(e.file) || e).concat(added), null, 1) + "\n";
const indexTmp = catalogFile + ".reaction17.tmp";
try {
  for (const e of index) { const t = path.join(OUT, e.file); if (fs.existsSync(t)) { fs.renameSync(t, path.join(backupDir, e.file)); backedUp.add(e.file); } }
  for (const f of stale) { const t = path.join(OUT, f); if (fs.existsSync(t)) { fs.renameSync(t, path.join(backupDir, f)); backedUp.add(f); } }
  for (const e of index) { fs.renameSync(path.join(TMP, e.file), path.join(OUT, e.file)); placed.push(e.file); }
  fs.writeFileSync(indexTmp, nextIndex); fs.renameSync(indexTmp, catalogFile);
} catch (error) {
  const restoreFailures = [];
  try { fs.rmSync(indexTmp, { force: true }); } catch (_) { /* 旧 INDEX は無傷 */ }
  for (const f of placed) { if (backedUp.has(f)) continue; try { fs.unlinkSync(path.join(OUT, f)); } catch (e) { restoreFailures.push({ f, e: String(e) }); } }
  for (const f of backedUp) { const b = path.join(backupDir, f); if (!fs.existsSync(b)) continue; try { fs.renameSync(b, path.join(OUT, f)); } catch (e) { restoreFailures.push({ f, e: String(e) }); } }
  if (restoreFailures.length) { keepBackup = true; console.error(`画像の復元に失敗。退避ディレクトリを手動で確認: ${backupDir}`, restoreFailures); }
  throw error;
} finally {
  if (!keepBackup) { try { fs.rmSync(backupDir, { recursive: true, force: true }); } catch (e) { console.error(`退避ディレクトリの削除に失敗（配置済み画像はそのまま）: ${backupDir}`, e); } }
}
console.log("reaction17 done", index.length, "entries", existing.length - stale.length + added.length, "added", added.length, "stale", stale.length, "chunks", JSON.stringify(CHUNKS));
}
try { await main(); } finally {
  try { fs.rmSync(TMP, { recursive: true, force: true }); } catch (e) { console.error(`一時ディレクトリの削除に失敗: ${TMP}`, e); }
  try { if (lockFd !== null) fs.closeSync(lockFd); fs.rmSync(LOCK, { force: true }); } catch (e) { console.error(`ロックファイルの削除に失敗: ${LOCK}`, e); }
}
