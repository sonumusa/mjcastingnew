<?php
require_once __DIR__ . '/config.php';
requireAuth();

$event = query('event');
if (!in_array($event, ['login', 'logout'], true)) {
    redirect('dashboard.php');
}

$destination = $event === 'login' ? 'module_select.php' : 'logout.php?finish=1';
$backupUrl = url('backup.php');
$destinationUrl = url($destination);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preparing database backup</title>
</head>
<body>
    <p>Your database backup is downloading. You will be redirected shortly.</p>
    <iframe src="<?= htmlspecialchars($backupUrl, ENT_QUOTES, 'UTF-8') ?>" title="Database backup download" hidden></iframe>
    <script>
        window.setTimeout(function () {
            window.location.replace(<?= json_encode($destinationUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
        }, 2000);
    </script>
</body>
</html>
