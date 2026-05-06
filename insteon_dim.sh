#!/bin/bash
set -euo pipefail

# insteon_dim.sh - Dim all Insteon devices to a set level
#
# Called from FPP playlist with NO arguments.
# FPP passes args as a single string which breaks argument parsing,
# so each action gets its own script with device IDs hardcoded below.
#
# Edit the --device and --level values below to match your setup.
# --level accepts 0-100 (percentage).

SCRIPT="/home/fpp/media/plugins/InsteonHub2/scripts/fpp_event.sh"

"${SCRIPT}" --device AABBCC --action dim --level 50
# Add more devices below, one line each:
# "${SCRIPT}" --device DDEEFF --action dim --level 50
