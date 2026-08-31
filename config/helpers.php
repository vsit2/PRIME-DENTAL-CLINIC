<?php
/**
 * Prime Dental Clinic Management System
 * Helper Utilities & Formatting Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

// Escape HTML for XSS safety
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Format currency with Rupee symbol and Indian numbering format
function formatCurrency(float|int|string $amount): string {
    $num = (float)$amount;
    $isNegative = $num < 0;
    $num = abs($num);
    
    // Split integer and decimal parts
    $parts = explode('.', number_format($num, 2, '.', ''));
    $intPart = $parts[0];
    $decPart = $parts[1];
    
    // Indian currency comma placement (last 3, then groups of 2)
    if (strlen($intPart) > 3) {
        $lastThree = substr($intPart, -3);
        $remaining = substr($intPart, 0, -3);
        $remainingFormatted = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $remaining);
        $formattedInt = $remainingFormatted . ',' . $lastThree;
    } else {
        $formattedInt = $intPart;
    }
    
    $formatted = CURRENCY_SYMBOL . ' ' . $formattedInt;
    if ($decPart > 0) {
        $formatted .= '.' . $decPart;
    }
    
    return $isNegative ? '-' . $formatted : $formatted;
}

// Format standard dates
function formatDate(?string $date, string $format = 'd M Y'): string {
    if (empty($date) || $date === '0000-00-00') {
        return '—';
    }
    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return '—';
    }
}

// Format date and time
function formatDateTime(?string $dateTime, string $format = 'd M Y, h:i A'): string {
    if (empty($dateTime) || $dateTime === '0000-00-00 00:00:00') {
        return '—';
    }
    try {
        $dt = new DateTime($dateTime);
        return $dt->format($format);
    } catch (Exception $e) {
        return '—';
    }
}

// Calculate age from DOB
function calculateAge(?string $dob): int {
    if (empty($dob)) return 0;
    try {
        $bday = new DateTime($dob);
        $today = new DateTime('today');
        return $bday->diff($today)->y;
    } catch (Exception $e) {
        return 0;
    }
}

// Generate the next unique Registration Number (e.g., PDC-0001, PDC-0002)
function getNextRegistrationNumber(): string {
    $db = getDB();
    $stmt = $db->query("SELECT registration_no FROM patients WHERE registration_no LIKE 'PDC-%' ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch();
    
    if ($row && preg_match('/PDC-(\d+)/', $row['registration_no'], $matches)) {
        $nextNum = ((int)$matches[1]) + 1;
    } else {
        // Count total patients if format differed
        $count = (int)$db->query("SELECT COUNT(*) FROM patients")->fetchColumn();
        $nextNum = $count + 1;
    }
    return sprintf("PDC-%04d", $nextNum);
}

// Generate the next Receipt Number (e.g. REC-0001)
function getNextReceiptNumber(): string {
    $db = getDB();
    $stmt = $db->query("SELECT receipt_no FROM payments WHERE receipt_no LIKE 'REC-%' ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch();
    
    if ($row && preg_match('/REC-(\d+)/', $row['receipt_no'], $matches)) {
        $nextNum = ((int)$matches[1]) + 1;
    } else {
        $count = (int)$db->query("SELECT COUNT(*) FROM payments")->fetchColumn();
        $nextNum = $count + 1;
    }
    return sprintf("REC-%04d", $nextNum);
}

// Get financial balances for a specific patient
function getPatientFinancialSummary(int $patientId): array {
    $db = getDB();
    
    // Total Billing (Sum of all treatment costs)
    $stmt1 = $db->prepare("SELECT COALESCE(SUM(treatment_cost), 0) AS total_bill FROM visits WHERE patient_id = ?");
    $stmt1->execute([$patientId]);
    $totalBill = (float)$stmt1->fetchColumn();
    
    // Total Payments Made
    $stmt2 = $db->prepare("SELECT COALESCE(SUM(amount), 0) AS total_paid FROM payments WHERE patient_id = ?");
    $stmt2->execute([$patientId]);
    $totalPaid = (float)$stmt2->fetchColumn();
    
    $balance = max(0, $totalBill - $totalPaid);
    $status = ($totalBill > 0 && $balance <= 0) ? 'Paid in Full' : ($balance > 0 ? 'Balance Due' : 'No Charges');
    
    return [
        'total_bill' => $totalBill,
        'total_paid' => $totalPaid,
        'balance' => $balance,
        'status' => $status
    ];
}

// Get global clinic financial summary
function getClinicFinancialSummary(): array {
    $db = getDB();
    
    $totalPatients = (int)$db->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    $totalVisits = (int)$db->query("SELECT COUNT(*) FROM visits")->fetchColumn();
    $totalBilling = (float)$db->query("SELECT COALESCE(SUM(treatment_cost), 0) FROM visits")->fetchColumn();
    $totalCollected = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments")->fetchColumn();
    $totalOutstanding = max(0, $totalBilling - $totalCollected);
    
    $today = date('Y-m-d');
    
    // Today's Patients & Visits
    $stmtTodayVisits = $db->prepare("SELECT COUNT(DISTINCT patient_id) FROM visits WHERE visit_date = ?");
    $stmtTodayVisits->execute([$today]);
    $todayPatients = (int)$stmtTodayVisits->fetchColumn();
    
    // Today's Collection
    $stmtTodayPaid = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_date = ?");
    $stmtTodayPaid->execute([$today]);
    $todayCollection = (float)$stmtTodayPaid->fetchColumn();
    
    // New Patients Registered This Month
    $firstDayMonth = date('Y-m-01');
    $stmtMonthPatients = $db->prepare("SELECT COUNT(*) FROM patients WHERE reg_date >= ?");
    $stmtMonthPatients->execute([$firstDayMonth]);
    $newPatientsMonth = (int)$stmtMonthPatients->fetchColumn();
    
    // Month's Collection
    $stmtMonthPaid = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_date >= ?");
    $stmtMonthPaid->execute([$firstDayMonth]);
    $monthCollection = (float)$stmtMonthPaid->fetchColumn();

    return [
        'total_patients' => $totalPatients,
        'total_visits' => $totalVisits,
        'total_billing' => $totalBilling,
        'total_collected' => $totalCollected,
        'total_outstanding' => $totalOutstanding,
        'today_patients' => $todayPatients,
        'today_collection' => $todayCollection,
        'new_patients_month' => $newPatientsMonth,
        'month_collection' => $monthCollection
    ];
}

// Fetch Clinic Settings
function getClinicSettings(): array {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM clinic_settings WHERE id = 1 LIMIT 1");
    $settings = $stmt->fetch();
    if (!$settings) {
        return [
            'clinic_name' => CLINIC_NAME,
            'tagline' => CLINIC_TAGLINE,
            'dentist_name' => DENTIST_NAME,
            'dentist_qualification' => DENTIST_QUALIFICATION,
            'reg_no' => DENTIST_REG_NO,
            'phone' => CLINIC_PHONE,
            'email' => CLINIC_EMAIL,
            'address' => CLINIC_FULL_ADDRESS,
            'currency_symbol' => CURRENCY_SYMBOL
        ];
    }
    return $settings;
}

// Flash Message Alerts
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// CSRF Token Helpers
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool {
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
