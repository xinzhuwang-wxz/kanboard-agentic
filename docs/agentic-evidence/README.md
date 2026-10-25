# Acceptance evidence

This directory preserves the accepted immutable AE bundle and delivery-check output for `kanboard-upstream@1.0.4`, verified on Linux on 2026-10-25. Screenshots are extracted from actual browser evidence; original-before.png comes from the pre-apply original baseline, not the candidate. The approval and narrow screenshots come from the adapted host.

Six gates passed: PHP/JavaScript checks and scoped native tests; host business tests; original/candidate black-box differential; authenticated AE runtime; browser acceptance; owned-process completion. Original and candidate each passed 53 native tests / 475 assertions. Twenty runtime/browser checks cover approval, role/CSRF/stale/replay rejection, cancellation, revoked membership, admin disable, durable lost-response receipt, fail-closed restart, truthful assignee labeling, drag, focus, browser history and narrow-screen bounds.

Scope: synthetic local users/tasks in real Kanboard, SQLite, loopback development server, deterministic demo provider. This is not production, paid-model, arbitrary-plugin, MySQL/PostgreSQL, LDAP, macOS, or full-upstream-suite qualification. The full upstream suite was not completed because optional LDAP runtime support was unavailable; the scoped native suite was actually run. Verification toolchain prerequisites are machine-specific and documented in the integration guide. Evidence paths retain their original machine locations for audit; those paths are not portable setup instructions.

No customer data, API credentials, deployment, or external publication is included. Hashes provide integrity, not signatures or third-party attestation. New real end-user writes still require their own explicit confirmation; engineering adaptation approval does not authorize them.
