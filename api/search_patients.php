<?php
/**
 * Prime Dental Clinic Management System
 * API: Real-time Instant Patient Search Endpoint
 */

if (!defined('PRIME_DENTAL')) {
    define('PRIME_DENTAL', true);
}
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_DIR . '/config/database.php';
require_once BASE_DIR . '/config/auth.php';
require_once BASE_DIR . '/config/helpers.php';

// Auth check
if (!Auth::check()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$query = trim($_GET['q'] ?? '');

if ($query === '') {
    echo json_encode(['success' => true, 'patients' => []]);
    exit;
}

try {
    $db = getDB();
    $searchTerm = "%" . mb_strtolower($query, 'UTF-8') . "%";
    $exactId = is_numeric($query) ? (int)$query : -1;

    $sql = "
        SELECT 
            p.id, 
            p.registration_no, 
            p.reg_date,
            p.created_at,
            p.first_name, 
            p.middle_name, 
            p.last_name, 
            p.age, 
            p.gender, 
            p.mobile, 
            p.place_of_work,
            COALESCE(SUM(v.treatment_cost), 0) AS total_bill,
            COALESCE(pay.total_paid, 0) AS total_paid
        FROM patients p
        LEFT JOIN visits v ON p.id = v.patient_id
        LEFT JOIN (
            SELECT patient_id, SUM(amount) AS total_paid 
            FROM payments 
            GROUP BY patient_id
        ) pay ON p.id = pay.patient_id
        WHERE 
            LOWER(p.registration_no) LIKE ? OR
            p.id = ? OR
            LOWER(p.first_name) LIKE ? OR
            LOWER(p.last_name) LIKE ? OR
            (p.middle_name IS NOT NULL AND LOWER(p.middle_name) LIKE ?) OR
            LOWER(CONCAT(p.first_name, ' ', p.last_name)) LIKE ? OR
            LOWER(CONCAT(p.first_name, ' ', COALESCE(p.middle_name, ''), ' ', p.last_name)) LIKE ? OR
            LOWER(CONCAT(p.first_name, ' ', IFNULL(p.middle_name, ''), ' ', p.last_name)) LIKE ? OR
            p.mobile LIKE ?
        GROUP BY p.id
        ORDER BY p.id DESC
        LIMIT 15
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        $searchTerm,
        $exactId,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm
    ]);
    $patients = $stmt->fetchAll();

    $formatted = [];
    foreach ($patients as $p) {
        $fullName = trim($p['first_name'] . ' ' . ($p['middle_name'] ? $p['middle_name'] . ' ' : '') . $p['last_name']);
        $totalBill = (float)$p['total_bill'];
        $totalPaid = (float)$p['total_paid'];
        $balance = max(0, $totalBill - $totalPaid);
        $regDateFormatted = formatDate($p['reg_date'] ?: substr($p['created_at'], 0, 10));

        $formatted[] = [
            'id' => (int)$p['id'],
            'registration_no' => $p['registration_no'],
            'full_name' => $fullName,
            'reg_date' => $regDateFormatted,
            'age' => (int)$p['age'],
            'gender' => $p['gender'],
            'mobile' => $p['mobile'],
            'place_of_work' => $p['place_of_work'] ?? '',
            'total_bill' => $totalBill,
            'total_paid' => $totalPaid,
            'balance' => $balance
        ];
    }

    echo json_encode(['success' => true, 'patients' => $formatted]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
