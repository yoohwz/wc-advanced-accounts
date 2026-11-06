# AAC Codex-owned workflow

Ship correct Advanced Accounts Free/Core changes with the minimum process that materially reduces risk. Codex Main owns technical execution end to end and coordinates subagents. ChatGPT has no required workflow role or approval. Human retains product priorities, significant business/scope decisions and merge, release, deployment and rollback authority.

Normal flow inside Run AAC-N: Discovery and Local Development → Frozen Candidate → Fresh Independent Technical Review (Controlled) → Main precheck → Full exact-head AAC Required CI → Main final technical Acceptance → READY_TO_MERGE. Human Merge follows separately, then Lightweight Main Integrity. Main handles correction/review/CI loops without requiring Human commands between technical phases.

## 1. Sources of truth and authority

| Fact | Authority |
| --- | --- |
| Human-requested outcome, bounded scope, risk and acceptance criteria | GitHub Issue #N (AAC-N), maintained by Codex Main |
| Candidate and ancestry | Git branch / PR head and Git history |
| Independent technical and outcome findings | PR review/comments bound to the reviewed SHA |
| Hosted checks | GitHub Actions/Checks |
| Product/security rules | Applicable repository contracts |
| Final technical Acceptance | SHA-bound Codex Main record on the PR, referencing review and CI |
| Merge | GitHub PR merge record after explicit Human Merge |
| Release/publication | Separate Human-authorized release process |

Codex Main owns discovery, technical architecture/design, implementation, tests, correction, delegation, Issue/PR upkeep, CI orchestration and final technical Acceptance within the authorized outcome and contracts. Independent review is a separate assessment, not a second implementation owner. Main's final Acceptance verifies the approved outcome and current evidence; it does not replace independent review or authorize merge/release.

Conversation memory and local notes are navigation hints only. On every resumed command, recover current Issue/PR/head/base/review/CI facts before acting. Do not create a task-state database, allocator, ledger, approval parser, digest chain or parallel control plane.

For governance amendments, the workflow accepted on the admitted base governs the amendment. Proposed text cannot waive its own admission, review, Acceptance or merge requirements. An explicit Human transition instruction must be recorded with its scope in the Issue; never infer it from the proposed text. Historical approvals and contracts remain historical evidence, not new authority.

## 2. Start, admission and recovery

Fetch origin; verify repository/origin, inspect local changes and recover existing Issue/branch/PR state before creating anything. GitHub Issue #N is canonical identity AAC-N. Except for read-only investigation, every change intended for merge requires an admitted Issue. Codex Main may create it directly from the Human request, recording the outcome, smallest scope, Fast/Controlled risk, acceptance criteria, admitted base and material authority limits. No separate actor or Human Create/Plan command is required when the request already authorizes the work.

Use one Issue and normally one branch/PR to main. Preserve unrelated work. Never push directly to main, force-push, reset/clean destructively, stash/rebase or delete unrelated work merely to make preflight pass. If main moved, inspect its delta and reassess affected scope, compatibility and evidence; Main resolves technical integration inside the authorized boundary, escalating only decisions described in section 6.

Keep the PR Draft through implementation, correction, independent review and Main's precheck. Only a blocker-free precheck permits the unchanged candidate to become Ready for the final hosted gate. Ordinary Draft pushes do not launch full hosted gate.

A Human-owned Local WordPress installation may be used for bounded manual/runtime validation. Automated or destructive integration tests must use disposable environments and must not repurpose that installation. The workflow is not bound to an absolute local path.

## 3. Human interaction and status

- A direct work request or Create ... lets Main record the minimum useful Issue and proceed when implementation is authorized and bounded. A request only for assessment remains read-only.
- Run AAC-N recovers state and performs all remaining authorized technical work through final Acceptance and READY_TO_MERGE, including discovery, review delegation, correction and observing CI to terminal result.
- Continue AAC-N is a convenience alias for resuming the same work, not lifecycle state.
- Review AAC-N is a manual/standalone independent-review fallback. Acceptance Review AAC-N is a compatibility alias for Main's remaining precheck/CI/final Acceptance work; it does not bypass prior checks. Neither command is required between normal Run phases.
- Plan Review AAC-N is a compatibility entry point for Main to investigate an unresolved boundary, decide technical matters it owns and ask Human only for an actual decision under section 6. It requires no ChatGPT participant or special approval stage.
- Merge AAC-N is explicit Human authorization to merge only the unchanged accepted candidate after fresh verification. Engineering commands never imply it.

For an admitted task, terminal/handoff reports end with:
STATUS: <navigation label>
Task: AAC-N
Next: <one exact short Human command, concrete decision request, or None>

Useful labels are IN_PROGRESS, READY_FOR_DISCOVERY, READY_TO_RUN, TECHNICAL_REVIEW_REQUIRED, TECHNICAL_REVIEW_BLOCKED, REQUIRED_CI_PENDING, HUMAN_DECISION_REQUIRED, READY_TO_MERGE and FINALIZED. They are navigation prose, not machine state or mandatory stops. Do not require Run/Continue merely to cross an internal technical phase.

READY_TO_RUN means the current outcome/scope is bounded and known business, security, data, compatibility and migration decisions needed for implementation are settled. Classify known unresolved items as BLOCKING CURRENT CANDIDATE or DEFERRED / NON-BLOCKING; explicitly exclude deferred items from completion and link material ones to follow-up Issues. Do not silently implement deferred work or let contradictory Issue wording make it a completion requirement. READY_FOR_DISCOVERY is useful when the initial request needs read-only investigation; once the authorized boundary is clear, Main continues without a separate ceremony.

## 4. Risk and proportional execution

Fast is bounded, understood work without sensitive behavior or safety-control semantics: documentation, comments, presentation-only CSS, formatting, unchanged-meaning translations and mechanical test/tooling maintenance.

Controlled is required for material changes to:
- identity/ownership, phone/email canonicalization, authentication, sessions or auth cookies;
- OTP, verification, reset/passwordless flows, authorization, privileged AJAX/REST or abuse controls;
- persistent identity/security/lifecycle state, migrations or privacy-sensitive logging/deletion;
- membership access, role entitlement, discounts or subscription lifecycle;
- SMS/CAPTCHA/provider trust and failure semantics;
- free/premium takeover, public/shared contracts, architecture or coordinated cross-repository writes;
- release/package safety or workflow-governance semantics.

If discovery reveals a Controlled trigger, classify it before making the sensitive change and continue under Controlled rules in the same Issue/PR. Controlled requires independent review, not a separate planning approval. Both lanes retain the final Required CI and Acceptance gates.

Choose the smallest complete solution using existing systems. Avoid speculative helpers, frameworks, services, abstractions, migration/proof machinery and side tasks. Reconsider a simpler approach when introduced complexity repeatedly demands patches. Validate decisive provider/release feasibility before investing in dependent foundations. Add focused deterministic coverage for real regressions where credible; do not create tests that merely mirror reversible prose or implementation details.

## 5. Delegation and compute

One Codex Main remains accountable for the result. Use subagents when useful for independent discovery, separable implementation or review. Give each an explicit objective, relevant contracts/facts, write scope, required output and completion criteria. Main verifies findings, integrates results, resolves conflicts and checks acceptance criteria; Human need not coordinate agents or relay prompts/results.

Prefer one writer. Multiple writers require separate files/checkouts and non-conflicting test state. Independent reviewers must not have implemented any part of the candidate and must use the fresh context and isolated source-read-only environment in section 8. A discovery agent cannot substitute for that reviewer. No mandatory discovery delegation precedes an ordinary technical choice or Human question. Do not create swarms, voting panels, persistent agent state or a generic planning framework.

- Main orchestration, Fast work and deterministic Controlled implementation/correction: GPT-6.1 Sol / MEDIUM.
- Focused difficult discovery/architecture when evidence warrants it: GPT-6.1 Sol / HIGH; delegation is optional.
- Every fresh independent Technical Reviewer and re-reviewer: GPT-6.1 Sol / HIGH.
- XHIGH requires a specific exceptional unresolved architecture/security reason.

Reduce irrelevant context before increasing compute. Record factual findings, decisions and validation, never raw hidden reasoning or model/effort history as task state.

## 6. Discovery and Human decisions

Discovery happens within normal execution. For Controlled persistent-state, public-identifier, privacy-data or migration work, inspect applicable new writes, legacy records, partial/failure states, ambiguous provenance, migration/rollback/recovery and public URL/permalink/consumer compatibility. Main chooses classes/methods, SQL/CAS/locking, state layout, test design and other technical mechanics consistent with the authorized outcome and contracts.

A trust model, migration policy, public contract or architectural question is not automatically outside Main's authority. Main resolves its technical aspects within existing product/security/compatibility commitments. Ask Human only when completing the task requires an unresolved business choice, significant change to scope or those commitments, action authority not already granted, or continuation that cannot be made safe. Never silently weaken mandatory security/data/compatibility invariants. Companion writes and release/production actions require their explicit authority.

Before asking, complete safe independent investigation and prepare a concrete decision: facts and smallest affected scope, what Human must choose and why, feasible options/material trade-offs, recommended option, blocked work and evidence limitations. Continue independent authorized work while waiting; do not implement across the unresolved boundary. Discovery delegation is useful when it improves evidence, not a prerequisite to asking Human. Optional/deferred items do not block the current candidate unless evidence shows they are inseparable.

## 7. Candidate and evidence

Use the smallest complete change, preserve compatibility identifiers, avoid opportunistic refactors/renames, validate affected behavior, restore task-created runtime state and push the candidate. Follow TESTING.md for applicable inexpensive local checks before freezing: minimum-supported PHP syntax when available, JavaScript syntax, exact-diff whitespace, affected deterministic regressions and bounded manual runtime observation when useful. Record unavailable checks honestly. Core CI provides focused checks only; no full disposable WP/Woo/MySQL runtime harness is available.

For product changes, demonstrate the actual outcome on relevant user surfaces; parser/state tests and green CI alone do not establish it. For hotfixes, distinguish merged source from delivered/released artifacts. Release tasks must also bind and validate the immutable artifacts under the release runbooks.

Keep one concise PR evidence record with Issue/scope/risk, changed behavior/files, exact head/base, commands/results, runtime limitations and links to independent review and CI. GitHub records the facts; no duplicate ledger. Human-facing prose uses clear Vietnamese, leads with the result/problem and ends with one concrete next action or None. Preserve code/paths/commands and repository document language.

## 8. Independent Technical Review

Every frozen Controlled candidate requires one fresh independent reviewer, normally before the final hosted gate. The PR remains Draft. The reviewer must:
- start in a fresh context and isolated source-read-only checkout/environment;
- receive the canonical Issue/approved Human decisions, admitted base, exact candidate SHA/diff, relevant contracts and validation evidence;
- not receive implementer scratch reasoning, self-review conclusions or a suggested verdict;
- independently inspect correctness, lifecycle/failure behavior, security/privacy, compatibility and test quality;
- verify Issue acceptance criteria, scope and actual product-outcome evidence, including material evidence gaps; these checks remain required without a separate ChatGPT Acceptance reviewer;
- run relevant focused checks independently when practical and bind findings or PASS to the exact SHA in GitHub evidence.

For auth/authorization inspect alternate entry points and adjacent allow/deny paths. For OTP/reset inspect expiry, attempts, resend, destination/context binding, replay, session rotation and auth-cookie/reset continuation. For persisted lifecycle inspect create/update/failure/retry/cleanup. For membership inspect entitlement and financial outcomes. For providers/contracts inspect absence, transport/rejection/malformed responses, credentials and producer/consumer compatibility.

A reviewer may return EARLY_RUNTIME_REQUIRED only for a concrete evidence gap that source/contracts/local evidence and independent probes cannot settle without actual WordPress/WooCommerce/database runtime. Core has no full runtime dispatch entry point. Main obtains the specific missing evidence in a disposable environment or reports the precise limitation; Draft focused checks cannot stand in for unrun runtime. This exception grants no Acceptance, Ready or merge authority and does not replace the normal final ready_for_review gate.

Severity: P0 is an exploitable auth bypass, credential exposure, takeover/data loss or immediate release stop; P1 is a material correctness/security/identity/persistence/access/financial/compatibility defect; P2 is a material failure-handling/test/observability gap blocking safe Acceptance; P3 is non-blocking improvement. A blocker requires evidence of a material defect, scope/acceptance violation or safety/data/compatibility risk. Severity labels alone do not turn preferences or optional cleanup into blockers. Record optional improvements without mandatory follow-up tasks unless Human prioritizes them.

Main corrects blocking P0/P1/P2 findings within the same Issue/PR, stays/returns Draft before correction, reruns relevant checks, pushes a new candidate and obtains a new fresh independent reviewer. Continue while each round makes material progress. Stop for Human on stagnation, repeated blockers, oscillation, unsafe continuation or expansion beyond authorized scope; stop correction when the approved outcome, applicable checks and mandatory safety requirements are met.

If required review delegation is unavailable, preserve the candidate and report TECHNICAL_REVIEW_REQUIRED with the exact limitation and fallback Review AAC-N. Self-review cannot replace it.

## 9. Exact-candidate invalidation

Any movement of the candidate SHA invalidates applicable Technical Review and Acceptance, including documentation-only, test-only, metadata-only or UI-only changes. Historical/inferred evidence is not current-head PASS; UNAVAILABLE is not PASS. There is no delta-carry-forward exception. Safe infrastructure retries on the unchanged candidate preserve applicable review evidence.

Base movement requires inspecting the delta and re-establishing scope/precheck and CI evidence against the current base before final Acceptance or merge. Main resolves authorized technical integration; significant changed commitments or missing scope authority go to Human. Do not silently reuse stale base/CI evidence.

## 10. Main precheck, CI, final Acceptance and Human Merge

Run/Continue automatically performs these phases after focused local validation and, for Controlled work, fresh exact-candidate independent review PASS. No additional Human Acceptance command is required. Main checks completion and evidence without repeating the full independent review unless new findings justify it.

### A. Internal precheck

Fresh-read Issue/approved decisions, admitted/current base, exact PR head and total diff, local/focused validation and limitations, required SHA-bound independent review, unresolved threads and new blocker evidence. Verify scope, acceptance criteria, actual product outcome, security, architecture and compatibility. If correction is needed, keep/return Draft and continue the local correction/fresh-review loop before launching the expensive normal final gate. The precheck is not durable Acceptance PASS.

### B. Launch and correlate final CI

After a blocker-free precheck, fresh-verify open Draft PR, expected repository, main target and unchanged head/base. Transition the unchanged PR to Ready; ready_for_review launches the existing single AAC Required CI job in `.github/workflows/membership.yml`. It checks out the exact head and verifies the open PR, expected repository, head and current base before and after checks using GitHub API evidence. It runs PHP 7.4 syntax for tracked PHP, JavaScript syntax for tracked JavaScript, `tests/membership-shortcode.php`, the checksum-pinned native WordPress 6.3 renderer, `tests/role-ownership.php wc-advanced-accounts`, release-tooling regressions and exact-diff whitespace. No database, WooCommerce runtime or Premium scenario matrix is installed. These checks are not full WordPress/Woo/MySQL compatibility proof.

Correlate the run with this PR's ready_for_review event and expected head/base using native PR/event/check/run metadata and the job's actual candidate checkout/base verification. Capture run URL/ID and attempt. A pull-request run can report a synthetic merge SHA: verify its PR/head association and actual checkout. Reject stale/wrong-PR/wrong-head/wrong-base evidence; never choose a run just by name, timestamp or green status. Core has no workflow_dispatch runtime entry point.

Draft opened/reopened/synchronize events run AAC Draft Checks with the same inexpensive focused checks; they cannot supply final Required CI evidence. Ready synchronize pushes do not launch a new final gate: return to Draft for corrections, refresh review and precheck, then use a new ready_for_review event. push main runs only AAC Main Integrity (exact checkout, PHP/JavaScript syntax, bounded diff whitespace), without focused suites or database/runtime services. Preserve this topology and grow tests from real regressions rather than a speculative generic harness. Do not add an aggregate job, matrix, path classifier or custom approval parser. If repository plan capabilities cannot enforce protection/rulesets, retain fresh exact-head evidence checks rather than emulate protection with a workflow database. No protection, secrets or settings change is authorized here.

### C. Observe and correct

Observe the correlated run in the same execution to terminal result, using bounded waits and progress updates. Queued/running, cancelled, skipped, timed-out, missing or unavailable evidence is not PASS. For credible transient/infrastructure failure without head/base movement, safely retry only the necessary job/run and observe its attempt; preserve independent review and record failure/retry evidence.

If CI exposes a source/test defect, record no final Acceptance, return Draft before correction, rerun focused checks and obtain fresh exact-candidate independent review when required. Main repeats precheck and the final gate automatically; no new Human Run/Acceptance command is needed for in-scope correction. Candidate movement invalidates the prior review and precheck. New blockers follow the same correction/decision rules.

Only genuine external/tooling interruption preventing terminal observation permits REQUIRED_CI_PENDING / Next: Run AAC-N. Report the limitation and exact PR/head/base/run/attempt for recovery, never a PASS claim. Ordinary queue time or an internal phase transition is not a handoff reason.

### D. Final technical Acceptance

Only after correlated full Required CI PASS, fresh-verify the PR remains open, head/base unchanged, required independent review PASS still matches the SHA and no new blocker/thread appeared. Resolve base movement under section 9 and blockers under sections 6/8 before proceeding. Return Ready candidates to Draft before source/test correction; synchronize does not launch a new full gate.

Main records durable SHA-bound final technical Acceptance PASS on the PR with the verified base, acceptance criteria/outcome assessment, independent review link when required, successful CI run/attempt, limitations and remaining non-blocking risks. No separate ChatGPT approval is required. A Ready PR or CI PASS alone is not final Acceptance. Normal successful output:

STATUS: READY_TO_MERGE
Task: AAC-N
Next: Merge AAC-N

### E. Human Merge

Merge AAC-N authorizes only merging the unchanged accepted candidate. Immediately before merge, fresh-verify head/base, required exact-head checks, unresolved blockers and final Acceptance SHA. Refresh invalidated evidence before proceeding if anything moved. Observe the actual GitHub merge before reporting FINALIZED. Acceptance and READY_TO_MERGE never authorize merge, release, deployment or rollback.

## 11. Companion repositories

Advanced Accounts Premium, Blacklist Manager, Loyalty and other companions are read-only unless the admitted Issue explicitly authorizes coordinated writes. If correctness requires such a write, prepare the smallest explicit scope expansion for Human; keep the same AAC Issue for one coherent change and separate branches/PRs per writable repository.

## 12. Release boundary

Engineering commands never imply release authority. Version/changelog finalization, installable release packages, Git tags/GitHub Releases, WordPress.org SVN and production distribution require separate explicit Human authorization.

Follow [releasing.md](releasing.md) for Core's WordPress.org release: establish the actually published baseline, prepare the accepted merged payload, validate it, perform a read-only SVN dry-run, request authorization for that exact candidate/version/destination, then publish and verify. Core does not inherit Premium's commercial ZIP publisher or license/updater feed. Neither merge nor Acceptance launches publication.

## Historical adoption boundaries

The following AAC-3 requirements describe the historical adoption only. References to ChatGPT preserve its original approval requirements and do not impose a role on new tasks.

## AAC-3 first adoption

Admitted base: `7b46246b9822464a35db65c9f0c4b048df875a98`. AAC-3 is GitHub Issue #3 and is Controlled. That base has no canonical governance; its existing membership/role checks and the Issue's explicit independent review, ChatGPT Acceptance and Human-only merge boundaries govern this adoption. This proposed workflow becomes authority only after Acceptance and Human merge. It cannot waive its own checks.

Keep the adoption PR Draft for local checks and fresh exact-head independent Technical Review. Draft CI retains every pre-existing focused test. ChatGPT separately reviews the unchanged candidate, and after a blocker-free precheck may make it Ready to obtain exact-head AAC Required CI before final SHA-bound Acceptance. Human Merge remains separate. No version change, Git tag/Release, SVN write, Premium write or settings change is authorized by AAC-3.

## Adoption provenance

Adapted from AAP `AGENTS.md` and `docs/workflow.md` at `11397a7c1237e05ae8979633014125ce3a2e835b` (2026-10-19). The Core adaptations are task/repository identity, available contracts/tests and CI evidence, read-only Premium companionship, WordPress.org release and this first-adoption boundary. AAP-specific historical adoption exceptions do not apply to Core.

## AAC-5 transition authority

AAC-5 is admitted on base `e9b8f095174dcf68b2b23ee08dac601a383adb57`. Human requested that AAC adopt the AAP workflow and supplied global instructions assigning Codex Main end-to-end technical execution/review/Acceptance while retaining Human merge/release/deployment/rollback authority. This instruction is recorded in [Issue #5](https://github.com/yoohwz/wc-advanced-accounts/issues/5). It delegates admission and final technical Acceptance for this amendment to Main, rather than letting proposed text authorize itself. All admitted-base gates remain: Draft implementation/review/precheck, fresh isolated independent exact-head Technical Review, semantic/outcome precheck, unchanged Ready transition, correlated AAC Required CI to terminal PASS and fresh head/base/blocker verification before SHA-bound final Acceptance. No merge, release, companion write or settings change is authorized; historical approvals remain unchanged.

Current alignment source: AAP `AGENTS.md` and `docs/workflow.md` at `d9bb5bd45a5fba56e60bc90c684db1dfd4c663b8` (2026-11-06, PR #165). Adaptations preserve AAC identity, Core contracts/invariants, focused CI evidence limits and the WordPress.org release path; Premium runtime dispatch and commercial publication are not Core capabilities.
