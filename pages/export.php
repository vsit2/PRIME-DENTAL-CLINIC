<?php
/**
 * Prime Dental Clinic Management System
 * Data Export & SQL Backup Engine
 */

define('PRIME_DENTAL', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_DIR . '/config/database.php';
require_once BASE_DIR . '/config/auth.php';
require_once BASE_DIR . '/config/helpers.php';

Auth::requireAuth();

$type = trim($_GET['type'] ?? 'patients');
$db = getDB();
$timestamp = date('Y-m-d_H-i-s');

if ($type === 'patients') {
    // Export All Patients CSV
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=prime_dental_patients_{$timestamp}.csv");

    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel
    fputs($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        'Registration No', 'Reg Date', 'First Name', 'Middle Name', 'Last Name',
        'Gender', 'Age', 'DOB', 'Mobile', 'Email', 'Place of Work', 'Education',
        'Address', 'Physician Name', 'Physician Contact', 'Emergency Contact',
        'Total Billing (INR)', 'Total Paid (INR)', 'Balance (INR)', 'Status'
    ]);

    $sql = "
        SELECT 
            p.*,
            COALESCE(SUM(v.treatment_cost), 0) AS total_bill,
            COALESCE(pay.total_paid, 0) AS total_paid
        FROM patients p
        LEFT JOIN visits v ON p.id = v.patient_id
        LEFT JOIN (
            SELECT patient_id, SUM(amount) AS total_paid 
            FROM payments 
            GROUP BY patient_id
        ) pay ON p.id = pay.patient_id
        GROUP BY p.id
        ORDER BY p.id ASC
    ";
    $patients = $db->query($sql)->fetchAll();

    foreach ($patients as $p) {
        $totalBill = (float)$p['total_bill'];
        $totalPaid = (float)$p['total_paid'];
        $balance = max(0, $totalBill - $totalPaid);
        $status = ($totalBill > 0 && $balance <= 0) ? 'Paid in Full' : ($balance > 0 ? 'Balance Due' : 'No Charges');

        fputcsv($output, [
            $p['registration_no'],
            $p['reg_date'],
            $p['first_name'],
            $p['middle_name'],
            $p['last_name'],
            $p['gender'],
            $p['age'],
            $p['dob'],
            $p['mobile'],
            $p['email'],
            $p['place_of_work'],
            $p['education'],
            $p['address'],
            $p['physician_name'],
            $p['physician_contact'],
            $p['emergency_contact'],
            $totalBill,
            $totalPaid,
            $balance,
            $status
        ]);
    }
    fclose($output);
    exit;

} elseif ($type === 'payments') {
    // Export All Payments CSV
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=prime_dental_payments_{$timestamp}.csv");

    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        'Receipt No', 'Payment Date', 'Registration No', 'Patient Name', 'Mobile',
        'Payment Method', 'Amount (INR)', 'Treatment / Notes'
    ]);

    $sql = "
        SELECT pay.*, p.registration_no, p.first_name, p.last_name, p.mobile, v.treatment
        FROM payments pay
        JOIN patients p ON pay.patient_id = p.id
        LEFT JOIN visits v ON pay.visit_id = v.id
        ORDER BY pay.id ASC
    ";
    $payments = $db->query($sql)->fetchAll();

    foreach ($payments as $pay) {
        fputcsv($output, [
            $pay['receipt_no'],
            $pay['payment_date'],
            $pay['registration_no'],
            $pay['first_name'] . ' ' . $pay['last_name'],
            $pay['mobile'],
            $pay['payment_method'],
            $pay['amount'],
            $pay['treatment'] ?: $pay['notes']
        ]);
    }
    fclose($output);
    exit;

} elseif ($type === 'outstanding') {
    // Export Outstanding Dues CSV
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=prime_dental_outstanding_dues_{$timestamp}.csv");

    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        'Registration No', 'Patient Name', 'Mobile', 'Total Billing (INR)',
        'Total Paid (INR)', 'Outstanding Balance (INR)'
    ]);

    $sql = "
        SELECT 
            p.registration_no,
            p.first_name,
            p.last_name,
            p.mobile,
            COALESCE(SUM(v.treatment_cost), 0) AS total_bill,
            COALESCE(pay.total_paid, 0) AS total_paid,
            (COALESCE(SUM(v.treatment_cost), 0) - COALESCE(pay.total_paid, 0)) AS balance
        FROM patients p
        LEFT JOIN visits v ON p.id = v.patient_id
        LEFT JOIN (
            SELECT patient_id, SUM(amount) AS total_paid 
            FROM payments 
            GROUP BY patient_id
        ) pay ON p.id = pay.patient_id
        GROUP BY p.id
        HAVING balance > 0
        ORDER BY balance DESC
    ";
    $dues = $db->query($sql)->fetchAll();

    foreach ($dues as $d) {
        fputcsv($output, [
            $d['registration_no'],
            $d['first_name'] . ' ' . $d['last_name'],
            $d['mobile'],
            $d['total_bill'],
            $d['total_paid'],
            $d['balance']
        ]);
    }
    fclose($output);
    exit;

} elseif ($type === 'sql') {
    // Generate SQL Dump Backup
    header('Content-Type: application/sql');
    header("Content-Disposition: attachment; filename=prime_dental_backup_{$timestamp}.sql");

    $tables = ['clinic_settings', 'users', 'patients', 'medical_history', 'visits', 'payments'];
    
    echo "-- ========================================================\n";
    echo "-- PRIME DENTAL CLINIC - Full Database SQL Backup\n";
    echo "-- Generated At: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Dentist: " . DENTIST_NAME . " (" . DENTIST_REG_NO . ")\n";
    echo "-- ========================================================\n\n";
    echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        // Table structure
        $createStmt = $db->query("SHOW CREATE TABLE `{$table}`")->fetch();
        echo "DROP TABLE IF EXISTS `{$table}`;\n";
        echo $createStmt['Create Table'] . ";\n\n";

        // Table data
        $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $fields = array_keys($row);
                $values = array_map(function($val) use ($db) {
                    if ($val === null) return 'NULL';
                    return $db->quote($val);
                }, array_values($row));

                echo "INSERT INTO `{$table}` (`" . implode('`, `', $fields) . "`) VALUES (" . implode(', ', $values) . ");\n";
            }
            echo "\n";
        }
    }

    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    exit;
}
