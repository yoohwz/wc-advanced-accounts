# AAC Lean Hybrid CI v1

The workflow exists to ship correct Advanced Accounts Free/Core changes with the minimum process that materially reduces risk. GitHub owns task/candidate/review/CI/merge facts; repository documents own stable product/security contracts. Do not build a second control plane for facts GitHub already records.

Normal flow: Local Development → Frozen Candidate → Fresh Technical Review (Controlled) → Acceptance Review orchestration [internal precheck → Full exact-head AAC Required CI → final ChatGPT Acceptance] → Human Merge → Lightweight Main Guard.

## 1. Sources of truth

| Fact | Authority |
| --- | --- |
| Task outcome, scope, risk and acceptance boundary | GitHub Issue #N (AAC-N) |
| Current candidate and ancestry | Git branch / PR head and Git history |
| Technical findings | PR review/comments |
| Hosted checks | GitHub Actions/Checks |
| Product/security rules | Applicable repository contracts |
| Acceptance | SHA-bound ChatGPT record on the PR |
| Merge | GitHub PR merge record after explicit Human Merge |
| Release/publication | Separate Human-authorized release process |

Conversation memory and local notes are navigation hints only. On every Run, Continue, Review, Acceptance Review or Merge, recover current Git/GitHub state before acting.

For workflow-governance amendments, the workflow accepted on the admitted base governs the amendment. Proposed text cannot waive its own admission, review, Acceptance or merge requirements.

## 2. Start and recover

Fetch origin; inspect local changes and existing Issue/branch/PR state before creating anything. Use one Issue, one admitted branch and one PR to main. Keep the PR Draft during implementation, correction, Technical Review and the internal Acceptance precheck. Only a blocker-free precheck permits the unchanged candidate to become Ready for the final hosted gate; durable Acceptance follows that gate. Ordinary Draft pushes do not launch full hosted gate. If main moved, inspect the delta; conflicting or material overlap requires a boundary decision.

Never overwrite unrelated work, push directly to main, force-push, reset/clean destructively or delete unrelated branches merely to make workflow preflight pass.

No task document, allocator, state JSON, ledger, identity ref, digest chain, approval parser or workflow-mode database exists. Recover Issue, PR, head/base, CI, reviews and next owner from GitHub.

A Human-owned Local WordPress installation may be used for bounded manual/runtime validation. Automated or destructive integration tests must use disposable environments and must not repurpose an existing Human installation. The workflow is not bound to an absolute local path.

## 3. Task identity and operator commands

GitHub Issue #N is canonical task identity AAC-N.

Except for read-only investigation, every repository change intended for merge requires an admitted AAC-N Issue. Fast and Controlled are risk lanes within governed AAC work; neither is an ungoverned direct-write route.

- Create ... — ChatGPT creates the minimum useful Issue boundary and risk.
- Run AAC-N — Codex recovers Issue/branch/PR/head/base/current evidence and performs the next implementation-owned or exceptional recovery step, including read-only discovery for discovery-first work. It is not the normal post-Acceptance CI poller.
- Continue AAC-N — recover GitHub state and resume the same task. This is a convenience alias, not lifecycle state.
- Plan Review AAC-N — ChatGPT resolves a genuinely unresolved product, architecture, security, data, permission or compatibility boundary.
- Review AAC-N — manual/standalone fresh Technical Review fallback. It never grants merge authority.
- Acceptance Review AAC-N — ChatGPT performs an internal exact-candidate precheck, transitions the unchanged Draft PR to Ready when no blocker remains, observes the triggered full exact-head Required CI to a terminal result in the same execution, then records final SHA-bound Acceptance only after CI PASS and fresh verification.
- Merge AAC-N — Human authorizes merge of the unchanged accepted candidate after fresh verification.

Every terminal/handoff Human-facing response should end with:
STATUS: <navigation label>
Task: AAC-N
Next: <one exact short Human command, or None>

Useful labels are READY_FOR_DISCOVERY, READY_TO_RUN, IN_PROGRESS, PLAN_REVIEW_REQUIRED, TECHNICAL_REVIEW_REQUIRED, TECHNICAL_REVIEW_BLOCKED, ACCEPTANCE_REVIEW_REQUIRED, REQUIRED_CI_PENDING, READY_TO_MERGE, HUMAN_DECISION_REQUIRED and FINALIZED. They are navigation prose only, not machine state.

Report READY_TO_RUN only for implementation-ready work: the current candidate outcome and scope are bounded, and every known product, security, data, compatibility and migration decision required to complete it is settled. Classify each known unresolved item before implementation as either BLOCKING CURRENT CANDIDATE or DEFERRED / NON-BLOCKING. An item deferred from the current candidate must be explicitly excluded from its completion boundary and linked to a follow-up Issue when material. No other sentence in the Issue may require that deferred item to be decided before candidate completion. Newly discovered blockers may still require Plan Review.

Use READY_FOR_DISCOVERY with Next: Run AAC-N when the Issue intentionally needs Codex discovery before source implementation because evidence cannot yet settle a material product, architecture, security, data, migration or compatibility boundary. This is not required for an ordinary bounded Controlled fix. Run first recovers state and performs read-only discovery and technical planning. Do not begin source-changing implementation across a known unresolved boundary. After discovery, return PLAN_REVIEW_REQUIRED with a concrete handoff if a decision outside implementer authority remains; otherwise report READY_TO_RUN or continue implementation only if the Issue already authorizes the discovered design and no unresolved boundary remains.

## 4. Risk and execution

Fast is bounded, understood work without sensitive behavior or safety-control semantics. Typical examples include documentation, comments, presentation-only CSS, formatting, translation refresh with unchanged meaning and mechanical test/tooling maintenance.

Controlled is required for material changes to:
- account identity/ownership or phone/email canonicalization;
- authentication, re-authentication, auth cookies or sessions;
- OTP, verification, password reset or passwordless behavior;
- authorization, capabilities, public privileged AJAX/REST or abuse/rate limiting;
- persistent identity/security/lifecycle state or migrations;
- membership access, role entitlement, discounts or subscription lifecycle;
- privacy-sensitive logging or deletion behavior;
- SMS/CAPTCHA/provider trust and failure semantics;
- free/premium takeover or compatibility-sensitive public/shared contracts;
- architecture or coordinated cross-repository writes;
- release/package safety;
- workflow-governance semantics.

If Fast discovery reveals a Controlled trigger, stop before the sensitive change, keep the same Issue/PR, escalate risk and continue under Controlled rules.

Separate Plan Review is required only when discovery exposes a genuinely unresolved boundary. A fully bounded Controlled bug/fix may implement directly.

Human owns product priorities/goals and merge, release, deployment and rollback decisions. ChatGPT frames the desired outcome, scope, product requirements and evidenced risks, resolves genuinely unsettled architecture/security boundaries and owns Acceptance. Codex autonomously owns discovery, technical design, implementation choices, tests, correction and PR upkeep within that boundary. ChatGPT must not default to prescribing classes/methods, SQL/CAS/locking, state layout or test design in Issues, plans or Acceptance; any such constraint must identify the evidenced risk and explain why a simpler behavioral requirement is insufficient.

Choose the smallest complete solution using existing systems. Do not add speculative helpers, frameworks, services, abstractions, migration/proof machinery or side tasks, or pull optional changes into current scope. Reconsider a simpler approach when introduced complexity repeatedly requires more patches. For provider/release-dependent roadmaps, validate decisive feasibility conditions before investing in dependent foundations.

## 5. Compute policy

- Root/main orchestration: GPT-6.1 Sol / MEDIUM.
- Fast implementation: GPT-6.1 Sol / MEDIUM.
- Controlled deterministic implementation and correction after the boundary is settled: GPT-6.1 Sol / MEDIUM.
- Controlled discovery/architecture when a separate Plan Review is actually needed: one fresh dedicated GPT-6.1 Sol / HIGH discovery subagent under section 6.
- Independent Technical Review and every fresh re-review: GPT-6.1 Sol / HIGH.
- XHIGH is exceptional and requires a specific unresolved architecture/security reason.

Reduce irrelevant context before increasing compute. Do not serialize model/effort or execution history into task state.

## 6. Discovery and Plan Review

Discovery normally happens inside Run AAC-N. If the Issue already settles all material product/security/scope/architecture boundaries, proceed without a separate ceremony. A DEFERRED / NON-BLOCKING item must not trigger PLAN_REVIEW_REQUIRED or be silently implemented in the current candidate. Revisit it in its follow-up task, or stop with evidence if discovery proves it inseparable from the current candidate.

For Controlled work involving persistent state, public identifiers, privacy data or migrations, first-pass discovery inspects applicable new writes, existing and legacy records, partial and failure states, ambiguous provenance, migration/rollback/recovery, and public URL/permalink/consumer compatibility. This focused inventory does not itself require Plan Review.

Return PLAN_REVIEW_REQUIRED only when discovery exposes a decision Codex does not own, such as a new trust/ownership model, persisted migration policy, public contract change, provider abstraction, free/premium lifecycle redesign, architecture ownership change or newly required companion write.

Once Codex determines that a genuine separate Plan Review is required, it must invoke and successfully complete a fresh dedicated **GPT-6.1 Sol / HIGH** discovery subagent through `collaboration.spawn_agent` (or the canonical collaboration subagent equivalent) before returning PLAN_REVIEW_REQUIRED. Main-agent scratch reasoning cannot substitute for this discovery pass. Ordinary bounded Controlled tasks do not require the subagent; deferred/non-blocking items do not trigger it.

Give the subagent a fresh context containing only the canonical Issue and approved prior decisions, admitted base/current repository facts, relevant source/contracts, exact unresolved boundary, known evidence/constraints and applicable read-only companion evidence. It independently inspects relevant code/contracts through read-only discovery and returns a concise factual packet:

1. Current factual state, including admitted base/current repository facts and discovery evidence.
2. Smallest affected scope.
3. Concrete unresolved decision outside implementer authority.
4. Feasible options and material trade-offs, when more than one exists.
5. Technically supportable proposed/default option.
6. Source work blocked pending Plan Review.
7. Important evidence gaps and limitations.

The discovery subagent does not own the Human/ChatGPT decision, source implementation, Acceptance, merge or release. Codex remains responsible for verifying its result against current Git/GitHub/repository state, removing speculation or unsupported claims, keeping deferred/non-blocking items outside the current decision, and distilling the final PLAN_REVIEW_REQUIRED handoff to ChatGPT. The final handoff contains the factual packet above and explicitly states that mandatory GPT-6.1 Sol / HIGH subagent discovery completed. Record only factual discovery results and actionable decisions in Issue/PR/workflow evidence; never copy or persist raw hidden reasoning/chain-of-thought. A full implementation design is not required when the decision determines it. ChatGPT reviews and resolves this concrete handoff, then returns execution to Run AAC-N.

If the mandatory discovery subagent cannot be created or cannot complete successfully, preserve task/PR state and stop with HUMAN_DECISION_REQUIRED stating the exact delegation failure. Do not silently fall back to main-agent-only discovery or begin source-changing implementation across the unresolved boundary. A retry may use a new fresh GPT-6.1 Sol / HIGH subagent.

Use one discovery subagent per Plan Review attempt by default. A second is justified only after a materially new unresolved boundary appears or the prior attempt failed to produce usable evidence, never merely for consensus. Do not create reviewer swarms, voting panels, nested agents, persistent agent state or a generic planning framework. This discovery requirement is separate from the existing fresh independent Technical Review and does not change implementation compute, Acceptance/CI orchestration, exact-SHA evidence, Human merge authority or release separation.

## 7. Candidate, CI and evidence

Codex owns technical design within the approved boundary. Use the smallest complete change, avoid opportunistic refactors/renames, add focused deterministic regression coverage where credible, validate the exact affected WordPress/WooCommerce behavior, restore task-created state and push the candidate.

Before freezing, run applicable inexpensive local checks: minimum-supported PHP syntax when available, JavaScript syntax, exact-diff whitespace, affected deterministic regressions and bounded manual Local runtime observation when useful. Record unavailable checks honestly. Core CI runs the focused checks described below; it does not provide a full disposable WP/Woo/MySQL integration harness.

For product changes, demonstrate the actual outcome on the relevant user surface; parser/state tests and green CI alone do not establish that outcome. For customer hotfixes, distinguish merged source from the delivered/released artifact, retaining separate release authorization.

Keep one concise PR evidence record containing Issue/scope/risk, changed behavior/files, exact head/base, commands/results, runtime limitations and next owner. Human-facing analysis, proposals, plans, reviews and handoffs use concise, clear Vietnamese: lead with the problem/result, explain the practical impact and end with one concrete next action or None. Preserve code, identifiers, paths and repository document language; omit implementation detail that does not help the Human decide.

AAC Required CI is one authoritative final candidate job, launched only by `ready_for_review` after a blocker-free internal Acceptance precheck. `.github/workflows/membership.yml` checks out the exact head and uses GitHub API evidence before and after checks to verify the open PR, expected repository, head and current base. It runs PHP 7.4 syntax for tracked PHP, JavaScript syntax for tracked JavaScript, `tests/membership-shortcode.php`, the checksum-pinned native WordPress 6.3 renderer, `tests/role-ownership.php wc-advanced-accounts`, release-tooling regressions and exact-diff whitespace. No database, WooCommerce runtime or Premium scenario matrix is installed. These focused checks are not full WordPress/Woo/MySQL compatibility proof.

Draft `opened`/`reopened`/`synchronize` events run `AAC Draft Checks` using the same inexpensive checks; they are not final Required CI evidence. Ready `synchronize` pushes do not launch a new final gate: return to Draft for corrections, refresh review and Acceptance precheck, then use a new `ready_for_review` event. `push main` runs only AAC Main Integrity: exact checkout, PHP/JavaScript syntax and bounded diff whitespace. It does not start tests or database/runtime services. No protection/secrets/settings are changed by this workflow.

Grow repository-owned tests from real regressions and product contracts rather than building a speculative generic harness.

If repository plan capabilities cannot enforce branch protection/rulesets, do not emulate them with a custom workflow database. READY_TO_MERGE and Merge must fresh-verify successful AAC Required CI on the exact head and final SHA-bound Acceptance PASS. Only the internal semantic precheck precedes the normal final gate.

## 8. Independent Technical Review

Every frozen Controlled candidate requires one fresh independent Technical Reviewer. The PR may remain Draft; full hosted gate is not required by default before Technical Review.

The reviewer must:
- start in a fresh context and isolated source-read-only checkout/environment;
- receive the Issue/approved boundary, admitted base, exact candidate SHA/diff, relevant contracts and validation evidence;
- not receive or rely on implementer scratch reasoning or suggested verdict;
- independently inspect correctness, lifecycle/failure behavior, security/privacy, compatibility and test quality;
- independently run relevant focused checks when practical;
- bind findings or PASS to the exact reviewed SHA in GitHub evidence.

For authentication/authorization, inspect alternate entry points and adjacent allow/deny paths. For OTP/reset, inspect expiry, attempts, resend, destination/context binding, replay, session rotation and auth-cookie/reset continuation. For persisted lifecycle changes, inspect create/update/failure/retry/cleanup. For membership discounts/access, inspect entitlement and financial outcomes. For provider/contracts, inspect absence, transport/rejection/malformed response, credentials and producer/consumer compatibility.

A reviewer may return `EARLY_RUNTIME_REQUIRED` only when source, contracts, deterministic/local evidence and independent probes cannot support a credible Technical verdict without additional real WordPress/WooCommerce/database runtime evidence. State the exact evidence gap. Core has no full runtime dispatch entry point. Codex must obtain the specific missing evidence in a disposable environment or report the precise limitation; Draft focused checks cannot stand in for unrun runtime. This exception does not grant Acceptance, Ready or merge authority. Do not parse review/Acceptance prose in automation or create a new Human command.

Severity:
- P0 — exploitable auth/authorization bypass, credential exposure, account takeover/data loss or immediate release stop.
- P1 — material correctness/security/identity/persistence/access/financial/compatibility defect.
- P2 — material failure-handling/test/observability gap that blocks safe Acceptance.
- P3 — non-blocking improvement.

Blocking findings must demonstrate a material defect, approved-scope/Acceptance violation or safety, data or compatibility risk. A P2 finding can block when it establishes such a violation; its label alone does not turn optional cleanup or a technical preference into a blocker. Record optional improvements as non-blocking without requiring follow-up tasks unless Human prioritizes them.

If review finds P0/P1/P2 blockers, keep corrections in the same Issue/PR, return to/stay in Draft before correction. Route findings back to the same implementer, rerun relevant checks, push a new exact candidate and use a new fresh reviewer.

Stop review/correction when the approved outcome, applicable tests and mandatory safety requirements are satisfied. Continue correction, validation and fresh re-review without a numeric round cap only while blocking in-scope findings remain and each round makes material progress. Stop at HUMAN_DECISION_REQUIRED for no material progress, repeated/stagnant blocker, oscillation, unsafe continuation or scope/architecture expansion.

If fresh review delegation is unavailable, preserve the candidate and stop at TECHNICAL_REVIEW_REQUIRED with Next: Review AAC-N.

## 9. Exact-candidate invalidation

Evidence is candidate-bound.

Any movement of the candidate SHA invalidates the applicable Technical Review and Acceptance, including documentation-only, test-only, metadata-only or UI-only movement. There is no delta-carry-forward exception.

Historical or inferred evidence is not current-head PASS. UNAVAILABLE is not PASS.

## 10. Acceptance Review and merge

Fast work enters Acceptance orchestration after relevant focused local checks. Controlled work additionally requires fresh exact-candidate Technical Review PASS. Full hosted gate is not required by default before the internal precheck; it is required before final Acceptance PASS.

One Human `Acceptance Review AAC-N` command owns the following phases through terminal CI and final Acceptance. Do not hand off a normally queued/running gate to `Run AAC-N`.

### A. Internal Acceptance precheck

Fresh-read the current Issue/approved Plan Review boundary, admitted and current base, exact PR head and total diff, available local/focused validation and limitations, required fresh SHA-bound Technical Review PASS, unresolved review threads and newer blocker evidence. Verify scope, product, security, architecture and compatibility semantics. Assess product-outcome evidence and remaining risks without repeating the full Technical Review unless new findings justify it.

If a blocker or evidence gap requires source/test/evidence correction, record `CHANGES_REQUIRED` and the appropriate blocking status without launching the normal final hosted gate. Keep/return the PR Draft for correction. The precheck is not durable Acceptance PASS or merge authority; do not publish it as such.

### B. Launch and correlate the final gate

After a blocker-free precheck, fresh-verify the PR is open and Draft, targets main in the expected repository, and head/base remain unchanged. Transition that unchanged PR to Ready for review. The GitHub-native `ready_for_review` event launches the single AAC Required CI job. No approval parser or task-state database authorizes the transition.

Correlate the observed AAC CI run with this PR's `ready_for_review` event, expected candidate SHA and base, using GitHub-native PR/event/check/run metadata and the job's exact-checkout/current-PR-base verification. Capture the run URL/ID and attempt in PR evidence. Do not select a run only by workflow name, latest timestamp or green status. A pull-request event can identify a synthetic merge SHA in run metadata: verify its PR/head association and the job's actual candidate checkout rather than treating that merge SHA as the candidate. Reject stale, wrong-PR, wrong-SHA or wrong-base evidence; a Draft-check run is not this normal final-gate event.

### C. Observe CI to terminal result

Continue observing that correlated run in the same Acceptance execution while queued/running. Use bounded waits and progress updates, then inspect the terminal conclusion of AAC Required CI; queued, cancelled, skipped, timed-out, missing or unavailable evidence is not PASS.

For a credible infrastructure/transient failure without head/base movement, safely retry only the necessary job/run and observe the new attempt to terminal result where practical. Same-SHA infrastructure retries preserve Technical Review evidence; they do not create Acceptance PASS. Record the failure reason and correlated retry attempt.

If CI exposes a source/test defect, record no final Acceptance PASS, return the PR to Draft before correction and hand back to the normal local/fresh-review loop. Candidate movement invalidates Technical Review and the precheck; a later Human `Acceptance Review AAC-N` starts again on the new reviewed SHA.

If tooling/platform interruption genuinely prevents obtaining a terminal result in this execution, report the exact external limitation and known PR/head/base/run/attempt for recovery, with no PASS claim. `REQUIRED_CI_PENDING / Next: Run AAC-N` is reserved for that exceptional interrupted recovery, never normal Acceptance success or ordinary CI queue/running time.

### D. Final Acceptance

Only after the correlated full exact-head AAC Required CI PASS, fresh-verify the PR is still open, head/base are unchanged, required Technical Review PASS remains bound to the same SHA, and no new blocking review thread/evidence appeared during CI. Head movement invalidates the precheck and Technical Review. Base movement stops final Acceptance: inspect the delta and re-establish the boundary/precheck and CI evidence against the current base before proceeding; material/conflicting overlap requires a boundary decision. New blockers prevent final Acceptance and return to the appropriate correction/decision owner. Return a moved Ready candidate to Draft before source/test correction; ordinary `synchronize` does not launch a new full gate.

Then record durable SHA-bound Acceptance PASS on the PR, referencing the verified base, Technical Review (when required), successful CI run/attempt and validation limitations. Normal successful output is:

STATUS: READY_TO_MERGE
Task: AAC-N
Next: Merge AAC-N

Acceptance never authorizes merge or release. A Ready PR or CI PASS alone is not Acceptance. Any later candidate movement invalidates final Acceptance and Technical Review, including documentation/test/metadata/UI-only changes; repeat local validation, fresh review and the full Acceptance orchestration. Safe infrastructure retries on the unchanged SHA do not invalidate existing applicable evidence.

Merge AAC-N authorizes only merge of the unchanged accepted candidate. Immediately before merge, reverify head/base, exact-head required checks, unresolved blockers and exact Acceptance SHA. If the candidate moved, refresh all invalidated evidence first.

Observe the actual GitHub merge before reporting FINALIZED.

## 11. Companion repositories

Advanced Accounts Premium, Blacklist Manager, Loyalty and other companion repositories are read-only by default. Inspect exact current source when compatibility requires it, but filesystem or connector access does not grant write authority.

If correctness demonstrably requires a companion write, stop for the smallest explicit scope expansion. Reuse the same AAC Issue when the mission remains one coherent change and use separate branches/PRs per writable repository.

## 12. Release boundary

Engineering commands never imply release authority. Version/changelog finalization, installable release packages, Git tags/GitHub Releases, WordPress.org SVN and production distribution require separate explicit Human authorization.

Follow [releasing.md](releasing.md) for Core's WordPress.org release: establish the actually published baseline, prepare the accepted merged payload, validate it, perform a read-only SVN dry-run, request authorization for that exact candidate/version/destination, then publish and verify. Core does not inherit Premium's commercial ZIP publisher or license/updater feed. Neither merge nor Acceptance launches publication.

## AAC-3 first adoption

Admitted base: `7b46246b9822464a35db65c9f0c4b048df875a98`. AAC-3 is GitHub Issue #3 and is Controlled. That base has no canonical governance; its existing membership/role checks and the Issue's explicit independent review, ChatGPT Acceptance and Human-only merge boundaries govern this adoption. This proposed workflow becomes authority only after Acceptance and Human merge. It cannot waive its own checks.

Keep the adoption PR Draft for local checks and fresh exact-head independent Technical Review. Draft CI retains every pre-existing focused test. ChatGPT separately reviews the unchanged candidate, and after a blocker-free precheck may make it Ready to obtain exact-head AAC Required CI before final SHA-bound Acceptance. Human Merge remains separate. No version change, Git tag/Release, SVN write, Premium write or settings change is authorized by AAC-3.

## Adoption provenance

Adapted from AAP `AGENTS.md` and `docs/workflow.md` at `11397a7c1237e05ae8979633014125ce3a2e835b` (2026-10-19). The Core adaptations are task/repository identity, available contracts/tests and CI evidence, read-only Premium companionship, WordPress.org release and this first-adoption boundary. AAP-specific historical adoption exceptions do not apply to Core.
