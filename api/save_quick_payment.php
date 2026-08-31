<?php
/**
 * Prime Dental Clinic Management System
 * API: Record Payment Action
 */

if (!defined('PRIME_DENTAL')) {
    define('PRIME_DENTAL', true);
}
if (!headers_sent()) {
    header('Content-Type: application/json');
}

require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_DIR . '/config/database.php';
require_once BASE_DIR . '/config/auth.php';
require_once BASE_DIR . '/config/helpers.php';

if (!Auth::check()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$patientId = (int)($_POST['patient_id'] ?? 0);
$visitId = !empty($_POST['visit_id']) ? (int)$_POST['visit_id'] : null;
$amount = (float)($_POST['amount'] ?? 0);
$paymentDate = trim($_POST['payment_date'] ?? date('Y-m-d'));
$paymentMethod = trim($_POST['payment_method'] ?? 'Cash');
$notes = trim($_POST['notes'] ?? '');

if ($patientId <= 0 || $amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Valid Patient ID and Payment Amount (> 0) are required.']);
    exit;
}

try {
    $db = getDB();
    $receiptNo = getNextReceiptNumber();

    $stmt = $db->prepare("
        INSERT INTO payments (receipt_no, patient_id, visit_id, payment_date, amount, payment_method, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $receiptNo,
        $patientId,
        $visitId,
        $paymentDate,
        $amount,
        $paymentMethod,
        $notes
    ]);
    $paymentId = $db->lastInsertId();

    $summary = getPatientFinancialSummary($patientId);

    echo json_encode([
        'success' => true,
        'payment_id' => $paymentId,
        'receipt_no' => $receiptNo,
        'financial_summary' => $summary,
        'message' => 'Payment of ' . formatCurrency($amount) . ' recorded successfully.'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
