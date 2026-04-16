# Copyright 

Copyright (c) 2026 William Tews (btews68)
This project is licensed under the MIT License. See the LICENSE file for details.

# FPP Insteon Hub 2 Plugin (2245-222)

This plugin scaffold lets Falcon Player trigger Insteon switch actions, scenes, and groups over the local Hub 2 API.

## Capabilities

- Turn a switch on / off
- Set dim level (0-100)
- Trigger scenes and groups by name
- Device friendly names (alias map)
- Playlist event hooks
- Exponential backoff with jitter on retries
- Response validation for better reliability
- Verbose debug output

## Important notes

- Hub 2 local API behavior can vary by firmware.
- Keep Hub IP static in your router.
- Keep FPP and Hub on same LAN.
- Default timeouts: 5 seconds per command, 2 retries with backoff.

## Files

- `settings.php` - plugin settings page (stores config in `config.json`)
- `devices.json` - friendly name to Insteon ID map (copy from `devices.example.json`)
- `scenes.json` - preset scene/group configs (copy from `scenes.example.json`)
- `playlist_actions.json` - trigger actions on playlist start (copy from `playlist_actions.example.json`)
- `scripts/insteon_lib.php` - command building, retry, and response validation
- `scripts/insteon_cli.php` - CLI entrypoint for all device/scene actions
- `scripts/fpp_event.sh` - shell wrapper for FPP event hooks
- `scripts/fpp_playlist_hook.sh` - playlist start/stop event handler

## Quick start on FPP

1. Copy this folder to:
   `/home/fpp/media/plugins/InsteonHub2`

2. In FPP shell:
   ```bash
   chmod +x /home/fpp/media/plugins/InsteonHub2/scripts/*.sh
   cd /home/fpp/media/plugins/InsteonHub2
   cp devices.example.json devices.json
   cp scenes.example.json scenes.json
   cp playlist_actions.example.json playlist_actions.json
   ```

3. Configure hub settings in plugin settings page or edit `config.json`:
   - Hub IP
   - Username
   - Password
   - Timeout (seconds)
   - Retries

4. Edit the three map files with your devices and scenes.

5. Test a command:
   ```bash
   /home/fpp/media/plugins/InsteonHub2/scripts/fpp_event.sh --device living_room_light --action on
   ```

## Install via JSON URL in FPP UI

FPP supports entering a pluginInfo.json URL directly in the Plugins page search box.

1. Put this plugin in a Git repository (GitHub works well).
2. Place a valid `pluginInfo.json` at repo root.
3. In FPP: Content Setup -> Plugin Manager.
4. Paste pluginInfo.json URL into the input and click Get Plugin Info.

Template files included in this project:

- `pluginInfo.url-template.json` - fill in your repo URLs, then save as `pluginInfo.json`
- `pluginList.example.json` - optional full plugin list file if you want a custom plugin catalog URL

Example direct URL format:

```text
https://raw.githubusercontent.com/YOUR_GITHUB_USER/InsteonHub2-FPP/main/pluginInfo.json
```

Important:

- FPP installs from `srcURL` in pluginInfo.json (git clone), not from zip URL.
- The repository URL in `srcURL` must be reachable by the FPP box.

## If settings page does not appear in menu

FPP discovers plugin pages via menu include files.
This plugin includes both `status_menu.inc` and `content_menu.inc` to add a UI link.

If the plugin is already installed from an older commit, upgrade it in FPP Plugin Manager, then refresh browser.

Direct access fallback URL:

```text
http://<FPP-IP>/plugin.php?plugin=InsteonHub2&page=settings.php
```

## Command examples

### By device friendly name

```bash
/home/fpp/media/plugins/InsteonHub2/scripts/fpp_event.sh --device living_room_light --action on
/home/fpp/media/plugins/InsteonHub2/scripts/fpp_event.sh --device living_room_light --action off
/home/fpp/media/plugins/InsteonHub2/scripts/fpp_event.sh --device living_room_light --action dim --level 35
```

### By raw Insteon hex

```bash
/home/fpp/media/plugins/InsteonHub2/scripts/fpp_event.sh --device AA.BB.CC --action on
```

### By scene/group name

```bash
/home/fpp/media/plugins/InsteonHub2/scripts/fpp_event.sh --scene party_mode
/home/fpp/media/plugins/InsteonHub2/scripts/fpp_event.sh --scene all_off
```

### With verbose output (for testing)

```bash
/home/fpp/media/plugins/InsteonHub2/scripts/fpp_event.sh --device living_room_light --action dim --level 50 --verbose
```

## Device ID format

Use either:
- Friendly name from `devices.json` (e.g., `living_room_light`)
- Raw Insteon hex: `AA.BB.CC` or `AABBCC`

## Scene/Group configuration

In `scenes.json`, define scenes with:
- `group`: Scene group ID (01-FF, hex)
- `mode`: `on` or `off`
- `level`: Brightness 0-100 (optional, defaults to 100)

Example:
```json
{
  "party_mode": { "group": "01", "mode": "on", "level": 100 },
  "movie_mode": { "group": "02", "mode": "on", "level": 25 },
  "all_off": { "group": "FF", "mode": "off" }
}
```

## Retry and backoff strategy

- Default: 2 retries (3 total attempts)
- Backoff: exponential with random jitter
  - Attempt 1: 100 + 0-75ms random
  - Attempt 2: 200 + 0-75ms random
  - Attempt 3: 400 + 0-75ms random
- Configurable in `config.json` via `retries` and `--backoff` CLI flag

## Response validation

The plugin checks for:
- HTTP 2xx status
- Non-empty body
- No "error" or "invalid" keywords in response
- Presence of known success patterns (`0262`, `0261`, `ok`, `success`)

## Troubleshooting

- Confirm Hub credentials in settings.
- Confirm port 25105 reachable from FPP machine.
- Try command manually over SSH first.
- Check plugin output by adding `--verbose` flag.
- Monitor FPP logs for event hook errors.
- Check network connectivity: `ping <hub-ip>`
- Check Hub 2 local API response: `curl http://<hub-ip>:25105/3?0262AABBCC0F11FF`

## Disclaimer

This is a practical scaffold for live testing and may require endpoint tuning for your specific Hub firmware version.

