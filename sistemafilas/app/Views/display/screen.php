<?php
$pageTitle   = 'Pantalla de Llamado';
$clinicName  = $cfg['clinic_name'] ?? ($branch['name'] ?? 'Hospital');
$refreshMs   = (int)($cfg['display_refresh_ms'] ?? 2000);
$announceRep = 2; // Always 2x – configurable via settings
?>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { width: 100%; height: 100%; overflow: hidden; background: #071423; font-family: 'Segoe UI', Arial, sans-serif; }

/* ── Background pattern (pure CSS, no internet) ──────────────────────────── */
body::before {
  content: '';
  position: fixed; inset: 0; pointer-events: none; z-index: 0;
  background-image:
    radial-gradient(circle at 20% 50%, rgba(37,99,235,0.08) 0%, transparent 50%),
    radial-gradient(circle at 80% 20%, rgba(124,58,237,0.05) 0%, transparent 40%);
}

/* ── Grid ──────────────────────────────────────────────────────────────────── */
.screen {
  display: grid;
  height: 100vh;
  grid-template-rows: 70px 1fr 230px;
  grid-template-columns: 1fr 320px;
  position: relative; z-index: 1;
}

/* ── Header ────────────────────────────────────────────────────────────────── */
.scr-header {
  grid-column: 1 / -1;
  background: linear-gradient(90deg, #0c1e45 0%, #0f2a5c 50%, #0c1e45 100%);
  border-bottom: 2px solid #2563eb;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 1.5rem;
}
.logo-area { display: flex; align-items: center; gap: 12px; }
.logo-svg-wrap {
  width: 46px; height: 46px;
  background: rgba(255,255,255,0.1);
  border: 1.5px solid rgba(255,255,255,0.25);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
}
.brand-name { font-size: 1.35rem; font-weight: 800; color: #fff; letter-spacing: 1px; line-height: 1.1; }
.brand-sub  { font-size: 0.7rem; color: rgba(255,255,255,0.55); letter-spacing: 2px; text-transform: uppercase; }
.clock-area { text-align: right; }
.clock-time { font-size: 2.1rem; font-weight: 200; color: #93c5fd; font-variant-numeric: tabular-nums; line-height: 1; }
.clock-date { font-size: 0.8rem; color: rgba(255,255,255,0.5); margin-top: 2px; }

/* ── Main calling area ─────────────────────────────────────────────────────── */
.calling-area {
  grid-column: 1; grid-row: 2;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 1.5rem;
  gap: 1.5rem;
}

/* Active call display */
.call-box {
  width: 100%; max-width: 580px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 20px;
  padding: 1.75rem 2.5rem;
  text-align: center;
  position: relative; overflow: hidden;
}
.call-box::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, #2563eb, #7c3aed, #06b6d4, #2563eb);
  background-size: 200% 100%;
  animation: gradShift 3s linear infinite;
}
@keyframes gradShift { 0% { background-position: 0% 50%; } 100% { background-position: 200% 50%; } }

.call-lbl { color: rgba(255,255,255,0.4); font-size: 0.72rem; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 6px; }
.call-num {
  font-size: clamp(3.5rem, 9vw, 6.5rem);
  font-weight: 900; color: #fff; letter-spacing: 6px; line-height: 1;
  text-shadow: 0 0 50px rgba(96,165,250,0.4);
  transition: all 0.2s;
}
.call-num.flash { animation: numFlash 0.5s ease; }
@keyframes numFlash {
  0%,100% { transform: scale(1); }
  40%     { transform: scale(1.07); color: #fbbf24; text-shadow: 0 0 60px rgba(251,191,36,0.8); }
}
.call-arrow { font-size: 1.6rem; color: rgba(255,255,255,0.3); margin: 4px 0; }
.call-win   { font-size: clamp(1.4rem, 4vw, 2.8rem); font-weight: 700; color: #60a5fa; }
.call-svc   { font-size: 0.82rem; color: rgba(255,255,255,0.4); margin-top: 4px; }

/* Windows grid */
.windows-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 10px;
  width: 100%; max-width: 680px;
}
.w-card {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 12px;
  padding: 10px 6px;
  text-align: center;
  transition: all 0.4s;
}
.w-card.occupied { border-color: rgba(37,99,235,0.4); background: rgba(37,99,235,0.08); }
.w-card.calling  {
  border-color: #f59e0b;
  background: rgba(245,158,11,0.1);
  animation: wPulse 1.1s infinite;
}
@keyframes wPulse {
  0%,100% { box-shadow: 0 0 0 0 rgba(245,158,11,0); }
  50%     { box-shadow: 0 0 18px 4px rgba(245,158,11,0.3); }
}
.w-num    { font-size: 1.7rem; font-weight: 800; color: #fff; line-height: 1; }
.w-name   { font-size: 0.6rem; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }
.w-ticket { font-size: 0.9rem; font-weight: 700; color: #fbbf24; min-height: 18px; margin-top: 4px; }
.w-cashier{ font-size: 0.58rem; color: rgba(255,255,255,0.35); }

/* ── Sidebar: recent calls ─────────────────────────────────────────────────── */
.recent-calls {
  grid-column: 2; grid-row: 2;
  background: rgba(0,0,0,0.2);
  border-left: 1px solid rgba(255,255,255,0.06);
  display: flex; flex-direction: column;
  overflow: hidden;
}
.rc-hdr {
  padding: 14px 16px 10px;
  font-size: 0.65rem; font-weight: 600; letter-spacing: 2.5px; text-transform: uppercase;
  color: rgba(255,255,255,0.35);
  border-bottom: 1px solid rgba(255,255,255,0.06);
}
.rc-scroll { flex: 1; overflow: hidden; }
.rc-item {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 16px;
  border-bottom: 1px solid rgba(255,255,255,0.04);
  transition: all 0.4s;
}
.rc-item:first-child { background: rgba(255,255,255,0.04); }
.rc-item.rc-new { animation: rcSlide 0.35s ease; }
@keyframes rcSlide { from { transform: translateY(-8px); opacity: 0; } to { transform: none; opacity: 1; } }
.rc-dot    { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.rc-ticket { font-size: 1.05rem; font-weight: 800; color: #fff; flex: 1; letter-spacing: 1px; }
.rc-meta   { text-align: right; }
.rc-win    { font-size: 0.7rem; color: #60a5fa; font-weight: 600; }
.rc-time   { font-size: 0.6rem; color: rgba(255,255,255,0.3); }

/* ── Bottom bar: ads + specialties ────────────────────────────────────────── */
.bottom-bar {
  grid-column: 1 / -1; grid-row: 3;
  display: grid;
  grid-template-columns: 1fr 1fr;
  border-top: 1px solid rgba(255,255,255,0.08);
  overflow: hidden;
}

/* ── Advertising carousel ──────────────────────────────────────────────────── */
.ads-wrap { position: relative; overflow: hidden; border-right: 1px solid rgba(255,255,255,0.06); }

.ads-slide { position: absolute; inset: 0; opacity: 0; transition: opacity 0.9s ease; }
.ads-slide.ads-active { opacity: 1; }

/* Video slide */
.ads-slide video { width: 100%; height: 100%; object-fit: cover; }

/* Placeholder slide (shown when no real video) */
.ads-placeholder {
  width: 100%; height: 100%;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 12px;
  color: rgba(255,255,255,0.6);
  font-size: 0.9rem;
}
.ads-placeholder.theme-1 { background: linear-gradient(135deg, #0c2461 0%, #1565c0 100%); }
.ads-placeholder.theme-2 { background: linear-gradient(135deg, #1a237e 0%, #283593 100%); }
.ads-placeholder.theme-3 { background: linear-gradient(135deg, #004d40 0%, #00796b 100%); }
.ads-placeholder.theme-4 { background: linear-gradient(135deg, #311b92 0%, #4527a0 100%); }

.ads-placeholder svg { opacity: 0.7; }
.ads-placeholder .ph-title { font-size: 1.1rem; font-weight: 700; color: rgba(255,255,255,0.9); }
.ads-placeholder .ph-sub   { font-size: 0.78rem; color: rgba(255,255,255,0.55); text-align: center; max-width: 240px; }

/* Ads nav dots */
.ads-dots {
  position: absolute; bottom: 8px; left: 50%; transform: translateX(-50%);
  display: flex; gap: 5px; z-index: 5;
}
.ads-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: rgba(255,255,255,0.3);
  transition: all 0.3s;
}
.ads-dot.active { background: #fff; width: 14px; border-radius: 3px; }

/* ── Specialties carousel ──────────────────────────────────────────────────── */
.spec-panel {
  position: relative;
  overflow: hidden;
  padding: 14px 20px;
}
.spec-hdr { font-size: 0.65rem; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; color: rgba(255,255,255,0.35); margin-bottom: 4px; }
.spec-slide { position: absolute; left: 20px; right: 20px; top: 40px; bottom: 14px; opacity: 0; transition: opacity 0.6s ease; display: flex; align-items: center; gap: 14px; }
.spec-slide.spec-active { opacity: 1; }
.spec-ico {
  width: 52px; height: 52px; flex-shrink: 0;
  background: rgba(96,165,250,0.15);
  border: 1px solid rgba(96,165,250,0.3);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
}
.spec-ico i { font-size: 1.4rem; color: #60a5fa; }
.spec-name   { font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 2px; }
.spec-doctor { font-size: 0.8rem; color: #60a5fa; margin-bottom: 3px; }
.spec-detail { font-size: 0.7rem; color: rgba(255,255,255,0.45); display: flex; gap: 10px; flex-wrap: wrap; }

/* ── Activate overlay ──────────────────────────────────────────────────────── */
.activate-overlay {
  position: fixed; inset: 0; z-index: 100;
  background: rgba(7,20,35,0.97);
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2rem;
}
.activate-logo svg { width: 100px; height: 100px; }
.activate-title { font-size: 2rem; font-weight: 800; color: #fff; text-align: center; }
.activate-sub   { font-size: 1rem; color: rgba(255,255,255,0.55); text-align: center; }
.activate-btn {
  background: #2563eb; color: #fff;
  border: none; border-radius: 14px;
  padding: 16px 48px; font-size: 1.3rem; font-weight: 700;
  cursor: pointer; display: flex; align-items: center; gap: 12px;
  transition: background 0.2s;
}
.activate-btn:hover { background: #1d4ed8; }
.activate-btn i { font-size: 1.8rem; }

/* ── Toast ──────────────────────────────────────────────────────────────────── */
.toast-call {
  position: fixed; top: 80px; left: 50%; transform: translateX(-50%) translateY(-10px);
  background: #f59e0b; color: #000;
  border-radius: 12px; padding: 12px 28px;
  font-size: 1.15rem; font-weight: 700;
  opacity: 0; transition: all 0.35s;
  z-index: 50; min-width: 320px; text-align: center;
  box-shadow: 0 6px 30px rgba(245,158,11,0.35);
}
.toast-call.show { opacity: 1; transform: translateX(-50%) translateY(0); }
</style>

<!-- Activate overlay -->
<div class="activate-overlay" id="activateOverlay">
  <div class="activate-logo">
    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
      <circle cx="50" cy="50" r="48" fill="rgba(37,99,235,0.2)" stroke="#2563eb" stroke-width="2"/>
      <rect x="42" y="18" width="16" height="64" rx="5" fill="white"/>
      <rect x="18" y="42" width="64" height="16" rx="5" fill="white"/>
      <circle cx="50" cy="50" r="9" fill="#e74c3c"/>
    </svg>
  </div>
  <div>
    <div class="activate-title"><?= htmlspecialchars($clinicName) ?></div>
    <div class="activate-sub">Sistema de Atención al Paciente</div>
  </div>
  <button class="activate-btn" onclick="activateScreen()" id="btnActivate">
    <i class="ti ti-player-play" aria-hidden="true"></i> Activar Pantalla
  </button>
  <div style="color:rgba(255,255,255,0.3);font-size:0.8rem">Haga clic para activar audio y pantalla completa</div>
</div>

<!-- Main screen -->
<div class="screen">

  <!-- Header -->
  <header class="scr-header">
    <div class="logo-area">
      <div class="logo-svg-wrap" aria-label="Logo hospital">
        <svg viewBox="0 0 40 40" width="28" height="28" xmlns="http://www.w3.org/2000/svg">
          <rect x="16.5" y="6"  width="7" height="28" rx="2.5" fill="white"/>
          <rect x="6"  y="16.5" width="28" height="7"  rx="2.5" fill="white"/>
          <circle cx="20" cy="20" r="4" fill="#e74c3c"/>
        </svg>
      </div>
      <div>
        <div class="brand-name"><?= htmlspecialchars($clinicName) ?></div>
        <div class="brand-sub">Sistema de Llamado</div>
      </div>
    </div>
    <div class="clock-area">
      <div class="clock-time" id="clockTime" aria-live="off">00:00:00</div>
      <div class="clock-date" id="clockDate"></div>
    </div>
  </header>

  <!-- Calling area -->
  <main class="calling-area">
    <div class="call-box">
      <div class="call-lbl">Llamando ahora</div>
      <div class="call-num" id="mainTicket" aria-live="assertive">
        <?= htmlspecialchars($lastCalls[0]['ticket_number'] ?? '— — —') ?>
      </div>
      <div class="call-arrow" aria-hidden="true"><i class="ti ti-arrow-down"></i></div>
      <div class="call-win" id="mainWindow"><?= htmlspecialchars($lastCalls[0]['window_name'] ?? 'En espera') ?></div>
      <div class="call-svc" id="mainSvc"></div>
    </div>

    <!-- 5 windows grid -->
    <div class="windows-grid" id="wGrid" role="list" aria-label="Estado de cajas">
      <?php foreach (array_slice($windows, 0, 5) as $w):
        $callData = null;
        foreach ($lastCalls as $lc) { if ($lc['window_number'] == $w['number']) { $callData = $lc; break; } }
      ?>
      <div class="w-card <?= $callData ? 'calling' : '' ?>"
           id="wcard-<?= $w['id'] ?>" data-wnum="<?= $w['number'] ?>" role="listitem"
           aria-label="<?= htmlspecialchars($w['name']) ?>">
        <div class="w-num"><?= $w['number'] ?></div>
        <div class="w-name"><?= htmlspecialchars($w['name']) ?></div>
        <div class="w-ticket" id="wt-<?= $w['id'] ?>"><?= $callData ? htmlspecialchars($callData['ticket_number']) : '' ?></div>
        <div class="w-cashier" id="wc-<?= $w['id'] ?>"></div>
      </div>
      <?php endforeach; ?>
    </div>
  </main>

  <!-- Recent calls sidebar -->
  <aside class="recent-calls">
    <div class="rc-hdr"><i class="ti ti-history me-1" aria-hidden="true"></i>Últimos llamados</div>
    <div class="rc-scroll" id="rcList">
      <?php foreach (array_slice($lastCalls, 0, 8) as $call): ?>
      <div class="rc-item">
        <div class="rc-dot" style="background:<?= htmlspecialchars($call['cat_color'] ?? '#60a5fa') ?>"></div>
        <div class="rc-ticket"><?= htmlspecialchars($call['ticket_number']) ?></div>
        <div class="rc-meta">
          <div class="rc-win"><?= htmlspecialchars($call['window_name']) ?></div>
          <div class="rc-time"><?= date('H:i', strtotime($call['called_at'])) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </aside>

  <!-- Bottom: Ads + Specialties -->
  <div class="bottom-bar">

    <!-- Advertising carousel (4 videos) -->
    <div class="ads-wrap" id="adsWrap">

      <?php
      // Build 4 slots: use DB content, fill remaining with placeholders
      $adSlots = array_pad(array_values($ads), 4, null);
      $phThemes = ['theme-1','theme-2','theme-3','theme-4'];
      $phIcons  = [
          ['ti-hand-sanitizer', 'Higiene de Manos',       'Lávese las manos frecuentemente por 20 segundos'],
          ['ti-stethoscope',    'Servicios del Hospital', 'Consulta, urgencias, laboratorio y más'],
          ['ti-heart-rate',     'Prevención y Salud',     'Cuide su salud con chequeos preventivos regulares'],
          ['ti-info-circle',    'Información General',    'Horarios: Lunes a Viernes 8:00 – 18:00'],
      ];
      ?>

      <?php foreach ($adSlots as $i => $ad): ?>
      <?php $isFirst = $i === 0; ?>

      <?php if ($ad && !in_array($ad['url'], ['','local:higiene_manos.mp4','local:servicios_hospital.mp4','local:prevencion_salud.mp4','local:informacion_general.mp4']) && $ad['type'] !== 'placeholder'): ?>
        <!-- Real content -->
        <div class="ads-slide <?= $isFirst?'ads-active':'' ?>"
             data-dur="<?= ($ad['duration'] ?? 15) * 1000 ?>"
             data-idx="<?= $i ?>">
          <?php if ($ad['type'] === 'video'): ?>
          <video autoplay muted loop playsinline>
            <source src="<?= htmlspecialchars($ad['url']) ?>">
          </video>
          <?php else: ?>
          <img src="<?= htmlspecialchars($ad['url']) ?>" alt="<?= htmlspecialchars($ad['title'] ?? '') ?>" style="width:100%;height:100%;object-fit:cover">
          <?php endif; ?>
        </div>

      <?php else: ?>
        <!-- Placeholder / local video slot -->
        <div class="ads-slide <?= $isFirst?'ads-active':'' ?>"
             data-dur="15000" data-idx="<?= $i ?>">

          <?php /* If local file exists, show <video>; else show styled placeholder */ ?>
          <?php
          $localFile = '';
          $localFileNames = ['higiene_manos.mp4','servicios_hospital.mp4','prevencion_salud.mp4','informacion_general.mp4'];
          if ($ad && $ad['type'] === 'placeholder') {
              preg_match('/local:(.+)$/', $ad['url'] ?? '', $m);
              if (!empty($m[1])) $localFile = $m[1];
          }
          $videoPath = APP_PATH . '/public/assets/videos/' . ($localFile ?: $localFileNames[$i] ?? '');
          $videoUrl  = APP_URL  . '/public/assets/videos/' . ($localFile ?: $localFileNames[$i] ?? '');
          ?>
          <?php if (file_exists($videoPath)): ?>
          <video autoplay muted loop playsinline style="width:100%;height:100%;object-fit:cover">
            <source src="<?= $videoUrl ?>">
          </video>
          <?php else: ?>
          <!-- Beautiful SVG placeholder – replace with real video in /public/assets/videos/ -->
          <div class="ads-placeholder <?= $phThemes[$i] ?>">
            <?php
            $ico = $phIcons[$i];
            // Inline SVG illustration (medical-themed, no internet needed)
            $svgIllus = [
              // Hands/hygiene
              '<svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="40" cy="40" r="38" fill="rgba(255,255,255,0.08)" stroke="rgba(255,255,255,0.2)" stroke-width="1.5"/><path d="M28 55 C28 48 32 44 40 44 C48 44 52 48 52 55" stroke="rgba(255,255,255,0.7)" stroke-width="2.5" fill="none" stroke-linecap="round"/><circle cx="40" cy="34" r="8" stroke="rgba(255,255,255,0.7)" stroke-width="2.5" fill="none"/><path d="M36 32 L40 28 L44 32" stroke="rgba(255,255,255,0.5)" stroke-width="1.5" fill="none" stroke-linecap="round"/><path d="M22 45 C22 45 18 42 18 38 C18 34 21 32 24 33" stroke="rgba(255,255,255,0.4)" stroke-width="1.5" fill="none" stroke-linecap="round"/><path d="M58 45 C58 45 62 42 62 38 C62 34 59 32 56 33" stroke="rgba(255,255,255,0.4)" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg>',
              // Stethoscope
              '<svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="40" cy="40" r="38" fill="rgba(255,255,255,0.08)" stroke="rgba(255,255,255,0.2)" stroke-width="1.5"/><circle cx="52" cy="48" r="10" stroke="rgba(255,255,255,0.7)" stroke-width="2.5" fill="none"/><circle cx="52" cy="48" r="4" fill="rgba(255,255,255,0.5)"/><path d="M30 20 L30 38 C30 46 36 52 44 52" stroke="rgba(255,255,255,0.7)" stroke-width="2.5" fill="none" stroke-linecap="round"/><circle cx="30" cy="20" r="4" stroke="rgba(255,255,255,0.6)" stroke-width="2" fill="none"/><circle cx="26" cy="18" r="3" stroke="rgba(255,255,255,0.4)" stroke-width="1.5" fill="none"/><circle cx="34" cy="18" r="3" stroke="rgba(255,255,255,0.4)" stroke-width="1.5" fill="none"/></svg>',
              // Heart rate
              '<svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="40" cy="40" r="38" fill="rgba(255,255,255,0.08)" stroke="rgba(255,255,255,0.2)" stroke-width="1.5"/><polyline points="18,40 28,40 33,28 38,52 44,36 49,44 52,40 62,40" stroke="rgba(255,255,255,0.8)" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>',
              // Info circle
              '<svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="40" cy="40" r="38" fill="rgba(255,255,255,0.08)" stroke="rgba(255,255,255,0.2)" stroke-width="1.5"/><rect x="37" y="30" width="6" height="26" rx="3" fill="rgba(255,255,255,0.75)"/><circle cx="40" cy="24" r="4" fill="rgba(255,255,255,0.75)"/></svg>',
            ];
            ?>
            <?= $svgIllus[$i] ?>
            <div class="ph-title"><?= htmlspecialchars($ad['title'] ?? $ico[1]) ?></div>
            <div class="ph-sub"><?= htmlspecialchars($ad['title'] ? '' : $ico[2]) ?></div>
            <div style="position:absolute;bottom:8px;right:10px;font-size:0.6rem;color:rgba(255,255,255,0.25)">
              Reemplazar: /public/assets/videos/<?= $localFileNames[$i] ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php endforeach; ?>

      <!-- Nav dots -->
      <div class="ads-dots" id="adsDots" aria-hidden="true">
        <?php for ($i = 0; $i < 4; $i++): ?>
        <div class="ads-dot <?= $i===0?'active':'' ?>" id="adsDot<?= $i ?>"></div>
        <?php endfor; ?>
      </div>
    </div>

    <!-- Specialties carousel -->
    <div class="spec-panel">
      <div class="spec-hdr"><i class="ti ti-stethoscope me-1" aria-hidden="true"></i>Especialidades disponibles</div>
      <?php foreach ($specialties as $i => $sp): ?>
      <div class="spec-slide <?= $i===0?'spec-active':'' ?>" id="spec<?= $i ?>">
        <div class="spec-ico"><i class="ti ti-stethoscope" aria-hidden="true"></i></div>
        <div>
          <div class="spec-name"><?= htmlspecialchars($sp['name']) ?></div>
          <div class="spec-doctor"><?= htmlspecialchars($sp['doctor_name'] ?? '') ?></div>
          <div class="spec-detail">
            <?php if ($sp['schedule']): ?><span><i class="ti ti-clock" aria-hidden="true"></i> <?= htmlspecialchars($sp['schedule']) ?></span><?php endif; ?>
            <?php if ($sp['room']): ?>    <span><i class="ti ti-door" aria-hidden="true"></i> <?= htmlspecialchars($sp['room']) ?></span><?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($specialties)): ?>
      <div class="spec-slide spec-active"><div class="spec-detail">Sin especialidades configuradas</div></div>
      <?php endif; ?>
    </div>

  </div>
</div>

<!-- Toast -->
<div class="toast-call" id="toastCall" role="alert" aria-live="assertive"></div>

<script>
/* ═══════════════════════════════════════════════════════════════════════════
   Display screen – 100% OFFLINE
   Bell = Web Audio API (sintetizado localmente, sin archivos, sin internet)
   Voz  = Web Speech API (motor TTS del sistema operativo, sin internet)
   Poll = fetch() a la propia API local
═══════════════════════════════════════════════════════════════════════════ */

const BRANCH_ID  = <?= $branchId ?>;
const REFRESH_MS = <?= $refreshMs ?>;
const REPEAT     = <?= $announceRep ?>;  // 2 veces

let lastId    = <?= !empty($lastCalls) ? (int)$lastCalls[0]['id'] : 0 ?>;
let audioCtx  = null;
let queue     = [];
let busy      = false;
let activated = false;

// ── Activar pantalla ─────────────────────────────────────────────────────────
function activateScreen() {
  audioCtx = new (window.AudioContext || window.webkitAudioContext)();
  activated = true;
  document.getElementById('activateOverlay').style.display = 'none';
  loadVoices();
  startClock();
  startCarousels();
  startPolling();
  // Optional fullscreen
  if (document.documentElement.requestFullscreen) {
    document.documentElement.requestFullscreen().catch(() => {});
  }
}

// ── Clock ────────────────────────────────────────────────────────────────────
function startClock() {
  function tick() {
    const n = new Date();
    document.getElementById('clockTime').textContent =
      String(n.getHours()).padStart(2,'0') + ':' +
      String(n.getMinutes()).padStart(2,'0') + ':' +
      String(n.getSeconds()).padStart(2,'0');
    document.getElementById('clockDate').textContent =
      n.toLocaleDateString('es-MX', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
  }
  tick(); setInterval(tick, 1000);
}

// ── Polling ───────────────────────────────────────────────────────────────────
function startPolling() {
  setInterval(() => {
    fetch(APP_URL + '/api/last-calls?branch_id=' + BRANCH_ID + '&last_id=' + lastId)
      .then(r => r.json())
      .then(d => {
        if (d.calls && d.calls.length) {
          d.calls.forEach(c => { queue.push(c); addToRecent(c); updateWindowCard(c); });
          lastId = Math.max(lastId, ...d.calls.map(c => +c.id));
          processQueue();
        }
        if (d.windows) updateWindowsGrid(d.windows);
      })
      .catch(() => {}); // silent fail – no internet ≠ crash
  }, REFRESH_MS);
}

// ── Queue ────────────────────────────────────────────────────────────────────
function processQueue() {
  if (busy || !queue.length) return;
  const call = queue.shift();
  busy = true;
  updateMainDisplay(call);
  announceN(call, REPEAT, () => { busy = false; setTimeout(processQueue, 600); });
}

function updateMainDisplay(call) {
  const el = document.getElementById('mainTicket');
  document.getElementById('mainWindow').textContent = call.window_name;
  document.getElementById('mainSvc').textContent    = call.service_name || '';
  el.textContent = call.ticket_number;
  el.classList.remove('flash');
  void el.offsetWidth;
  el.classList.add('flash');
  showToast('🔔  ' + call.ticket_number + '  →  ' + call.window_name);
}

function updateWindowCard(call) {
  document.querySelectorAll('.w-card').forEach(c => c.classList.remove('calling'));
  const card = document.querySelector('[data-wnum="' + call.window_number + '"]');
  if (card) {
    card.classList.add('calling');
    const wt = card.querySelector('.w-ticket');
    if (wt) wt.textContent = call.ticket_number;
  }
}

function updateWindowsGrid(windows) {
  windows.forEach(w => {
    const card = document.querySelector('[data-wnum="' + w.number + '"]');
    if (!card) return;
    const wt = card.querySelector('.w-ticket');
    const wc = card.querySelector('.w-cashier');
    if (w.current_ticket) { if (wt) wt.textContent = w.current_ticket; card.classList.add('occupied'); }
    else                  { if (wt) wt.textContent = ''; card.classList.remove('calling'); }
    if (wc) wc.textContent = w.cashier_name || '';
  });
}

function addToRecent(call) {
  const list = document.getElementById('rcList');
  const div  = document.createElement('div');
  div.className = 'rc-item rc-new';
  div.innerHTML =
    '<div class="rc-dot" style="background:' + (call.cat_color || '#60a5fa') + '"></div>' +
    '<div class="rc-ticket">' + escHtml(call.ticket_number) + '</div>' +
    '<div class="rc-meta">' +
      '<div class="rc-win">'  + escHtml(call.window_name) + '</div>' +
      '<div class="rc-time">' + new Date().toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit',hour12:false}) + '</div>' +
    '</div>';
  list.insertBefore(div, list.firstChild);
  while (list.children.length > 8) list.removeChild(list.lastChild);
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Announce N times ─────────────────────────────────────────────────────────
function announceN(call, times, done) {
  let count = 0;
  function step() {
    if (count >= times) { done(); return; }
    count++;
    playBell(() => {
      const text = 'Ticket ' + call.ticket_number.replace(/-/g,' ') + '. Pase a ' + call.window_name;
      speakText(text, () => setTimeout(step, 900));
    });
  }
  step();
}

// ── Bell – Web Audio API (100% offline, no archivos externos) ─────────────────
// Simula 3 armónicos de campana de bronce: 1× 2.756× 5.404×
function playBell(cb) {
  if (!audioCtx) { if (cb) cb(); return; }
  const freq = 880;  // La (A5) – tono de hospital/clínica
  const dur  = 1.6;
  [[1,0.55],[2.756,0.25],[5.404,0.1]].forEach(([ratio,vol]) => {
    const osc  = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.type = 'sine';
    osc.frequency.value = freq * ratio;
    gain.gain.setValueAtTime(0, audioCtx.currentTime);
    gain.gain.linearRampToValueAtTime(vol, audioCtx.currentTime + 0.01);
    gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + dur);
    osc.start(audioCtx.currentTime);
    osc.stop(audioCtx.currentTime + dur + 0.05);
  });
  setTimeout(cb || (() => {}), 650);
}

// ── TTS – Web Speech API (motor del OS, sin internet) ─────────────────────────
let cachedVoice = null;

function loadVoices() {
  function pick() {
    const voices = window.speechSynthesis.getVoices();
    cachedVoice = voices.find(v => v.lang === 'es-MX') ||
                  voices.find(v => v.lang.startsWith('es')) || null;
  }
  pick();
  window.speechSynthesis.onvoiceschanged = pick;
}

function speakText(text, cb) {
  if (!window.speechSynthesis) { setTimeout(cb || (() => {}), 1500); return; }
  window.speechSynthesis.cancel();
  const utt = new SpeechSynthesisUtterance(text);
  utt.lang   = 'es-MX';
  utt.rate   = 0.88;
  utt.pitch  = 1.0;
  utt.volume = 1.0;
  if (cachedVoice) utt.voice = cachedVoice;
  utt.onend  = cb || (() => {});
  utt.onerror = cb || (() => {});
  window.speechSynthesis.speak(utt);
}

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(msg) {
  const t = document.getElementById('toastCall');
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(t._t);
  t._t = setTimeout(() => t.classList.remove('show'), 4500);
}

// ── Carousels ─────────────────────────────────────────────────────────────────
function startCarousels() {
  // Ads carousel
  const slides = document.querySelectorAll('.ads-slide');
  const dots   = document.querySelectorAll('.ads-dot');
  if (slides.length > 1) {
    let idx = 0;
    function nextAd() {
      slides[idx].classList.remove('ads-active');
      dots[idx] && dots[idx].classList.remove('active');
      idx = (idx + 1) % slides.length;
      slides[idx].classList.add('ads-active');
      dots[idx] && dots[idx].classList.add('active');
      const dur = parseInt(slides[idx].dataset.dur) || 15000;
      setTimeout(nextAd, dur);
    }
    const firstDur = parseInt(slides[0].dataset.dur) || 15000;
    setTimeout(nextAd, firstDur);
  }

  // Specialties carousel
  const specs = document.querySelectorAll('.spec-slide');
  if (specs.length > 1) {
    let si = 0;
    setInterval(() => {
      specs[si].classList.remove('spec-active');
      si = (si + 1) % specs.length;
      specs[si].classList.add('spec-active');
    }, 5000);
  }
}
</script>
