#!/usr/bin/php
<?php

declare(strict_types=1);

require_once __DIR__ . '/insteon_lib.php';

function parse_args(array $argv): array
{
    $out = [];
    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];
        if (substr($arg, 0, 2) !== '--') {
            continue;
        }

        $key = substr($arg, 2);
        $next = $argv[$i + 1] ?? null;
        if ($next !== null && substr($next, 0, 2) !== '--') {
            $out[$key] = $next;
            $i++;
        } else {
            $out[$key] = '1';
        }
    }
    return $out;
}

function usage(): void
{
    $text = <<<TXT
Usage:
  insteon_cli.php --device ALIAS_OR_AABBCC --action on|off|dim [--level 0-100] [--config /path/config.json] [--verbose]
  insteon_cli.php --scene SCENE_NAME [--config /path/config.json] [--verbose]
  
Device ALIAS_OR_AABBCC:
  - Can be friendly name from devices.json (e.g., "living_room_light")
  - Or raw Insteon hex (e.g., AA.BB.CC or AABBCC)

Scene SCENE_NAME:
  - Friendly scene alias from scenes.json (e.g., "party_mode")

Options:
  --level 0-100      For dim action (default 50)
  --config PATH      Override config.json path
  --verbose          Show detailed response info
  --backoff MS       Base backoff ms for retries (default 100)
TXT;
    fwrite(STDERR, $text . PHP_EOL);
}

try {
    $args = parse_args($argv);

    $configPath = $args['config'] ?? (__DIR__ . '/../config.json');
    $cfg = insteon_read_config($configPath);

    $device = $args['device'] ?? '';
    $scene = $args['scene'] ?? '';
    $action = strtolower($args['action'] ?? '');
    $level = isset($args['level']) ? (int) $args['level'] : null;
    $verbose = isset($args['verbose']);
    $baseBackoffMs = isset($args['backoff']) ? (int) $args['backoff'] : 100;

    $host = (string) ($cfg['host'] ?? '');
    $user = (string) ($cfg['username'] ?? '');
    $pass = (string) ($cfg['password'] ?? '');
    $timeout = (int) ($cfg['timeout_seconds'] ?? 5);
    $retries = (int) ($cfg['retries'] ?? 2);

    $deviceMapPath = __DIR__ . '/../devices.json';
    $deviceMap = insteon_load_alias_map($deviceMapPath);

    $sceneMapPath = __DIR__ . '/../scenes.json';
    $sceneMap = insteon_load_scene_map($sceneMapPath);

    $rawCmd = null;
    if ($scene !== '') {
        $resolved = insteon_resolve_scene($scene, $sceneMap);
        $rawCmd = insteon_build_scene_command(
            $resolved['group'],
            $resolved['mode'],
            $resolved['level']
        );
    } else {
        if ($device === '' || $action === '') {
            usage();
            exit(2);
        }

        $targetId = insteon_resolve_device_target($device, $deviceMap);
        $rawCmd = insteon_build_raw_command($targetId, $action, $level);
    }

    $attempt = 0;
    $result = null;
    $maxAttempts = max(1, $retries);

    while ($attempt < $maxAttempts) {
        $attempt++;
        $result = insteon_send_hub_command($host, $user, $pass, $rawCmd, $timeout);

        if (!empty($result['ok'])) {
            break;
        }

        if ($attempt < $maxAttempts) {
            $backoffUs = insteon_compute_backoff_us($attempt, $baseBackoffMs);
            usleep($backoffUs);
        }
    }

    if ($verbose) {
        fwrite(STDOUT, json_encode([
            'attempts' => $attempt,
            'max_attempts' => $maxAttempts,
            'result' => $result,
        ], JSON_PRETTY_PRINT) . PHP_EOL);
    }

    if (empty($result['ok'])) {
        fwrite(STDERR, 'Command failed after ' . $attempt . ' attempt(s)' . PHP_EOL);
        if ($result !== null) {
            fwrite(STDERR, json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL);
        }
        exit(1);
    }

    fwrite(STDOUT, 'OK' . PHP_EOL);
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
