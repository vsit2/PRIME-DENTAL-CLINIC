<?php
/**
 * Prime Dental Clinic Management System
 * Clinical Visits & Procedures Overview
 */

define('PRIME_DENTAL', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_DIR . '/config/database.php';
require_once BASE_DIR . '/config/auth.php';
require_once BASE_DIR . '/config/helpers.php';

Auth::requireAuth();

$pageTitle = "Visits & Clinical Records";
$db = getDB();

$search = trim($_GET['search'] ?? '');
$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(p.registration_no LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR v.treatment LIKE ? OR v.diagnosis LIKE ?)";
    $term = "%{$search}%";
    $params = array_merge($params, [$term, $term, $term, $term, $term]);
}

if (!empty($fromDate)) {
    $where[] = "v.visit_date >= ?";
    $params[] = $fromDate;
}

if (!empty($toDate)) {
    $where[] = "v.visit_date <= ?";
    $params[] = $toDate;
}

$whereSql = implode(' AND ', $where);

$sql = "
    SELECT 
        v.*,
        p.registration_no,
        p.first_name,
        p.last_name,
        p.mobile,
        COALESCE(SUM(pay.amount), 0) AS visit_paid
    FROM visits v
    JOIN patients p ON v.patient_id = p.id
    LEFT JOIN payments pay ON v.id = pay.visit_id
    WHERE {$whereSql}
    GROUP BY v.id
    ORDER BY v.visit_date DESC, v.id DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$visits = $stmt->fetchAll();

require_once BASE_DIR . '/includes/header.php';
require_once BASE_DIR . '/includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once BASE_DIR . '/includes/topbar.php'; ?>

    <main class="page-content">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
            <div>
                <h1 style="font-size: 24px; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">
                    Visits & Clinical Records
                </h1>
                <p style="color: var(--text-muted); font-size: 13.5px;">
                    <?= count($visits) ?> clinical visit log<?= count($visits) === 1 ? '' : 's' ?> found
                </p>
            </div>
            <a href="<?= BASE_URL ?>/pages/visit-add.php" class="btn btn-primary">
                + Record New Visit
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-body" style="padding: 16px 20px;">
                <form method="GET" action="" style="display: flex; gap: 14px; flex-wrap: wrap; align-items: center;">
                    <input type="text" name="search" class="form-control" placeholder="Search treatment, diagnosis, or patient..." value="<?= e($search) ?>" style="flex: 1; min-width: 220px;">
                    
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13px; color: var(--text-muted);">From:</span>
                        <input type="date" name="from_date" class="form-control" value="<?= e($fromDate) ?>" style="width: auto;">
                    </div>

                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13px; color: var(--text-muted);">To:</span>
                        <input type="date" name="to_date" class="form-control" value="<?= e($toDate) ?>" style="width: auto;">
                    </div>

                    <button type="submit" class="btn btn-secondary">Filter</button>
                    <?php if (!empty($search) || !empty($fromDate) || !empty($toDate)): ?>
                        <a href="<?= BASE_URL ?>/pages/visits.php" class="btn btn-secondary" style="color: var(--text-muted);">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Visits Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Tooth #</th>
                            <th>Treatment Performed</th>
                            <th>Diagnosis</th>
                            <th>Cost</th>
                            <th>Follow-up</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($visits)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 36px; color: var(--text-muted);">
                                    No clinical visits found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($visits as $v): ?>
                                <tr>
                                    <td>
                                        <strong><?= formatDate($v['visit_date']) ?></strong>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $v['patient_id'] ?>" style="font-weight: 600; color: var(--text-main);">
                                            <?= e($v['first_name'] . ' ' . $v['last_name']) ?>
                                        </a>
                                        <div style="font-size: 11.5px; color: var(--text-muted); font-family: monospace;">
                                            <?= e($v['registration_no']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-neutral"><?= e($v['tooth_number'] ?: '—') ?></span>
                                    </td>
                                    <td>
                                        <strong style="color: var(--text-main);"><?= e($v['treatment']) ?></strong>
                                        <?php if ($v['dentist_notes']): ?>
                                            <div style="font-size: 12px; color: var(--text-muted); max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <?= e($v['dentist_notes']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($v['diagnosis'] ?: '—') ?></td>
                                    <td style="font-weight: 700; color: var(--primary-hover);">
                                        <?= formatCurrency($v['treatment_cost']) ?>
                                    </td>
                                    <td>
                                        <?= $v['follow_up_date'] ? formatDate($v['follow_up_date']) : '—' ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="btn-action-group" style="justify-content: flex-end;">
                                            <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $v['patient_id'] ?>" class="btn-action" title="View Patient Profile">
                                                👁️
                                            </a>
                                            <a href="<?= BASE_URL ?>/print/print-prescription.php?id=<?= $v['id'] ?>" target="_blank" class="btn-action" title="Print Prescription Slip">
                                                💊 Rx
                                            </a>
                                            <a href="<?= BASE_URL ?>/pages/payment-add.php?patient_id=<?= $v['patient_id'] ?>&visit_id=<?= $v['id'] ?>" class="btn-action btn-action-primary" title="Record Payment">
                                                ₹ Pay
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
