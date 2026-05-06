#!/bin/bash
set -euo pipefail

# insteon_off.sh - Turn off all Insteon devices
#
# Called from FPP playlist with NO arguments.
# FPP passes args as a single string which breaks argument parsing,
# so each action gets its own script with device IDs hardcoded below.
#
# Edit the --device values below to match your Insteon device IDs.
# Find your device IDs in the Insteon app or in devices.json.
# Use the 6-character hex ID (e.g. 42729E) or dotted format (AA.BB.CC).

SCRIPT="/home/fpp/media/plugins/InsteonHub2/scripts/fpp_event.sh"

"${SCRIPT}" --device AABBCC --action off
# Add more devices below, one line each:
# "${SCRIPT}" --device DDEEFF --action off
