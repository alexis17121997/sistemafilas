<?php $pageTitle = 'Panel de Caja'; ?>

<!-- ── Cashier top status bar ──────────────────────────────────────────────── -->
<div class="cashier-topbar mb-3">
  <div class="d-flex align-items-center gap-3 flex-wrap">

    <!-- Window badge -->
    <div class="cashier-window-badge d-flex align-items-center gap-3">
      <div class="cw-num" aria-label="Caja <?= $assignment['window_number'] ?>">
        <?= $assignment['window_number'] ?>
      </div>
      <div>
        <div class="cw-name"><?= htmlspecialchars($assignment['window_name']) ?></div>
        <div class="cw-user"><i class="ti ti-user me-1" aria-hidden="true"></i><?= htmlspecialchars($user['full_name']) ?></div>
      </div>
    </div>

    <div class="ms-auto d-flex gap-2 align-items-center flex-wrap">
      <!-- Today stats pills -->
      <div class="stat-pill stat-served" title="Atendidos hoy">
        <i class="ti ti-circle-check" aria-hidden="true"></i>
        <span id="pServed"><?= $stats['served'] ?></span>
        <small>hoy</small>
      </div>
      <div class="stat-pill stat-waiting" title="En espera">
        <i class="ti ti-clock" aria-hidden="true"></i>
        <span id="pWaiting"><?= count($waiting) ?></span>
        <small>espera</small>
      </div>

      <!-- Release window -->
      <a href="<?= APP_URL ?>/cashier/release" class="btn btn-outline-danger btn-sm"
         onclick="return confirm('¿Liberar caja <?= $assignment['window_number'] ?> y terminar turno?')">
        <i class="ti ti-logout" aria-hidden="true"></i>
        <span class="d-none d-md-inline ms-1">Liberar Caja</span>
      </a>
    </div>
  </div>
</div>

<div class="row g-3">

  <!-- ════════════════════════════════════════
       LEFT COLUMN: Action panel
  ════════════════════════════════════════ -->
  <div class="col-lg-5 col-xl-4">

    <!-- ── STEP A: Idle – Call next ──────────────────────────────────────── -->
    <div id="panelIdle" class="cashier-panel <?= $currentTicket ? 'd-none' : '' ?>">
      <div class="panel-body text-center py-4 px-3">
        <div class="idle-icon mb-3" aria-hidden="true">
          <i class="ti ti-ticket"></i>
        </div>
        <p class="text-muted mb-4" id="idleStatus">
          <?= count($waiting) > 0
            ? '<strong>' . count($waiting) . '</strong> paciente' . (count($waiting)>1?'s':'') . ' en espera'
            : 'Sin pacientes en espera' ?>
        </p>
        <button class="btn-call-next" id="btnCallNext" onclick="callNext()"
                <?= empty($waiting) ? '' : '' ?> aria-label="Llamar siguiente ticket">
          <span class="btn-call-icon" aria-hidden="true"><i class="ti ti-bell-ringing"></i></span>
          <span class="btn-call-label">LLAMAR SIGUIENTE</span>
          <span class="btn-call-sub" id="callSub">
            <?= count($waiting) > 0
              ? count($waiting) . ' en espera'
              : 'Cola vacía' ?>
          </span>
        </button>
      </div>
    </div>

    <!-- ── STEP B: Ticket revealed ───────────────────────────────────────── -->
    <div id="panelTicket" class="cashier-panel <?= !$currentTicket ? 'd-none' : '' ?>">

      <!-- Ticket number revealed -->
      <div class="ticket-reveal-header">
        <div class="tr-label">Ticket llamado</div>
        <div class="tr-number <?= !$currentTicket ? '' : 'visible' ?>" id="trNumber"
             style="color: <?= htmlspecialchars($currentTicket['cat_color'] ?? '#2563eb') ?>">
          <?= htmlspecialchars($currentTicket['ticket_number'] ?? '') ?>
        </div>
        <div class="tr-meta" id="trMeta">
          <span class="tr-cat"><?= htmlspecialchars($currentTicket['cat_name'] ?? '') ?></span>
          <span class="tr-sep" aria-hidden="true">·</span>
          <span class="tr-svc"><?= htmlspecialchars($currentTicket['service_name'] ?? '') ?></span>
        </div>
        <div class="tr-status" id="trStatus">
          <?php if ($currentTicket): ?>
          <span class="badge <?= $currentTicket['status']==='calling'?'bg-info':'bg-primary' ?>">
            <?= $currentTicket['status']==='calling' ? '📣 Llamando' : '🟢 Atendiendo' ?>
          </span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Action buttons -->
      <div class="ticket-actions" id="ticketActions">

        <button class="btn-action btn-serving" id="btnServing"
                onclick="serveCurrent()" aria-label="Marcar como atendiendo"
                <?= ($currentTicket && $currentTicket['status']==='serving') ? 'disabled' : '' ?>>
          <i class="ti ti-user-check" aria-hidden="true"></i>
          Atendiendo
        </button>

        <button class="btn-action btn-complete" id="btnComplete"
                onclick="completeCurrent()" aria-label="Completar atención">
          <i class="ti ti-circle-check" aria-hidden="true"></i>
          Completar
        </button>

        <button class="btn-action btn-recall" id="btnRecall"
                onclick="recallCurrent()" aria-label="Rellamar al paciente">
          <i class="ti ti-volume" aria-hidden="true"></i>
          Rellamar
        </button>

        <button class="btn-action btn-noshow" id="btnNoShow"
                onclick="noShowCurrent()" aria-label="Marcar como no presentado">
          <i class="ti ti-user-x" aria-hidden="true"></i>
          No Presentó
        </button>

      </div>

      <!-- Back to idle (after complete/noshow, or force reset) -->
      <div class="text-center pb-3">
        <button class="btn btn-sm btn-outline-secondary" onclick="forceCallNext()"
                title="Llamar siguiente sin esperar">
          <i class="ti ti-arrow-right" aria-hidden="true"></i> Siguiente ticket
        </button>
      </div>
    </div>

  </div>

  <!-- ════════════════════════════════════════
       RIGHT COLUMN: Waiting queue
  ════════════════════════════════════════ -->
  <div class="col-lg-7 col-xl-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <span class="fw-semibold small">
          <i class="ti ti-list-numbers me-1 text-primary" aria-hidden="true"></i>
          Cola de espera
        </span>
        <span class="badge bg-warning text-dark" id="qBadge" aria-label="Pacientes en espera"><?= count($waiting) ?></span>
      </div>

      <div class="card-body p-0" style="max-height:72vh;overflow-y:auto" id="queueList">
        <?php if (empty($waiting)): ?>
        <div class="text-center py-5 text-muted" id="emptyQ">
          <i class="ti ti-check-circle fs-1 text-success d-block mb-2" aria-hidden="true"></i>
          No hay pacientes en espera
        </div>
        <?php else: ?>
          <?php foreach ($waiting as $i => $t): ?>
          <div class="queue-row <?= $i === 0 ? 'queue-row-first' : '' ?>"
               role="row" aria-label="Ticket <?= htmlspecialchars($t['ticket_number']) ?>">
            <div class="qr-pos" aria-hidden="true"><?= $i + 1 ?></div>
            <div class="qr-dot" style="background:<?= htmlspecialchars($t['cat_color']) ?>" aria-hidden="true"></div>
            <div class="qr-info">
              <span class="qr-ticket"><?= htmlspecialchars($t['ticket_number']) ?></span>
              <span class="qr-cat text-muted"><?= htmlspecialchars($t['cat_name']) ?></span>
            </div>
            <span class="qr-badge" style="background:<?= htmlspecialchars($t['cat_color']) ?>22;color:<?= htmlspecialchars($t['cat_color']) ?>">
              <?= htmlspecialchars($t['service_name']) ?>
            </span>
            <span class="qr-time text-muted" aria-label="Hora de emisión"><?= date('H:i', strtotime($t['issued_at'])) ?></span>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<!-- ── Call overlay (flash when ticket is announced) ─────────────────────── -->
<div class="call-overlay" id="callOverlay" style="display:none" role="alert" aria-live="assertive">
  <div class="call-overlay-inner">
    <div class="co-label">LLAMANDO</div>
    <div class="co-ticket" id="coTicket"></div>
    <div class="co-window" id="coWindow"></div>
  </div>
</div>

<?php
$extraJs = '<script>
const WINDOW_NUM  = ' . (int)$assignment['window_number'] . ';
const WINDOW_NAME = ' . json_encode($assignment['window_name']) . ';
let CURRENT_ID    = ' . ($currentTicket ? (int)$currentTicket['id'] : 0) . ';
</script>
<script src="' . APP_URL . '/public/js/cashier.js"></script>';
?>
