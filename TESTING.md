# Focused validation

## AAP-95 role ownership and retirement

`php tests/role-ownership.php wc-advanced-accounts` exercises the production local ownership protocol and that component's real admin adapters using PHP 7.4-compatible WordPress/Woo/WCS storage stand-ins. It checks all three creator identities, conservative legacy/manual state, independent nonce/capability guards, persisted configuration/users/raw claims/admissions/canonical sources/immutable intent, exact WCS origin coverage, absent/old companion denial, bounded/incomplete scans, query failure and creation/delete/cleanup/commit failure recovery. It does not certify native WCS or commercial activation. The normal claim writer is unchanged; `wc-loyalty#40` remains deferred.

See `docs/ROLE-OWNERSHIP-RETIREMENT.md` for the installed-helper byte compatibility gate, required InnoDB stores and the strict 2,000-row scan limits. Incomplete coverage always denies deletion.

## Core workflow checks

Follow [docs/workflow.md](docs/workflow.md) for Main-owned review, precheck, CI,
final technical Acceptance and separate Human Merge. This file defines validation
and its limits; checks do not grant merge or publication authority.

Run `AAC_BASE=<base-sha> bash scripts/check-source.sh focused` with PHP, Node.js,
Python 3 and curl available. CI uses PHP 7.4 and Node.js 22. This checks all tracked
PHP/JavaScript syntax, exact-diff whitespace and every existing focused suite:

- `php tests/membership-shortcode.php` — shortcode behavior using stand-ins;
- `bash tests/run-membership-renderer.sh` — native WordPress 6.3 rendering files,
  downloaded and SHA-256 checked; no database or WooCommerce runtime;
- `php tests/role-ownership.php wc-advanced-accounts` — ownership/failure stand-ins;
- `python3 -m unittest discover -s tests -p 'test_*.py'` — release payload,
  version/changelog rejection, tampering and stale PR/head/base regressions.

`AAC Draft Checks` provides inexpensive draft feedback. Only a correlated
`ready_for_review` run of `AAC Required CI` can supply final CI evidence.
`AAC Main Integrity` runs syntax and diff checks only. No check here certifies
full WordPress/WooCommerce/MySQL runtime, WCS, commercial activation, Plugin
Check or live SVN publication. Release validation is separate: see
[docs/releasing.md](docs/releasing.md).

## AAC-7 Free account features

The normal focused command also runs `tests/profile-captcha.php`,
`tests/account-administration.php`, `tests/custom-links.php` and
`tests/login-otp.php`. They exercise production handlers with controlled
WordPress/WooCommerce storage, HTTP, mail and session stand-ins: provider rejection,
malformed/transport responses and replay; profile ownership/privacy cleanup;
per-user admin authority, email-only batch dry-run/failure and privacy confirmation;
link CRUD/native routing separation; login OTP cooldown/destination limits,
account restrictions, expiry/attempt cleanup and replay. They do not certify live
CAPTCHA credentials, browser challenges, actual mail delivery or native database
and filesystem behavior.

For product outcome evidence use a disposable WordPress/WooCommerce installation,
never automated tests against a Human-owned database. Observe registration name
fields, My Account avatar upload/replace/remove and display format, CAPTCHA login
and registration errors, Users/profile and Tools actions, WordPress privacy request
pending/confirmed states, menu links/order/visibility/new-tab behavior and shortcode
message/default/hide behavior. Include password, email OTP, reset continuation,
phone aliases and Free/Premium ownership where affected. Intercept mail/external
HTTP in disposable probes; identify simulated provider results separately from
unrun live verification. Keep runtime observations and limitations on the PR,
including versions and exact candidate binding. Required CI remains focused.

### AAC-7 settings UI refinement

`node tests/account-settings-ui.js` checks inactive profile values, provider-field
visibility without disabling/clearing saved keys, and add-link dialog cancel and
validation behavior with a small DOM fixture. Native WooCommerce `save_fields`
probes additionally verify that disabling Avatar retains the stored name format
and switching/turning off CAPTCHA retains other provider keys and policy flags.
Observe desktop/mobile settings and keyboard dialog open/cancel/Escape on the
local preview without saving policy changes. Exercise actual writes, batch
continuation/results and links only in the disposable runtime. UI presentation
must keep the existing POST names, nonce/capability guards and runtime policy.
