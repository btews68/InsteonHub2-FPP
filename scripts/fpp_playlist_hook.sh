#!/bin/bash
set -euo pipefail

# FPP Insteon Plugin - Playlist Event Hook
#
# Use this to trigger Insteon actions on playlist start/stop.
# The event.json passed by FPP contains playlist name and status.
#
# FPP calls this script with $1 = event.json path
#
# Example setup in FPP:
#   - In Plugin Settings, bind this script to playlist-start and playlist-stop
#   - Actions are defined in a playlist_actions.json map

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
EVENT_FILE="${1:-.}"
PLUGIN_DIR="${SCRIPT_DIR}/.."
PLAYLIST_MAP="${PLUGIN_DIR}/playlist_actions.json"
CLI="${SCRIPT_DIR}/insteon_cli.php"
CONFIG="${PLUGIN_DIR}/config.json"

if [[ ! -f "${EVENT_FILE}" ]]; then
  echo "Event file not found: ${EVENT_FILE}" >&2
  exit 1
fi

if [[ ! -f "${PLAYLIST_MAP}" ]]; then
  echo "Playlist map not found: ${PLAYLIST_MAP}" >&2
  exit 1
fi

PHP_BIN="$(command -v php)"
if [[ -z "${PHP_BIN}" ]]; then
  echo "php not found" >&2
  exit 1
fi

PLAYLIST_NAME=$(grep -o '"filename":"[^"]*' "${EVENT_FILE}" | head -1 | cut -d'"' -f4)
if [[ -z "${PLAYLIST_NAME}" ]]; then
  echo "Could not extract playlist name from event" >&2
  exit 0
fi

if [[ ! -f "${PLAYLIST_MAP}" ]]; then
  exit 0
fi

ACTIONS=$(grep -o "\"${PLAYLIST_NAME}\"[^}]*}" "${PLAYLIST_MAP}" 2>/dev/null | head -1 || echo "")
if [[ -z "${ACTIONS}" ]]; then
  exit 0
fi

echo "Playlist: ${PLAYLIST_NAME}, Actions: ${ACTIONS}"

"${PHP_BIN}" "${CLI}" --scene "${PLAYLIST_NAME}" --config "${CONFIG}" || true
