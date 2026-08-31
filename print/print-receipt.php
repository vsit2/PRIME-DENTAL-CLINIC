<?php
/**
 * Prime Dental Clinic Management System
 * Official Printable Payment Receipt
 */

define('PRIME_DENTAL', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_DIR . '/config/database.php';
require_once BASE_DIR . '/config/auth.php';
require_once BASE_DIR . '/config/helpers.php';

Auth::requireAuth();

$paymentId = (int)($_GET['id'] ?? 0);
if ($paymentId <= 0) die("Invalid Payment ID.");

$db = getDB();
$clinic = getClinicSettings();

// Fetch Payment Details
$stmtPay = $db->prepare("
    SELECT 
        p.*,
        pt.registration_no,
        pt.first_name,
        pt.middle_name,
        pt.last_name,
        pt.mobile,
        pt.address,
        v.treatment,
        v.treatment_cost,
        v.visit_date
    FROM payments p
    JOIN patients pt ON p.patient_id = pt.id
    LEFT JOIN visits v ON p.visit_id = v.id
    WHERE p.id = ?
    LIMIT 1
");
$stmtPay->execute([$paymentId]);
$payment = $stmtPay->fetch();
if (!$payment) die("Payment record not found.");

$patientId = (int)$payment['patient_id'];
$fullName = trim($payment['first_name'] . ' ' . ($payment['middle_name'] ? $payment['middle_name'] . ' ' : '') . $payment['last_name']);

// Calculate totals up to this payment
$stmtAllPay = $db->prepare("SELECT SUM(amount) FROM payments WHERE patient_id = ? AND id <= ?");
$stmtAllPay->execute([$patientId, $paymentId]);
$totalPaidToDate = (float)$stmtAllPay->fetchColumn();

$currentPayAmount = (float)$payment['amount'];
$previousPaid = max(0, $totalPaidToDate - $currentPayAmount);

$stmtTotalBill = $db->prepare("SELECT COALESCE(SUM(treatment_cost), 0) FROM visits WHERE patient_id = ?");
$stmtTotalBill->execute([$patientId]);
$totalBill = (float)$stmtTotalBill->fetchColumn();

// If visit cost is standalone
if ($totalBill == 0 && !empty($payment['treatment_cost'])) {
    $totalBill = (float)$payment['treatment_cost'];
}

$remainingBalance = max(0, $totalBill - $totalPaidToDate);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - <?= e($payment['receipt_no']) ?> | <?= e(CLINIC_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/print.css">
    <style>
        body {
            background: #f1f5f9;
            padding: 30px 10px;
            font-family: 'Inter', sans-serif;
            color: #0f172a;
        }
        .receipt-card {
            background: #ffffff;
            max-width: 680px;
            margin: 0 auto;
            padding: 36px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            position: relative;
        }
        .receipt-badge {
            background: #f0fdfa;
            color: #0f766e;
            border: 1px solid #99f6e4;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
        }
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .receipt-table th, .receipt-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13.5px;
        }
        .receipt-table th {
            background: #f8fafc;
            text-align: left;
            font-weight: 600;
            color: #64748b;
        }
        @media print {
            body { padding: 0; background: #fff; }
            .receipt-card { padding: 0; box-shadow: none; border: none; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print" style="max-width: 680px; margin: 0 auto 20px; display: flex; justify-content: space-between; align-items: center;">
    <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $patientId ?>" class="btn btn-secondary">
        &larr; Back to Patient Profile
    </a>
    <button onclick="window.print()" class="btn btn-primary" style="padding: 10px 24px;">
        🖨️ Print Receipt
    </button>
</div>

<div class="receipt-card">
    <!-- CLINIC LETTERHEAD -->
    <div class="print-letterhead" style="margin-bottom: 20px;">
        <div>
            <h1 class="print-clinic-name" style="font-size: 22px;"><?= e($clinic['clinic_name']) ?></h1>
            <div class="print-tagline">"<?= e($clinic['tagline']) ?>"</div>
            <div class="print-doctor-info">
                <?= e($clinic['dentist_name']) ?> <span style="font-size: 11px; font-weight: normal;"><?= e($clinic['dentist_qualification']) ?></span>
            </div>
            <div style="font-size: 11.5px; font-weight: 600; color: #475569;">
                Reg No: <?= e($clinic['reg_no']) ?>
            </div>
        </div>
        <div class="print-clinic-contact">
            <div>📞 <?= e($clinic['phone']) ?></div>
            <div>✉️ <?= e($clinic['email']) ?></div>
            <div style="max-width: 250px; margin-top: 4px;">
                <?= nl2br(e($clinic['address'])) ?>
            </div>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0d9488; padding-bottom: 10px; margin-bottom: 16px;">
        <span style="font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; color: #0d9488; text-transform: uppercase;">
            Official Payment Receipt
        </span>
        <div style="text-align: right;">
            <div class="receipt-badge">Receipt #: <?= e($payment['receipt_no']) ?></div>
            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Date: <strong><?= formatDate($payment['payment_date']) ?></strong></div>
        </div>
    </div>

    <!-- PATIENT INFO -->
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 16px; margin-bottom: 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 13px;">
        <div><strong>Patient Name:</strong> <?= e($fullName) ?></div>
        <div><strong>Registration No:</strong> <span style="font-family: monospace;"><?= e($payment['registration_no']) ?></span></div>
        <div><strong>Contact Number:</strong> <?= e($payment['mobile']) ?></div>
        <div><strong>Payment Method:</strong> <span class="badge badge-neutral"><?= e($payment['payment_method']) ?></span></div>
    </div>

    <!-- FINANCIAL BREAKDOWN TABLE -->
    <table class="receipt-table">
        <thead>
            <tr>
                <th>Description / Treatment</th>
                <th style="text-align: right;">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong><?= e($payment['treatment'] ?: 'Dental Treatment / Procedure') ?></strong>
                    <?php if ($payment['notes']): ?>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Note: <?= e($payment['notes']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="text-align: right; font-weight: 600;"><?= formatCurrency($totalBill) ?></td>
            </tr>
            <tr>
                <td style="color: #64748b;">Previous Amount Paid</td>
                <td style="text-align: right; color: #64748b;"><?= formatCurrency($previousPaid) ?></td>
            </tr>
            <tr style="background: #f0fdfa;">
                <td style="font-weight: 700; color: #0f766e;">Current Payment Received</td>
                <td style="text-align: right; font-weight: 800; color: #047857; font-size: 16px;">
                    <?= formatCurrency($currentPayAmount) ?>
                </td>
            </tr>
            <tr>
                <td><strong>Total Paid to Date</strong></td>
                <td style="text-align: right; font-weight: 700; color: #047857;"><?= formatCurrency($totalPaidToDate) ?></td>
            </tr>
            <tr style="background: <?= $remainingBalance > 0 ? '#fff1f2' : '#f0fdf4' ?>;">
                <td style="font-weight: 700;">
                    <?= $remainingBalance > 0 ? 'Remaining Balance Due' : 'Status' ?>
                </td>
                <td style="text-align: right; font-weight: 800; font-size: 15px; color: <?= $remainingBalance > 0 ? '#b91c1c' : '#047857' ?>;">
                    <?= $remainingBalance > 0 ? formatCurrency($remainingBalance) : 'PAID IN FULL ✓' ?>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- SIGNATURE AND FOOTER -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 40px; padding-top: 20px;">
        <div style="font-size: 11px; color: #64748b; line-height: 1.5;">
            Thank you for choosing Prime Dental Clinic.<br>
            <em>"The Prime Destination For Smiles"</em>
        </div>
        <div style="text-align: center; border-top: 1px solid #0f172a; width: 180px; padding-top: 6px; font-size: 12px;">
            <strong><?= e($clinic['dentist_name']) ?></strong><br>
            <span style="font-size: 10.5px; color: #64748b;">Authorized Signatory</span>
        </div>
    </div>
</div>

</body>
</html>
