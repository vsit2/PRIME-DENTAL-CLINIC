<?php
/**
 * Prime Dental Clinic Management System
 * Complete Patient Profile & Clinical Record
 */

define('PRIME_DENTAL', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_DIR . '/config/database.php';
require_once BASE_DIR . '/config/auth.php';
require_once BASE_DIR . '/config/helpers.php';

Auth::requireAuth();

$patientId = (int)($_GET['id'] ?? 0);
if ($patientId <= 0) {
    header("Location: " . BASE_URL . "/pages/patients.php");
    exit;
}

$db = getDB();

// 1. Fetch Patient Info
$stmtPatient = $db->prepare("SELECT * FROM patients WHERE id = ? LIMIT 1");
$stmtPatient->execute([$patientId]);
$patient = $stmtPatient->fetch();

if (!$patient) {
    die("Patient not found.");
}

// 2. Fetch Medical History
$stmtMed = $db->prepare("SELECT * FROM medical_history WHERE patient_id = ? LIMIT 1");
$stmtMed->execute([$patientId]);
$medHistory = $stmtMed->fetch() ?: [];

// 3. Fetch Visits History in Chronological Order (Newest first for clinical convenience)
$stmtVisits = $db->prepare("SELECT * FROM visits WHERE patient_id = ? ORDER BY visit_date DESC, id DESC");
$stmtVisits->execute([$patientId]);
$visits = $stmtVisits->fetchAll();

// 4. Fetch Payment History
$stmtPayments = $db->prepare("
    SELECT p.*, v.treatment 
    FROM payments p 
    LEFT JOIN visits v ON p.visit_id = v.id 
    WHERE p.patient_id = ? 
    ORDER BY p.payment_date DESC, p.id DESC
");
$stmtPayments->execute([$patientId]);
$payments = $stmtPayments->fetchAll();

// 5. Calculate Financials
$financials = getPatientFinancialSummary($patientId);
$fullName = trim($patient['first_name'] . ' ' . ($patient['middle_name'] ? $patient['middle_name'] . ' ' : '') . $patient['last_name']);

$pageTitle = $fullName . " (" . $patient['registration_no'] . ")";

// Parse Initial Reasons JSON
$initialReasons = [];
if (!empty($patient['initial_reasons'])) {
    $decoded = json_decode($patient['initial_reasons'], true);
    if (is_array($decoded)) $initialReasons = $decoded;
}

// Build list of active medical alerts
$activeMedicalAlerts = [];
foreach (MEDICAL_CONDITIONS_LIST as $key => $label) {
    if (!empty($medHistory[$key])) {
        $detail = '';
        if ($key === 'drug_allergy' && !empty($medHistory['drug_allergy_details'])) {
            $detail = ": " . $medHistory['drug_allergy_details'];
        } elseif ($key === 'habits' && !empty($medHistory['habits_details'])) {
            $detail = ": " . $medHistory['habits_details'];
        } elseif ($key === 'current_medication' && !empty($medHistory['medication_details'])) {
            $detail = ": " . $medHistory['medication_details'];
        } elseif ($key === 'other_conditions' && !empty($medHistory['other_details'])) {
            $detail = ": " . $medHistory['other_details'];
        }
        $activeMedicalAlerts[] = [
            'key' => $key,
            'label' => $label . $detail,
            'is_critical' => in_array($key, ['drug_allergy', 'bleeding_disorder', 'cardiovascular_disorders', 'hiv_aids', 'hepatitis'])
        ];
    }
}

require_once BASE_DIR . '/includes/header.php';
require_once BASE_DIR . '/includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once BASE_DIR . '/includes/topbar.php'; ?>

    <main class="page-content">
        <!-- 1. PATIENT HEADER BANNER -->
        <div class="patient-profile-header">
            <div class="patient-profile-left">
                <div class="patient-avatar-large">
                    <?= strtoupper(substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1)) ?>
                </div>
                <div>
                    <h1 class="patient-title-name"><?= e($fullName) ?></h1>
                    <div class="patient-meta-pills">
                        <span class="badge badge-primary" style="font-family:monospace; font-size:13px;">
                            <?= e($patient['registration_no']) ?>
                        </span>
                        <span class="badge badge-neutral">
                            <?= $patient['age'] ?> Years &bull; <?= e($patient['gender']) ?>
                        </span>
                        <span class="badge badge-neutral">
                            📞 <?= e($patient['mobile']) ?>
                        </span>
                        <?php if ($patient['dob']): ?>
                            <span class="badge badge-neutral">
                                🎂 DOB: <?= formatDate($patient['dob']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($patient['place_of_work']): ?>
                            <span class="badge badge-neutral">
                                💼 <?= e($patient['place_of_work']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Financial Metric Cards in Profile -->
            <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                <div class="patient-financial-card-box">
                    <div class="fin-metric">
                        <div class="fin-metric-label">Total Bill</div>
                        <div class="fin-metric-val" style="color: var(--text-main);"><?= formatCurrency($financials['total_bill']) ?></div>
                    </div>
                    <div class="fin-metric">
                        <div class="fin-metric-label">Total Paid</div>
                        <div class="fin-metric-val" style="color: var(--success-text);"><?= formatCurrency($financials['total_paid']) ?></div>
                    </div>
                    <div class="fin-metric">
                        <div class="fin-metric-label">Outstanding</div>
                        <div class="fin-metric-val" style="color: <?= $financials['balance'] > 0 ? 'var(--danger-text)' : 'var(--text-muted)' ?>;">
                            <?= formatCurrency($financials['balance']) ?>
                        </div>
                    </div>
                    <div>
                        <?php if ($financials['total_bill'] > 0 && $financials['balance'] <= 0): ?>
                            <span class="badge badge-paid" style="font-size: 13px; padding: 6px 12px;">PAID IN FULL</span>
                        <?php elseif ($financials['balance'] > 0): ?>
                            <span class="badge badge-due" style="font-size: 13px; padding: 6px 12px;">BALANCE DUE: <?= formatCurrency($financials['balance']) ?></span>
                        <?php else: ?>
                            <span class="badge badge-neutral" style="font-size: 13px; padding: 6px 12px;">NO CHARGES</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <a href="<?= BASE_URL ?>/pages/visit-add.php?patient_id=<?= $patient['id'] ?>" class="btn btn-primary">
                        + Add Visit
                    </a>
                    <a href="<?= BASE_URL ?>/pages/payment-add.php?patient_id=<?= $patient['id'] ?>" class="btn btn-success">
                        ₹ Record Payment
                    </a>
                    <a href="<?= BASE_URL ?>/print/print-record.php?id=<?= $patient['id'] ?>" target="_blank" class="btn btn-secondary" title="Print Full Patient Record">
                        🖨️ Print Record
                    </a>
                    <a href="<?= BASE_URL ?>/pages/patient-edit.php?id=<?= $patient['id'] ?>" class="btn btn-secondary">
                        ✏️ Edit
                    </a>
                </div>
            </div>
        </div>

        <!-- MEDICAL ALERTS NOTICE (If any positive conditions exist) -->
        <?php if (!empty($activeMedicalAlerts)): ?>
            <div class="medical-alert-box">
                <div class="medical-alert-title">
                    ⚠️ MEDICAL ALERTS & HEALTH CONDITIONS NOTED:
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                    <?php foreach ($activeMedicalAlerts as $alert): ?>
                        <span class="badge <?= $alert['is_critical'] ? 'badge-due' : 'badge-partial' ?>" style="font-size: 12.5px; padding: 5px 10px;">
                            <?= $alert['is_critical'] ? '🚨' : '⚠️' ?> <?= e($alert['label']) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- PROFILE TABS -->
        <div class="profile-tabs">
            <button class="tab-btn active" data-tab="tab-visits">Visit & Treatment History (<?= count($visits) ?>)</button>
            <button class="tab-btn" data-tab="tab-billing">Payment & Billing History (<?= count($payments) ?>)</button>
            <button class="tab-btn" data-tab="tab-info">Demographic & Medical Profile</button>
        </div>

        <!-- TAB 1: VISITS & CLINICAL HISTORY -->
        <div id="tab-visits" class="tab-pane active">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="font-size: 18px; font-weight: 700; margin: 0;">Clinical Visits & Procedures</h2>
                <a href="<?= BASE_URL ?>/pages/visit-add.php?patient_id=<?= $patient['id'] ?>" class="btn btn-primary" style="padding: 7px 14px; font-size: 13px;">
                    + Record New Visit
                </a>
            </div>

            <?php if (empty($visits)): ?>
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 40px; color: var(--text-muted);">
                        <div style="font-size: 36px; margin-bottom: 8px;">🩺</div>
                        <div style="font-size: 16px; font-weight: 600; margin-bottom: 4px;">No clinical visits recorded yet</div>
                        <p style="margin-bottom: 16px; font-size: 13.5px;">Click below to record the patient's first clinical examination or procedure.</p>
                        <a href="<?= BASE_URL ?>/pages/visit-add.php?patient_id=<?= $patient['id'] ?>" class="btn btn-primary">+ Add First Visit</a>
                    </div>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <?php foreach ($visits as $idx => $v): 
                        // Fetch payments specific to this visit
                        $stmtVPay = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE visit_id = ?");
                        $stmtVPay->execute([$v['id']]);
                        $vPaid = (float)$stmtVPay->fetchColumn();
                        $vCost = (float)$v['treatment_cost'];
                        $vBalance = max(0, $vCost - $vPaid);

                        // Parse reasons
                        $vReasons = [];
                        if (!empty($v['reason_for_visit'])) {
                            $decodedR = json_decode($v['reason_for_visit'], true);
                            if (is_array($decodedR)) $vReasons = $decodedR;
                            else $vReasons = [$v['reason_for_visit']];
                        }
                    ?>
                        <div class="card">
                            <div class="card-header" style="background: #f8fafc;">
                                <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                                    <span class="badge badge-primary" style="font-size: 13px; font-weight: 700;">
                                        📅 Visit Date: <?= formatDate($v['visit_date']) ?>
                                    </span>
                                    <?php if ($v['tooth_number']): ?>
                                        <span class="badge badge-neutral" style="font-size: 13px;">
                                            🦷 Tooth #: <strong><?= e($v['tooth_number']) ?></strong>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($v['follow_up_date']): ?>
                                        <span class="badge badge-partial" style="font-size: 12px;">
                                            🔔 Follow-up: <?= formatDate($v['follow_up_date']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="text-align: right;">
                                        <span style="font-size: 12px; color: var(--text-muted);">Treatment Cost:</span>
                                        <strong style="font-size: 16px; color: var(--text-main); margin-left: 4px;">
                                            <?= formatCurrency($vCost) ?>
                                        </strong>
                                    </div>
                                    <a href="<?= BASE_URL ?>/print/print-prescription.php?id=<?= $v['id'] ?>" target="_blank" class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;" title="Print Prescription Slip">
                                        💊 Print Rx
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                    <!-- Clinical Details Left -->
                                    <div>
                                        <?php if (!empty($vReasons)): ?>
                                            <div style="margin-bottom: 12px;">
                                                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Reason for Visit:</div>
                                                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 4px;">
                                                    <?php foreach ($vReasons as $r): ?>
                                                        <span class="badge badge-neutral"><?= e($r) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($v['chief_complaint']): ?>
                                            <div style="margin-bottom: 12px;">
                                                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Chief Complaint:</div>
                                                <div style="font-size: 14px; margin-top: 2px; color: var(--text-main);"><?= nl2br(e($v['chief_complaint'])) ?></div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($v['diagnosis']): ?>
                                            <div style="margin-bottom: 12px;">
                                                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Diagnosis:</div>
                                                <div style="font-size: 14px; font-weight: 600; margin-top: 2px; color: var(--primary-hover);"><?= nl2br(e($v['diagnosis'])) ?></div>
                                            </div>
                                        <?php endif; ?>

                                        <div style="margin-bottom: 12px;">
                                            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Treatment Performed:</div>
                                            <div style="font-size: 14px; margin-top: 2px; font-weight: 600; color: var(--text-main);"><?= nl2br(e($v['treatment'] ?: 'Consultation / Evaluation')) ?></div>
                                        </div>
                                    </div>

                                    <!-- Notes & Prescription Right -->
                                    <div>
                                        <?php if ($v['dentist_notes']): ?>
                                            <div style="margin-bottom: 12px; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid var(--border-light);">
                                                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">👨‍⚕️ Dentist Notes:</div>
                                                <div style="font-size: 13.5px; margin-top: 4px; color: var(--text-body);"><?= nl2br(e($v['dentist_notes'])) ?></div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($v['prescription']): ?>
                                            <div style="background: #f0fdf4; padding: 12px; border-radius: 8px; border: 1px solid #bbf7d0;">
                                                <div style="font-size: 12px; font-weight: 700; color: var(--success-text); text-transform: uppercase;">💊 Prescription (Rx):</div>
                                                <div style="font-size: 13.5px; margin-top: 4px; font-family: monospace; white-space: pre-wrap; color: var(--text-main);"><?= e($v['prescription']) ?></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB 2: BILLING & PAYMENT HISTORY -->
        <div id="tab-billing" class="tab-pane">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="font-size: 18px; font-weight: 700; margin: 0;">Payment History & Ledgers</h2>
                <a href="<?= BASE_URL ?>/pages/payment-add.php?patient_id=<?= $patient['id'] ?>" class="btn btn-primary" style="padding: 7px 14px; font-size: 13px;">
                    + Add New Payment
                </a>
            </div>

            <!-- Financial Overview Summary Cards -->
            <div class="stats-grid" style="margin-bottom: 20px;">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total Treatment Cost</h3>
                        <div class="stat-value"><?= formatCurrency($financials['total_bill']) ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total Paid to Date</h3>
                        <div class="stat-value" style="color: var(--success-text);"><?= formatCurrency($financials['total_paid']) ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Current Balance Due</h3>
                        <div class="stat-value" style="color: <?= $financials['balance'] > 0 ? 'var(--danger-text)' : 'var(--text-muted)' ?>;">
                            <?= formatCurrency($financials['balance']) ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Receipt No</th>
                                <th>Payment Date</th>
                                <th>Treatment / Note</th>
                                <th>Payment Method</th>
                                <th>Amount Paid</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                        No payments recorded yet for this patient.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($payments as $pay): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-neutral" style="font-family: monospace; font-weight: 700;">
                                                <?= e($pay['receipt_no']) ?>
                                            </span>
                                        </td>
                                        <td><?= formatDate($pay['payment_date']) ?></td>
                                        <td>
                                            <strong><?= e($pay['treatment'] ?: 'General Dental Payment') ?></strong>
                                            <?php if ($pay['notes']): ?>
                                                <div style="font-size: 12px; color: var(--text-muted);"><?= e($pay['notes']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary"><?= e($pay['payment_method']) ?></span>
                                        </td>
                                        <td>
                                            <strong style="color: var(--success-text); font-size: 14.5px;">
                                                <?= formatCurrency($pay['amount']) ?>
                                            </strong>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="<?= BASE_URL ?>/print/print-receipt.php?id=<?= $pay['id'] ?>" target="_blank" class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;" title="Print Payment Receipt">
                                                🧾 Print Receipt
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 3: DEMOGRAPHICS & COMPLETE MEDICAL PROFILE -->
        <div id="tab-info" class="tab-pane">
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <div class="card-icon">📋</div>
                        <div>
                            <h2 class="card-title">Patient Demographics & Medical Profile</h2>
                            <div class="card-subtitle">Full medical history, physician, and emergency details</div>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>/pages/patient-edit.php?id=<?= $patient['id'] ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">
                        ✏️ Edit Information
                    </a>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                        <!-- Col 1: Personal Details -->
                        <div>
                            <h3 style="font-size: 15px; border-bottom: 2px solid var(--border-light); padding-bottom: 6px; margin-bottom: 12px; color: var(--primary);">
                                Personal Information
                            </h3>
                            <p style="margin-bottom: 8px;"><strong>Full Name:</strong> <?= e($fullName) ?></p>
                            <p style="margin-bottom: 8px;"><strong>Registration No:</strong> <span style="font-family:monospace;"><?= e($patient['registration_no']) ?></span></p>
                            <p style="margin-bottom: 8px;"><strong>Registration Date:</strong> <?= formatDate($patient['reg_date']) ?></p>
                            <p style="margin-bottom: 8px;"><strong>Date of Birth:</strong> <?= formatDate($patient['dob']) ?></p>
                            <p style="margin-bottom: 8px;"><strong>Age / Gender:</strong> <?= $patient['age'] ?> Years / <?= e($patient['gender']) ?></p>
                            <p style="margin-bottom: 8px;"><strong>Mobile:</strong> <?= e($patient['mobile']) ?></p>
                            <p style="margin-bottom: 8px;"><strong>Email:</strong> <?= e($patient['email'] ?: '—') ?></p>
                            <p style="margin-bottom: 8px;"><strong>Place of Work:</strong> <?= e($patient['place_of_work'] ?: '—') ?></p>
                            <p style="margin-bottom: 8px;"><strong>Education:</strong> <?= e($patient['education'] ?: '—') ?></p>
                            <p style="margin-bottom: 8px;"><strong>Address:</strong> <?= nl2br(e($patient['address'] ?: '—')) ?></p>
                        </div>

                        <!-- Col 2: Physician & Emergency -->
                        <div>
                            <h3 style="font-size: 15px; border-bottom: 2px solid var(--border-light); padding-bottom: 6px; margin-bottom: 12px; color: var(--primary);">
                                Physician & Emergency Contacts
                            </h3>
                            <p style="margin-bottom: 8px;"><strong>Family Physician:</strong> <?= e($patient['physician_name'] ?: '—') ?></p>
                            <p style="margin-bottom: 8px;"><strong>Physician Contact:</strong> <?= e($patient['physician_contact'] ?: '—') ?></p>
                            <p style="margin-bottom: 8px;"><strong>Emergency Contact Person:</strong> <?= e($patient['emergency_person'] ?: '—') ?></p>
                            <p style="margin-bottom: 8px;"><strong>Relationship:</strong> <?= e($patient['emergency_relationship'] ?: '—') ?></p>
                            <p style="margin-bottom: 8px;"><strong>Emergency Contact No:</strong> <?= e($patient['emergency_contact'] ?: '—') ?></p>

                            <h3 style="font-size: 15px; border-bottom: 2px solid var(--border-light); padding-bottom: 6px; margin-top: 20px; margin-bottom: 12px; color: var(--primary);">
                                Initial Reasons for Registration
                            </h3>
                            <?php if (!empty($initialReasons)): ?>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                    <?php foreach ($initialReasons as $r): ?>
                                        <span class="badge badge-primary"><?= e($r) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p style="color: var(--text-muted);">None specified</p>
                            <?php endif; ?>
                        </div>

                        <!-- Col 3: Medical Conditions Grid -->
                        <div>
                            <h3 style="font-size: 15px; border-bottom: 2px solid var(--border-light); padding-bottom: 6px; margin-bottom: 12px; color: var(--primary);">
                                Medical Conditions
                            </h3>
                            <ul style="list-style: none; padding: 0;">
                                <?php foreach (MEDICAL_CONDITIONS_LIST as $k => $label): 
                                    $hasCond = !empty($medHistory[$k]);
                                ?>
                                    <li style="display: flex; align-items: center; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid var(--border-subtle); font-size: 13px;">
                                        <span><?= e($label) ?></span>
                                        <?php if ($hasCond): ?>
                                            <span class="badge badge-due" style="font-size: 11px;">YES</span>
                                        <?php else: ?>
                                            <span class="badge badge-neutral" style="font-size: 11px;">No</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <?php if (!empty($medHistory['additional_notes'])): ?>
                                <div style="margin-top: 12px; background: #f8fafc; padding: 10px; border-radius: 6px; font-size: 13px;">
                                    <strong>Additional Notes:</strong><br>
                                    <?= nl2br(e($medHistory['additional_notes'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

<?php require_once BASE_DIR . '/includes/footer.php'; ?>
