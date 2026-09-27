---
plan_id: PLAN-L6-01-wp-integration-ci
title: Run WordPress integration tests on current commits
kind: impl
drive: fullstack
layer: L6
status: draft
agent_slots:
  - role: qa
    slot_label: current-head-wordpress-integration
generates: []
dependencies:
  parent: null
  requires: []
  blocks: []
  references:
    - '#332'
github_issue_id: 332
behavior_contract_id: WT-AT-GATE-01
responsibility_owner: ci-integration
---

# WordPress integration CI on current commits

## Scope

Run the archived plugin's PHPUnit integration suite for changed commits using the matching stable WordPress core, PHPUnit test library, and MariaDB. The test must work when the checkout is mounted read-only, so archived source is exercised without relying on writes to compatibility mounts.

The PHPUnit integration job runs for relevant push and pull-request changes. It provisions WordPress core into the test library, uses the same WordPress version as the service container, and connects to the database through an explicitly published host port.

## Verification

- `vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap-integration.php --colors=always`: 29 tests, 144 assertions pass against WordPress 7.1.2 and MariaDB 10.11.
- The same integration suite passes with the checkout mounted read-only, using `phpunit-integration.xml.dist` and an isolated MariaDB service.
- `npm test` passes; generated acceptance outputs are synchronized and the evidence rebind check passes.
- Current-head GitHub `test` and `current-theme-quality-gate` workflows pass at the PR head. The Lighthouse job is skipped by its existing condition.

## Completion boundary

This closes the missing continuous execution of the integration suite and verifies that archived source is read-only compatible. It does not claim completion of the full WP product acceptance or every WordPress release line.
