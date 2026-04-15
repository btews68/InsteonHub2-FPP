#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PHP_BIN="$(command -v php)"

if [[ -z "${PHP_BIN}" ]]; then
  echo "php not found" >&2
  exit 1
fi

"${PHP_BIN}" "${SCRIPT_DIR}/insteon_cli.php" "$@"
