<?php $pageTitle = 'Usuarios'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="fw-bold mb-0"><i class="ti ti-users me-2 text-primary"></i>Gestión de Usuarios</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateUser">
    <i class="ti ti-plus me-1"></i> Nuevo Usuario
  </button>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Nombre</th><th>Usuario</th><th>Rol</th><th>Sucursal</th><th>Email</th><th>Estado</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td class="fw-semibold"><?= htmlspecialchars($u['full_name']) ?></td>
            <td><code><?= htmlspecialchars($u['username']) ?></code></td>
            <td><span class="badge bg-<?= ['admin'=>'danger','supervisor'=>'warning','cashier'=>'primary','dispenser'=>'info','display'=>'secondary'][$u['role_name']] ?? 'light' ?>">
              <?= ucfirst($u['role_name']) ?></span></td>
            <td><?= htmlspecialchars($u['branch_name'] ?? '–') ?></td>
            <td><?= htmlspecialchars($u['email'] ?? '–') ?></td>
            <td><?= $u['active'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?></td>
            <td>
              <button class="btn btn-sm btn-outline-secondary" onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)">
                <i class="ti ti-edit"></i>
              </button>
              <form method="POST" action="<?= APP_URL ?>/admin/users/<?= $u['id'] ?>/delete" class="d-inline"
                    onsubmit="return confirm('¿Desactivar usuario?')">
                <button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="modalCreateUser" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="<?= APP_URL ?>/admin/users/create" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Nuevo Usuario</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">Nombre completo</label><input name="full_name" class="form-control" required></div>
        <div class="col-6"><label class="form-label">Usuario</label><input name="username" class="form-control" required></div>
        <div class="col-6"><label class="form-label">Contraseña</label><input type="password" name="password" class="form-control" required></div>
        <div class="col-6"><label class="form-label">Rol</label>
          <select name="role_id" class="form-select" required>
            <?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>"><?= ucfirst($r['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-6"><label class="form-label">Sucursal</label>
          <select name="branch_id" class="form-select">
            <option value="">– Sin sucursal –</option>
            <?php foreach ($branches as $b): ?><option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-12"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Crear</button></div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="modalEditUser" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" id="formEditUser" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Editar Usuario</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">Nombre completo</label><input name="full_name" id="edit_full_name" class="form-control" required></div>
        <div class="col-6"><label class="form-label">Nueva contraseña <small class="text-muted">(dejar vacío para no cambiar)</small></label><input type="password" name="password" class="form-control"></div>
        <div class="col-6"><label class="form-label">Email</label><input type="email" name="email" id="edit_email" class="form-control"></div>
        <div class="col-6"><label class="form-label">Rol</label>
          <select name="role_id" id="edit_role_id" class="form-select">
            <?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>"><?= ucfirst($r['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-6"><label class="form-label">Sucursal</label>
          <select name="branch_id" id="edit_branch_id" class="form-select">
            <option value="">– Sin sucursal –</option>
            <?php foreach ($branches as $b): ?><option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-6"><label class="form-label">Estado</label>
          <select name="active" id="edit_active" class="form-select"><option value="1">Activo</option><option value="0">Inactivo</option></select>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
    </form>
  </div>
</div>

<script>
function editUser(u) {
  document.getElementById('edit_full_name').value = u.full_name;
  document.getElementById('edit_email').value = u.email || '';
  document.getElementById('edit_role_id').value = u.role_id;
  document.getElementById('edit_branch_id').value = u.branch_id || '';
  document.getElementById('edit_active').value = u.active ? '1' : '0';
  document.getElementById('formEditUser').action = APP_URL + '/admin/users/' + u.id + '/update';
  new bootstrap.Modal(document.getElementById('modalEditUser')).show();
}
</script>
