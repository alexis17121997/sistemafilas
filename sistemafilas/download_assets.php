<?php
/**
 * download_assets.php
 * ─────────────────────────────────────────────────────────────────────────
 * Ejecutar UNA VEZ desde el navegador o CLI para que el sistema
 * funcione SIN INTERNET (Bootstrap, Tabler Icons, etc. quedan locales).
 *
 * Uso CLI:   php download_assets.php
 * Uso web:   http://localhost/clinic-queue/download_assets.php
 * ─────────────────────────────────────────────────────────────────────────
 */

set_time_limit(120);
$vendorDir = __DIR__ . '/public/vendor';
if (!is_dir($vendorDir)) mkdir($vendorDir, 0755, true);

$assets = [
    // Bootstrap 5 CSS
    'bootstrap.min.css'          => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
    // Bootstrap 5 JS bundle (includes Popper)
    'bootstrap.bundle.min.js'    => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
    // Tabler Icons CSS
    'tabler-icons.min.css'       => 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.0.0/dist/tabler-icons.min.css',
    // Tabler Icons font files (woff2)
    'tabler-icons.woff2'         => 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.0.0/dist/fonts/tabler-icons.woff2',
    'tabler-icons.woff'          => 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.0.0/dist/fonts/tabler-icons.woff',
    'tabler-icons.ttf'           => 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.0.0/dist/fonts/tabler-icons.ttf',
];

$results = [];
$allOk   = true;

foreach ($assets as $localName => $url) {
    $dest = $vendorDir . '/' . $localName;
    if (file_exists($dest) && filesize($dest) > 1000) {
        $results[] = ['file' => $localName, 'status' => 'skip', 'size' => filesize($dest)];
        continue;
    }
    $ctx     = stream_context_create(['http' => ['timeout' => 30]]);
    $content = @file_get_contents($url, false, $ctx);
    if ($content === false || strlen($content) < 500) {
        $results[] = ['file' => $localName, 'status' => 'error', 'size' => 0];
        $allOk = false;
    } else {
        file_put_contents($dest, $content);
        $results[] = ['file' => $localName, 'status' => 'ok', 'size' => strlen($content)];
    }
}

// Fix font paths in Tabler CSS to point to local ./fonts/
$tablerCss = $vendorDir . '/tabler-icons.min.css';
if (file_exists($tablerCss)) {
    $css = file_get_contents($tablerCss);
    // Replace CDN font paths with local relative paths
    $css = preg_replace('/url\(["\']?https?:\/\/[^)\'"]*(tabler-icons\.(woff2?|ttf))["\']?\)/i',
        "url('fonts/$1')", $css);
    // Also handle relative paths that might break
    $css = str_replace('url("fonts/', 'url("./fonts/', $css);
    file_put_contents($tablerCss, $css);

    // Create fonts subdir and copy font files
    $fontsDir = $vendorDir . '/fonts';
    if (!is_dir($fontsDir)) mkdir($fontsDir, 0755, true);
    foreach (['tabler-icons.woff2','tabler-icons.woff','tabler-icons.ttf'] as $f) {
        $src = $vendorDir . '/' . $f;
        if (file_exists($src)) copy($src, $fontsDir . '/' . $f);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Descarga de Recursos – Sistema de Filas</title>
  <style>
    body { font-family: monospace; max-width: 700px; margin: 40px auto; padding: 0 20px; }
    h1 { font-size: 20px; }
    .ok   { color: #16a34a; }
    .skip { color: #2563eb; }
    .error{ color: #dc2626; }
    table { border-collapse: collapse; width: 100%; }
    td, th { border: 1px solid #ddd; padding: 8px 12px; text-align: left; }
    .btn { display: inline-block; margin-top: 16px; padding: 10px 24px; background: #2563eb; color: white; border-radius: 8px; text-decoration: none; }
  </style>
</head>
<body>
<h1>🏥 Descarga de Recursos Offline</h1>
<p>Estos archivos quedan guardados en <code>/public/vendor/</code> y el sistema los usará automáticamente sin internet.</p>

<table>
  <tr><th>Archivo</th><th>Estado</th><th>Tamaño</th></tr>
  <?php foreach ($results as $r): ?>
  <tr>
    <td><?= htmlspecialchars($r['file']) ?></td>
    <td class="<?= $r['status'] ?>">
      <?= ['ok'=>'✓ Descargado','skip'=>'↷ Ya existe','error'=>'✗ Error'][$r['status']] ?>
    </td>
    <td><?= $r['size'] > 0 ? number_format($r['size']/1024, 1).' KB' : '–' ?></td>
  </tr>
  <?php endforeach; ?>
</table>

<?php if ($allOk): ?>
<p class="ok" style="margin-top:16px">✓ <strong>Todos los recursos están disponibles offline.</strong>
  El sistema funcionará sin conexión a internet.</p>
<?php else: ?>
<p class="error" style="margin-top:16px">⚠ Algunos archivos no se pudieron descargar.
  Verifique la conexión e intente de nuevo.</p>
<?php endif; ?>

<a href="index.php" class="btn">← Ir al sistema</a>
</body>
</html>
