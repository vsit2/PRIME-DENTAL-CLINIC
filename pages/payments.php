<?php
/**
 * Prime Dental Clinic Management System
 * Payments & Accounts Ledger
 */

define('PRIME_DENTAL', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_DIR . '/config/database.php';
require_once BASE_DIR . '/config/auth.php';
require_once BASE_DIR . '/config/helpers.php';

Auth::requireAuth();

$pageTitle = "Payments & Accounts";
$db = getDB();

$search = trim($_GET['search'] ?? '');
$method = trim($_GET['method'] ?? 'all');
$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(pay.receipt_no LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR p.registration_no LIKE ? OR pay.notes LIKE ?)";
    $term = "%{$search}%";
    $params = array_merge($params, [$term, $term, $term, $term, $term]);
}

if ($method !== 'all' && !empty($method)) {
    $where[] = "pay.payment_method = ?";
    $params[] = $method;
}

if (!empty($fromDate)) {
    $where[] = "pay.payment_date >= ?";
    $params[] = $fromDate;
}

if (!empty($toDate)) {
    $where[] = "pay.payment_date <= ?";
    $params[] = $toDate;
}

$whereSql = implode(' AND ', $where);

$sql = "
    SELECT 
        pay.*,
        p.registration_no,
        p.first_name,
        p.last_name,
        p.mobile,
        v.treatment,
        v.treatment_cost
    FROM payments pay
    JOIN patients p ON pay.patient_id = p.id
    LEFT JOIN visits v ON pay.visit_id = v.id
    WHERE {$whereSql}
    ORDER BY pay.payment_date DESC, pay.id DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Total collected in current filter
$filteredTotal = 0;
foreach ($payments as $py) {
    $filteredTotal += (float)$py['amount'];
}

require_once BASE_DIR . '/includes/header.php';
require_once BASE_DIR . '/includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once BASE_DIR . '/includes/topbar.php'; ?>

    <main class="page-content">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
            <div>
                <h1 style="font-size: 24px; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">
                    Payments & Billing Ledger
                </h1>
                <p style="color: var(--text-muted); font-size: 13.5px;">
                    Total <?= count($payments) ?> payment transaction<?= count($payments) === 1 ? '' : 's' ?> &bull; Filtered Total: <strong><?= formatCurrency($filteredTotal) ?></strong>
                </p>
            </div>
            <a href="<?= BASE_URL ?>/pages/payment-add.php" class="btn btn-primary">
                + Record New Payment
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-body" style="padding: 16px 20px;">
                <form method="GET" action="" style="display: flex; gap: 14px; flex-wrap: wrap; align-items: center;">
                    <input type="text" name="search" class="form-control" placeholder="Search receipt no, patient name, notes..." value="<?= e($search) ?>" style="flex: 1; min-width: 200px;">

                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13px; color: var(--text-muted);">Method:</span>
                        <select name="method" class="form-control" style="width: auto;" onchange="this.form.submit()">
                            <option value="all" <?= $method === 'all' ? 'selected' : '' ?>>All Methods</option>
                            <option value="Cash" <?= $method === 'Cash' ? 'selected' : '' ?>>Cash</option>
                            <option value="UPI" <?= $method === 'UPI' ? 'selected' : '' ?>>UPI</option>
                            <option value="Card" <?= $method === 'Card' ? 'selected' : '' ?>>Card</option>
                            <option value="Bank Transfer" <?= $method === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                            <option value="Other" <?= $method === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13px; color: var(--text-muted);">From:</span>
                        <input type="date" name="from_date" class="form-control" value="<?= e($fromDate) ?>" style="width: auto;">
                    </div>

                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13px; color: var(--text-muted);">To:</span>
                        <input type="date" name="to_date" class="form-control" value="<?= e($toDate) ?>" style="width: auto;">
                    </div>

                    <button type="submit" class="btn btn-secondary">Filter</button>
                    <?php if (!empty($search) || $method !== 'all' || !empty($fromDate) || !empty($toDate)): ?>
                        <a href="<?= BASE_URL ?>/pages/payments.php" class="btn btn-secondary" style="color: var(--text-muted);">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Receipt No</th>
                            <th>Date</th>
                            <th>Patient Name</th>
                            <th>Treatment / Reason</th>
                            <th>Payment Method</th>
                            <th>Amount (₹)</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 36px; color: var(--text-muted);">
                                    No payment transactions found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $pay): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-primary" style="font-family: monospace; font-size: 12.5px;">
                                            <?= e($pay['receipt_no']) ?>
                                        </span>
                                    </td>
                                    <td><?= formatDate($pay['payment_date']) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $pay['patient_id'] ?>" style="font-weight: 600; color: var(--text-main);">
                                            <?= e($pay['first_name'] . ' ' . $pay['last_name']) ?>
                                        </a>
                                        <div style="font-size: 11.5px; color: var(--text-muted); font-family: monospace;">
                                            <?= e($pay['registration_no']) ?> &bull; 📞 <?= e($pay['mobile']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= e($pay['treatment'] ?: 'General Dental Bill') ?></strong>
                                        <?php if ($pay['notes']): ?>
                                            <div style="font-size: 12px; color: var(--text-muted);"><?= e($pay['notes']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-neutral"><?= e($pay['payment_method']) ?></span>
                                    </td>
                                    <td>
                                        <strong style="color: var(--success-text); font-size: 15px;">
                                            <?= formatCurrency($pay['amount']) ?>
                                        </strong>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="btn-action-group" style="justify-content: flex-end;">
                                            <a href="<?= BASE_URL ?>/print/print-receipt.php?id=<?= $pay['id'] ?>" target="_blank" class="btn-action" title="Print Official Receipt">
                                                🧾 Print Receipt
                                            </a>
                                            <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $pay['patient_id'] ?>" class="btn-action" title="View Patient Profile">
                                                👁️ Profile
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

<?php require_once BASE_DIR . '/includes/footer.php'; ?>
