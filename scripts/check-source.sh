#!/usr/bin/env bash
set -euo pipefail
while IFS= read -r -d '' path; do
  case "$path" in
    *.php) php -l "$path" >/dev/null ;;
    *.js) node --check "$path" ;;
  esac
done < <(git ls-files -z)
git diff --check "$AAC_BASE" HEAD
if [[ "${1:-}" == focused ]]; then
  php tests/membership-shortcode.php
  bash tests/run-membership-renderer.sh
  php tests/role-ownership.php wc-advanced-accounts
  python3 -m unittest discover -s tests -p 'test_*.py'
fi
