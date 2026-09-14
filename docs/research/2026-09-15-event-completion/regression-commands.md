# 変更直後の43 ACと再検証コマンド

これは変更直後の43 staleの対応表。現在の未解消件数とは限らない。親が既に再実行し現source一致を確認した証拠は再実行不要。専用lab起動・静的サーバー8099を確認し、未実行コマンドだけを次の順で直列実行する。DB検証を並行させない。

```sh
node scripts/verify-capability-manifest.mjs
node scripts/verify-capability-health.mjs
node scripts/verify-ai-boundary.mjs
node docs/research/2026-09-10-current-theme-quality/reading-layout/probe.mjs after
node scripts/verify-privacy-boundary.mjs
node scripts/verify-site-page-quality.mjs
node docs/research/2026-09-10-reduced-motion/verify.mjs
node scripts/verify-i18n-profile.mjs
node scripts/verify-site-pages.mjs
node scripts/verify-site-page-editor.mjs
node scripts/verify-footer-rendering.mjs
node scripts/verify-footer-editor.mjs
node scripts/verify-event-state.mjs
node scripts/verify-event-boundaries.mjs
node scripts/verify-content-faces.mjs
python3 scripts/verify-content-management.py
node scripts/verify-interview-lifecycle.mjs
node scripts/verify-form-flow.mjs
node scripts/verify-form-kinds.mjs
node scripts/verify-form-steps.mjs
node scripts/verify-form-slots.mjs
node scripts/verify-form-boundaries.mjs
node scripts/verify-content-inheritance.mjs
node scripts/verify-learning-faces.mjs
node scripts/verify-learning-publication.mjs
node docs/research/2026-09-13-learning-navigation-ownership/probe.mjs
node scripts/verify-site-search.mjs
node scripts/verify-site-search-boundaries.mjs
node scripts/verify-site-search-access.mjs
node scripts/verify-site-search-passwords.mjs
node scripts/verify-site-search-performance.mjs
node scripts/verify-site-search-input.mjs
node scripts/verify-site-search-locale.mjs
```

各ACの対象row_namesは `regression-command-map.json` にregistryから全件転記した。証拠ファイルが存在するだけでは足りず、completedと指定行全PASSとsource digest一致が必要。再実行成功後にだけacceptance-evidence.jsonを再束縛し、audit→build→E2Eを実行する。

| AC | 証拠（docs/research/からの相対path） |
| --- | --- |
| `WT-AC-TR-CORE-01A` | `2026-09-09-capability-manifest/verify.json` |
| `WT-AC-TR-CORE-01B` | `2026-09-09-capability-manifest/verify.json` |
| `WT-AC-TR-CORE-02A` | `2026-09-09-capability-manifest/verify.json`<br>`2026-09-09-capability-manifest/runtime.json` |
| `WT-AC-TR-CORE-02B` | `2026-09-09-capability-manifest/runtime.json` |
| `WT-AC-TR-CORE-03A` | `2026-09-09-ai-boundary/verify.json` |
| `WT-AC-TR-CORE-03B` | `2026-09-09-ai-boundary/verify.json` |
| `WT-AC-VOCAB-02A` | `2026-09-10-current-theme-quality/reading-layout/verify.json` |
| `WT-AC-VOCAB-02B` | `2026-09-10-current-theme-quality/reading-layout/verify.json` |
| `WT-AC-NFR-PRIV-01A` | `2026-09-13-privacy-boundary/verify.json` |
| `WT-AC-NFR-PRIV-01B` | `2026-09-13-privacy-boundary/verify.json` |
| `WT-AC-NFR-SP-01A` | `2026-09-08-content-faces/results/site-quality/verify.json` |
| `WT-AC-NFR-SP-01B` | `2026-09-08-content-faces/results/site-quality/verify.json` |
| `WT-AC-A11Y-02A` | `2026-09-10-reduced-motion/verify.json` |
| `WT-AC-A11Y-02B` | `2026-09-10-reduced-motion/verify.json` |
| `WT-AC-ENV-01A` | `2026-09-10-i18n-boundary/verify.json` |
| `WT-AC-ENV-01B` | `2026-09-10-i18n-boundary/verify.json` |
| `WT-AC-PAGE-01A` | `2026-09-08-content-faces/results/site-pages/verify.json`<br>`2026-09-08-content-faces/results/site-pages/editor.json` |
| `WT-AC-PAGE-01B` | `2026-09-08-content-faces/results/site-pages/verify.json` |
| `WT-AC-PARTS-01C` | `2026-09-09-footer-data/rendering.json`<br>`2026-09-09-footer-data/editor.json` |
| `WT-AC-EVENT-01A` | `2026-09-08-event-state/verify.json`<br>`2026-09-08-event-state/boundaries-verify.json` |
| `WT-AC-EVENT-01B` | `2026-09-08-event-state/verify.json`<br>`2026-09-08-event-state/boundaries-verify.json` |
| `WT-AC-PAID-01A` | `2026-09-08-content-faces/results/verify.json`<br>`2026-09-08-content-faces/results/management.json` |
| `WT-AC-PAID-01B` | `2026-09-08-content-faces/results/verify.json` |
| `WT-AC-INTERVIEW-01A` | `2026-09-08-content-faces/results/verify.json`<br>`2026-09-08-content-faces/results/management.json` |
| `WT-AC-INTERVIEW-01B` | `2026-09-08-content-faces/results/verify.json`<br>`2026-09-08-content-faces/results/management.json`<br>`2026-09-08-content-faces/results/interview-lifecycle.json` |
| `WT-AC-BLP-01A` | `2026-09-08-content-faces/results/verify.json`<br>`2026-09-08-content-faces/results/management.json` |
| `WT-AC-BLP-01B` | `2026-09-08-content-faces/results/verify.json`<br>`2026-09-08-content-faces/results/management.json` |
| `WT-AC-FORM-01A` | `2026-09-08-form-flow/verify.json`<br>`2026-09-08-form-flow/kinds-verify.json`<br>`2026-09-08-form-flow/steps-verify.json`<br>`2026-09-08-form-flow/slots-verify.json`<br>`2026-09-08-form-flow/boundaries-verify.json` |
| `WT-AC-FORM-01B` | `2026-09-08-form-flow/verify.json`<br>`2026-09-08-form-flow/kinds-verify.json`<br>`2026-09-08-form-flow/steps-verify.json`<br>`2026-09-08-form-flow/slots-verify.json`<br>`2026-09-08-form-flow/boundaries-verify.json` |
| `WT-AC-PARTS-03A` | `2026-09-08-content-faces/results/inheritance/verify.json` |
| `WT-AC-PARTS-03B` | `2026-09-08-content-faces/results/inheritance/verify.json` |
| `WT-AC-PAID-01C` | `2026-09-08-content-faces/results/verify.json` |
| `WT-AC-INTERVIEW-01C` | `2026-09-08-content-faces/results/verify.json`<br>`2026-09-08-content-faces/results/management.json`<br>`2026-09-08-content-faces/results/interview-lifecycle.json` |
| `WT-AC-BLP-01C` | `2026-09-08-content-faces/results/verify.json` |
| `WT-AC-LEARN-01A` | `2026-09-08-content-faces/results/learning/verify.json`<br>`2026-09-08-content-faces/results/management.json` |
| `WT-AC-LEARN-01B` | `2026-09-08-content-faces/results/learning/verify.json` |
| `WT-AC-LEARN-01C` | `2026-09-08-content-faces/results/learning/verify.json`<br>`2026-09-08-content-faces/results/learning/publication.json` |
| `WT-AC-LEARN-01D` | `2026-09-13-learning-navigation-ownership/verify.json` |
| `WT-AC-PAGE-01C` | `2026-09-08-content-faces/results/site-pages/verify.json` |
| `WT-AC-SEARCH-01A` | `2026-09-08-site-search/results/verify.json` |
| `WT-AC-SEARCH-01B` | `2026-09-08-site-search/results/boundaries.json`<br>`2026-09-08-site-search/results/access.json`<br>`2026-09-08-site-search/results/passwords.json`<br>`2026-09-08-site-search/results/performance.json` |
| `WT-AC-SEARCH-01C` | `2026-09-08-site-search/results/boundaries.json`<br>`2026-09-08-site-search/results/input.json`<br>`2026-09-08-site-search/results/passwords.json` |
| `WT-AC-SEARCH-01D` | `2026-09-08-site-search/results/verify.json`<br>`2026-09-08-site-search/results/boundaries.json`<br>`2026-09-08-site-search/results/input.json`<br>`2026-09-08-site-search/results/locale.json` |

完成カタログ追加のために、43 AC以外にも生成器が読むHOME証拠（verify-home-completion.mjs、verify-home-isolation.mjs、verify-home-rank-fallback.mjs、measure-home-presentation.mjs）の現source一致が必要。親の実行済み記録を優先し、重複実行しない。
