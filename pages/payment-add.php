<?php
/**
 * Prime Dental Clinic Management System
 * Record Payment for Patient
 */

define('PRIME_DENTAL', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_DIR . '/config/database.php';
require_once BASE_DIR . '/config/auth.php';
require_once BASE_DIR . '/config/helpers.php';

Auth::requireAuth();

$db = getDB();
$patientId = (int)($_GET['patient_id'] ?? 0);
$visitId = !empty($_GET['visit_id']) ? (int)$_GET['visit_id'] : null;

$patient = null;
$financials = ['total_bill' => 0, 'total_paid' => 0, 'balance' => 0];

if ($patientId > 0) {
    $stmt = $db->prepare("SELECT * FROM patients WHERE id = ? LIMIT 1");
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();
    if ($patient) {
        $financials = getPatientFinancialSummary($patientId);
    }
}

// Fetch all patients for selection if no patient selected
$allPatients = $db->query("SELECT id, registration_no, first_name, last_name, mobile FROM patients ORDER BY first_name ASC")->fetchAll();

// Fetch patient's visits if patient is selected
$patientVisits = [];
if ($patientId > 0) {
    $stmtV = $db->prepare("SELECT id, visit_date, treatment, treatment_cost FROM visits WHERE patient_id = ? ORDER BY visit_date DESC");
    $stmtV->execute([$patientId]);
    $patientVisits = $stmtV->fetchAll();
}

$nextReceiptNo = getNextReceiptNumber();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedPatientId = (int)($_POST['patient_id'] ?? 0);
    $selectedVisitId = !empty($_POST['visit_id']) ? (int)$_POST['visit_id'] : null;
    $paymentDate = trim($_POST['payment_date'] ?? date('Y-m-d'));
    $amount = (float)($_POST['amount'] ?? 0);
    $paymentMethod = trim($_POST['payment_method'] ?? 'Cash');
    $notes = trim($_POST['notes'] ?? '');

    if ($selectedPatientId <= 0) $errors[] = "Please select a patient.";
    if ($amount <= 0) $errors[] = "Payment amount must be greater than zero.";
    if (empty($paymentDate)) $errors[] = "Payment date is required.";

    if (empty($errors)) {
        try {
            $receiptNo = getNextReceiptNumber();
            $stmtPay = $db->prepare("
                INSERT INTO payments (receipt_no, patient_id, visit_id, payment_date, amount, payment_method, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtPay->execute([
                $receiptNo,
                $selectedPatientId,
                $selectedVisitId,
                $paymentDate,
                $amount,
                $paymentMethod,
                $notes
            ]);
            $paymentId = $db->lastInsertId();

            setFlash('success', "Payment of " . formatCurrency($amount) . " recorded successfully with Receipt No: {$receiptNo}!");
            header("Location: " . BASE_URL . "/pages/patient-view.php?id=" . $selectedPatientId);
            exit;

        } catch (Exception $e) {
            $errors[] = "Failed to record payment: " . $e->getMessage();
        }
    }
}

$pageTitle = "Record Payment";
require_once BASE_DIR . '/includes/header.php';
require_once BASE_DIR . '/includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once BASE_DIR . '/includes/topbar.php'; ?>

    <main class="page-content">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
            <div>
                <h1 style="font-size: 24px; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">
                    Record Patient Payment
                </h1>
                <p style="color: var(--text-muted); font-size: 13.5px;">
                    Log payment transaction, generate receipt, and update outstanding balances.
                </p>
            </div>
            <?php if ($patient): ?>
                <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $patient['id'] ?>" class="btn btn-secondary">
                    &larr; Back to Patient Profile
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/pages/payments.php" class="btn btn-secondary">
                    &larr; Back to Payments
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

        <?php if ($patient): ?>
            <!-- Patient Financial Summary Banner -->
            <div class="card" style="background: linear-gradient(135deg, #f0fdfa, #f8fafc); border: 1px solid #ccfbf1; margin-bottom: 24px;">
                <div class="card-body" style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                        <div>
                            <div style="font-size: 18px; font-weight: 700; color: var(--text-main);">
                                <?= e($patient['first_name'] . ' ' . $patient['last_name']) ?>
                            </div>
                            <div style="font-size: 13px; color: var(--text-muted);">
                                Reg No: <strong><?= e($patient['registration_no']) ?></strong> &bull; 📞 <?= e($patient['mobile']) ?>
                            </div>
                        </div>

                        <div style="display: flex; gap: 20px; text-align: right;">
                            <div>
                                <div style="font-size: 11.5px; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Total Bill</div>
                                <div style="font-size: 16px; font-weight: 700;"><?= formatCurrency($financials['total_bill']) ?></div>
                            </div>
                            <div>
                                <div style="font-size: 11.5px; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Total Paid</div>
                                <div style="font-size: 16px; font-weight: 700; color: var(--success-text);"><?= formatCurrency($financials['total_paid']) ?></div>
                            </div>
                            <div>
                                <div style="font-size: 11.5px; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Outstanding Due</div>
                                <div style="font-size: 18px; font-weight: 800; color: <?= $financials['balance'] > 0 ? 'var(--danger-text)' : 'var(--text-muted)' ?>;">
                                    <?= formatCurrency($financials['balance']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <div class="card-icon">🧾</div>
                        <div>
                            <h2 class="card-title">Payment Details</h2>
                            <div class="card-subtitle">Next Receipt Sequence: <strong><?= $nextReceiptNo ?></strong></div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="patient_id">Patient <span class="required">*</span></label>
                                <?php if ($patient): ?>
                                    <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                                    <input type="text" class="form-control" value="<?= e($patient['registration_no'] . ' — ' . $patient['first_name'] . ' ' . $patient['last_name']) ?>" readonly style="background:#f8fafc;font-weight:600;">
                                <?php else: ?>
                                    <select id="patient_id" name="patient_id" class="form-control" required onchange="window.location='?patient_id='+this.value">
                                        <option value="">-- Select Patient --</option>
                                        <?php foreach ($allPatients as $p): ?>
                                            <option value="<?= $p['id'] ?>">
                                                <?= e($p['registration_no']) ?> &bull; <?= e($p['first_name'] . ' ' . $p['last_name']) ?> (<?= e($p['mobile']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="visit_id">Linked Visit / Treatment (Optional)</label>
                                <select id="visit_id" name="visit_id" class="form-control">
                                    <option value="">-- General Account Payment / Advance --</option>
                                    <?php foreach ($patientVisits as $pv): ?>
                                        <option value="<?= $pv['id'] ?>" <?= ($visitId === (int)$pv['id']) ? 'selected' : '' ?>>
                                            <?= formatDate($pv['visit_date']) ?> — <?= e($pv['treatment']) ?> (Cost: <?= formatCurrency($pv['treatment_cost']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="payment_date">Payment Date <span class="required">*</span></label>
                                <input type="date" id="payment_date" name="payment_date" class="form-control" value="<?= e($_POST['payment_date'] ?? date('Y-m-d')) ?>" required>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="amount">Amount to Pay (₹) <span class="required">*</span></label>
                                <input type="number" step="0.01" id="amount" name="amount" class="form-control" placeholder="0.00" value="<?= e($_POST['amount'] ?? ($financials['balance'] > 0 ? $financials['balance'] : '')) ?>" required style="font-size:16px; font-weight:700; color:var(--success-text);">
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="payment_method">Payment Method <span class="required">*</span></label>
                                <select id="payment_method" name="payment_method" class="form-control" required>
                                    <option value="Cash">Cash</option>
                                    <option value="UPI">UPI (Google Pay / PhonePe / Paytm)</option>
                                    <option value="Card">Credit / Debit Card</option>
                                    <option value="Bank Transfer">Bank Transfer / NEFT / IMPS</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Live Calculated Remaining Balance -->
                        <?php if ($patient): ?>
                            <div class="col-12">
                                <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 14px 20px; display: flex; align-items: center; justify-content: space-between;">
                                    <div>
                                        <span style="font-size: 13px; color: var(--text-muted); font-weight: 600;">Remaining Balance After This Payment:</span>
                                        <span id="live_remaining_after_pay" style="font-size: 18px; font-weight: 800; color: var(--text-main); margin-left: 10px;">₹ 0.00</span>
                                    </div>
                                    <div id="live_pay_badge">
                                        <span class="badge badge-neutral">Ready</span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label" for="notes">Payment Notes / Transaction Reference</label>
                                <input type="text" id="notes" name="notes" class="form-control" placeholder="e.g. UPI Ref #89283728392, Cheque #000123, Cash installment" value="<?= e($_POST['notes'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" style="padding: 12px 30px; font-size: 15px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>Confirm & Record Payment &rarr;</span>
                    </button>
                </div>
            </div>
        </form>
    </main>

    <?php if ($patient): ?>
        <script>
            const totalDue = <?= (float)$financials['balance'] ?>;
            const payAmountInput = document.getElementById('amount');
            const remainingDisplay = document.getElementById('live_remaining_after_pay');
            const payBadge = document.getElementById('live_pay_badge');

            function calcRemaining() {
                const paying = parseFloat(payAmountInput.value) || 0;
                const rem = Math.max(0, totalDue - paying);

                remainingDisplay.innerText = '₹ ' + rem.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                if (paying >= totalDue && totalDue > 0) {
                    payBadge.innerHTML = '<span class="badge badge-paid">WILL BE PAID IN FULL</span>';
                } else if (paying > 0 && rem > 0) {
                    payBadge.innerHTML = `<span class="badge badge-partial">REMAINING DUE: ₹ ${rem.toLocaleString('en-IN')}</span>`;
                } else {
                    payBadge.innerHTML = '<span class="badge badge-due">BALANCE DUE</span>';
                }
            }

            payAmountInput.addEventListener('input', calcRemaining);
            calcRemaining();
        </script>
    <?php endif; ?>

<?php require_once BASE_DIR . '/includes/footer.php'; ?>
