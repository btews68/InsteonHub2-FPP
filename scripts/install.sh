#!/bin/bash
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"

chmod +x "${PLUGIN_DIR}/scripts/fpp_event.sh"
chmod +x "${PLUGIN_DIR}/scripts/insteon_cli.php"

if [[ ! -f "${PLUGIN_DIR}/config.json" ]]; then
  cp "${PLUGIN_DIR}/config.example.json" "${PLUGIN_DIR}/config.json"
  echo "Created config.json from config.example.json"
fi

echo "InsteonHub2 plugin install complete"
