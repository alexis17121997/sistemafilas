<?php $pageTitle = 'Dashboard Admin'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-0 fw-bold"><i class="ti ti-dashboard me-2 text-primary"></i>Panel de Administración</h4>
    <p class="text-muted mb-0 small">Sucursal: <?= htmlspecialchars($user['branch_name'] ?? '') ?> — <?= date('l, d F Y') ?></p>
  </div>
</div>

<!-- Stats row -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['icon'=>'ti-ticket','label'=>'En Espera',   'val'=>$stats['waiting']??0,  'color'=>'warning'],
    ['icon'=>'ti-phone',  'label'=>'Llamando',   'val'=>$stats['calling']??0,  'color'=>'info'],
    ['icon'=>'ti-user-check','label'=>'Atendiendo','val'=>$stats['serving']??0,'color'=>'primary'],
    ['icon'=>'ti-check',  'label'=>'Atendidos Hoy','val'=>$stats['served']??0, 'color'=>'success'],
    ['icon'=>'ti-users',  'label'=>'Total Usuarios','val'=>count($users),       'color'=>'secondary'],
    ['icon'=>'ti-building','label'=>'Sucursales', 'val'=>count($branches),      'color'=>'dark'],
  ];
  foreach ($cards as $c): ?>
  <div class="col-6 col-md-4 col-lg-2">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center py-3">
        <div class="stat-icon bg-<?= $c['color'] ?>-subtle text-<?= $c['color'] ?> rounded-circle mx-auto mb-2">
          <i class="ti <?= $c['icon'] ?> fs-4"></i>
        </div>
        <h3 class="fw-bold mb-0"><?= $c['val'] ?></h3>
        <p class="text-muted small mb-0"><?= $c['label'] ?></p>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Quick Links -->
<div class="row g-3">
  <?php
  $links = [
    ['href'=>'/admin/users','icon'=>'ti-users','title'=>'Usuarios','desc'=>'Gestionar cuentas y roles','color'=>'primary'],
    ['href'=>'/admin/branches','icon'=>'ti-building','title'=>'Sucursales','desc'=>'Administrar sucursales','color'=>'info'],
    ['href'=>'/admin/windows','icon'=>'ti-layout-grid','title'=>'Cajas','desc'=>'Configurar ventanillas','color'=>'success'],
    ['href'=>'/admin/printer','icon'=>'ti-printer','title'=>'Impresora','desc'=>'Diseño de tickets','color'=>'warning'],
    ['href'=>'/admin/advertising','icon'=>'ti-movie','title'=>'Publicidad','desc'=>'Contenido para pantallas','color'=>'danger'],
    ['href'=>'/admin/specialties','icon'=>'ti-stethoscope','title'=>'Especialidades','desc'=>'Médicos y especialidades','color'=>'secondary'],
    ['href'=>'/supervisor/dashboard','icon'=>'ti-eye','title'=>'Monitor','desc'=>'Ver estado en tiempo real','color'=>'dark'],
    ['href'=>'/supervisor/reports','icon'=>'ti-chart-bar','title'=>'Reportes','desc'=>'Estadísticas del día','color'=>'primary'],
  ];
  foreach ($links as $l): ?>
  <div class="col-6 col-md-3">
    <a href="<?= APP_URL . $l['href'] ?>" class="card border-0 shadow-sm text-decoration-none quick-link-card h-100">
      <div class="card-body d-flex align-items-center gap-3 py-3">
        <div class="quick-icon bg-<?= $l['color'] ?>-subtle text-<?= $l['color'] ?> rounded-circle">
          <i class="ti <?= $l['icon'] ?> fs-5"></i>
        </div>
        <div>
          <p class="fw-semibold mb-0 text-dark"><?= $l['title'] ?></p>
          <p class="text-muted mb-0" style="font-size:0.75rem"><?= $l['desc'] ?></p>
        </div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>
