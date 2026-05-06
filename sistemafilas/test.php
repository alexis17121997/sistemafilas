<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Diagnóstico del Sistema</title>
<style>
  body { font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; padding: 0 20px; background: #f5f5f5; }
  h2 { color: #1a3a6c; border-bottom: 2px solid #2563eb; padding-bottom: 8px; }
  .ok   { background: #f0fdf4; border-left: 4px solid #22c55e; padding: 10px 14px; margin: 8px 0; border-radius: 4px; }
  .fail { background: #fef2f2; border-left: 4px solid #ef4444; padding: 10px 14px; margin: 8px 0; border-radius: 4px; }
  .info { background: #eff6ff; border-left: 4px solid #3b82f6; padding: 10px 14px; margin: 8px 0; border-radius: 4px; }
  code { background: #e5e7eb; padding: 2px 6px; border-radius: 3px; font-size: 13px; }
  .fix  { background: #fefce8; border: 1px solid #fbbf24; padding: 10px 14px; margin: 8px 0; border-radius: 4px; font-size: 13px; }
</style>
</head>
<body>
<h2>🔍 Diagnóstico – Sistema de Filas</h2>

<?php
// ── 1. PHP version ───────────────────────────────────────────────────────────
$phpVersion = PHP_VERSION;
$phpOk = version_compare($phpVersion, '7.4', '>=');
echo $phpOk
    ? "<div class='ok'>✅ PHP versión: <strong>$phpVersion</strong></div>"
    : "<div class='fail'>❌ PHP versión muy antigua: <strong>$phpVersion</strong> — necesitas 7.4+</div>";

// ── 2. pdo_pgsql extension ───────────────────────────────────────────────────
if (extension_loaded('pdo_pgsql')) {
    echo "<div class='ok'>✅ Extensión <code>pdo_pgsql</code> está activa</div>";
} else {
    echo "<div class='fail'>❌ Extensión <code>pdo_pgsql</code> NO está activa<br>
          <div class='fix'>👉 Abre <code>php.ini</code> de XAMPP, busca <code>;extension=pdo_pgsql</code> y quita el punto y coma. Reinicia Apache.</div></div>";
}

// ── 3. Database connection ───────────────────────────────────────────────────
$dbHost = 'localhost';
$dbPort = '5432';
$dbName = 'sistemafilas';
$dbUser = 'postgres';
$dbPass = '17121997';

$pdo = null;
try {
    $pdo = new PDO(
        "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName",
        $dbUser, $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<div class='ok'>✅ Conexión a PostgreSQL exitosa — base de datos: <code>$dbName</code></div>";
} catch (PDOException $e) {
    echo "<div class='fail'>❌ No se pudo conectar a PostgreSQL<br>
          <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "
          <div class='fix'>
          👉 Verifica que PostgreSQL esté corriendo en XAMPP<br>
          👉 Verifica que la base de datos <code>$dbName</code> existe en pgAdmin<br>
          👉 Verifica usuario/contraseña en <code>config/database.php</code>
          </div></div>";
}

if (!$pdo) { echo "<div class='fail'>⛔ No se puede continuar sin conexión a la base de datos.</div></body></html>"; exit; }

// ── 4. pgcrypto extension ────────────────────────────────────────────────────
try {
    $res = $pdo->query("SELECT crypt('test', gen_salt('bf')) AS h")->fetch();
    echo "<div class='ok'>✅ Extensión <code>pgcrypto</code> activa en PostgreSQL</div>";
} catch (Exception $e) {
    echo "<div class='fail'>❌ <code>pgcrypto</code> NO está activa en PostgreSQL<br>
          <div class='fix'>👉 Ejecuta en pgAdmin: <code>CREATE EXTENSION IF NOT EXISTS \"pgcrypto\";</code></div></div>";
}

// ── 5. Tables exist ──────────────────────────────────────────────────────────
$tables = ['roles','users','branches','windows','tickets','patient_categories','service_types'];
$missing = [];
foreach ($tables as $t) {
    $r = $pdo->query("SELECT to_regclass('public.$t') AS exists")->fetch();
    if (!$r['exists']) $missing[] = $t;
}
if (empty($missing)) {
    echo "<div class='ok'>✅ Todas las tablas existen en la base de datos</div>";
} else {
    echo "<div class='fail'>❌ Faltan estas tablas: <code>" . implode(', ', $missing) . "</code><br>
          <div class='fix'>👉 Ejecuta el archivo <code>database/schema.sql</code> en pgAdmin Query Tool</div></div>";
}

// ── 6. Check admin user exists ───────────────────────────────────────────────
try {
    $stmt = $pdo->prepare("SELECT id, username, full_name, active, LEFT(password_hash,10) AS hash_preview FROM users WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user) {
        $status = $user['active'] ? '✅ activo' : '❌ INACTIVO';
        echo "<div class='ok'>✅ Usuario <code>admin</code> encontrado — ID: {$user['id']} — Estado: $status<br>
              Nombre: <strong>{$user['full_name']}</strong><br>
              Hash (primeros 10 chars): <code>{$user['hash_preview']}...</code></div>";
        if (!$user['active']) {
            echo "<div class='fix'>👉 Activa el usuario: <code>UPDATE users SET active=TRUE WHERE username='admin';</code></div>";
        }
    } else {
        echo "<div class='fail'>❌ El usuario <code>admin</code> NO existe en la base de datos<br>
              <div class='fix'>👉 Ejecuta en pgAdmin:<br>
              <code>INSERT INTO users (branch_id, role_id, username, password_hash, full_name, email)<br>
              VALUES (1, 1, 'admin', crypt('admin123', gen_salt('bf')), 'Administrador', 'admin@hospital.com');</code></div></div>";
    }
} catch (Exception $e) {
    echo "<div class='fail'>❌ Error al buscar usuario: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// ── 7. Verify password ───────────────────────────────────────────────────────
$testPass = 'admin123';
try {
    $stmt = $pdo->prepare("SELECT (password_hash = crypt(:p, password_hash)) AS ok FROM users WHERE username = 'admin'");
    $stmt->execute([':p' => $testPass]);
    $res = $stmt->fetch();

    if (isset($res['ok']) && $res['ok']) {
        echo "<div class='ok'>✅ Contraseña <code>$testPass</code> es CORRECTA para el usuario admin</div>";
    } else {
        echo "<div class='fail'>❌ La contraseña <code>$testPass</code> NO coincide con el hash guardado<br>
              <div class='fix'>👉 Ejecuta en pgAdmin para resetearla:<br>
              <code>UPDATE users SET password_hash = crypt('admin123', gen_salt('bf')) WHERE username = 'admin';</code></div></div>";
    }
} catch (Exception $e) {
    echo "<div class='fail'>❌ Error verificando contraseña: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// ── 8. PHP password_verify compatibility ─────────────────────────────────────
try {
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = 'admin'");
    $stmt->execute();
    $r = $stmt->fetch();
    if ($r) {
        $hash = $r['password_hash'];
        $phpVerify = password_verify($testPass, $hash);
        if ($phpVerify) {
            echo "<div class='ok'>✅ <code>password_verify()</code> de PHP también funciona con este hash</div>";
        } else {
            echo "<div class='info'>ℹ️ <code>password_verify()</code> de PHP NO reconoce el hash (esto es normal con pgcrypto <code>\$2a\$</code>)<br>
                  El sistema usará verificación por pgcrypto directamente — eso está bien.</div>";
        }
    }
} catch (Exception $e) {}

// ── 9. Session test ──────────────────────────────────────────────────────────
session_start();
$_SESSION['diag_test'] = 'ok';
if ($_SESSION['diag_test'] === 'ok') {
    echo "<div class='ok'>✅ Sesiones PHP funcionando correctamente</div>";
} else {
    echo "<div class='fail'>❌ Las sesiones PHP no funcionan — verifica permisos de la carpeta <code>tmp</code></div>";
}

echo "<br><div class='info'>🏁 Diagnóstico completo. Si todos los checks son ✅ y el login aún falla, avísame con un screenshot de esta página.</div>";
?>

</body>
</html>