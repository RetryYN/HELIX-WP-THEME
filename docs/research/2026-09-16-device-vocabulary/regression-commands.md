# 現在41 staleの再検証

正確なAC→証拠→row_namesは regression-command-map.json。DB検証は直列。

```sh
node scripts/verify-capability-manifest.mjs
node scripts/verify-capability-health.mjs
node scripts/verify-ai-boundary.mjs
node scripts/verify-privacy-boundary.mjs
node scripts/verify-site-page-quality.mjs
node docs/research/2026-09-10-reduced-motion/verify.mjs
node scripts/verify-i18n-profile.mjs
node scripts/verify-site-pages.mjs
node scripts/verify-site-page-editor.mjs
node scripts/verify-footer-rendering.mjs
node scripts/verify-footer-editor.mjs
node scripts/verify-event-state.mjs --strict
node scripts/verify-event-boundaries.mjs --strict
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

カタログの追加完成証拠:

```sh
node scripts/verify-home-completion.mjs
node scripts/verify-event-completion.mjs
node scripts/verify-banner-zones.mjs
```
