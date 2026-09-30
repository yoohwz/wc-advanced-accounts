#!/usr/bin/env bash
set -euo pipefail
repo="$(cd "$(dirname "$0")/.." && pwd)"
fixture="$(mktemp -d "${TMPDIR:-/tmp}/aap-membership-native.XXXXXX")"
trap 'rm -rf "$fixture"' EXIT
curl --fail --silent --show-error --location https://wordpress.org/wordpress-6.3.tar.gz -o "$fixture/wordpress.tar.gz"
php -r 'exit(hash_file("sha256", $argv[1]) === "44654fa2913ee27cdea2a9d79a09f1e7fc981a18b654f13abc4ac74e455e7aa2" ? 0 : 1);' "$fixture/wordpress.tar.gz"
tar -xzf "$fixture/wordpress.tar.gz" -C "$fixture"
AAP_MEMBERSHIP_WP_ROOT="$fixture/wordpress" php "$repo/tests/membership-renderer.php"
