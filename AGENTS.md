# Repository instructions

Repository: yoohwz/wc-advanced-accounts.

Read [docs/workflow.md](docs/workflow.md) before any repository write or review. It is the canonical workflow for admission, risk, delegation, review, CI, Acceptance and merge. Read [TESTING.md](TESTING.md) before runtime validation. Load applicable product, authentication, integration, compatibility and architecture contracts when their semantics are affected.

Human-facing analysis, status, handoff, review and conclusion prose defaults to concise, clear Vietnamese. Lead with the problem or result, explain the practical impact and end with one concrete next action or None. Preserve source code, identifiers, paths, commands, Git names, enum/status tokens, repository document language and error messages when translation would reduce precision.

## Authority and execution

- Human Owner: product goals/priorities, significant business/scope/contract changes, and merge, release, deployment and rollback authority.
- Codex Main: the single accountable technical owner for discovery, architecture/design within the authorized outcome and contracts, Issue/PR upkeep, implementation, subagent coordination, tests, correction, CI and exact-candidate final technical Acceptance.
- Independent reviewer: fresh-context, isolated source-read-only, exact-candidate review for every Controlled candidate, including Issue acceptance criteria and product-outcome evidence.
- Git/GitHub: canonical Issue scope, candidate/diff/history, review, CI, Acceptance and merge evidence.

ChatGPT has no required role or approval. Human instructions go directly to Codex Main. Main continues through implementation, independent review, correction, precheck, CI and final Acceptance to READY_TO_MERGE without asking Human to issue commands between technical phases. Main's Acceptance never substitutes for required independent review and never authorizes merge or release.

Every repository change intended for merge requires an admitted AAC-N Issue as defined in docs/workflow.md. Codex may create and bound that Issue from the Human request; no separate planning actor is required. Recover current Git/GitHub facts before acting. Ask Human only for an unresolved business/scope/contract decision, missing action authority or a blocker that cannot be resolved safely within the authorized task. Technical choices within the agreed boundary belong to Main.

Prefer the smallest complete solution using existing systems. Avoid speculative helpers, frameworks, services, abstractions, migration/proof machinery and side tasks. Reconsider a simpler approach when introduced complexity repeatedly demands more patches. Validate decisive provider/release feasibility before investing in dependent foundations. Do not duplicate GitHub-native facts into task-state files, manifests, ledgers or transition records.

## Risk and delegation

Use Fast/Controlled risk lanes from docs/workflow.md. Security, identity/authentication, privacy, persistent lifecycle/migration, membership access/discounts, provider trust, public/shared contracts, architecture, release safety and workflow-governance changes are Controlled. Escalate within the same Issue/PR when new risk appears.

Main may use subagents for useful independent discovery, isolated implementation or review, with explicit scope, write ownership, output and completion criteria. Prefer one writer; parallel writers require separate files/checkouts and non-conflicting test state. Main verifies and integrates results. Every frozen Controlled candidate requires a fresh independent reviewer under docs/workflow.md; an implementer must not review their own work as the independent reviewer. Any candidate SHA movement invalidates applicable Technical Review and Acceptance, including documentation/test/metadata/UI-only changes.

Routine technical discovery requires no separate Plan Review or mandatory discovery subagent. Preserve the focused lifecycle/legacy/failure/compatibility investigation required by docs/workflow.md. If required independent review is unavailable, preserve the candidate and report the limitation; do not replace it with self-review.

Compute policy and review/correction rules live in docs/workflow.md. Record factual findings and decisions only, never raw hidden reasoning. Tool access does not grant authority outside the Human-approved scope.

## Repository and runtime safety

Before writes, verify the expected repository/origin, fetch origin, inspect branch/status and preserve unrelated work. Never reset, clean, stash, rebase or force-push merely to manufacture a clean state. Companion repositories are read-only unless the admitted Issue explicitly authorizes coordinated writes.

A Human-owned Local WordPress installation may be used for bounded manual/runtime validation when appropriate. Automated or destructive integration tests must use disposable environments and must not repurpose an existing Human installation. No absolute local path is workflow authority.

## Core product invariants

- Advanced Accounts Free/Core remains the free WordPress.org WooCommerce plugin, slug `wc-advanced-accounts`; Premium is a separate read-only companion.
- Preserve supported WordPress/PHP/WooCommerce, My Account, Classic Checkout and Checkout Blocks behavior unless the Issue explicitly changes support.
- Do not opportunistically rename persisted yoaa_*, yoohw_*, wc_advanced_accounts_* or inherited compatibility identifiers.
- Preserve existing phone-account aliases and stored identity compatibility unless an approved migration boundary changes them.
- Browser-controlled hidden fields, raw user IDs, unsigned cookies or request parameters are not sufficient authentication/authorization/verification proof.
- Nonces do not replace capability/ownership checks or abuse controls.
- OTP/password-reset/verification changes require focused inspection of the existing entry points, expiry, attempts, resend, replay, context binding and session/reset continuation. Core has no separate authentication contract yet; do not claim the Premium contract or harness exists here.
- Membership rendering follows docs/MEMBERSHIP-RENDERING.md; role creation/retirement/deletion follows docs/ROLE-OWNERSHIP-RETIREMENT.md. Preserve the shared helper byte contract and conservative denial on incomplete proof.
- SMS/CAPTCHA providers are external contracts and must fail safely without credential leakage or authorization elevation.
- Membership/access changes must not silently broaden access; discount changes are financial semantics.
- Diagnostic/support logging must not expose passwords, OTPs, reset credentials, auth cookies/tokens, provider/license secrets or raw nonces.
- Optional integrations must degrade without fatal coupling when absent.

## Validation, merge and release

Follow docs/workflow.md for exact-head review, the precheck → full AAC Required CI → final Acceptance sequence, CI correlation/recovery and Human Merge. Follow TESTING.md for applicable focused validation and evidence limits. Keep correction PRs Draft; refresh invalidated evidence after candidate movement. READY_TO_MERGE requires final Acceptance and current successful Required CI on the exact candidate; it grants no merge authority.

Merge is not release. Version/changelog changes, installable release packages, Git tags/GitHub Releases, WordPress.org SVN publication and production distribution require separate explicit Human authorization. Follow [docs/releasing.md](docs/releasing.md) for separately authorized preparation, read-only SVN dry-run, Human-gated publication and verification. Core has no commercial ZIP publisher or license/updater feed. AAC CI remains focused validation, not full WordPress/Woo/MySQL runtime proof.
