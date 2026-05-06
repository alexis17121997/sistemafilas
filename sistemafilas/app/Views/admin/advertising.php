<?php $pageTitle = 'Contenido Publicitario'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="fw-bold mb-0"><i class="ti ti-movie me-2 text-primary"></i>Publicidad para Pantallas</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAd">
    <i class="ti ti-plus me-1"></i> Agregar Contenido
  </button>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr><th>#</th><th>Tipo</th><th>Título</th><th>URL</th><th>Duración</th><th>Estado</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($content as $c): ?>
        <tr>
          <td><?= $c['sort_order'] ?></td>
          <td><span class="badge bg-<?= $c['type']==='video'?'danger':'info' ?>">
            <i class="ti ti-<?= $c['type']==='video'?'movie':'photo' ?> me-1"></i><?= ucfirst($c['type']) ?></span></td>
          <td><?= htmlspecialchars($c['title'] ?? '') ?></td>
          <td><a href="<?= htmlspecialchars($c['url']) ?>" target="_blank" class="text-truncate d-inline-block" style="max-width:200px"><?= htmlspecialchars($c['url']) ?></a></td>
          <td><?= $c['duration'] ?>s</td>
          <td><?= $c['active']?'<span class="badge bg-success">Activo</span>':'<span class="badge bg-secondary">Inactivo</span>' ?></td>
          <td>
            <form method="POST" action="<?= APP_URL ?>/admin/advertising/<?= $c['id'] ?>/delete" class="d-inline"
                  onsubmit="return confirm('¿Eliminar?')">
              <button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($content)): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No hay contenido publicitario aún.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalAd" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="<?= APP_URL ?>/admin/advertising/save" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Nuevo Contenido</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-6"><label class="form-label">Tipo</label>
          <select name="type" class="form-select">
            <option value="image">Imagen</option>
            <option value="video">Video (YouTube / URL)</option>
          </select>
        </div>
        <div class="col-6"><label class="form-label">Duración (seg)</label>
          <input type="number" name="duration" class="form-control" value="10" min="3" max="300"></div>
        <div class="col-12"><label class="form-label">Título</label><input name="title" class="form-control"></div>
        <div class="col-12"><label class="form-label">URL</label>
          <input name="url" class="form-control" required placeholder="https://...">
          <div class="form-text">Para video: URL directa o embed de YouTube. Para imagen: URL pública.</div>
        </div>
        <div class="col-6"><label class="form-label">Orden</label><input type="number" name="sort_order" class="form-control" value="0"></div>
        <div class="col-6 d-flex align-items-end">
          <div class="form-check mb-1">
            <input class="form-check-input" type="checkbox" name="active" value="1" id="ad_active" checked>
            <label class="form-check-label" for="ad_active">Activo</label>
          </div>
        </div>
      </div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar</button></div>
    </form>
  </div>
</div>
