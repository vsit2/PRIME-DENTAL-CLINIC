<?php
/**
 * Prime Dental Clinic Management System
 * Main Dashboard Page
 */

if (!defined('PRIME_DENTAL')) {
    define('PRIME_DENTAL', true);
}
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_DIR . '/config/database.php';
require_once BASE_DIR . '/config/auth.php';
require_once BASE_DIR . '/config/helpers.php';

Auth::requireAuth();

$pageTitle = "Dashboard";
$clinic = getClinicSettings();
$stats = getClinicFinancialSummary();
$db = getDB();

// Fetch Recent Patients with financial summary
$sqlRecent = "
    SELECT 
        p.id, 
        p.registration_no, 
        p.first_name, 
        p.middle_name, 
        p.last_name, 
        p.mobile, 
        p.gender, 
        p.age,
        p.created_at,
        COALESCE(SUM(v.treatment_cost), 0) AS total_bill,
        COALESCE(pay.total_paid, 0) AS total_paid
    FROM patients p
    LEFT JOIN visits v ON p.id = v.patient_id
    LEFT JOIN (
        SELECT patient_id, SUM(amount) AS total_paid 
        FROM payments 
        GROUP BY patient_id
    ) pay ON p.id = pay.patient_id
    GROUP BY p.id
    ORDER BY p.id DESC
    LIMIT 6
";
$recentPatients = $db->query($sqlRecent)->fetchAll();

// Fetch Recent Clinical Visits
$sqlRecentVisits = "
    SELECT 
        v.id,
        v.patient_id,
        v.visit_date,
        v.treatment,
        v.tooth_number,
        v.treatment_cost,
        p.registration_no,
        p.first_name,
        p.last_name
    FROM visits v
    JOIN patients p ON v.patient_id = p.id
    ORDER BY v.visit_date DESC, v.id DESC
    LIMIT 5
";
$recentVisits = $db->query($sqlRecentVisits)->fetchAll();

require_once BASE_DIR . '/includes/header.php';
require_once BASE_DIR . '/includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once BASE_DIR . '/includes/topbar.php'; ?>

    <main class="page-content">
        <!-- 1. CLINIC / DENTIST HERO BANNER -->
        <div class="clinic-hero-banner">
            <div class="hero-content-row">
                <div class="hero-left">
                    <div class="hero-badge-icon">🦷</div>
                    <div>
                        <h1 class="hero-clinic-title"><?= e($clinic['clinic_name']) ?></h1>
                        <div class="hero-tagline">"<?= e($clinic['tagline']) ?>"</div>
                        
                        <div class="hero-doctor-meta">
                            <span class="hero-doc-badge">
                                👨‍⚕️ <?= e($clinic['dentist_name']) ?> <?= e($clinic['dentist_qualification']) ?>
                            </span>
                            <span class="hero-doc-badge" style="background:rgba(255,255,255,0.15);">
                                Reg No: <strong><?= e($clinic['reg_no']) ?></strong>
                            </span>
                            <span>📞 <?= e($clinic['phone']) ?></span>
                            <span>✉️ <?= e($clinic['email']) ?></span>
                        </div>

                        <div class="hero-address-snippet">
                            📍 <?= e($clinic['address']) ?>
                        </div>
                    </div>
                </div>

                <div class="hero-actions">
                    <a href="<?= BASE_URL ?>/pages/patient-add.php" class="hero-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Register Patient</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 2. PROMINENT DASHBOARD LIVE SEARCH -->
        <div class="card search-card" style="margin-bottom: 24px; border: 2px solid var(--primary-light); overflow: visible !important; position: relative; z-index: 50;">
            <div class="card-body" style="padding: 16px 20px;">
                <div style="font-size: 13px; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <span>Instant Patient Finder</span>
                </div>
                <div class="global-search-container">
                    <div class="search-input-wrapper">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" 
                               class="search-input global-patient-search-input" 
                               placeholder="Search patient by name, registration number or mobile number..." 
                               autocomplete="off"
                               style="font-size: 15px; padding: 12px 16px 12px 46px; background: #ffffff;">
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. REAL-TIME DATABASE STATISTICS CARDS -->
        <div class="stats-grid">
            <!-- Total Patients -->
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Patients</h3>
                    <div class="stat-value"><?= number_format($stats['total_patients']) ?></div>
                    <div class="stat-subtext">
                        <span class="badge badge-primary" style="font-size:11px;">+<?= $stats['new_patients_month'] ?> this month</span>
                    </div>
                </div>
                <div class="stat-icon-wrapper stat-icon-teal">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
            </div>

            <!-- Today's Patients -->
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Today's Patients</h3>
                    <div class="stat-value"><?= number_format($stats['today_patients']) ?></div>
                    <div class="stat-subtext">
                        <span>Visits recorded today</span>
                    </div>
                </div>
                <div class="stat-icon-wrapper stat-icon-blue">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
            </div>

            <!-- Total Amount Collected -->
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Collected</h3>
                    <div class="stat-value" style="color: var(--success-text);">
                        <?= formatCurrency($stats['total_collected']) ?>
                    </div>
                    <div class="stat-subtext">
                        <span>Today: <?= formatCurrency($stats['today_collection']) ?></span>
                    </div>
                </div>
                <div class="stat-icon-wrapper stat-icon-green">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
            </div>

            <!-- Outstanding Balance -->
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Outstanding Balance</h3>
                    <div class="stat-value" style="color: var(--danger-text);">
                        <?= formatCurrency($stats['total_outstanding']) ?>
                    </div>
                    <div class="stat-subtext">
                        <a href="<?= BASE_URL ?>/pages/outstanding.php" style="color: var(--danger); font-weight: 600; text-decoration: underline;">
                            View Unpaid Balances &rarr;
                        </a>
                    </div>
                </div>
                <div class="stat-icon-wrapper stat-icon-red">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 4. QUICK ACTION SHORTCUTS -->
        <div style="display: flex; gap: 12px; margin-bottom: 28px; flex-wrap: wrap;">
            <a href="<?= BASE_URL ?>/pages/patient-add.php" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                <span>Quick Add Patient</span>
            </a>
            <a href="<?= BASE_URL ?>/pages/patients.php" class="btn btn-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <span>Patient Records</span>
            </a>
            <a href="<?= BASE_URL ?>/pages/payments.php" class="btn btn-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                <span>Payment & Accounts</span>
            </a>
            <a href="<?= BASE_URL ?>/pages/outstanding.php" class="btn btn-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <span>Outstanding Dues</span>
            </a>
            <a href="<?= BASE_URL ?>/pages/reports.php" class="btn btn-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                <span>Reports & Analytics</span>
            </a>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
            <!-- 5. RECENT PATIENTS LIST -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <div class="card-icon">👥</div>
                        <div>
                            <h2 class="card-title">Recent Patients</h2>
                            <div class="card-subtitle">Latest registered patient profiles</div>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>/pages/patients.php" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">
                        View All Patients &rarr;
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Reg No</th>
                                <th>Patient Name</th>
                                <th>Mobile</th>
                                <th>Total Bill</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentPatients)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                        No patients registered yet. Click "Quick Add Patient" to create the first record!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentPatients as $p): 
                                    $fullName = trim($p['first_name'] . ' ' . ($p['middle_name'] ? $p['middle_name'] . ' ' : '') . $p['last_name']);
                                    $totalBill = (float)$p['total_bill'];
                                    $totalPaid = (float)$p['total_paid'];
                                    $balance = max(0, $totalBill - $totalPaid);
                                    $isPaid = ($totalBill > 0 && $balance <= 0);
                                    $hasDue = ($balance > 0);
                                ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-primary" style="font-family:monospace;font-size:12px;">
                                                <?= e($p['registration_no']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $p['id'] ?>" style="font-weight:600;color:var(--text-main);">
                                                <?= e($fullName) ?>
                                            </a>
                                            <div style="font-size:11.5px;color:var(--text-muted);"><?= $p['age'] ?> yrs &bull; <?= e($p['gender']) ?></div>
                                        </td>
                                        <td><?= e($p['mobile']) ?></td>
                                        <td style="font-weight:600;"><?= formatCurrency($totalBill) ?></td>
                                        <td style="color:var(--success-text);font-weight:600;"><?= formatCurrency($totalPaid) ?></td>
                                        <td>
                                            <strong style="color: <?= $hasDue ? 'var(--danger)' : 'var(--text-muted)' ?>;">
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
                                        <td>
                                            <div class="btn-action-group">
                                                <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $p['id'] ?>" class="btn-action" title="View Profile">
                                                    👁️
                                                </a>
                                                <a href="<?= BASE_URL ?>/pages/visit-add.php?patient_id=<?= $p['id'] ?>" class="btn-action btn-action-primary" title="Add Visit">
                                                    + Visit
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

            <!-- 6. RECENT VISITS & CLINICAL ACTIVITY -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <div class="card-icon">🩺</div>
                        <div>
                            <h2 class="card-title">Recent Visits</h2>
                            <div class="card-subtitle">Clinical treatment logs</div>
                        </div>
                    </div>
                </div>
                <div class="card-body" style="padding: 12px 18px;">
                    <?php if (empty($recentVisits)): ?>
                        <div style="text-align: center; padding: 24px; color: var(--text-muted); font-size: 13px;">
                            No recent clinical visits recorded.
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 14px;">
                            <?php foreach ($recentVisits as $v): ?>
                                <div style="border-bottom: 1px solid var(--border-subtle); padding-bottom: 12px;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                                        <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $v['patient_id'] ?>" style="font-weight: 600; font-size: 13.5px; color: var(--text-main);">
                                            <?= e($v['first_name'] . ' ' . $v['last_name']) ?>
                                        </a>
                                        <span style="font-size: 11.5px; color: var(--text-muted); font-weight: 500;">
                                            📅 <?= formatDate($v['visit_date']) ?>
                                        </span>
                                    </div>
                                    <div style="font-size: 12.5px; color: var(--text-body); margin-bottom: 4px;">
                                        <strong>Treatment:</strong> <?= e($v['treatment'] ?: 'General Consultation') ?>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px;">
                                        <span class="badge badge-neutral">Tooth: <?= e($v['tooth_number'] ?: '—') ?></span>
                                        <strong style="color: var(--primary-hover);"><?= formatCurrency($v['treatment_cost']) ?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

<?php require_once BASE_DIR . '/includes/footer.php'; ?>
