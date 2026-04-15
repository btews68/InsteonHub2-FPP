<?php

declare(strict_types=1);

$configPath = __DIR__ . '/config.json';
$devicesPath = __DIR__ . '/devices.json';
$devicesExamplePath = __DIR__ . '/devices.example.json';
$defaultConfig = [
    'host' => '',
    'username' => '',
    'password' => '',
    'timeout_seconds' => 5,
    'retries' => 2,
];

$config = $defaultConfig;
if (file_exists($configPath)) {
    $raw = file_get_contents($configPath);
    if ($raw !== false) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $config = array_merge($config, $decoded);
        }
    }
}

$message = '';
$messageType = 'ok';

$defaultDevices = [
  'front_porch' => '42729E',
];

$devicesData = $defaultDevices;
if (file_exists($devicesPath)) {
  $rawDevices = file_get_contents($devicesPath);
  if ($rawDevices !== false) {
    $decodedDevices = json_decode($rawDevices, true);
    if (is_array($decodedDevices)) {
      $devicesData = $decodedDevices;
    }
  }
} elseif (file_exists($devicesExamplePath)) {
  $rawDevices = file_get_contents($devicesExamplePath);
  if ($rawDevices !== false) {
    $decodedDevices = json_decode($rawDevices, true);
    if (is_array($decodedDevices)) {
      $devicesData = $decodedDevices;
    }
  }
}

$devicesJsonText = json_encode($devicesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if (!is_string($devicesJsonText)) {
  $devicesJsonText = "{}";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config['host'] = trim((string) ($_POST['host'] ?? ''));
    $config['username'] = trim((string) ($_POST['username'] ?? ''));
    $config['password'] = trim((string) ($_POST['password'] ?? ''));
    $config['timeout_seconds'] = max(1, (int) ($_POST['timeout_seconds'] ?? 5));
    $config['retries'] = max(1, (int) ($_POST['retries'] ?? 1));
  $devicesJsonText = (string) ($_POST['devices_json'] ?? '{}');

    $ok = file_put_contents(
        $configPath,
        json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

  $decodedDevices = json_decode($devicesJsonText, true);
  $devicesOk = false;
  $devicesError = '';

  if (!is_array($decodedDevices)) {
    $devicesError = 'devices.json must be a valid JSON object.';
  } else {
    $normalized = [];
    foreach ($decodedDevices as $name => $id) {
      if (!is_string($name) || trim($name) === '') {
        $devicesError = 'Each device alias key must be a non-empty string.';
        break;
      }
      if (!is_scalar($id) || trim((string) $id) === '') {
        $devicesError = 'Each device id must be a non-empty value.';
        break;
      }
      $normalized[trim($name)] = trim((string) $id);
    }

    if ($devicesError === '') {
      $encodedDevices = json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
      if (is_string($encodedDevices)) {
        $devicesWrite = file_put_contents($devicesPath, $encodedDevices . PHP_EOL);
        $devicesOk = $devicesWrite !== false;
        if ($devicesOk) {
          $devicesJsonText = $encodedDevices;
        } else {
          $devicesError = 'Failed to save devices.json.';
        }
      } else {
        $devicesError = 'Failed to encode devices JSON.';
      }
    }
  }

  if ($ok !== false && $devicesOk) {
    $message = 'Settings and device aliases saved.';
    $messageType = 'ok';
  } elseif ($ok !== false) {
    $message = 'Hub settings saved, but device aliases were not saved: ' . $devicesError;
    $messageType = 'error';
  } else {
    $message = 'Failed to save hub settings.';
    $messageType = 'error';
  }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Insteon Hub 2 Settings</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; max-width: 740px; }
    .row { margin: 10px 0; }
    label { display: block; font-weight: bold; margin-bottom: 4px; }
    input, textarea { width: 100%; padding: 8px; box-sizing: border-box; }
    textarea { min-height: 180px; font-family: monospace; }
    button { padding: 10px 16px; }
    .msg { padding: 10px; margin-bottom: 12px; background: #eef7ee; border: 1px solid #a8d5a8; }
    .msg.error { background: #fdeeee; border-color: #e5a9a9; }
    .hint { color: #555; font-size: 14px; }
  </style>
</head>
<body>
  <h2>Insteon Hub 2 Plugin Settings</h2>

  <?php if ($message !== ''): ?>
    <div class="msg <?php echo $messageType === 'error' ? 'error' : ''; ?>"><?php echo h($message); ?></div>
  <?php endif; ?>

  <form method="post">
    <div class="row">
      <label for="host">Hub IP or hostname</label>
      <input id="host" name="host" value="<?php echo h((string) $config['host']); ?>" required>
    </div>

    <div class="row">
      <label for="username">Hub username</label>
      <input id="username" name="username" value="<?php echo h((string) $config['username']); ?>" required>
    </div>

    <div class="row">
      <label for="password">Hub password</label>
      <input id="password" name="password" type="password" value="<?php echo h((string) $config['password']); ?>" required>
    </div>

    <div class="row">
      <label for="timeout_seconds">Timeout seconds</label>
      <input id="timeout_seconds" name="timeout_seconds" type="number" min="1" max="30" value="<?php echo h((string) $config['timeout_seconds']); ?>">
    </div>

    <div class="row">
      <label for="retries">Retries</label>
      <input id="retries" name="retries" type="number" min="1" max="10" value="<?php echo h((string) $config['retries']); ?>">
    </div>

    <div class="row">
      <label for="devices_json">Device aliases (devices.json)</label>
      <textarea id="devices_json" name="devices_json" spellcheck="false"><?php echo h($devicesJsonText); ?></textarea>
      <div class="hint">JSON object format: { "front_porch": "42729E", "garage": "12AB34" }</div>
    </div>

    <div class="row">
      <button type="submit">Save Settings + Devices</button>
    </div>
  </form>

  <p class="hint">Use with event command example:</p>
  <pre>/home/fpp/media/plugins/InsteonHub2/scripts/fpp_event.sh --device AA.BB.CC --action on</pre>

  <h3>Advanced configuration</h3>
  <p class="hint">
    Optional device/scene maps for cleaner commands:
  </p>
  <ul class="hint">
    <li><code>devices.json</code> - map friendly names to Insteon IDs, e.g. <code>living_room_light</code> → <code>AA.BB.CC</code></li>
    <li><code>scenes.json</code> - named groups and presets, e.g. <code>party_mode</code> → group 01, on, 100%</li>
    <li><code>playlist_actions.json</code> - trigger scenes on FPP playlist start/stop</li>
  </ul>
  <p class="hint">
    Copy the <code>.example.json</code> files and edit with your devices and scenes.
  </p>
</body>
</html>
