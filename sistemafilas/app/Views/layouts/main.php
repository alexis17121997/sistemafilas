<?php
$vendor    = APP_PATH . '/public/vendor';
$vendorUrl = APP_URL  . '/public/vendor';
$local     = file_exists($vendor . '/bootstrap.min.css');
$CSS_BS    = $local ? "$vendorUrl/bootstrap.min.css"       : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css';
$CSS_ICO   = $local ? "$vendorUrl/tabler-icons.min.css"    : 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.0.0/dist/tabler-icons.min.css';
$JS_BS     = $local ? "$vendorUrl/bootstrap.bundle.min.js" : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? APP_NAME ?></title>
  <link rel="stylesheet" href="<?= $CSS_BS ?>">
  <link rel="stylesheet" href="<?= $CSS_ICO ?>">
  <link rel="stylesheet" href="<?= APP_URL ?>/public/css/style.css">
</head>
<body class="main-body">

<nav class="navbar navbar-expand-lg navbar-dark main-nav shadow-sm">
  <div class="container-fluid px-3">
    <a class="navbar-brand d-flex align-items-center gap-2 py-1 text-decoration-none" href="<?= APP_URL ?>/">
      <div class="hospital-logo-icon" aria-label="Logo hospital">
        <svg viewBox="0 0 40 40" width="36" height="36" xmlns="http://www.w3.org/2000/svg">
          <circle cx="20" cy="20" r="18" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.4)" stroke-width="1.2"/>
          <rect x="16.5" y="9"  width="7" height="22" rx="2.5" fill="white"/>
          <rect x="9"  y="16.5" width="22" height="7" rx="2.5" fill="white"/>
          <circle cx="20" cy="20" r="3" fill="#e74c3c"/>
        </svg>
      </div>
      <div>
        <div class="nav-brand-name"><?= APP_NAME ?></div>
        <div class="nav-brand-sub">Sistema de Gestión de Filas</div>
      </div>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto gap-0">
        <?php $u = Auth::user(); if ($u): ?>

        <?php if ($u['role'] === 'admin'): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="ti ti-settings" aria-hidden="true"></i> Admin
          </a>
          <ul class="dropdown-menu shadow-sm border-0">
            <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/dashboard"><i class="ti ti-dashboard me-1 text-primary"></i>Dashboard</a></li>
            <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/users"><i class="ti ti-users me-1 text-primary"></i>Usuarios</a></li>
            <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/branches"><i class="ti ti-building me-1 text-primary"></i>Sucursales</a></li>
            <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/windows"><i class="ti ti-layout-grid me-1 text-primary"></i>Cajas</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/printer"><i class="ti ti-printer me-1 text-success"></i>Impresora</a></li>
            <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/advertising"><i class="ti ti-movie me-1 text-danger"></i>Publicidad</a></li>
            <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/specialties"><i class="ti ti-stethoscope me-1 text-info"></i>Especialidades</a></li>
          </ul>
        </li>
        <?php endif; ?>

        <?php if (in_array($u['role'], ['admin','supervisor'])): ?>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/supervisor/dashboard"><i class="ti ti-eye" aria-hidden="true"></i><span class="d-none d-md-inline ms-1">Monitor</span></a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/supervisor/reports"><i class="ti ti-chart-bar" aria-hidden="true"></i><span class="d-none d-md-inline ms-1">Reportes</span></a></li>
        <?php endif; ?>

        <?php if (in_array($u['role'], ['cashier','admin','supervisor'])): ?>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/cashier"><i class="ti ti-ticket" aria-hidden="true"></i><span class="d-none d-md-inline ms-1">Mi Caja</span></a></li>
        <?php endif; ?>

        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/display?branch=<?= $u['branch_id'] ?? 1 ?>" target="_blank"><i class="ti ti-device-tv" aria-hidden="true"></i><span class="d-none d-md-inline ms-1">Pantalla</span></a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/dispenser?branch=<?= $u['branch_id'] ?? 1 ?>" target="_blank"><i class="ti ti-printer" aria-hidden="true"></i><span class="d-none d-md-inline ms-1">Dispensador</span></a></li>
        <?php endif; ?>
      </ul>

      <?php if ($u = Auth::user()): ?>
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-25 d-none d-md-inline-flex align-items-center gap-1" style="font-size:0.7rem">
          <i class="ti ti-building" aria-hidden="true"></i><?= htmlspecialchars($u['branch_name'] ?? 'Sin sucursal') ?>
        </span>
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-light dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
            <div class="user-avatar-sm"><?= strtoupper(substr($u['full_name'], 0, 1)) ?></div>
            <span class="d-none d-md-inline"><?= htmlspecialchars($u['full_name']) ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow border-0">
            <li class="px-3 py-2">
              <div class="fw-semibold small"><?= htmlspecialchars($u['full_name']) ?></div>
              <div class="text-muted small"><?= ucfirst($u['role']) ?> &middot; <?= htmlspecialchars($u['branch_name'] ?? '') ?></div>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/logout"><i class="ti ti-logout me-1" aria-hidden="true"></i>Cerrar sesión</a></li>
          </ul>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container-fluid px-3 py-4">
  <?php if (isset($flash) && $flash): ?>
  <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
  </div>
  <?php endif; ?>
  <?= $content ?>
</div>

<script src="<?= $JS_BS ?>"></script>
<script>const APP_URL = '<?= APP_URL ?>';</script>
<?php if (isset($extraJs)) echo $extraJs; ?>
</body>
</html>
