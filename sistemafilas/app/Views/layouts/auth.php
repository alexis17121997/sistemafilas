<?php
$vendor  = APP_PATH . '/public/vendor';
$vUrl    = APP_URL  . '/public/vendor';
$local   = file_exists($vendor . '/bootstrap.min.css');
$CSS_BS  = $local ? "$vUrl/bootstrap.min.css"       : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css';
$CSS_ICO = $local ? "$vUrl/tabler-icons.min.css"    : 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.0.0/dist/tabler-icons.min.css';
$JS_BS   = $local ? "$vUrl/bootstrap.bundle.min.js" : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? 'Acceso – ' . APP_NAME ?></title>
  <link rel="stylesheet" href="<?= $CSS_BS ?>">
  <link rel="stylesheet" href="<?= $CSS_ICO ?>">
  <link rel="stylesheet" href="<?= APP_URL ?>/public/css/style.css">
</head>
<body class="auth-bg d-flex align-items-center justify-content-center min-vh-100">
  <?= $content ?>
  <script src="<?= $JS_BS ?>"></script>
</body>
</html>
