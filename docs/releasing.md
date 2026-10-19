# Advanced Accounts Core — WordPress.org release

This runbook is the canonical Free/Core release path for slug
`wc-advanced-accounts`. Release is separate from engineering Acceptance and Human
Merge. Every release needs explicit Human selection of version and destination,
preparation authority, and later publication authority for the concrete verified
payload. Nothing runs on merge. Do not use the Premium commercial ZIP publisher,
license/updater feed or a companion repository's credentials/workflows.

Core currently has no automated publisher or production GitHub Environment. The
smallest sufficient path is a local, reviewed preparation helper plus manual SVN
publication by the authorized committer. No new service, certification matrix,
release-state ledger or approval parser is needed. This runbook does not create
Git tags or GitHub Releases; those need their own explicit authority if desired.

The mechanics adapt YSP's `docs/releasing.md` at
`ccda6798fca09e0948b0b871490163585aeae50e` and the WOS/BM Free precedents:
accepted source, immutable preparation, dry-run first, credentials only at live
commit, atomic trunk/new-tag publication and read-only recovery. Their automated
publisher/control-plane prerequisites do not apply to this manual path.
See the official [SVN handbook](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/).

## 1. Establish the actual public baseline and release boundary

Read these afresh before choosing any future version:

- [Public plugin listing](https://wordpress.org/plugins/wc-advanced-accounts/)
- [Public SVN](https://plugins.svn.wordpress.org/wc-advanced-accounts/), including
  `trunk/readme.txt`, the stable tag and the existing `tags/` listing
- [Plugin information API](https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request%5Bslug%5D=wc-advanced-accounts)
- The API's versioned public download, and any relevant existing Git tags/releases

Use `curl --fail --show-error --location --max-time 60` and unauthenticated
`svn --non-interactive --no-auth-cache` reads. Record public version, download
URL/checksum and SVN revision in the release PR. Confirm listing/API version,
SVN Stable tag, tag header and versioned download identity agree. Source version,
local ZIPs and merged-but-unpublished changes are not proof of publication.
Network failures or disagreement mean baseline UNVERIFIED; stop version selection
until reconciled. Do not assume the version in this repository is latest public.

List `tags/` successfully and require the proposed numeric version to be absent.
Check existing Git tags/releases too; an existing/conflicting target stops the
release, never delete or retarget it. Compare versions numerically (e.g. PHP
`version_compare`), not lexically. Choose a version newer than the public baseline.

Human authorizes a governed AAC-N release-preparation Issue for that version and
WordPress.org destination. Update `Version:` in `wc-advanced-accounts.php`,
`Stable tag:` in `readme.txt`, and the newest non-empty changelog entries in both
`readme.txt` and `changelog.txt`. Preserve the existing dated-heading convention
and historical published notes. Describe net user-visible behavior since the
actual public baseline: final features, fixes for shipped defects, meaningful
security/compatibility changes and required owner action. Omit internal workflow,
CI, PR chronology and unreleased intermediate regressions. Only update Upgrade
Notice when action/compatibility warrants it. Do not advance Tested up to or
support versions beyond executed evidence.

Complete focused checks, applicable independent Technical Review, exact-head
`AAC Required CI`, SHA-bound ChatGPT Acceptance and separate Human Merge of the
final versioned candidate. No metadata edits after Acceptance. With a squash or
merge commit, compare the entire accepted head tree with the selected merged
source tree; unequal trees need an explained reviewed boundary and refreshed
applicable evidence. Do not silently treat ancestry alone as equivalent bytes.

Read-only AAC-3 discovery on 2026-10-19 observed API version `1.4.5`,
`trunk/readme.txt` Stable tag `1.4.5`, and a tags listing through `1.4.5`.
This historical observation is not a release receipt or a permanent baseline;
versioned-download identity must still be verified for the actual release.

## 2. Prepare the accepted merged source

Prerequisites: Git, Python 3, PHP/Node/curl for focused checks, SVN for publication,
and an isolated WordPress/WooCommerce installation with WP-CLI and Plugin Check
for release validation. Inspect available versions and record them. Do not use a
Human-owned site's database for automated/destructive validation. The manual path
needs no GitHub secrets/Environment. A WordPress.org committer must have an SVN
password available privately at the final interactive commit, never in chat,
Issues, scripts, command arguments, artifacts or logs.

Fetch origin, verify expected `yoohwz/wc-advanced-accounts` origin, current merged
PR/Acceptance/CI provenance and a clean detached worktree at exact `origin/main`.
Use full SHA, not a floating branch as release identity. If main moved beyond the
intended release, stop and choose/review the current source before preparation.

```sh
# Replace values from the reviewed release PR; these are not standing authority.
AAC_SHA=<40-character-merged-sha>
AAC_VERSION=<approved-numeric-version>
AAC_RC=<new-absolute-directory-outside-checkout>
python3 scripts/prepare-release.py --candidate "$AAC_SHA" --version "$AAC_VERSION" --output "$AAC_RC"
python3 scripts/prepare-release.py --candidate "$AAC_SHA" --version "$AAC_VERSION" --verify "$AAC_RC"
```

The helper reads tracked Git blobs, never executes candidate PHP, and admits only
root product files plus `inc/`, `css/`, `js/`, `font/`, `img/`, `languages/`.
It excludes development roots, hidden files, caches, archives, backups and logs;
rejects selected symlinks/submodules; checks matching version and top changelogs;
and creates two in-memory byte-identical deterministic ZIPs. Adding a new runtime
root requires updating this single staging definition in a reviewed task.

Output contains `payload/wc-advanced-accounts/`, an installable single-root ZIP and
`release-manifest.json` binding exact candidate/tree, version, ZIP SHA-256 and
all product file SHA-256 values. Preserve these bytes and record the ZIP/manifest
checksums in release evidence outside the preparation directory. Verification
reconstructs expected bytes from the exact merged Git source and compares the
complete payload/ZIP and manifest; it does not merely trust a self-hashed manifest.
The helper does not check public availability, grant Acceptance or publish.

Run Plugin Check against **this staged payload**, not the unfiltered repository.
In a disposable WordPress environment with WooCommerce and Plugin Check installed,
copy the staged directory to `wp-content/plugins/wc-advanced-accounts`, ensure the
checker CLI bootstrap is available, then use the installed checker interface:

```sh
wp plugin check wc-advanced-accounts --format=json --path="$AAC_DISPOSABLE_WP" \
  --require="$AAC_DISPOSABLE_WP/wp-content/plugins/plugin-check/cli.php" \
  > "$AAC_CHECK_REPORT"
```

Record WP/PHP/Woo/Plugin Check versions, command, exit status, raw findings and
classification. A zero CLI exit alone does not prove no blocking findings. Resolve
material errors/new regressions; any source change returns to the governed PR and
fresh checks/review/Acceptance/merge/preparation. Do not hide findings with excluded
checks. Use the official [readme validator](https://wordpress.org/plugins/developers/readme-validator/)
when public copy changes. Perform focused install/activation/upgrade and affected
customer-surface checks in the disposable site. Report unrun environments honestly.
Rerun `--verify` after validation to ensure the prepared payload is unchanged.

## 3. Read-only SVN preflight and local dry-run

This step has **no production credentials and no remote writes**. Use a new
scratch SVN working copy; no existing checkout or published tag is repurposed.
Set the exact prepared paths and record the successful remote listing/revision:

```sh
AAC_SVN_URL=https://plugins.svn.wordpress.org/wc-advanced-accounts
AAC_SVN=<new-absolute-scratch-directory>
svn --non-interactive --no-auth-cache ls "$AAC_SVN_URL/tags/"
AAC_SVN_REV=$(svn --non-interactive --no-auth-cache info --show-item revision "$AAC_SVN_URL")
svn --non-interactive --no-auth-cache checkout --depth immediates -r "$AAC_SVN_REV" "$AAC_SVN_URL" "$AAC_SVN"
svn --non-interactive --no-auth-cache update --set-depth infinity -r "$AAC_SVN_REV" "$AAC_SVN/trunk"
```

Require successful reads and target absence in that pinned revision. Do not infer
absence from a failed `svn ls`. `assets/` and old tags remain sparse and untouched.
Replace only local trunk contents with the verified prepared plugin directory:

```sh
# macOS/Linux rsync: preview first, then stage locally. Both commands are local.
rsync -ain --delete --exclude=.svn/ "$AAC_RC/payload/wc-advanced-accounts/" "$AAC_SVN/trunk/"
rsync -ai --delete --exclude=.svn/ "$AAC_RC/payload/wc-advanced-accounts/" "$AAC_SVN/trunk/"
svn add --force "$AAC_SVN/trunk"
# Schedule each path shown as ! by svn status for deletion using svn delete.
# Review those exact paths; do not parse whitespace-delimited file names.
svn status "$AAC_SVN"
svn copy "$AAC_SVN/trunk" "$AAC_SVN/tags/$AAC_VERSION"
svn status "$AAC_SVN"
svn diff --summarize "$AAC_SVN"
svn diff "$AAC_SVN/trunk"
```

Before approval: schedule every missing tracked trunk file for deletion, reject
conflicts/unversioned leftovers, compare **all** local trunk and target-tag file
paths/bytes with prepared payload (ignoring only `.svn`), and inspect the complete
SVN change set. It must contain only `trunk` and the newly added
`tags/<version>`, including required adds/deletes. No `assets/`, existing tag,
property/external changes or other paths may be included. Preserve a concise
summary of source SHA, version, checksums, public baseline, SVN revision and delta
for Human review. Nothing in this dry-run authorizes a commit.

## 4. Human-authorized live publication

Request explicit publication authority for that exact SHA/version/checksum and
WordPress.org destination only after preparation, validation and dry-run are
reviewable. Human owns the decision. A changed candidate/version/preparation or
remote delta requires fresh preflight and approval; do not reuse old approval.

Immediately before live commit:

1. Fetch origin and reverify unchanged selected merged source and provenance.
2. Run the helper's `--verify` again; compare checksums with the reviewed receipt.
3. Successfully list current remote tags; target must still be absent. Run
   `svn --non-interactive --no-auth-cache diff --summarize -r "$AAC_SVN_REV:HEAD" "$AAC_SVN_URL"`.
   Any change under this plugin stops publication for new preflight/approval.
   Unrelated plugins' global SVN revisions alone are not a product change.
4. Reinspect local status/diff and complete trunk/tag byte equality. Do not update
   or rebuild the reviewed payload as a workaround.
5. The authorized Human committer executes **one atomic commit**, naming only
   trunk and the new tag. Enter the SVN-specific password at the hidden terminal
   prompt. No password env var, command argument, auth cache or debug tracing:

```sh
svn commit --no-auth-cache --username yoohw \
  "$AAC_SVN/trunk" "$AAC_SVN/tags/$AAC_VERSION" \
  -m "Release $AAC_VERSION; source $AAC_SHA"
```

No release credential is available during checks or preflight. Preserve the
returned SVN revision. Do not run a second commit automatically on failure.
This manual Human step is the publication gate; no GitHub settings change is
required. Future automation would require a separately reviewed scope and a
Human-gated production Environment, not secrets added to this CI workflow.

## 5. Postflight and read-only recovery

Read the committed SVN revision/log, confirm expected committer, source SHA
message and changed paths limited to trunk/new tag. Export trunk and the new tag
at that revision into separate scratch directories; compare complete paths/bytes
with prepared payload. Confirm header, Stable tag and changelogs. Then verify
public listing/API version and download the API's **versioned public ZIP**.
Compare extracted product inventory and bytes with preparation; public ZIP
container metadata/checksum may differ, so byte identity means extracted files.
Validate safe archive paths before extraction. Record public ZIP checksum too.

If SVN is correct but the listing/ZIP has not propagated, report
`WPORG_PROPAGATION_PENDING`; wait and repeat **reads only**. If WordPress.org
requires release confirmation, Human completes its official private UI/email
flow. Never copy private confirmation links/tokens into chat or GitHub.

For a lost/timed-out commit response or failed postflight, report outcome unknown,
read fresh SVN logs/tag/trunk first, and reconcile the intended SHA/version/bytes.
An existing matching tag/revision means continue verification, never recommit.
An existing differing tag or unexpected payload means stop for Human resolution;
never overwrite tags, silently rollback trunk or retry blind. Rollback requires
its own Human decision and normally a new reviewed version. Successful release
means verified SVN plus public version/download, not merely commit exit code.

Report exact source SHA, version, SVN revision, preparation/public ZIP checksums,
validation limitations and public verification result. No Git tag/GitHub Release,
Premium feed write or site deployment is implied.
