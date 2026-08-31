<?php
/**
 * Prime Dental Clinic Management System
 * Add New Clinical Visit & Billing
 */

define('PRIME_DENTAL', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_DIR . '/config/database.php';
require_once BASE_DIR . '/config/auth.php';
require_once BASE_DIR . '/config/helpers.php';

Auth::requireAuth();

$db = getDB();
$patientId = (int)($_GET['patient_id'] ?? 0);
$patient = null;

if ($patientId > 0) {
    $stmt = $db->prepare("SELECT * FROM patients WHERE id = ? LIMIT 1");
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();
}

// All patients for dropdown selection if no patient_id provided
$allPatients = $db->query("SELECT id, registration_no, first_name, last_name, mobile FROM patients ORDER BY first_name ASC")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedPatientId = (int)($_POST['patient_id'] ?? 0);
    $visitDate = trim($_POST['visit_date'] ?? date('Y-m-d'));
    $chiefComplaint = trim($_POST['chief_complaint'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $treatment = trim($_POST['treatment'] ?? '');
    $toothNumber = trim($_POST['tooth_number'] ?? '');
    $dentistNotes = trim($_POST['dentist_notes'] ?? '');
    $prescription = trim($_POST['prescription'] ?? '');
    $followUpDate = !empty($_POST['follow_up_date']) ? trim($_POST['follow_up_date']) : null;
    $treatmentCost = (float)($_POST['treatment_cost'] ?? 0);

    // Initial Payment (optional)
    $amountPaid = (float)($_POST['amount_paid'] ?? 0);
    $paymentMethod = trim($_POST['payment_method'] ?? 'Cash');
    $paymentNotes = trim($_POST['payment_notes'] ?? 'Initial payment during visit');

    // Reasons
    $reasonsSelected = $_POST['reasons'] ?? [];
    if (!is_array($reasonsSelected)) $reasonsSelected = [];
    $otherReason = trim($_POST['other_reason_text'] ?? '');
    if (in_array('Any Other', $reasonsSelected) && !empty($otherReason)) {
        $reasonsSelected[] = "Other: " . $otherReason;
    }
    $reasonJson = json_encode(array_values($reasonsSelected));

    // Validations
    if ($selectedPatientId <= 0) $errors[] = "Please select a valid patient.";
    if (empty($visitDate)) $errors[] = "Visit Date is required.";
    if (empty($treatment)) $errors[] = "Treatment / Procedure performed is required.";
    if ($treatmentCost < 0) $errors[] = "Treatment cost cannot be negative.";
    if ($amountPaid < 0) $errors[] = "Payment amount cannot be negative.";

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Insert Visit
            $stmtVisit = $db->prepare("
                INSERT INTO visits (
                    patient_id, visit_date, chief_complaint, reason_for_visit,
                    diagnosis, treatment, tooth_number, dentist_notes,
                    prescription, follow_up_date, treatment_cost
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?
                )
            ");
            $stmtVisit->execute([
                $selectedPatientId, $visitDate, $chiefComplaint, $reasonJson,
                $diagnosis, $treatment, $toothNumber, $dentistNotes,
                $prescription, $followUpDate, $treatmentCost
            ]);
            $visitId = $db->lastInsertId();

            // If initial payment recorded
            if ($amountPaid > 0) {
                $receiptNo = getNextReceiptNumber();
                $stmtPay = $db->prepare("
                    INSERT INTO payments (receipt_no, patient_id, visit_id, payment_date, amount, payment_method, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtPay->execute([
                    $receiptNo,
                    $selectedPatientId,
                    $visitId,
                    $visitDate,
                    $amountPaid,
                    $paymentMethod,
                    $paymentNotes
                ]);
            }

            $db->commit();

            setFlash('success', "Clinical visit recorded successfully for patient!");
            header("Location: " . BASE_URL . "/pages/patient-view.php?id=" . $selectedPatientId);
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Failed to record visit: " . $e->getMessage();
        }
    }
}

$pageTitle = "Add New Clinical Visit";
$extraScripts = ['assets/js/dental-chart.js', 'assets/js/registration.js'];
require_once BASE_DIR . '/includes/header.php';
require_once BASE_DIR . '/includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once BASE_DIR . '/includes/topbar.php'; ?>

    <main class="page-content">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
            <div>
                <h1 style="font-size: 24px; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">
                    Record Clinical Visit & Treatment
                </h1>
                <p style="color: var(--text-muted); font-size: 13.5px;">
                    Log examination notes, tooth number, diagnosis, prescription, treatment cost, and payments.
                </p>
            </div>
            <?php if ($patient): ?>
                <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $patient['id'] ?>" class="btn btn-secondary">
                    &larr; Back to Patient Profile
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/pages/patients.php" class="btn btn-secondary">
                    &larr; Back to Patients
                </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="medical-alert-box" style="margin-bottom: 20px;">
                <div class="medical-alert-title">⚠️ Errors:</div>
                <ul style="margin: 4px 0 0 20px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="visitForm">
            <!-- SECTION 1: PATIENT & VISIT METADATA -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <div class="card-icon">👤</div>
                        <div>
                            <h2 class="card-title">1. Patient & Visit Information</h2>
                            <div class="card-subtitle">Select patient and visit date</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="patient_id">Select Patient <span class="required">*</span></label>
                                <?php if ($patient): ?>
                                    <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                                    <input type="text" class="form-control" value="<?= e($patient['registration_no'] . ' — ' . $patient['first_name'] . ' ' . $patient['last_name'] . ' (📞 ' . $patient['mobile'] . ')') ?>" readonly style="background:#f0fdfa;font-weight:600;">
                                <?php else: ?>
                                    <select id="patient_id" name="patient_id" class="form-control" required>
                                        <option value="">-- Choose Existing Patient --</option>
                                        <?php foreach ($allPatients as $p): ?>
                                            <option value="<?= $p['id'] ?>" <?= ($patientId === (int)$p['id']) ? 'selected' : '' ?>>
                                                <?= e($p['registration_no']) ?> &bull; <?= e($p['first_name'] . ' ' . $p['last_name']) ?> (<?= e($p['mobile']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-3">
                            <div class="form-group">
                                <label class="form-label" for="visit_date">Visit Date <span class="required">*</span></label>
                                <input type="date" id="visit_date" name="visit_date" class="form-control" value="<?= e($_POST['visit_date'] ?? date('Y-m-d')) ?>" required>
                            </div>
                        </div>

                        <div class="col-3">
                            <div class="form-group">
                                <label class="form-label" for="follow_up_date">Follow-up Date</label>
                                <input type="date" id="follow_up_date" name="follow_up_date" class="form-control" value="<?= e($_POST['follow_up_date'] ?? '') ?>" min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: REASON FOR VISIT -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <div class="card-icon">📋</div>
                        <div>
                            <h2 class="card-title">2. Reason for Current Visit</h2>
                            <div class="card-subtitle">Select all dental concerns for this consultation</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="checkbox-chip-grid">
                        <?php 
                        $postedReasons = $_POST['reasons'] ?? [];
                        foreach (REASONS_FOR_VISIT_LIST as $reason): 
                            $isOther = ($reason === 'Any Other');
                            $isChecked = in_array($reason, $postedReasons);
                        ?>
                            <label class="checkbox-chip <?= $isChecked ? 'active' : '' ?>">
                                <input type="checkbox" 
                                       name="reasons[]" 
                                       value="<?= e($reason) ?>" 
                                       <?= $isChecked ? 'checked' : '' ?>
                                       <?= $isOther ? 'id="reason_other_check"' : '' ?>>
                                <span class="checkbox-custom-box"></span>
                                <span class="chip-label"><?= e($reason) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div id="other_reason_container" style="margin-top: 14px; display: <?= in_array('Any Other', $postedReasons) ? 'block' : 'none' ?>;">
                        <label class="form-label" for="other_reason_text">Specify Other Reason:</label>
                        <input type="text" id="other_reason_text" name="other_reason_text" class="form-control" placeholder="Details of other complaint..." value="<?= e($_POST['other_reason_text'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- SECTION 3: CLINICAL INFORMATION & INTERACTIVE TOOTH CHART -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <div class="card-icon">🦷</div>
                        <div>
                            <h2 class="card-title">3. Dental Treatment & Clinical Record</h2>
                            <div class="card-subtitle">Interactive Tooth Selector, Examination, and Diagnosis</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Interactive Dental Tooth Chart -->
                    <div class="dental-chart-container">
                        <div class="dental-chart-header">
                            <div class="dental-chart-title">
                                <span>🦷 Interactive FDI Tooth Chart (11–48)</span>
                            </div>
                            <div class="dental-chart-shortcuts">
                                <span style="font-size: 12px; color: var(--text-muted); margin-right: 4px;">Quick Select:</span>
                                <button type="button" class="dental-shortcut-btn" data-action="full">Full Mouth</button>
                                <button type="button" class="dental-shortcut-btn" data-action="upper">Upper Arch</button>
                                <button type="button" class="dental-shortcut-btn" data-action="lower">Lower Arch</button>
                                <button type="button" class="dental-shortcut-btn" data-action="upper_ant">Upper Anteriors</button>
                                <button type="button" class="dental-shortcut-btn" data-action="lower_ant">Lower Anteriors</button>
                                <button type="button" class="dental-shortcut-btn" data-action="clear" style="color:var(--danger);">Clear</button>
                            </div>
                        </div>

                        <div class="dental-arch-wrapper">
                            <!-- UPPER ARCH -->
                            <div class="dental-quadrant-row">
                                <!-- Quadrant 1 (Upper Right 18-11) -->
                                <div class="dental-quadrant">
                                    <?php foreach ([18, 17, 16, 15, 14, 13, 12, 11] as $t): ?>
                                        <div class="tooth-item" data-tooth="<?= $t ?>" title="Tooth #<?= $t ?>">
                                            <span class="tooth-num"><?= $t ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <!-- Quadrant 2 (Upper Left 21-28) -->
                                <div class="dental-quadrant">
                                    <?php foreach ([21, 22, 23, 24, 25, 26, 27, 28] as $t): ?>
                                        <div class="tooth-item" data-tooth="<?= $t ?>" title="Tooth #<?= $t ?>">
                                            <span class="tooth-num"><?= $t ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="dental-arch-divider"></div>

                            <!-- LOWER ARCH -->
                            <div class="dental-quadrant-row">
                                <!-- Quadrant 4 (Lower Right 48-41) -->
                                <div class="dental-quadrant">
                                    <?php foreach ([48, 47, 46, 45, 44, 43, 42, 41] as $t): ?>
                                        <div class="tooth-item" data-tooth="<?= $t ?>" title="Tooth #<?= $t ?>">
                                            <span class="tooth-num"><?= $t ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <!-- Quadrant 3 (Lower Left 31-38) -->
                                <div class="dental-quadrant">
                                    <?php foreach ([31, 32, 33, 34, 35, 36, 37, 38] as $t): ?>
                                        <div class="tooth-item" data-tooth="<?= $t ?>" title="Tooth #<?= $t ?>">
                                            <span class="tooth-num"><?= $t ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="dental-chart-selection-summary">
                            <div>Selected Tooth / Teeth: <span id="selected_teeth_display"><span style="color:#94a3b8;font-style:italic;">None selected</span></span></div>
                        </div>
                    </div>

                    <div class="form-grid" style="margin-top: 20px;">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label" for="tooth_number">Tooth / Teeth Number</label>
                                <input type="text" id="tooth_number" name="tooth_number" class="form-control" placeholder="Click teeth above or type (e.g. 16, 46, Upper Anterior)" value="<?= e($_POST['tooth_number'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="chief_complaint">Chief Complaint</label>
                                <textarea id="chief_complaint" name="chief_complaint" class="form-control" rows="3" placeholder="Patient's reported issue in their own words..."><?= e($_POST['chief_complaint'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="diagnosis">Diagnosis</label>
                                <textarea id="diagnosis" name="diagnosis" class="form-control" rows="3" placeholder="Clinical & radiographic diagnosis (e.g. Irreversible Pulpitis #26)..."><?= e($_POST['diagnosis'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label" for="treatment">Treatment Performed <span class="required">*</span></label>
                                <textarea id="treatment" name="treatment" class="form-control" rows="3" placeholder="Details of dental treatment / surgical procedure / restorations completed today..." required><?= e($_POST['treatment'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="dentist_notes">Dentist Notes / Observations</label>
                                <textarea id="dentist_notes" name="dentist_notes" class="form-control" rows="4" placeholder="Materials used, shades, anesthesia given, post-op instructions..."><?= e($_POST['dentist_notes'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="prescription">Prescription (Rx)</label>
                                <textarea id="prescription" name="prescription" class="form-control" rows="4" placeholder="Medications, dosage, frequency, and instructions (e.g. Tab Augmentin 625mg BD x 5 days)..." style="font-family: monospace;"><?= e($_POST['prescription'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: BILLING & PAYMENT MANAGEMENT -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <div class="card-icon">💳</div>
                        <div>
                            <h2 class="card-title">4. Financial & Payment Record</h2>
                            <div class="card-subtitle">Treatment cost and initial payment receipt</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="treatment_cost">Total Treatment Cost (₹) <span class="required">*</span></label>
                                <input type="number" step="0.01" id="treatment_cost" name="treatment_cost" class="form-control" placeholder="0.00" value="<?= e($_POST['treatment_cost'] ?? '') ?>" required style="font-size:16px; font-weight:700;">
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="amount_paid">Amount Paid Today (₹)</label>
                                <input type="number" step="0.01" id="amount_paid" name="amount_paid" class="form-control" placeholder="0.00" value="<?= e($_POST['amount_paid'] ?? '') ?>" style="font-size:16px; font-weight:700; color:var(--success-text);">
                                <span class="form-hint">Leave 0 if payment is deferred</span>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="payment_method">Payment Method</label>
                                <select id="payment_method" name="payment_method" class="form-control">
                                    <option value="Cash">Cash</option>
                                    <option value="UPI">UPI (GPay / PhonePe / Paytm)</option>
                                    <option value="Card">Debit / Credit Card</option>
                                    <option value="Bank Transfer">Bank Transfer / NEFT</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Live Calculated Balance Card -->
                        <div class="col-12">
                            <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                                <div>
                                    <span style="font-size: 13px; color: var(--text-muted); font-weight: 600;">Calculated Visit Balance:</span>
                                    <span id="live_calculated_balance" style="font-size: 18px; font-weight: 800; color: var(--text-main); margin-left: 10px;">₹ 0.00</span>
                                </div>
                                <div id="live_status_badge">
                                    <span class="badge badge-neutral">Enter amounts to calculate balance</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label" for="payment_notes">Payment Notes / Transaction ID</label>
                                <input type="text" id="payment_notes" name="payment_notes" class="form-control" placeholder="e.g. GPay UPI Ref 9283748291 / Cash advance" value="<?= e($_POST['payment_notes'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" style="padding: 12px 30px; font-size: 15px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        <span>Save Visit & Billing Record &rarr;</span>
                    </button>
                </div>
            </div>
        </form>
    </main>

    <script>
        // Live Balance Calculation
        const costInput = document.getElementById('treatment_cost');
        const paidInput = document.getElementById('amount_paid');
        const balanceDisplay = document.getElementById('live_calculated_balance');
        const statusBadge = document.getElementById('live_status_badge');

        function updateBalance() {
            const cost = parseFloat(costInput.value) || 0;
            const paid = parseFloat(paidInput.value) || 0;
            const bal = Math.max(0, cost - paid);

            balanceDisplay.innerText = '₹ ' + bal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            if (cost === 0 && paid === 0) {
                statusBadge.innerHTML = '<span class="badge badge-neutral">No Charges</span>';
            } else if (bal === 0 && cost > 0) {
                statusBadge.innerHTML = '<span class="badge badge-paid">PAID IN FULL</span>';
            } else {
                statusBadge.innerHTML = `<span class="badge badge-due">BALANCE DUE: ₹ ${bal.toLocaleString('en-IN')}</span>`;
            }
        }

        costInput.addEventListener('input', updateBalance);
        paidInput.addEventListener('input', updateBalance);
        updateBalance();
    </script>

<?php require_once BASE_DIR . '/includes/footer.php'; ?>
