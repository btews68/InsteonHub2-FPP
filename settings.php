<?php

declare(strict_types=1);

$configPath = __DIR__ . '/config.json';
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config['host'] = trim((string) ($_POST['host'] ?? ''));
    $config['username'] = trim((string) ($_POST['username'] ?? ''));
    $config['password'] = trim((string) ($_POST['password'] ?? ''));
    $config['timeout_seconds'] = max(1, (int) ($_POST['timeout_seconds'] ?? 5));
    $config['retries'] = max(1, (int) ($_POST['retries'] ?? 1));

    $ok = file_put_contents(
        $configPath,
        json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    $message = $ok !== false ? 'Settings saved.' : 'Failed to save settings.';
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
    input { width: 100%; padding: 8px; box-sizing: border-box; }
    button { padding: 10px 16px; }
    .msg { padding: 10px; margin-bottom: 12px; background: #eef7ee; border: 1px solid #a8d5a8; }
    .hint { color: #555; font-size: 14px; }
  </style>
</head>
<body>
  <h2>Insteon Hub 2 Plugin Settings</h2>

  <?php if ($message !== ''): ?>
    <div class="msg"><?php echo h($message); ?></div>
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
      <button type="submit">Save Settings</button>
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
