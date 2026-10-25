# Kanboard Agentic

A native Agentic Everything integration for **Kanboard 1.2.56**, pinned to upstream commit `e197e71af4ff9f371b931ab0338b2b41827e6394`. Kanboard stays a PHP application: its original board, task models, users, project memberships, role restrictions and CSRF controls remain authoritative.

## What it does

- A draggable, keyboard-accessible assistant opens on the existing task page.
- Read one task and its permitted assignees; preview a status or assignee change.
- AE `GovernedRuntime` and `ToolBroker` bind confirmation to the user, capability, arguments, exact task snapshot, release, policy and expiry.
- The PHP host rechecks current permissions and writes through its original task models. Completed receipts remain in host SQLite.
- Administrators can disable all assistant operations, including pending confirmations, in **Settings → Agentic Everything**.

The default provider is a **deterministic demo**, not natural-language AI. It makes zero model API calls. An existing subagent can use AE's `FileModelProvider` via a trusted private `AE_MODEL_DIRECTORY`; no paid provider is configured here.

## Run locally

Requirements: the checked-out Kanboard sources and bundled vendor directory, PHP 8.3+ with PDO SQLite, XML/DOM/SimpleXML, GD, mbstring and normal PHP core extensions; Node 24; a separately built, trusted AE installation. The original upstream test suite additionally uses PHPUnit and Symfony Stopwatch as pinned in `composer.lock`.

```sh
export AE_ROOT=/absolute/path/to/agentic-everything
export AE_PHP=/absolute/path/to/php
export DB_FILENAME=/absolute/path/to/private/kanboard.sqlite
node plugins/AgenticEverything/Runtime/serve.mjs
```

Open `http://127.0.0.1:18856`. `AE_HTTP_PORT` can select another unprivileged loopback port. The launcher owns one Node runtime and one PHP development server. Its ephemeral internal bridge token is passed only to the child PHP environment; it is never browser configuration or a persistent service credential. Use Kanboard's existing login and change upstream default administrator credentials before using non-test data. The development server is **not a production deployment**.

No paid API, remote publication, database migration of production data or external deployment is implied. All verification uses synthetic local users and tasks in the real original host, not a substitute fixture application.

## Architecture and trust

`Plugin.php` attaches existing Kanboard template/asset hooks. `AeController` serves same-origin session/CSRF-protected requests; browser input cannot set the principal. `Runtime/serve.mjs` is a single AE owner using the official shared runtime. Its private CLI transport `Runtime/host.php` reloads the active native user for every operation. The host owns `ae_task_operations`; original task tables are not replaced.

Task versions are SHA-256 fingerprints of the complete current task row and ordered subtasks, not second-resolution timestamps. This prevents same-second edits from silently invalidating a confirmation. Assignment checks the current project's assignable users and custom restrictions. Status changes preserve Kanboard's close-subtasks semantics.

## Recovery and boundaries

- AE run/SDK/approval state is **in memory**. A restart discards pending approvals and fails closed; it does not resume a durable agent workflow.
- Completed native receipts survive restart and are subject-bound. Browser refresh or a lost response can query a receipt without issuing another write.
- Repeated, stale, expired, cross-user, unauthorized and tampered confirmations are denied. An indeterminate host outcome is marked unknown and requires host review; no blind write retry is offered.
- Qualified write scope is SQLite, native tasks, and projects without automated actions. External tasks and automated-action projects keep their original controls but are not silently exposed through this assistant.
- A single local owner is required. Production multiworker routing, external mail/plugin effects, load testing and paid model quality are outside this acceptance scope.

## Development and verification

The AE recipe and fixed `kanboard-upstream-v1` verification profile capture the pinned original before apply, compare the same native browser task journey afterward, run the scoped original task/role test suite, and exercise real assistant confirmations, permission denials, CSRF, version conflicts, duplicate submission, lost response, restart, drag, close/focus and narrow screens. Inspect the generated delivery bundle and its exact statuses; a static verifier's NOT RUN is not a runtime pass.

The `kanboard-upstream-v1` acceptance collector is qualified on the cloud Debian environment, with hash-pinned PHP tooling under `/tmp/kanboard-php-runtime`, Stopwatch under `/tmp/kanboard-test-deps/stopwatch`, and the separately qualified Chromium headless-shell. It does not download or install tools while verifying. Those machine-specific verification prerequisites are separate from the portable plugin launcher above; no Mac execution or cross-platform qualification is claimed.

The scoped upstream suite contains 53 tests and 475 assertions. Full Kanboard qualification includes optional LDAP/database/integration dependencies and is not inferred from this subset. Full production security and arbitrary third-party plugins are not certified.

## Disable, upgrade and rollback

Disable the assistant in native settings to block new and pending operations immediately. Removing this plugin removes the UI and runtime entry point without removing ordinary task data. Keep the operation table for audit; back up the host database before upgrades. AE file rollback restores only unchanged recipe-owned files and does not undo business writes or database migrations. Review an unknown operation in the host before any manual correction.

## License

Kanboard is MIT licensed by Frédéric Guillot; its root `LICENSE` is unchanged. This integration is MIT licensed; see `LICENSE`. All bundled upstream vendor and library notices remain in place. See `THIRD_PARTY_NOTICES.md` for paths and redistribution obligations.
