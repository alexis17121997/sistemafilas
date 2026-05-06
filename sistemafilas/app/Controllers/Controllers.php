<?php

// ═══════════════════════════════════════════════════════════════════════════════
//  AuthController
// ═══════════════════════════════════════════════════════════════════════════════
class AuthController extends Controller {

    public function loginForm(): void {
        if (Auth::user()) $this->redirectByRole();
        $this->view('auth/login', [], 'auth');
    }

    public function login(): void {
        $u = $this->sanitize($this->post('username', ''));
        $p = $this->post('password', '');

        if (Auth::attempt($u, $p)) {
            $this->redirectByRole();
        }
        $this->view('auth/login', ['error' => 'Usuario o contraseña incorrectos.'], 'auth');
    }

    public function logout(): void {
        Auth::logout();
        $this->redirect('/login');
    }

    private function redirectByRole(): void {
        $role = Auth::user()['role'];
        $map  = [
            'admin'      => '/admin/dashboard',
            'supervisor' => '/supervisor/dashboard',
            'cashier'    => '/cashier',
            'dispenser'  => '/dispenser',
            'display'    => '/display',
        ];
        $this->redirect($map[$role] ?? '/login');
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
//  AdminController
// ═══════════════════════════════════════════════════════════════════════════════
class AdminController extends Controller {

    public function dashboard(): void {
        $this->requireRole('admin');
        $user     = $this->currentUser();
        $branches = Branch::all();
        $users    = User::all();
        $stats    = Ticket::todayStats($user['branch_id'] ?? 1);
        $this->view('admin/dashboard', compact('user','branches','users','stats'));
    }

    // ── Users ────────────────────────────────────────────────────────────────
    public function users(): void {
        $this->requireRole('admin');
        $user    = $this->currentUser();
        $users   = User::all();
        $roles   = Role::all();
        $branches = Branch::all();
        $flash   = $this->getFlash();
        $this->view('admin/users', compact('user','users','roles','branches','flash'));
    }

    public function createUser(): void {
        $this->requireRole('admin');
        try {
            User::create([
                'branch_id' => $this->post('branch_id'),
                'role_id'   => $this->post('role_id'),
                'username'  => $this->sanitize($this->post('username','')),
                'password'  => $this->post('password',''),
                'full_name' => $this->sanitize($this->post('full_name','')),
                'email'     => $this->sanitize($this->post('email','')),
            ]);
            $this->flash('success', 'Usuario creado correctamente.');
        } catch (Exception $e) {
            $this->flash('error', 'Error: ' . $e->getMessage());
        }
        $this->redirect('/admin/users');
    }

    public function updateUser(string $id): void {
        $this->requireRole('admin');
        User::update((int)$id, [
            'full_name' => $this->sanitize($this->post('full_name','')),
            'email'     => $this->sanitize($this->post('email','')),
            'branch_id' => $this->post('branch_id'),
            'role_id'   => $this->post('role_id'),
            'password'  => $this->post('password',''),
            'active'    => $this->post('active','1') === '1',
        ]);
        $this->flash('success', 'Usuario actualizado.');
        $this->redirect('/admin/users');
    }

    public function deleteUser(string $id): void {
        $this->requireRole('admin');
        User::delete((int)$id);
        $this->flash('success', 'Usuario desactivado.');
        $this->redirect('/admin/users');
    }

    // ── Branches ─────────────────────────────────────────────────────────────
    public function branches(): void {
        $this->requireRole('admin');
        $user     = $this->currentUser();
        $branches = Branch::all();
        $flash    = $this->getFlash();
        $this->view('admin/branches', compact('user','branches','flash'));
    }

    public function createBranch(): void {
        $this->requireRole('admin');
        Branch::create([
            'name'    => $this->sanitize($this->post('name','')),
            'address' => $this->sanitize($this->post('address','')),
            'phone'   => $this->sanitize($this->post('phone','')),
        ]);
        $this->flash('success', 'Sucursal creada.');
        $this->redirect('/admin/branches');
    }

    public function updateBranch(string $id): void {
        $this->requireRole('admin');
        Branch::update((int)$id, [
            'name'    => $this->sanitize($this->post('name','')),
            'address' => $this->sanitize($this->post('address','')),
            'phone'   => $this->sanitize($this->post('phone','')),
            'active'  => $this->post('active','1') === '1',
        ]);
        $this->flash('success', 'Sucursal actualizada.');
        $this->redirect('/admin/branches');
    }

    // ── Windows ───────────────────────────────────────────────────────────────
    public function windows(): void {
        $this->requireRole('admin');
        $user     = $this->currentUser();
        $branches = Branch::all();
        $windows  = Window::allForBranch($user['branch_id'] ?? 1);
        $services = ServiceType::all();
        $flash    = $this->getFlash();
        $this->view('admin/windows', compact('user','branches','windows','services','flash'));
    }

    public function createWindow(): void {
        $this->requireRole('admin');
        $branchId = (int)$this->post('branch_id', $this->currentUser()['branch_id']);
        $id = (int) Database::execute(
            "INSERT INTO windows (branch_id, number, name) VALUES (\$1,\$2,\$3) RETURNING id",
            [$branchId, (int)$this->post('number'), $this->sanitize($this->post('name',''))]
        );
        $svcIds = $this->post('service_types', []);
        foreach ((array)$svcIds as $svcId) {
            Database::execute(
                'INSERT INTO window_service_types (window_id, service_type_id) VALUES ($1,$2) ON CONFLICT DO NOTHING',
                [$id, (int)$svcId]
            );
        }
        $this->flash('success', 'Caja creada.');
        $this->redirect('/admin/windows');
    }

    public function deleteWindow(string $id): void {
        $this->requireRole('admin');
        Database::execute('UPDATE windows SET active=FALSE WHERE id=$1', [(int)$id]);
        $this->flash('success', 'Caja eliminada.');
        $this->redirect('/admin/windows');
    }

    // ── Printer Config ────────────────────────────────────────────────────────
    public function printer(): void {
        $this->requireRole('admin');
        $user   = $this->currentUser();
        $config = PrinterConfig::forBranch($user['branch_id'] ?? 1);
        $flash  = $this->getFlash();
        $this->view('admin/printer', compact('user','config','flash'));
    }

    public function savePrinter(): void {
        $this->requireRole('admin');
        $branchId = $this->currentUser()['branch_id'] ?? 1;
        PrinterConfig::save((int)$branchId, [
            'printer_name' => $this->sanitize($this->post('printer_name','')),
            'paper_width'  => (int)$this->post('paper_width', 72),
            'font_size'    => (int)$this->post('font_size', 14),
            'header_text'  => $this->post('header_text',''),
            'footer_text'  => $this->post('footer_text',''),
            'show_logo'    => $this->post('show_logo'),
        ]);
        $this->flash('success', 'Configuración guardada.');
        $this->redirect('/admin/printer');
    }

    // ── Advertising ──────────────────────────────────────────────────────────
    public function advertising(): void {
        $this->requireRole('admin');
        $user    = $this->currentUser();
        $content = AdvertisingContent::allForBranch($user['branch_id'] ?? 1);
        $flash   = $this->getFlash();
        $this->view('admin/advertising', compact('user','content','flash'));
    }

    public function saveAdvertising(): void {
        $this->requireRole('admin');
        $branchId = $this->currentUser()['branch_id'] ?? 1;
        AdvertisingContent::save((int)$branchId, [
            'id'         => $this->post('id'),
            'type'       => $this->post('type','image'),
            'title'      => $this->sanitize($this->post('title','')),
            'url'        => $this->post('url',''),
            'duration'   => (int)$this->post('duration',10),
            'sort_order' => (int)$this->post('sort_order',0),
            'active'     => $this->post('active','1')==='1',
        ]);
        $this->flash('success', 'Contenido guardado.');
        $this->redirect('/admin/advertising');
    }

    public function deleteAdvertising(string $id): void {
        $this->requireRole('admin');
        AdvertisingContent::delete((int)$id);
        $this->flash('success', 'Contenido eliminado.');
        $this->redirect('/admin/advertising');
    }

    // ── Specialties ──────────────────────────────────────────────────────────
    public function specialties(): void {
        $this->requireRole('admin');
        $user      = $this->currentUser();
        $specs     = Specialty::forBranch($user['branch_id'] ?? 1);
        $flash     = $this->getFlash();
        $this->view('admin/specialties', compact('user','specs','flash'));
    }

    public function saveSpecialty(): void {
        $this->requireRole('admin');
        $branchId = $this->currentUser()['branch_id'] ?? 1;
        Specialty::save((int)$branchId, [
            'id'          => $this->post('id'),
            'name'        => $this->sanitize($this->post('name','')),
            'doctor_name' => $this->sanitize($this->post('doctor_name','')),
            'schedule'    => $this->sanitize($this->post('schedule','')),
            'room'        => $this->sanitize($this->post('room','')),
            'image_url'   => $this->post('image_url',''),
            'sort_order'  => (int)$this->post('sort_order',0),
            'active'      => true,
        ]);
        $this->flash('success', 'Especialidad guardada.');
        $this->redirect('/admin/specialties');
    }

    public function deleteSpecialty(string $id): void {
        $this->requireRole('admin');
        Specialty::delete((int)$id);
        $this->flash('success', 'Especialidad eliminada.');
        $this->redirect('/admin/specialties');
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
//  SupervisorController
// ═══════════════════════════════════════════════════════════════════════════════
class SupervisorController extends Controller {

    public function dashboard(): void {
        $this->requireRole('admin','supervisor');
        $user     = $this->currentUser();
        $branchId = $user['branch_id'] ?? 1;
        $windows  = Window::statusView($branchId);
        $stats    = Ticket::todayStats($branchId);
        $queues   = Database::query('SELECT * FROM v_queue_status WHERE branch_id=$1 ORDER BY priority DESC', [$branchId]);
        $this->view('supervisor/dashboard', compact('user','windows','stats','queues'));
    }

    public function reports(): void {
        $this->requireRole('admin','supervisor');
        $user     = $this->currentUser();
        $branchId = $user['branch_id'] ?? 1;
        $date     = $this->get('date', date('Y-m-d'));

        $cashierStats = Database::query(
            "SELECT al.*, u.full_name, w.name AS window_name
             FROM attendance_logs al
             JOIN users u ON u.id = al.cashier_id
             LEFT JOIN windows w ON w.id = al.window_id
             WHERE al.branch_id=\$1 AND al.date=\$2
             ORDER BY al.tickets_served DESC",
            [$branchId, $date]
        );

        $hourlyData = Database::query(
            "SELECT EXTRACT(HOUR FROM issued_at) AS hour, COUNT(*) AS total
             FROM tickets WHERE branch_id=\$1 AND issued_at::date=\$2
             GROUP BY hour ORDER BY hour",
            [$branchId, $date]
        );

        $this->view('supervisor/reports', compact('user','cashierStats','hourlyData','date'));
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
//  CashierController
// ═══════════════════════════════════════════════════════════════════════════════
class CashierController extends Controller {

    public function index(): void {
        $this->requireRole('cashier','supervisor','admin');
        $user       = $this->currentUser();
        $assignment = Window::getCurrentAssignment($user['id']);
        if (!$assignment) {
            $this->redirect('/cashier/select-window');
            return;
        }
        $this->showDashboard($user, $assignment);
    }

    public function selectWindowForm(): void {
        $this->requireRole('cashier','supervisor','admin');
        $user     = $this->currentUser();
        $branchId = $user['branch_id'] ?? 1;

        // Show only windows NOT currently occupied by another cashier (or this cashier)
        $windows  = Database::query(
            "SELECT w.* FROM windows w
             LEFT JOIN window_assignments wa ON wa.window_id = w.id AND wa.active = TRUE
             WHERE w.branch_id=\$1 AND w.active=TRUE AND (wa.id IS NULL OR wa.user_id=\$2)
             ORDER BY w.number",
            [$branchId, $user['id']]
        );
        $this->view('cashier/select_window', compact('user','windows'));
    }

    public function assignWindow(): void {
        $this->requireRole('cashier','supervisor','admin');
        $user     = $this->currentUser();
        $windowId = (int)$this->post('window_id', 0);
        if ($windowId) {
            Window::assign($windowId, $user['id']);
            // Start shift if not started today
            Database::execute(
                "INSERT INTO attendance_logs (date, branch_id, cashier_id, window_id, shift_start)
                 VALUES (CURRENT_DATE,\$1,\$2,\$3,NOW())
                 ON CONFLICT (date, cashier_id) DO UPDATE SET window_id=EXCLUDED.window_id,
                 shift_start=COALESCE(attendance_logs.shift_start, NOW())",
                [$user['branch_id'], $user['id'], $windowId]
            );
        }
        $this->redirect('/cashier');
    }

    public function releaseWindow(): void {
        $this->requireRole('cashier','supervisor','admin');
        $user = $this->currentUser();
        Window::releaseUser($user['id']);
        // End shift
        Database::execute(
            "UPDATE attendance_logs SET shift_end=NOW() WHERE cashier_id=\$1 AND date=CURRENT_DATE",
            [$user['id']]
        );
        $this->redirect('/cashier/select-window');
    }

    private function showDashboard(array $user, array $assignment): void {
        $branchId = $user['branch_id'] ?? 1;
        $windowId = $assignment['window_id'];
        $svcIds   = Window::getServiceTypeIds($windowId);
        $waiting  = Ticket::getWaiting($branchId, $svcIds);
        $stats    = User::getCashierStats($user['id']);

        // Current ticket on this window
        $currentTicket = Database::queryOne(
            "SELECT t.*, pc.name AS cat_name, pc.color AS cat_color,
                    st.name AS service_name
             FROM tickets t
             JOIN patient_categories pc ON pc.id = t.category_id
             JOIN service_types st ON st.id = t.service_type_id
             WHERE t.window_id=\$1 AND t.status IN ('calling','serving')",
            [$windowId]
        );

        $services = ServiceType::all();
        $this->view('cashier/dashboard',
            compact('user','assignment','waiting','stats','currentTicket','services','svcIds'));
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
//  DispenserController
// ═══════════════════════════════════════════════════════════════════════════════
class DispenserController extends Controller {

    public function kiosk(): void {
        // Dispenser is public – no login needed; but we track branch by GET param
        $branchId   = (int)$this->get('branch', 1);
        $categories = PatientCategory::all();
        $services   = ServiceType::all();
        $config     = PrinterConfig::forBranch($branchId);
        $branch     = Branch::find($branchId);
        $this->view('dispenser/kiosk', compact('branchId','categories','services','config','branch'), 'blank');
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
//  DisplayController
// ═══════════════════════════════════════════════════════════════════════════════
class DisplayController extends Controller {

    public function screen(): void {
        // Display is public (no login)
        $branchId    = (int)$this->get('branch', 1);
        $branch      = Branch::find($branchId);
        $windows     = Window::allForBranch($branchId);
        $ads         = AdvertisingContent::forBranch($branchId);
        $specialties = Specialty::forBranch($branchId);
        $settings    = Database::query(
            'SELECT key, value FROM settings WHERE branch_id=$1', [$branchId]
        );
        $cfg = [];
        foreach ($settings as $s) $cfg[$s['key']] = $s['value'];

        // Last 5 calls
        $lastCalls = Database::query(
            "SELECT tc.id, t.ticket_number, w.number AS window_number, w.name AS window_name,
                    pc.color AS cat_color, pc.name AS cat_name, tc.called_at
             FROM ticket_calls tc
             JOIN tickets t ON t.id=tc.ticket_id
             JOIN windows w ON w.id=tc.window_id
             JOIN patient_categories pc ON pc.id=t.category_id
             WHERE t.branch_id=\$1
             ORDER BY tc.id DESC LIMIT 10",
            [$branchId]
        );

        $this->view('display/screen',
            compact('branchId','branch','windows','ads','specialties','cfg','lastCalls'), 'blank');
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
//  ApiController  (JSON AJAX endpoints)
// ═══════════════════════════════════════════════════════════════════════════════
class ApiController extends Controller {

    // ── Issue ticket (from dispenser) ─────────────────────────────────────────
    public function issueTicket(): void {
        $branchId   = (int)$this->post('branch_id', 1);
        $svcId      = (int)$this->post('service_type_id');
        $catId      = (int)$this->post('category_id');

        if (!$svcId || !$catId) { $this->json(['error' => 'Datos incompletos'], 400); return; }

        try {
            $ticket = Ticket::issue($branchId, $svcId, $catId);
            // Get wait info
            $waiting = count(Ticket::getWaiting($branchId, [$svcId]));
            $ticket['estimated_wait'] = $waiting * 5; // rough 5 min per ticket
            $this->json(['success' => true, 'ticket' => $ticket]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // ── Call next ticket (from cashier) ──────────────────────────────────────
    public function callNext(): void {
        $this->requireRole('cashier','supervisor','admin');
        $user       = $this->currentUser();
        $assignment = Window::getCurrentAssignment($user['id']);
        if (!$assignment) { $this->json(['error' => 'Sin caja asignada'], 400); return; }

        $ticket = Ticket::nextForWindow($assignment['window_id'], $user['branch_id'] ?? 1);
        if (!$ticket) { $this->json(['error' => 'No hay tickets en espera', 'empty' => true]); return; }

        $callId = Ticket::saveCall($ticket['id'], $assignment['window_id'], $user['id']);

        $this->json([
            'success'       => true,
            'ticket'        => $ticket,
            'call_id'       => $callId,
            'window_number' => $assignment['window_number'],
            'window_name'   => $assignment['window_name'],
        ]);
    }

    // ── Recall current ticket ─────────────────────────────────────────────────
    public function recall(): void {
        $this->requireRole('cashier','supervisor','admin');
        $user       = $this->currentUser();
        $assignment = Window::getCurrentAssignment($user['id']);
        if (!$assignment) { $this->json(['error' => 'Sin caja asignada'], 400); return; }

        $ticket = Ticket::recall($assignment['window_id'], $user['id']);
        if (!$ticket) { $this->json(['error' => 'No hay ticket activo']); return; }

        $callId = Ticket::saveCall($ticket['id'], $assignment['window_id'], $user['id']);

        $this->json([
            'success'       => true,
            'ticket'        => $ticket,
            'call_id'       => $callId,
            'window_number' => $assignment['window_number'],
            'window_name'   => $assignment['window_name'],
        ]);
    }

    // ── Mark as serving ───────────────────────────────────────────────────────
    public function serve(): void {
        $this->requireRole('cashier','supervisor','admin');
        $ticketId = (int)$this->post('ticket_id');
        if ($ticketId) Ticket::markServing($ticketId);
        $this->json(['success' => true]);
    }

    // ── Complete ticket ───────────────────────────────────────────────────────
    public function complete(): void {
        $this->requireRole('cashier','supervisor','admin');
        $ticketId = (int)$this->post('ticket_id');
        if ($ticketId) Ticket::complete($ticketId, $this->currentUser()['id']);
        $this->json(['success' => true]);
    }

    // ── No show ───────────────────────────────────────────────────────────────
    public function noShow(): void {
        $this->requireRole('cashier','supervisor','admin');
        $ticketId = (int)$this->post('ticket_id');
        if ($ticketId) Ticket::noShow($ticketId);
        $this->json(['success' => true]);
    }

    // ── Polling: last calls since last_id (for display screen) ────────────────
    public function lastCalls(): void {
        $branchId = (int)$this->get('branch_id', 1);
        $lastId   = (int)$this->get('last_id', 0);
        $calls    = Ticket::getCallsSince($branchId, $lastId);
        $stats    = Ticket::todayStats($branchId);
        $windows  = Window::statusView($branchId);
        $this->json(['calls' => $calls, 'stats' => $stats, 'windows' => $windows]);
    }

    // ── Waiting count (cashier sidebar refresh) ───────────────────────────────
    public function queueStatus(): void {
        $this->requireRole('cashier','supervisor','admin');
        $user       = $this->currentUser();
        $assignment = Window::getCurrentAssignment($user['id']);
        $svcIds     = $assignment ? Window::getServiceTypeIds($assignment['window_id']) : [];
        $waiting    = Ticket::getWaiting($user['branch_id'] ?? 1, $svcIds);
        $stats      = User::getCashierStats($user['id']);
        $this->json(['waiting' => $waiting, 'stats' => $stats]);
    }

    // ── Ticket print data ─────────────────────────────────────────────────────
    public function ticketPrint(string $ticketId): void {
        $ticket = Database::queryOne(
            "SELECT t.*, pc.name AS cat_name, pc.color AS cat_color,
                    st.name AS service_name, b.name AS branch_name
             FROM tickets t
             JOIN patient_categories pc ON pc.id=t.category_id
             JOIN service_types st ON st.id=t.service_type_id
             JOIN branches b ON b.id=t.branch_id
             WHERE t.id=\$1",
            [(int)$ticketId]
        );
        if (!$ticket) { $this->json(['error' => 'Ticket no encontrado'], 404); return; }
        $config = PrinterConfig::forBranch($ticket['branch_id']);
        $this->view('ticket/print', compact('ticket','config'), 'blank');
    }
}
