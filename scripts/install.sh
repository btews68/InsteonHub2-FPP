#!/bin/bash
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"
FPP_SCRIPTS_DIR="/home/fpp/media/scripts"

# Fix permissions on all scripts
chmod +x "${PLUGIN_DIR}/scripts/fpp_event.sh"
chmod +x "${PLUGIN_DIR}/scripts/fpp_playlist_hook.sh"
chmod +x "${PLUGIN_DIR}/scripts/insteon_cli.php"

# Fix line endings on all scripts
for f in "${PLUGIN_DIR}/scripts/"*.sh "${PLUGIN_DIR}/scripts/"*.php; do
  sed -i 's/\r$//' "$f"
done

# Copy convenience scripts to FPP scripts folder so playlists can find them
if [[ -d "${FPP_SCRIPTS_DIR}" ]]; then
  for script in insteon_on.sh insteon_off.sh insteon_dim.sh; do
    if [[ -f "${PLUGIN_DIR}/${script}" ]]; then
      cp "${PLUGIN_DIR}/${script}" "${FPP_SCRIPTS_DIR}/${script}"
      sed -i 's/\r$//' "${FPP_SCRIPTS_DIR}/${script}"
      chmod +x "${FPP_SCRIPTS_DIR}/${script}"
      echo "Installed ${script} to ${FPP_SCRIPTS_DIR}"
    fi
  done
else
  echo "Warning: ${FPP_SCRIPTS_DIR} not found - skipping FPP scripts folder copy"
fi

# Create config.json from example if not already present
if [[ ! -f "${PLUGIN_DIR}/config.json" ]]; then
  cp "${PLUGIN_DIR}/config.example.json" "${PLUGIN_DIR}/config.json"
  echo "Created config.json from config.example.json"
fi

# Create device/scene maps from examples if not already present
for map in devices scenes playlist_actions; do
  if [[ ! -f "${PLUGIN_DIR}/${map}.json" && -f "${PLUGIN_DIR}/${map}.example.json" ]]; then
    cp "${PLUGIN_DIR}/${map}.example.json" "${PLUGIN_DIR}/${map}.json"
    echo "Created ${map}.json from example"
  fi
done

echo "InsteonHub2 plugin install complete"
