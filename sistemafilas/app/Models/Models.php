<?php

// ─── Window Model ─────────────────────────────────────────────────────────────
class Window {
    public static function allForBranch(int $branchId): array {
        return Database::query(
            'SELECT w.*, STRING_AGG(st.name, \', \') AS services
             FROM windows w
             LEFT JOIN window_service_types wst ON wst.window_id = w.id
             LEFT JOIN service_types st ON st.id = wst.service_type_id
             WHERE w.branch_id = $1 AND w.active = TRUE
             GROUP BY w.id ORDER BY w.number',
            [$branchId]
        );
    }

    public static function find(int $id): ?array {
        return Database::queryOne('SELECT * FROM windows WHERE id=$1', [$id]);
    }

    public static function getServiceTypeIds(int $windowId): array {
        $rows = Database::query(
            'SELECT service_type_id FROM window_service_types WHERE window_id=$1', [$windowId]
        );
        return array_column($rows, 'service_type_id');
    }

    public static function statusView(int $branchId): array {
        return Database::query(
            'SELECT * FROM v_window_status WHERE branch_id=$1 ORDER BY number',
            [$branchId]
        );
    }

    // Assign cashier to window (release any existing)
    public static function assign(int $windowId, int $userId): void {
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            // Release user from any previous window
            $db->prepare(
                "UPDATE window_assignments SET active=FALSE, released_at=NOW() WHERE user_id=\$1 AND active=TRUE"
            )->execute([$userId]);

            // Release whoever was on this window
            $db->prepare(
                "UPDATE window_assignments SET active=FALSE, released_at=NOW() WHERE window_id=\$1 AND active=TRUE"
            )->execute([$windowId]);

            // New assignment
            $db->prepare(
                "INSERT INTO window_assignments (window_id, user_id) VALUES (\$1, \$2)"
            )->execute([$windowId, $userId]);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack(); throw $e;
        }
    }

    public static function releaseUser(int $userId): void {
        Database::execute(
            "UPDATE window_assignments SET active=FALSE, released_at=NOW() WHERE user_id=\$1 AND active=TRUE",
            [$userId]
        );
    }

    public static function getCurrentAssignment(int $userId): ?array {
        return Database::queryOne(
            "SELECT wa.*, w.name AS window_name, w.number AS window_number
             FROM window_assignments wa
             JOIN windows w ON w.id = wa.window_id
             WHERE wa.user_id=\$1 AND wa.active=TRUE",
            [$userId]
        );
    }
}

// ─── User Model ───────────────────────────────────────────────────────────────
class User {
    public static function all(): array {
        return Database::query(
            'SELECT u.*, r.name AS role_name, b.name AS branch_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN branches b ON b.id = u.branch_id
             ORDER BY u.id'
        );
    }

    public static function allForBranch(int $branchId): array {
        return Database::query(
            'SELECT u.*, r.name AS role_name
             FROM users u JOIN roles r ON r.id=u.role_id
             WHERE u.branch_id=$1 ORDER BY u.full_name',
            [$branchId]
        );
    }

    public static function find(int $id): ?array {
        return Database::queryOne(
            'SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=$1',
            [$id]
        );
    }

    public static function create(array $data): int {
        return (int) Database::execute(
            "INSERT INTO users (branch_id, role_id, username, password_hash, full_name, email)
             VALUES (\$1,\$2,\$3,\$4,\$5,\$6) RETURNING id",
            [
                $data['branch_id'], $data['role_id'], $data['username'],
                password_hash($data['password'], PASSWORD_BCRYPT),
                $data['full_name'], $data['email'] ?? null
            ]
        );
    }

    public static function update(int $id, array $data): void {
        if (!empty($data['password'])) {
            Database::execute(
                "UPDATE users SET full_name=\$1, email=\$2, branch_id=\$3, role_id=\$4,
                  password_hash=\$5, active=\$6 WHERE id=\$7",
                [$data['full_name'], $data['email'], $data['branch_id'], $data['role_id'],
                 password_hash($data['password'], PASSWORD_BCRYPT), $data['active'] ?? true, $id]
            );
        } else {
            Database::execute(
                "UPDATE users SET full_name=\$1, email=\$2, branch_id=\$3, role_id=\$4, active=\$5 WHERE id=\$6",
                [$data['full_name'], $data['email'], $data['branch_id'], $data['role_id'],
                 $data['active'] ?? true, $id]
            );
        }
    }

    public static function delete(int $id): void {
        Database::execute("UPDATE users SET active=FALSE WHERE id=\$1", [$id]);
    }

    public static function getCashierStats(int $cashierId, string $date = ''): array {
        $date = $date ?: date('Y-m-d');
        return Database::queryOne(
            "SELECT COALESCE(tickets_served,0) AS served,
                    COALESCE(tickets_cancelled,0) AS cancelled,
                    COALESCE(avg_service_seconds,0) AS avg_seconds,
                    shift_start, shift_end
             FROM attendance_logs WHERE cashier_id=\$1 AND date=\$2",
            [$cashierId, $date]
        ) ?? ['served'=>0,'cancelled'=>0,'avg_seconds'=>0,'shift_start'=>null,'shift_end'=>null];
    }
}

// ─── Branch Model ─────────────────────────────────────────────────────────────
class Branch {
    public static function all(): array {
        return Database::query('SELECT * FROM branches WHERE active=TRUE ORDER BY name');
    }

    public static function find(int $id): ?array {
        return Database::queryOne('SELECT * FROM branches WHERE id=$1', [$id]);
    }

    public static function create(array $d): int {
        return (int) Database::execute(
            "INSERT INTO branches (name, address, phone) VALUES (\$1,\$2,\$3) RETURNING id",
            [$d['name'], $d['address'], $d['phone']]
        );
    }

    public static function update(int $id, array $d): void {
        Database::execute(
            "UPDATE branches SET name=\$1, address=\$2, phone=\$3, active=\$4 WHERE id=\$5",
            [$d['name'], $d['address'], $d['phone'], $d['active'] ?? true, $id]
        );
    }
}

// ─── ServiceType Model ────────────────────────────────────────────────────────
class ServiceType {
    public static function all(): array {
        return Database::query('SELECT * FROM service_types WHERE active=TRUE ORDER BY name');
    }
}

// ─── PatientCategory Model ────────────────────────────────────────────────────
class PatientCategory {
    public static function all(): array {
        return Database::query('SELECT * FROM patient_categories WHERE active=TRUE ORDER BY priority DESC');
    }
}

// ─── Specialty Model ─────────────────────────────────────────────────────────
class Specialty {
    public static function forBranch(int $branchId): array {
        return Database::query(
            'SELECT * FROM specialties WHERE branch_id=$1 AND active=TRUE ORDER BY sort_order, name',
            [$branchId]
        );
    }

    public static function save(int $branchId, array $d): int {
        if (!empty($d['id'])) {
            Database::execute(
                "UPDATE specialties SET name=\$1, doctor_name=\$2, schedule=\$3, room=\$4,
                  image_url=\$5, sort_order=\$6, active=\$7 WHERE id=\$8",
                [$d['name'], $d['doctor_name'], $d['schedule'], $d['room'],
                 $d['image_url'], $d['sort_order']??0, $d['active']??true, $d['id']]
            );
            return $d['id'];
        }
        return (int) Database::execute(
            "INSERT INTO specialties (branch_id, name, doctor_name, schedule, room, image_url, sort_order)
             VALUES (\$1,\$2,\$3,\$4,\$5,\$6,\$7) RETURNING id",
            [$branchId, $d['name'], $d['doctor_name'], $d['schedule'], $d['room'],
             $d['image_url'], $d['sort_order']??0]
        );
    }

    public static function delete(int $id): void {
        Database::execute("DELETE FROM specialties WHERE id=\$1", [$id]);
    }
}

// ─── PrinterConfig Model ─────────────────────────────────────────────────────
class PrinterConfig {
    public static function forBranch(int $branchId): array {
        $cfg = Database::queryOne('SELECT * FROM printer_configs WHERE branch_id=$1', [$branchId]);
        if (!$cfg) {
            Database::execute(
                'INSERT INTO printer_configs (branch_id) VALUES ($1)', [$branchId]
            );
            $cfg = Database::queryOne('SELECT * FROM printer_configs WHERE branch_id=$1', [$branchId]);
        }
        return $cfg;
    }

    public static function save(int $branchId, array $d): void {
        Database::execute(
            "INSERT INTO printer_configs (branch_id, printer_name, paper_width, font_size, header_text, footer_text, show_logo)
             VALUES (\$1,\$2,\$3,\$4,\$5,\$6,\$7)
             ON CONFLICT (branch_id) DO UPDATE SET
               printer_name=EXCLUDED.printer_name, paper_width=EXCLUDED.paper_width,
               font_size=EXCLUDED.font_size, header_text=EXCLUDED.header_text,
               footer_text=EXCLUDED.footer_text, show_logo=EXCLUDED.show_logo,
               updated_at=NOW()",
            [$branchId, $d['printer_name'], $d['paper_width'], $d['font_size'],
             $d['header_text'], $d['footer_text'], isset($d['show_logo'])]
        );
    }
}

// ─── AdvertisingContent Model ─────────────────────────────────────────────────
class AdvertisingContent {
    public static function forBranch(int $branchId): array {
        return Database::query(
            'SELECT * FROM advertising_content WHERE branch_id=$1 AND active=TRUE ORDER BY sort_order, id',
            [$branchId]
        );
    }

    public static function allForBranch(int $branchId): array {
        return Database::query(
            'SELECT * FROM advertising_content WHERE branch_id=$1 ORDER BY sort_order, id',
            [$branchId]
        );
    }

    public static function save(int $branchId, array $d): void {
        if (!empty($d['id'])) {
            Database::execute(
                "UPDATE advertising_content SET title=\$1, type=\$2, url=\$3, duration=\$4, sort_order=\$5, active=\$6
                 WHERE id=\$7",
                [$d['title'], $d['type'], $d['url'], $d['duration'], $d['sort_order']??0, $d['active']??true, $d['id']]
            );
        } else {
            Database::execute(
                "INSERT INTO advertising_content (branch_id, type, title, url, duration, sort_order)
                 VALUES (\$1,\$2,\$3,\$4,\$5,\$6)",
                [$branchId, $d['type'], $d['title'], $d['url'], $d['duration']??10, $d['sort_order']??0]
            );
        }
    }

    public static function delete(int $id): void {
        Database::execute("DELETE FROM advertising_content WHERE id=\$1", [$id]);
    }
}

// ─── Role Model ───────────────────────────────────────────────────────────────
class Role {
    public static function all(): array {
        return Database::query('SELECT * FROM roles ORDER BY id');
    }
}
