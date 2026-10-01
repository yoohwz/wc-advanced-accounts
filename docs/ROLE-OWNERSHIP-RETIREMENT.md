# Physical role ownership and safe retirement (R5)

This contract covers Advanced Accounts Premium, Advanced Accounts Core and WooCommerce Loyalty Premium. It does not cover the separate `loyalty-for-woocommerce` edition or arbitrary third-party role deleters.

## Creator provenance and usage

Each component includes the identical guarded `YOSWC_Role_Ownership` v1 implementation locally. No optional companion bootstrap or licensing decision establishes ownership. The per-slug `yoswc_role_owner_{slug}` option binds schema version, exact owner, slug, `plugin-created` origin, management state, creation timestamp and random generation. The physical role carries that generation; the creator's existing registry contains `r5_generation`. Legacy registry/capability markers are retained as compatibility hints, never promoted to creator proof. Automatic selection-based adoption is disabled; old public migration entry points are non-mutating.

Creation writes a pending record before native role creation, verifies the physical role and registry, then verifies the active record. Any failed stage reports failure and leaves a non-deletable pending/unknown recovery state. Retry never adopts an existing role. A recreated slug without the matching physical generation cannot inherit old delete authority. Role names and selection do not prove creation.

Membership/Loyalty selectors list editable custom roles with existing usage hints; additional manual roles require native `promote_users` authority and protected core roles are excluded from new choices. They preserve existing selected roles. Manual roles, roles from another creator and retired roles may be used without modifying creator provenance. Retirement changes only management state and preserves all users, claims, rules, immutable intent and orders/subscriptions. It never automatically deselects usage. Historical roles without strong provenance remain unknown even after retirement.

## Exceptional physical deletion

Hard deletion is a separate capability/nonce-protected POST, initiated by the exact creator for a retired role. All three installed helpers must be readable and byte-identical. Missing, old, renamed-directory or incompatible components deny deletion; installed but unloaded components are inspected without invoking their business engines. Protected/core roles and ambiguous foreign/shared markers are denied.

Fresh persisted Membership selection/settings contain all supported sale/access/discount mappings; Loyalty selection/level/discount rules are also inspected regardless of activation/licensing. Physical user assignments, raw shared role claims, saved Loyalty levels, Membership reservations, canonical Membership state and immutable Woo intent/lineage are non-mutating dependencies. Raw malformed/unknown state is never sanitized to a negative result. Active/reversible sources and ungranted intent block; validated non-restorable canonical history alone may permit deletion. A finite end does not expire an active subscription. WCS intent requires exact parent/customer/item origin coverage and terminal corresponding child claims; absent APIs or incomplete lineage deny.

Both usermeta locators and Woo CPT/HPOS/item metadata are inspected, so an unindexed partial intent cannot disappear from the proof. Source rows are checked through Woo CRUD, immutable identity/digest validation and fresh canonical user metadata. Preparing, orphaned or mismatched storage denies deletion.

The operation uses a shared compare-and-delete management lease and one SERIALIZABLE InnoDB transaction on a dedicated native WordPress database connection. That connection refuses automatic reconnect/retry; custom database drop-ins deny exceptional deletion. Losing a connection cannot retry a destructive statement outside its transaction. Relevant options and bounded usermeta/Woo metadata keyset ranges stay locked through physical deletion, registry/provenance cleanup and readback. Unsupported/missing storage tables, query errors, incomplete scans and write/commit failures deny. Failed deletion/cleanup rolls back physical role and provenance together and refreshes WordPress caches. There is no stored scan PASS, custom table, generic queue or bulk role stripping.

Each scan uses at most 20 windows of 100 rows. Usermeta coverage is at most 2,000 rows; each Woo metadata store covers at most 2,000 relevant rows. A full final window is incomplete proof and denies even if the scanned prefix is unused. Larger sites may keep a role retired; repeated retries do not turn incomplete coverage into permission. Hard deletion does not change the plugin's general PHP/WordPress/Woo support; installations without all required InnoDB storage remain usable with retirement only.

## Boundaries and recovery

Ordinary Loyalty claim grant/backfill/revoke remains unchanged. Last-source removal of a pre-existing manual user role is deferred to `yoohwz/wc-loyalty#40`; R5 makes no claim to repair it. Existing registered cross-source preservation and AAP manual-role preservation remain regression requirements.

Pending/corrupt ownership is displayed as unavailable for hard deletion. Resolve underlying storage failure without adopting the physical role from legacy hints. Failed hard delete preserves retired ownership for retry. The three coordinated candidates must be accepted together; each updated manager independently removes its unsafe ordinary direct-delete path and denies exceptional deletion while companions are old/absent. Merge order is separately authorized after exact-head review/CI/Acceptance. No release/version/feed publication is part of R5.
