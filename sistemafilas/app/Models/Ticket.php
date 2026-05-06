<?php

class Ticket {

    // ─── Issue a new ticket ──────────────────────────────────────────────────
    public static function issue(int $branchId, int $serviceTypeId, int $categoryId): array {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // Lock and increment daily counter
            $counter = $db->prepare(
                'INSERT INTO daily_counters (branch_id, service_type_id, category_id, date, last_number)
                 VALUES ($1, $2, $3, CURRENT_DATE, 1)
                 ON CONFLICT (branch_id, service_type_id, category_id, date)
                 DO UPDATE SET last_number = daily_counters.last_number + 1
                 RETURNING last_number'
            );
            $counter->execute([$branchId, $serviceTypeId, $categoryId]);
            $num = $counter->fetchColumn();

            // Build ticket number: e.g.  G-SRV-001
            $cat  = Database::queryOne('SELECT code FROM patient_categories WHERE id = $1', [$categoryId]);
            $svc  = Database::queryOne('SELECT code FROM service_types WHERE id = $1', [$serviceTypeId]);
            $ticketNum = sprintf('%s-%s-%03d', $cat['code'], $svc['code'], $num);

            $id = Database::execute(
                'INSERT INTO tickets (branch_id, service_type_id, category_id, ticket_number)
                 VALUES ($1, $2, $3, $4) RETURNING id',
                [$branchId, $serviceTypeId, $categoryId, $ticketNum]
            );

            $db->commit();

            return [
                'id'            => $id,
                'ticket_number' => $ticketNum,
                'service_code'  => $svc['code'],
                'cat_code'      => $cat['code'],
                'issued_at'     => date('Y-m-d H:i:s'),
            ];
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    // ─── Get next ticket for a window ────────────────────────────────────────
    public static function nextForWindow(int $windowId, int $branchId): ?array {
        // Get service types for this window
        $services = Database::query(
            'SELECT service_type_id FROM window_service_types WHERE window_id = $1',
            [$windowId]
        );
        if (empty($services)) return null;

        $svcIds = array_column($services, 'service_type_id');
        $in     = implode(',', array_fill(1, count($svcIds), '?'));
        $params = array_merge([$branchId], $svcIds);

        // Priority: higher category.priority first, then oldest issued_at
        $placeholders = implode(',', array_map(fn($i) => '$' . ($i + 1), array_keys($svcIds)));
        $sql = "SELECT t.*, pc.priority, pc.name AS cat_name, pc.color AS cat_color,
                       st.name AS service_name, st.code AS service_code
                FROM tickets t
                JOIN patient_categories pc ON pc.id = t.category_id
                JOIN service_types st ON st.id = t.service_type_id
                WHERE t.branch_id = \$1
                  AND t.service_type_id IN ($placeholders)
                  AND t.status = 'waiting'
                ORDER BY pc.priority DESC, t.issued_at ASC
                LIMIT 1
                FOR UPDATE SKIP LOCKED";

        $db   = Database::getInstance();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $ticket = $stmt->fetch();
            if (!$ticket) { $db->commit(); return null; }

            // Mark as calling
            $db->prepare(
                "UPDATE tickets SET status = 'calling', window_id = \$1, called_at = NOW(),
                  call_count = call_count + 1 WHERE id = \$2"
            )->execute([$windowId, $ticket['id']]);

            $db->commit();
            return $ticket;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    // ─── Recall the current ticket on a window ───────────────────────────────
    public static function recall(int $windowId, int $cashierId): ?array {
        $ticket = Database::queryOne(
            "SELECT t.*, w.name AS window_name
             FROM tickets t
             JOIN windows w ON w.id = t.window_id
             WHERE t.window_id = \$1 AND t.status IN ('calling','serving')",
            [$windowId]
        );
        if (!$ticket) return null;

        Database::execute(
            "UPDATE tickets SET call_count = call_count + 1, called_at = NOW() WHERE id = \$1",
            [$ticket['id']]
        );

        self::logCall($ticket['id'], $windowId, $cashierId, $ticket['call_count'] + 1);
        return $ticket;
    }

    // ─── Mark ticket as serving ──────────────────────────────────────────────
    public static function markServing(int $ticketId): void {
        Database::execute(
            "UPDATE tickets SET status='serving', served_at=NOW() WHERE id=\$1",
            [$ticketId]
        );
    }

    // ─── Complete ticket ─────────────────────────────────────────────────────
    public static function complete(int $ticketId, int $cashierId): void {
        $t = Database::queryOne('SELECT * FROM tickets WHERE id=$1', [$ticketId]);
        if (!$t) return;

        $waitSecs    = $t['called_at']  ? strtotime($t['called_at'])  - strtotime($t['issued_at'])   : null;
        $serviceSecs = $t['served_at']  ? time() - strtotime($t['served_at']) : null;

        Database::execute(
            "UPDATE tickets SET status='served', completed_at=NOW(),
             wait_seconds=\$1, service_seconds=\$2, cashier_id=\$3 WHERE id=\$4",
            [$waitSecs, $serviceSecs, $cashierId, $ticketId]
        );

        self::updateAttendanceLog($t['branch_id'], $cashierId, $t['window_id']);
    }

    // ─── No-show ─────────────────────────────────────────────────────────────
    public static function noShow(int $ticketId): void {
        Database::execute(
            "UPDATE tickets SET status='no_show', completed_at=NOW() WHERE id=\$1",
            [$ticketId]
        );
    }

    // ─── Log call ────────────────────────────────────────────────────────────
    private static function logCall(int $ticketId, int $windowId, int $cashierId, int $callNum): void {
        Database::execute(
            'INSERT INTO ticket_calls (ticket_id, window_id, cashier_id, call_num)
             VALUES ($1,$2,$3,$4)',
            [$ticketId, $windowId, $cashierId, $callNum]
        );
    }

    // ─── Save call + get last call for display polling ───────────────────────
    public static function saveCall(int $ticketId, int $windowId, int $cashierId): int {
        return (int) Database::execute(
            'INSERT INTO ticket_calls (ticket_id, window_id, cashier_id)
             VALUES ($1,$2,$3) RETURNING id',
            [$ticketId, $windowId, $cashierId]
        );
    }

    public static function getCallsSince(int $branchId, int $lastId): array {
        return Database::query(
            "SELECT tc.id, tc.called_at, tc.call_num,
                    t.ticket_number, t.id AS ticket_id,
                    w.number AS window_number, w.name AS window_name,
                    pc.name AS cat_name, pc.color AS cat_color,
                    st.name AS service_name
             FROM ticket_calls tc
             JOIN tickets t  ON t.id  = tc.ticket_id
             JOIN windows w  ON w.id  = tc.window_id
             JOIN patient_categories pc ON pc.id = t.category_id
             JOIN service_types st ON st.id = t.service_type_id
             WHERE t.branch_id = \$1 AND tc.id > \$2
             ORDER BY tc.id ASC",
            [$branchId, $lastId]
        );
    }

    // ─── Get waiting tickets ─────────────────────────────────────────────────
    public static function getWaiting(int $branchId, array $serviceTypeIds = []): array {
        $where = '';
        $params = [$branchId];
        if ($serviceTypeIds) {
            $phs = implode(',', array_map(fn($i) => '$' . ($i + 2), array_keys($serviceTypeIds)));
            $where = " AND t.service_type_id IN ($phs)";
            $params = array_merge($params, $serviceTypeIds);
        }
        return Database::query(
            "SELECT t.*, pc.name AS cat_name, pc.color AS cat_color, pc.priority,
                    st.name AS service_name, st.code AS service_code
             FROM tickets t
             JOIN patient_categories pc ON pc.id = t.category_id
             JOIN service_types st ON st.id = t.service_type_id
             WHERE t.branch_id = \$1 AND t.status = 'waiting' $where
             ORDER BY pc.priority DESC, t.issued_at ASC",
            $params
        );
    }

    // ─── Update attendance log ────────────────────────────────────────────────
    private static function updateAttendanceLog(int $branchId, int $cashierId, ?int $windowId): void {
        Database::execute(
            "INSERT INTO attendance_logs (date, branch_id, cashier_id, window_id, tickets_served)
             VALUES (CURRENT_DATE, \$1, \$2, \$3, 1)
             ON CONFLICT (date, cashier_id) DO UPDATE
             SET tickets_served = attendance_logs.tickets_served + 1,
                 window_id = EXCLUDED.window_id",
            [$branchId, $cashierId, $windowId]
        );
    }

    // ─── Stats for today ─────────────────────────────────────────────────────
    public static function todayStats(int $branchId): array {
        return Database::queryOne(
            "SELECT
               COUNT(*) FILTER (WHERE status='waiting')  AS waiting,
               COUNT(*) FILTER (WHERE status='calling')  AS calling,
               COUNT(*) FILTER (WHERE status='serving')  AS serving,
               COUNT(*) FILTER (WHERE status='served')   AS served,
               COUNT(*) FILTER (WHERE status='no_show')  AS no_show,
               COUNT(*)                                  AS total
             FROM tickets
             WHERE branch_id=\$1 AND issued_at::date = CURRENT_DATE",
            [$branchId]
        ) ?? [];
    }
}
