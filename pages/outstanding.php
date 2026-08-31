<?php
/**
 * Prime Dental Clinic Management System
 * Outstanding Payments Tracker
 */

define('PRIME_DENTAL', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_DIR . '/config/database.php';
require_once BASE_DIR . '/config/auth.php';
require_once BASE_DIR . '/config/helpers.php';

Auth::requireAuth();

$pageTitle = "Outstanding Payments";
$db = getDB();

$search = trim($_GET['search'] ?? '');
$sortBy = trim($_GET['sort'] ?? 'highest'); // highest, name, oldest

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(p.registration_no LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR CONCAT(p.first_name, ' ', p.last_name) LIKE ? OR p.mobile LIKE ?)";
    $term = "%{$search}%";
    $params = array_merge($params, [$term, $term, $term, $term, $term]);
}

$whereSql = implode(' AND ', $where);

$orderSql = "ORDER BY balance DESC";
if ($sortBy === 'name') {
    $orderSql = "ORDER BY p.first_name ASC, p.last_name ASC";
} elseif ($sortBy === 'reg_asc') {
    $orderSql = "ORDER BY p.registration_no ASC";
}

$sql = "
    SELECT 
        p.id,
        p.registration_no,
        p.first_name,
        p.middle_name,
        p.last_name,
        p.mobile,
        p.gender,
        p.age,
        COALESCE(SUM(v.treatment_cost), 0) AS total_bill,
        COALESCE(pay.total_paid, 0) AS total_paid,
        (COALESCE(SUM(v.treatment_cost), 0) - COALESCE(pay.total_paid, 0)) AS balance,
        pay.last_payment_date,
        MAX(v.visit_date) AS last_visit_date
    FROM patients p
    LEFT JOIN visits v ON p.id = v.patient_id
    LEFT JOIN (
        SELECT 
            patient_id, 
            SUM(amount) AS total_paid,
            MAX(payment_date) AS last_payment_date
        FROM payments 
        GROUP BY patient_id
    ) pay ON p.id = pay.patient_id
    WHERE {$whereSql}
    GROUP BY p.id
    HAVING balance > 0
    {$orderSql}
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$outstandingPatients = $stmt->fetchAll();

$totalOutstandingSum = 0;
foreach ($outstandingPatients as $op) {
    $totalOutstandingSum += (float)$op['balance'];
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
                    Outstanding Payments Tracker
                </h1>
                <p style="color: var(--text-muted); font-size: 13.5px;">
                    Track and collect unpaid dental balances from patients.
                </p>
            </div>
            <a href="<?= BASE_URL ?>/pages/export.php?type=outstanding" class="btn btn-secondary">
                📥 Export Dues List (CSV)
            </a>
        </div>

        <!-- Summary Banner -->
        <div class="stats-grid" style="margin-bottom: 24px;">
            <div class="stat-card" style="border-left: 4px solid var(--danger);">
                <div class="stat-info">
                    <h3>Total Outstanding Amount</h3>
                    <div class="stat-value" style="color: var(--danger-text);">
                        <?= formatCurrency($totalOutstandingSum) ?>
                    </div>
                    <div class="stat-subtext">Across <?= count($outstandingPatients) ?> patient accounts</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-red">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3>Patients with Balance Due</h3>
                    <div class="stat-value"><?= count($outstandingPatients) ?></div>
                    <div class="stat-subtext">Requires follow-up</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-amber">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                </div>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-body" style="padding: 16px 20px;">
                <form method="GET" action="" style="display: flex; gap: 14px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
                    <div style="display: flex; gap: 10px; flex: 1; max-width: 480px;">
                        <input type="text" name="search" class="form-control" placeholder="Search patient by name, PDC no, or mobile..." value="<?= e($search) ?>">
                        <button type="submit" class="btn btn-secondary">Search</button>
                        <?php if (!empty($search) || $sortBy !== 'highest'): ?>
                            <a href="<?= BASE_URL ?>/pages/outstanding.php" class="btn btn-secondary" style="color: var(--text-muted);">Reset</a>
                        <?php endif; ?>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px;">
                        <label class="form-label" style="margin: 0; font-size: 13px;">Sort By:</label>
                        <select name="sort" class="form-control" style="width: auto;" onchange="this.form.submit()">
                            <option value="highest" <?= $sortBy === 'highest' ? 'selected' : '' ?>>Highest Balance Due</option>
                            <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Patient Name (A-Z)</option>
                            <option value="reg_asc" <?= $sortBy === 'reg_asc' ? 'selected' : '' ?>>Reg No (Ascending)</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- Outstanding Patients Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Reg No</th>
                            <th>Patient Name</th>
                            <th>Mobile</th>
                            <th>Total Bill</th>
                            <th>Paid to Date</th>
                            <th>Outstanding Balance</th>
                            <th>Last Payment Date</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($outstandingPatients)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <div style="font-size: 36px; margin-bottom: 8px;">🎉</div>
                                    <div style="font-weight: 700; font-size: 16px; color: var(--success-text);">All Patient Balances are Clear!</div>
                                    <p style="font-size: 13px; margin-top: 4px;">There are currently no outstanding dues on record.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($outstandingPatients as $p): 
                                $fullName = trim($p['first_name'] . ' ' . ($p['middle_name'] ? $p['middle_name'] . ' ' : '') . $p['last_name']);
                            ?>
                                <tr>
                                    <td>
                                        <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $p['id'] ?>" class="badge badge-primary" style="font-family: monospace; font-size: 12.5px;">
                                            <?= e($p['registration_no']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $p['id'] ?>" style="font-weight: 700; color: var(--text-main); font-size: 14.5px;">
                                            <?= e($fullName) ?>
                                        </a>
                                        <div style="font-size: 12px; color: var(--text-muted);">
                                            <?= $p['age'] ?>y &bull; <?= e($p['gender']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong>📞 <?= e($p['mobile']) ?></strong>
                                    </td>
                                    <td style="font-weight: 600;"><?= formatCurrency($p['total_bill']) ?></td>
                                    <td style="color: var(--success-text); font-weight: 600;"><?= formatCurrency($p['total_paid']) ?></td>
                                    <td>
                                        <span class="badge badge-due" style="font-size: 13.5px; font-weight: 800; padding: 6px 12px;">
                                            <?= formatCurrency($p['balance']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= formatDate($p['last_payment_date'] ?: $p['last_visit_date']) ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="btn-action-group" style="justify-content: flex-end;">
                                            <a href="<?= BASE_URL ?>/pages/payment-add.php?patient_id=<?= $p['id'] ?>" class="btn-action btn-action-primary" title="Record Payment">
                                                ₹ Collect Payment
                                            </a>
                                            <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $p['id'] ?>" class="btn-action" title="View Patient Profile">
                                                👁️
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
