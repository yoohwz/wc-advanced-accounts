# Focused validation

## AAP-95 role ownership and retirement

`php tests/role-ownership.php wc-advanced-accounts` exercises the production local ownership protocol and that component's real admin adapters using PHP 7.4-compatible WordPress/Woo/WCS storage stand-ins. It checks all three creator identities, conservative legacy/manual state, independent nonce/capability guards, persisted configuration/users/raw claims/admissions/canonical sources/immutable intent, exact WCS origin coverage, absent/old companion denial, bounded/incomplete scans, query failure and creation/delete/cleanup/commit failure recovery. It does not certify native WCS or commercial activation. The normal claim writer is unchanged; `wc-loyalty#40` remains deferred.

See `docs/ROLE-OWNERSHIP-RETIREMENT.md` for the installed-helper byte compatibility gate, required InnoDB stores and the strict 2,000-row scan limits. Incomplete coverage always denies deletion.

## Core workflow checks

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
