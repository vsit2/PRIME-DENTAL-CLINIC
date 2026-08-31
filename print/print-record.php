<?php
/**
 * Prime Dental Clinic Management System
 * Printable Full Patient Medical & Dental Record (A4 Layout)
 */

define('PRIME_DENTAL', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_DIR . '/config/database.php';
require_once BASE_DIR . '/config/auth.php';
require_once BASE_DIR . '/config/helpers.php';

Auth::requireAuth();

$patientId = (int)($_GET['id'] ?? 0);
if ($patientId <= 0) die("Invalid Patient ID.");

$db = getDB();
$clinic = getClinicSettings();

// Fetch patient
$stmt = $db->prepare("SELECT * FROM patients WHERE id = ? LIMIT 1");
$stmt->execute([$patientId]);
$patient = $stmt->fetch();
if (!$patient) die("Patient not found.");

// Fetch medical history
$stmtMed = $db->prepare("SELECT * FROM medical_history WHERE patient_id = ? LIMIT 1");
$stmtMed->execute([$patientId]);
$medHistory = $stmtMed->fetch() ?: [];

// Fetch visits
$stmtVisits = $db->prepare("SELECT * FROM visits WHERE patient_id = ? ORDER BY visit_date ASC, id ASC");
$stmtVisits->execute([$patientId]);
$visits = $stmtVisits->fetchAll();

// Fetch payments
$stmtPayments = $db->prepare("SELECT * FROM payments WHERE patient_id = ? ORDER BY payment_date ASC, id ASC");
$stmtPayments->execute([$patientId]);
$payments = $stmtPayments->fetchAll();

$financials = getPatientFinancialSummary($patientId);
$fullName = trim($patient['first_name'] . ' ' . ($patient['middle_name'] ? $patient['middle_name'] . ' ' : '') . $patient['last_name']);

// Build active medical conditions
$activeConditions = [];
foreach (MEDICAL_CONDITIONS_LIST as $k => $label) {
    if (!empty($medHistory[$k])) {
        $detail = '';
        if ($k === 'drug_allergy' && !empty($medHistory['drug_allergy_details'])) {
            $detail = " (" . $medHistory['drug_allergy_details'] . ")";
        } elseif ($k === 'habits' && !empty($medHistory['habits_details'])) {
            $detail = " (" . $medHistory['habits_details'] . ")";
        } elseif ($k === 'current_medication' && !empty($medHistory['medication_details'])) {
            $detail = " (" . $medHistory['medication_details'] . ")";
        } elseif ($k === 'other_conditions' && !empty($medHistory['other_details'])) {
            $detail = " (" . $medHistory['other_details'] . ")";
        }
        $activeConditions[] = $label . $detail;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Record - <?= e($fullName) ?> (<?= e($patient['registration_no']) ?>) | <?= e(CLINIC_NAME) ?></title>
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
        .print-sheet {
            background: #ffffff;
            max-width: 850px;
            margin: 0 auto;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .section-header {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0d9488;
            border-bottom: 2px solid #0d9488;
            padding-bottom: 4px;
            margin: 20px 0 10px 0;
        }
        @media print {
            body { padding: 0; background: #fff; }
            .print-sheet { padding: 0; box-shadow: none; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print" style="max-width: 850px; margin: 0 auto 20px; display: flex; justify-content: space-between; align-items: center;">
    <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $patientId ?>" class="btn btn-secondary">
        &larr; Back to Profile
    </a>
    <button onclick="window.print()" class="btn btn-primary" style="padding: 10px 24px;">
        🖨️ Print Patient Record (A4)
    </button>
</div>

<div class="print-sheet">
    <!-- CLINIC LETTERHEAD -->
    <div class="print-letterhead">
        <div>
            <h1 class="print-clinic-name"><?= e($clinic['clinic_name']) ?></h1>
            <div class="print-tagline">"<?= e($clinic['tagline']) ?>"</div>
            <div class="print-doctor-info">
                <?= e($clinic['dentist_name']) ?> <span style="font-size: 12px; font-weight: normal;"><?= e($clinic['dentist_qualification']) ?></span>
            </div>
            <div style="font-size: 12px; font-weight: 600; color: #475569;">
                Reg No: <?= e($clinic['reg_no']) ?>
            </div>
        </div>
        <div class="print-clinic-contact">
            <div>📞 <?= e($clinic['phone']) ?></div>
            <div>✉️ <?= e($clinic['email']) ?></div>
            <div style="max-width: 280px; margin-top: 4px;">
                <?= nl2br(e($clinic['address'])) ?>
            </div>
        </div>
    </div>

    <!-- PATIENT DEMOGRAPHICS -->
    <div class="section-header">Patient Information</div>
    <div class="print-patient-meta-grid">
        <div class="print-meta-item"><span class="print-meta-label">Patient Name:</span> <?= e($fullName) ?></div>
        <div class="print-meta-item"><span class="print-meta-label">Registration No:</span> <?= e($patient['registration_no']) ?></div>
        <div class="print-meta-item"><span class="print-meta-label">Age / Gender:</span> <?= $patient['age'] ?> Years / <?= e($patient['gender']) ?></div>
        <div class="print-meta-item"><span class="print-meta-label">Date of Birth:</span> <?= formatDate($patient['dob']) ?></div>
        <div class="print-meta-item"><span class="print-meta-label">Mobile Number:</span> <?= e($patient['mobile']) ?></div>
        <div class="print-meta-item"><span class="print-meta-label">Email:</span> <?= e($patient['email'] ?: '—') ?></div>
        <div class="print-meta-item"><span class="print-meta-label">Place of Work:</span> <?= e($patient['place_of_work'] ?: '—') ?></div>
        <div class="print-meta-item"><span class="print-meta-label">Registration Date:</span> <?= formatDate($patient['reg_date']) ?></div>
        <div class="print-meta-item" style="grid-column: span 2;"><span class="print-meta-label">Address:</span> <?= e($patient['address'] ?: '—') ?></div>
    </div>

    <!-- PHYSICIAN & EMERGENCY -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; font-size: 13px;">
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; border-radius: 4px;">
            <strong>Physician Information:</strong><br>
            Name: <?= e($patient['physician_name'] ?: '—') ?><br>
            Contact: <?= e($patient['physician_contact'] ?: '—') ?>
        </div>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; border-radius: 4px;">
            <strong>Emergency Contact:</strong><br>
            Person: <?= e($patient['emergency_person'] ?: '—') ?> (<?= e($patient['emergency_relationship'] ?: 'Relation') ?>)<br>
            Contact: <?= e($patient['emergency_contact'] ?: '—') ?>
        </div>
    </div>

    <!-- MEDICAL HISTORY -->
    <div class="section-header">Medical History & Conditions</div>
    <div style="background: <?= !empty($activeConditions) ? '#fff1f2' : '#f8fafc' ?>; border: 1px solid <?= !empty($activeConditions) ? '#fecaca' : '#e2e8f0' ?>; padding: 12px; border-radius: 4px; margin-bottom: 16px; font-size: 13px;">
        <?php if (empty($activeConditions)): ?>
            <span style="color: #15803d; font-weight: 600;">✓ No medical contraindications or systemic disorders reported.</span>
        <?php else: ?>
            <strong style="color: #b91c1c;">⚠️ Recorded Medical Conditions:</strong>
            <ul style="margin: 6px 0 0 20px; color: #991b1b;">
                <?php foreach ($activeConditions as $cond): ?>
                    <li><?= e($cond) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if (!empty($medHistory['additional_notes'])): ?>
            <div style="margin-top: 6px; color: #475569;">
                <strong>Clinical Notes:</strong> <?= nl2br(e($medHistory['additional_notes'])) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- CLINICAL VISITS & PROCEDURES -->
    <div class="section-header">Clinical Visits & Treatment History</div>
    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Date</th>
                <th style="width: 10%;">Tooth #</th>
                <th style="width: 38%;">Diagnosis & Treatment Performed</th>
                <th style="width: 28%;">Prescription (Rx) / Notes</th>
                <th style="width: 12%; text-align: right;">Cost (₹)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($visits)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #64748b;">No visit history recorded.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($visits as $v): ?>
                    <tr>
                        <td><strong><?= formatDate($v['visit_date']) ?></strong></td>
                        <td><?= e($v['tooth_number'] ?: '—') ?></td>
                        <td>
                            <?php if ($v['diagnosis']): ?>
                                <div style="font-weight: 600; color: #0d9488;">Dx: <?= e($v['diagnosis']) ?></div>
                            <?php endif; ?>
                            <div><strong>Tx:</strong> <?= nl2br(e($v['treatment'])) ?></div>
                            <?php if ($v['chief_complaint']): ?>
                                <div style="font-size: 11px; color: #64748b;">Complaint: <?= e($v['chief_complaint']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($v['prescription']): ?>
                                <div style="font-family: monospace; font-size: 11px; white-space: pre-wrap; background: #f0fdf4; padding: 4px; border-radius: 3px;"><?= e($v['prescription']) ?></div>
                            <?php endif; ?>
                            <?php if ($v['dentist_notes']): ?>
                                <div style="font-size: 11px; color: #475569; margin-top: 2px;">Note: <?= e($v['dentist_notes']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right; font-weight: 700;"><?= formatCurrency($v['treatment_cost']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- FINANCIAL & PAYMENT SUMMARY -->
    <div class="section-header">Financial & Payment History</div>
    <table>
        <thead>
            <tr>
                <th>Receipt No</th>
                <th>Payment Date</th>
                <th>Payment Method</th>
                <th>Notes</th>
                <th style="text-align: right;">Amount Paid (₹)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payments)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #64748b;">No payments recorded.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><strong><?= e($p['receipt_no']) ?></strong></td>
                        <td><?= formatDate($p['payment_date']) ?></td>
                        <td><?= e($p['payment_method']) ?></td>
                        <td><?= e($p['notes'] ?: '—') ?></td>
                        <td style="text-align: right; font-weight: 700; color: #047857;"><?= formatCurrency($p['amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- FINANCIAL TOTALS BOX -->
    <div style="background: #f8fafc; border: 1.5pt solid #cbd5e1; padding: 12px 18px; border-radius: 4px; margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <strong>Total Treatment Cost:</strong> <?= formatCurrency($financials['total_bill']) ?>
        </div>
        <div>
            <strong>Total Amount Paid:</strong> <span style="color: #047857;"><?= formatCurrency($financials['total_paid']) ?></span>
        </div>
        <div>
            <strong>Remaining Balance:</strong> 
            <span style="font-size: 15px; font-weight: 800; color: <?= $financials['balance'] > 0 ? '#b91c1c' : '#047857' ?>;">
                <?= formatCurrency($financials['balance']) ?> 
                (<?= $financials['balance'] <= 0 && $financials['total_bill'] > 0 ? 'PAID IN FULL' : ($financials['balance'] > 0 ? 'DUE' : 'NIL') ?>)
            </span>
        </div>
    </div>

    <!-- DOCTOR SIGNATURE SECTION -->
    <div class="print-signature-section">
        <div style="font-size: 11px; color: #64748b;">
            Printed On: <?= date('d M Y, h:i A') ?><br>
            System: Prime Dental Clinic Portal
        </div>
        <div class="print-signature-box">
            <br><br>
            <div><strong><?= e($clinic['dentist_name']) ?></strong></div>
            <div><?= e($clinic['dentist_qualification']) ?></div>
            <div>Reg No: <?= e($clinic['reg_no']) ?></div>
            <div style="font-size: 10px; color: #475569;">Authorized Signature & Clinic Stamp</div>
        </div>
    </div>
</div>

</body>
</html>
