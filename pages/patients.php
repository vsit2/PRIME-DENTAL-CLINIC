<?php
/**
 * Prime Dental Clinic Management System
 * All Patients List Page with Filters, Search, and Status Sorting
 */

define('PRIME_DENTAL', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_DIR . '/config/database.php';
require_once BASE_DIR . '/config/auth.php';
require_once BASE_DIR . '/config/helpers.php';

Auth::requireAuth();

$pageTitle = "All Patients";
$db = getDB();

// Handle Patient Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $deleteId = (int)($_POST['patient_id'] ?? 0);
    if ($deleteId > 0) {
        $stmtDel = $db->prepare("DELETE FROM patients WHERE id = ?");
        $stmtDel->execute([$deleteId]);
        setFlash('success', "Patient record deleted successfully.");
        header("Location: " . BASE_URL . "/pages/patients.php");
        exit;
    }
}

// Search & Filter parameters
$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? 'all'); // all, due, paid
$sortBy = trim($_GET['sort'] ?? 'newest'); // newest, name, balance_desc, reg_asc

// Build Query
$whereConditions = ["1=1"];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(p.registration_no LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR CONCAT(p.first_name, ' ', p.last_name) LIKE ? OR p.mobile LIKE ?)";
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$whereSql = implode(' AND ', $whereConditions);

// Determine Having Clause based on filter
$havingSql = "";
if ($filter === 'due') {
    $havingSql = "HAVING balance > 0";
} elseif ($filter === 'paid') {
    $havingSql = "HAVING total_bill > 0 AND balance = 0";
}

// Determine Order By
$orderSql = "ORDER BY p.id DESC";
if ($sortBy === 'name') {
    $orderSql = "ORDER BY p.first_name ASC, p.last_name ASC";
} elseif ($sortBy === 'balance_desc') {
    $orderSql = "ORDER BY balance DESC";
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
        p.gender,
        p.age,
        p.mobile,
        p.email,
        p.place_of_work,
        p.created_at,
        MAX(v.visit_date) AS last_visit_date,
        COALESCE(SUM(v.treatment_cost), 0) AS total_bill,
        COALESCE(pay.total_paid, 0) AS total_paid,
        (COALESCE(SUM(v.treatment_cost), 0) - COALESCE(pay.total_paid, 0)) AS balance
    FROM patients p
    LEFT JOIN visits v ON p.id = v.patient_id
    LEFT JOIN (
        SELECT patient_id, SUM(amount) AS total_paid 
        FROM payments 
        GROUP BY patient_id
    ) pay ON p.id = pay.patient_id
    WHERE {$whereSql}
    GROUP BY p.id
    {$havingSql}
    {$orderSql}
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();

require_once BASE_DIR . '/includes/header.php';
require_once BASE_DIR . '/includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once BASE_DIR . '/includes/topbar.php'; ?>

    <main class="page-content">
        <!-- Page Header -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
            <div>
                <h1 style="font-size: 24px; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">
                    Patient Records
                </h1>
                <p style="color: var(--text-muted); font-size: 13.5px;">
                    Total <?= count($patients) ?> patient record<?= count($patients) === 1 ? '' : 's' ?> found
                </p>
            </div>
            <a href="<?= BASE_URL ?>/pages/patient-add.php" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Add New Patient</span>
            </a>
        </div>

        <!-- Filter & Search Controls -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-body" style="padding: 16px 20px;">
                <form method="GET" action="" style="display: flex; gap: 14px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
                    <div style="display: flex; gap: 12px; flex-wrap: wrap; flex: 1; max-width: 500px;">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Filter by name, PDC reg no, or mobile..." 
                               value="<?= e($search) ?>" 
                               style="flex: 1; min-width: 220px;">
                        <button type="submit" class="btn btn-secondary">Search</button>
                        <?php if (!empty($search) || $filter !== 'all' || $sortBy !== 'newest'): ?>
                            <a href="<?= BASE_URL ?>/pages/patients.php" class="btn btn-secondary" style="color: var(--text-muted);">Reset</a>
                        <?php endif; ?>
                    </div>

                    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <label class="form-label" style="margin: 0; font-size: 13px;">Status:</label>
                            <select name="filter" class="form-control" style="width: auto; padding: 7px 12px;" onchange="this.form.submit()">
                                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Patients</option>
                                <option value="due" <?= $filter === 'due' ? 'selected' : '' ?>>Balance Due</option>
                                <option value="paid" <?= $filter === 'paid' ? 'selected' : '' ?>>Paid in Full</option>
                            </select>
                        </div>

                        <div style="display: flex; align-items: center; gap: 6px;">
                            <label class="form-label" style="margin: 0; font-size: 13px;">Sort By:</label>
                            <select name="sort" class="form-control" style="width: auto; padding: 7px 12px;" onchange="this.form.submit()">
                                <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Recently Registered</option>
                                <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Patient Name (A-Z)</option>
                                <option value="balance_desc" <?= $sortBy === 'balance_desc' ? 'selected' : '' ?>>Highest Outstanding</option>
                                <option value="reg_asc" <?= $sortBy === 'reg_asc' ? 'selected' : '' ?>>Reg No (Ascending)</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Patients Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Reg No</th>
                            <th>Patient Name</th>
                            <th>Mobile</th>
                            <th>Last Visit</th>
                            <th>Total Bill</th>
                            <th>Total Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($patients)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <div style="font-size: 32px; margin-bottom: 8px;">🔍</div>
                                    <div style="font-weight: 600; font-size: 16px; margin-bottom: 4px;">No patients match your criteria</div>
                                    <p style="margin-bottom: 16px; font-size: 13.5px;">Try clearing filters or register a new patient.</p>
                                    <a href="<?= BASE_URL ?>/pages/patient-add.php" class="btn btn-primary">+ Add New Patient</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($patients as $p): 
                                $fullName = trim($p['first_name'] . ' ' . ($p['middle_name'] ? $p['middle_name'] . ' ' : '') . $p['last_name']);
                                $totalBill = (float)$p['total_bill'];
                                $totalPaid = (float)$p['total_paid'];
                                $balance = max(0, $totalBill - $totalPaid);
                                $isPaid = ($totalBill > 0 && $balance <= 0);
                                $hasDue = ($balance > 0);
                            ?>
                                <tr>
                                    <td>
                                        <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $p['id'] ?>" class="badge badge-primary" style="font-family:monospace;font-size:12.5px;">
                                            <?= e($p['registration_no']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $p['id'] ?>" style="font-weight: 700; color: var(--text-main); font-size: 14.5px;">
                                            <?= e($fullName) ?>
                                        </a>
                                        <div style="font-size: 12px; color: var(--text-muted);">
                                            <?= $p['age'] ?> yrs &bull; <?= e($p['gender']) ?> 
                                            <?= $p['place_of_work'] ? '&bull; ' . e($p['place_of_work']) : '' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500;">📞 <?= e($p['mobile']) ?></div>
                                        <?php if ($p['email']): ?>
                                            <div style="font-size: 11.5px; color: var(--text-muted);"><?= e($p['email']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= formatDate($p['last_visit_date']) ?>
                                    </td>
                                    <td style="font-weight: 600;">
                                        <?= formatCurrency($totalBill) ?>
                                    </td>
                                    <td style="color: var(--success-text); font-weight: 600;">
                                        <?= formatCurrency($totalPaid) ?>
                                    </td>
                                    <td>
                                        <strong style="color: <?= $hasDue ? 'var(--danger-text)' : 'var(--text-muted)' ?>; font-size: 14px;">
                                            <?= formatCurrency($balance) ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php if ($isPaid): ?>
                                            <span class="badge badge-paid">PAID IN FULL</span>
                                        <?php elseif ($hasDue): ?>
                                            <span class="badge badge-due">BALANCE DUE</span>
                                        <?php else: ?>
                                            <span class="badge badge-neutral">NO CHARGES</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="btn-action-group" style="justify-content: flex-end;">
                                            <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $p['id'] ?>" class="btn-action" title="View Patient Profile">
                                                👁️ View
                                            </a>
                                            <a href="<?= BASE_URL ?>/pages/visit-add.php?patient_id=<?= $p['id'] ?>" class="btn-action btn-action-primary" title="Add New Visit">
                                                + Visit
                                            </a>
                                            <a href="<?= BASE_URL ?>/pages/payment-add.php?patient_id=<?= $p['id'] ?>" class="btn-action" title="Add Payment">
                                                ₹ Pay
                                            </a>
                                            <a href="<?= BASE_URL ?>/print/print-record.php?id=<?= $p['id'] ?>" target="_blank" class="btn-action" title="Print Full Patient Record">
                                                🖨️
                                            </a>
                                            <a href="<?= BASE_URL ?>/pages/patient-edit.php?id=<?= $p['id'] ?>" class="btn-action" title="Edit Demographics">
                                                ✏️
                                            </a>
                                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete patient <?= e($fullName) ?> (<?= e($p['registration_no']) ?>)? All visits, clinical records, and payments will be deleted.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="patient_id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn-action btn-action-danger" title="Delete Patient">
                                                    🗑️
                                                </button>
                                            </form>
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
