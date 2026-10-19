# Repository instructions

Repository: yoohwz/wc-advanced-accounts.

Read docs/workflow.md before any repository write or review. Read TESTING.md before runtime validation. Load the applicable product, authentication, integration, compatibility and architecture contracts when their semantics are affected.

Human-facing analysis, status, handoff, review and conclusion prose defaults to concise, clear Vietnamese. Lead with the problem or result, explain the practical impact and end with one concrete next action or None. Preserve source code, identifiers, paths, commands, Git names, enum/status tokens and error messages when translation would reduce precision.

## Authority

- Human Owner: product priorities and goals; merge, release, deployment and rollback authority.
- ChatGPT: desired outcome, scope, product requirements, evidenced risks, genuinely unresolved architecture/security boundaries, Plan Review when actually required, and exact-head Acceptance Review.
- Codex implementer: discovery, technical design inside the approved boundary, implementation, tests, validation, commits and PR upkeep.
- Independent reviewer: fresh-context exact-candidate Technical Review for Controlled work.
- Git/GitHub: Issue scope, branch/PR head and base, diff/history, CI/review evidence and merge provenance.

Codex owns implementation choices within the approved boundary, including classes/methods, SQL/CAS/locking, state layout and test design. ChatGPT does not prescribe these details by default in Issues, plans or Acceptance; an evidenced risk requiring such a constraint must explain why a simpler behavioral requirement is insufficient. Prefer the smallest complete solution using existing systems. Avoid speculative helpers, frameworks, services, abstractions, migration/proof machinery or side tasks; reconsider a simpler solution when new complexity repeatedly demands more patches. Validate decisive provider/release feasibility before investing in dependent foundations.

Tool access never transfers another role's authority. Do not duplicate GitHub-native facts into task-state files, manifests, ledgers or transition records.

## Human commands

GitHub Issue #N is canonical task identity AAC-N. Except for read-only investigation, every repository change intended for merge requires an admitted AAC-N Issue. Fast and Controlled are risk lanes inside that governed task identity; neither creates a direct-write path. There is no allocator, identity registry or workflow-state database.

- Create ... — ChatGPT records the minimum useful Issue boundary and risk.
- Run AAC-N — Codex recovers current GitHub state and performs the next implementation-owned or exceptional recovery step, including read-only discovery for discovery-first work. It is not the normal post-Acceptance CI poller.
- Continue AAC-N — convenience alias to recover and resume the same task; it is not lifecycle state.
- Plan Review AAC-N — ChatGPT resolves a genuinely unresolved product, architecture, security, data, permission or compatibility boundary.
- Review AAC-N — manual fallback for the fresh independent review required by a frozen Controlled candidate.
- Acceptance Review AAC-N — ChatGPT performs an internal exact-head precheck, launches and observes full exact-head Required CI to terminal result in the same execution, then records final SHA-bound Acceptance in GitHub only after CI PASS and fresh verification.
- Merge AAC-N — Human authorizes merge of the unchanged accepted candidate after fresh verification.

For an admitted task, terminal/handoff reports end with:
STATUS: <navigation label>
Task: AAC-N
Next: <one exact short Human command, or None>

Useful labels include READY_FOR_DISCOVERY, READY_TO_RUN, IN_PROGRESS, PLAN_REVIEW_REQUIRED, TECHNICAL_REVIEW_REQUIRED, TECHNICAL_REVIEW_BLOCKED, ACCEPTANCE_REVIEW_REQUIRED, REQUIRED_CI_PENDING, READY_TO_MERGE, HUMAN_DECISION_REQUIRED and FINALIZED. They are navigation hints only.

Report READY_TO_RUN only when the candidate outcome/scope is bounded, all known product/security/data/compatibility/migration decisions needed for completion are settled, and the Issue contains no contradictory completion requirement. Classify known unresolved items before implementation as BLOCKING CURRENT CANDIDATE or DEFERRED / NON-BLOCKING. Explicitly exclude deferred items from the current candidate and link material ones to follow-up Issues; do not silently implement them or stop for Plan Review merely because they exist. A newly discovered in-scope blocker may still require Plan Review.

Use READY_FOR_DISCOVERY with Next: Run AAC-N when the Issue intentionally requires discovery to settle a material boundary before source implementation. Codex first recovers state and performs read-only discovery/technical planning. Do not make source changes across a known unresolved boundary. Continue implementation only when the Issue authorizes the discovered design and no unresolved boundary remains; otherwise hand off a concrete PLAN_REVIEW_REQUIRED decision. Ordinary bounded Controlled fixes need no discovery-first phase.

## Risk and delegation

Fast is bounded work without sensitive behavior or safety-control semantics.

Controlled covers identity, authentication, authorization, OTP/reset/verification, security/privacy, persistent lifecycle or migration, membership access/discounts, provider trust, public/shared contracts, architecture, coordinated cross-repository writes, release safety and workflow-governance semantics.

If Fast discovery exposes a Controlled trigger, keep the same Issue/PR, escalate and continue under Controlled rules.

Plan Review is conditional on a real unresolved boundary; Controlled does not automatically require a separate Plan Review. For Controlled persistent-state, public-identifier, privacy-data or migration work, first-pass discovery inspects applicable new writes, legacy records, partial/failure states, ambiguous provenance, migration/rollback/recovery and public URL/permalink/consumer compatibility.

When a genuine separate Plan Review is required, Codex must complete one fresh dedicated GPT-6.1 Sol / HIGH discovery subagent through `collaboration.spawn_agent` (or the canonical equivalent) before returning PLAN_REVIEW_REQUIRED. Use a fresh context containing only the canonical Issue/approved decisions, admitted base/current repository facts, relevant source/contracts, exact unresolved boundary, known evidence/constraints and applicable read-only companion evidence. The subagent independently performs read-only discovery; it cannot decide Plan Review, implement source, grant Acceptance, merge or release. Ordinary bounded Controlled work and deferred/non-blocking items do not trigger this delegation.

The subagent returns a factual discovery packet: current state, smallest affected scope, exact decision outside implementer authority, feasible options/material trade-offs when multiple exist, technically supportable proposed/default option, blocked source work and evidence gaps/limitations. Codex verifies that packet against current Git/GitHub/repository state, removes unsupported claims and deferred/non-blocking items from the decision, and distills the final PLAN_REVIEW_REQUIRED handoff to ChatGPT. The handoff includes admitted base/current facts and discovery evidence and explicitly confirms completed GPT-6.1 Sol / HIGH subagent discovery. Record factual results and actionable decisions only; do not copy or persist raw hidden reasoning/chain-of-thought. ChatGPT owns the decision; Codex retains discovery/technical planning responsibility within its authority. A full implementation design is unnecessary when the decision determines it.

If mandatory discovery delegation cannot be created or completed successfully, preserve task/PR state and stop at HUMAN_DECISION_REQUIRED with the exact delegation failure. Do not silently substitute main-agent discovery or change source across the unresolved boundary. A retry uses a new fresh GPT-6.1 Sol / HIGH subagent. Use one discovery subagent per Plan Review attempt by default; a second is justified only by a materially new unresolved boundary or an unusable prior attempt, never consensus. Do not create swarms, voting panels, nested agents, persistent agent state or a generic planning framework.

Keep the PR Draft through implementation, correction, Technical Review and the internal Acceptance precheck. A blocker-free precheck permits the unchanged PR to become Ready for final CI; durable Acceptance follows CI PASS. Run applicable focused local checks before freezing; ordinary Draft pushes do not launch full hosted gate.

Use one implementer. Every Controlled exact candidate requires one fresh independent reviewer in a fresh context and isolated source-read-only checkout/environment. Give the reviewer the Issue/approved boundary, admitted base, exact candidate SHA/diff, relevant contracts and validation evidence, not implementer scratch reasoning or self-review conclusions.

Any candidate movement invalidates the applicable Technical Review and Acceptance, including documentation-only, test-only, metadata-only or UI-only movement. Never carry old PASS evidence forward to a new SHA.

Review blockers require evidence of a material defect, approved-scope/Acceptance violation or safety, data or compatibility risk; a P2 label alone does not make optional cleanup or a technical preference blocking. Record optional improvements as non-blocking without requiring follow-up tasks unless Human prioritizes them. Stop review/correction when the approved outcome, applicable tests and mandatory safety requirements are satisfied.

If review finds blocking P0/P1/P2 issues, correct within the same Issue/PR, rerun relevant checks, push a new exact candidate and use a new fresh reviewer. Continue while each round makes material progress. Stop at HUMAN_DECISION_REQUIRED for stagnation, repeated blockers, oscillation, unsafe continuation or scope/architecture expansion.

## Compute policy

- Root, Fast implementation and deterministic Controlled implementation/correction: GPT-6.1 Sol / MEDIUM.
- Controlled discovery/architecture when a separate Plan Review is actually needed: one fresh dedicated GPT-6.1 Sol / HIGH discovery subagent as required above.
- Every fresh independent Technical Reviewer and re-reviewer: GPT-6.1 Sol / HIGH.
- XHIGH requires a specific exceptional unresolved architecture/security reason.

Reduce irrelevant context before increasing compute. Do not persist model/effort history as workflow state.

## Repository and runtime safety

Before writes, verify the expected repository/origin, fetch origin, inspect branch/status and preserve unrelated work. Never reset, clean, stash, rebase or force-push merely to manufacture a clean state.

A Human-owned Local WordPress installation may be used for bounded manual/runtime validation when appropriate, but repository governance does not depend on one absolute filesystem path. Automated or destructive integration tests must use disposable environments and must not repurpose an existing Human installation.

Companion repositories are read-only unless the admitted Issue explicitly authorizes coordinated writes.

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

AAC Required CI is the authoritative final candidate job on `ready_for_review`; it verifies current PR/head/base before and after PHP 7.4 syntax, JavaScript syntax, the existing shortcode/native WordPress-renderer/role-ownership checks, release-tooling regressions and diff whitespace. The native renderer downloads checksum-pinned WordPress 6.3 files without a database; CI is not full WP/Woo/MySQL runtime proof. Draft pushes run only `AAC Draft Checks` with the same inexpensive focused checks; they cannot supply final Required CI evidence. After the internal Acceptance precheck, transition the unchanged Draft PR to Ready, observe the correlated `ready_for_review` run to terminal PASS, fresh-check head/base/review/blockers, then record final SHA-bound ChatGPT Acceptance. Main runs lightweight `AAC Main Integrity`. See docs/workflow.md for exceptional recovery and the AAC-3 adoption boundary.

Acceptance and merge are separate. Merge AAC-N authorizes only the unchanged exact accepted candidate. Any candidate movement requires refreshed evidence. Return the PR to Draft before source/test correction and repeat fresh review/Acceptance. Safe infrastructure retries on the same SHA preserve evidence.

Merge is not release. Version/changelog changes, installable release packages, Git tags/GitHub Releases, WordPress.org SVN publication and production distribution require separate explicit Human authorization. Follow docs/releasing.md for separately authorized WordPress.org preparation, read-only SVN dry-run, Human-gated publication and verification. No commercial ZIP publisher, license/updater feed or automatic release is part of Core governance.
