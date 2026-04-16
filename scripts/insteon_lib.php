<?php

declare(strict_types=1);

function insteon_normalize_device_id(string $deviceId): string
{
    $id = strtoupper(str_replace(['.', ':', '-', ' '], '', $deviceId));
    if (!preg_match('/^[0-9A-F]{6}$/', $id)) {
        throw new InvalidArgumentException('Invalid device id. Use AA.BB.CC or AABBCC.');
    }
    return $id;
}

function insteon_level_to_hex(int $level): string
{
    if ($level < 0 || $level > 100) {
        throw new InvalidArgumentException('Dim level must be 0-100.');
    }

    $value = (int) round(($level / 100) * 255);
    return strtoupper(str_pad(dechex($value), 2, '0', STR_PAD_LEFT));
}

function insteon_normalize_group_id(string $groupId): string
{
    $id = strtoupper(str_replace(['0X', '.', ':', '-', ' '], '', $groupId));
    if (!preg_match('/^[0-9A-F]{2}$/', $id)) {
        throw new InvalidArgumentException('Invalid scene/group id. Use 01-FF (hex).');
    }
    return $id;
}

function insteon_resolve_device_target(string $target, array $deviceMap = []): string
{
    $normalized = strtoupper(str_replace(['.', ':', '-', ' '], '', $target));
    if (preg_match('/^[0-9A-F]{6}$/', $normalized)) {
        return $normalized;
    }

    $key = strtolower(trim($target));
    if ($key === '') {
        throw new InvalidArgumentException('Device target cannot be empty.');
    }

    if (!isset($deviceMap[$key])) {
        throw new InvalidArgumentException('Unknown device alias: ' . $target);
    }

    return insteon_normalize_device_id((string) $deviceMap[$key]);
}

function insteon_build_raw_command(string $deviceId, string $action, ?int $level = null): string
{
    $id = insteon_normalize_device_id($deviceId);
    $action = strtolower($action);

    if ($action === 'on') {
        return '0262' . $id . '0F11FF=I=3';
    }

    if ($action === 'off') {
        return '0262' . $id . '0F1300=I=3';
    }

    if ($action === 'dim') {
        if ($level === null) {
            throw new InvalidArgumentException('Dim action requires level.');
        }
        return '0262' . $id . '0F11' . insteon_level_to_hex($level) . '=I=3';
    }

    throw new InvalidArgumentException('Unsupported action. Use on, off, or dim.');
}

function insteon_build_scene_command(string $groupId, string $mode = 'on', ?int $level = null): string
{
    $group = insteon_normalize_group_id($groupId);
    $mode = strtolower($mode);

    if ($mode === 'on') {
        $cmd = '11';
        $lvl = $level === null ? 'FF' : insteon_level_to_hex($level);
        return '0261' . $group . $cmd . $lvl . '=I=3';
    }

    if ($mode === 'off') {
        return '0261' . $group . '1300=I=3';
    }

    throw new InvalidArgumentException('Unsupported scene mode. Use on or off.');
}

function insteon_load_alias_map(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException('Unable to read map file: ' . $path);
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid JSON map file: ' . $path);
    }

    $map = [];
    foreach ($decoded as $name => $value) {
        if (!is_string($name) || !is_scalar($value)) {
            continue;
        }
        $map[strtolower(trim($name))] = (string) $value;
    }

    return $map;
}

function insteon_load_scene_map(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException('Unable to read scene map file: ' . $path);
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid JSON scene map file: ' . $path);
    }

    $map = [];
    foreach ($decoded as $name => $scene) {
        if (!is_string($name) || !is_array($scene)) {
            continue;
        }

        $group = (string) ($scene['group'] ?? '');
        $mode = strtolower((string) ($scene['mode'] ?? 'on'));
        $level = isset($scene['level']) ? (int) $scene['level'] : null;

        $map[strtolower(trim($name))] = [
            'group' => $group,
            'mode' => $mode,
            'level' => $level,
        ];
    }

    return $map;
}

function insteon_resolve_scene(string $sceneName, array $sceneMap): array
{
    $key = strtolower(trim($sceneName));
    if ($key === '') {
        throw new InvalidArgumentException('Scene name cannot be empty.');
    }
    if (!isset($sceneMap[$key])) {
        throw new InvalidArgumentException('Unknown scene alias: ' . $sceneName);
    }

    $scene = $sceneMap[$key];
    return [
        'group' => insteon_normalize_group_id((string) ($scene['group'] ?? '')),
        'mode' => strtolower((string) ($scene['mode'] ?? 'on')),
        'level' => isset($scene['level']) ? (int) $scene['level'] : null,
    ];
}

function insteon_response_looks_successful(int $httpCode, string $body): bool
{
    if ($httpCode < 200 || $httpCode >= 300) {
        return false;
    }

    if (trim($body) === '') {
        // Hub 2 often returns HTTP 200 with an empty body for accepted commands.
        return true;
    }

    $needle = strtolower($body);
    if (strpos($needle, 'error') !== false || strpos($needle, 'invalid') !== false) {
        return false;
    }

    return strpos($needle, '0262') !== false
        || strpos($needle, '0261') !== false
        || strpos($needle, 'ok') !== false
        || strpos($needle, 'success') !== false;
}

function insteon_send_hub_command(
    string $host,
    string $username,
    string $password,
    string $rawCommand,
    int $timeoutSeconds = 5
): array {
    if ($host === '' || $username === '' || $password === '') {
        throw new InvalidArgumentException('Host, username, and password are required.');
    }

    $url = 'http://' . $host . ':25105/3?' . $rawCommand;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => $username . ':' . $password,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeoutSeconds,
        CURLOPT_FAILONERROR => false,
    ]);

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'url' => $url,
        'http_code' => $httpCode,
        'curl_errno' => $errno,
        'curl_error' => $error,
        'body' => is_string($body) ? $body : '',
        'ok' => $errno === 0 && insteon_response_looks_successful($httpCode, is_string($body) ? $body : ''),
    ];
}

function insteon_compute_backoff_us(int $attempt, int $baseBackoffMs): int
{
    $base = max(10, $baseBackoffMs);
    $exp = 1 << max(0, $attempt - 1);
    $jitterMs = random_int(0, 75);
    return (int) (($base * $exp + $jitterMs) * 1000);
}

function insteon_read_config(string $configPath): array
{
    if (!file_exists($configPath)) {
        throw new RuntimeException('Config file not found: ' . $configPath);
    }

    $raw = file_get_contents($configPath);
    if ($raw === false) {
        throw new RuntimeException('Unable to read config file: ' . $configPath);
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid JSON config: ' . $configPath);
    }

    return $decoded;
}
