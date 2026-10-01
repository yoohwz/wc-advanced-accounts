# Focused validation

## AAP-95 role ownership and retirement

`php tests/role-ownership.php wc-advanced-accounts` exercises the production local ownership protocol and that component's real admin adapters using PHP 7.4-compatible WordPress/Woo/WCS storage stand-ins. It checks all three creator identities, conservative legacy/manual state, independent nonce/capability guards, persisted configuration/users/raw claims/admissions/canonical sources/immutable intent, exact WCS origin coverage, absent/old companion denial, bounded/incomplete scans, query failure and creation/delete/cleanup/commit failure recovery. It does not certify native WCS or commercial activation. The normal claim writer is unchanged; `wc-loyalty#40` remains deferred.

See `docs/ROLE-OWNERSHIP-RETIREMENT.md` for the installed-helper byte compatibility gate, required InnoDB stores and the strict 2,000-row scan limits. Incomplete coverage always denies deletion.
