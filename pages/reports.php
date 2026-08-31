<?php
/**
 * Prime Dental Clinic Management System
 * Reports & Analytics Dashboard
 */

define('PRIME_DENTAL', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_DIR . '/config/database.php';
require_once BASE_DIR . '/config/auth.php';
require_once BASE_DIR . '/config/helpers.php';

Auth::requireAuth();

$pageTitle = "Reports & Analytics";
$db = getDB();
$stats = getClinicFinancialSummary();

// 1. Monthly Financials (Last 6 Months)
$sqlMonthly = "
    SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') AS ym,
        DATE_FORMAT(payment_date, '%M %Y') AS month_label,
        COUNT(*) AS tx_count,
        SUM(amount) AS total_collected
    FROM payments
    WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY ym
    ORDER BY ym DESC
";
$monthlyData = $db->query($sqlMonthly)->fetchAll();

// 2. Payment Method Breakdown
$sqlMethods = "
    SELECT 
        payment_method,
        COUNT(*) AS count,
        SUM(amount) AS total_amount
    FROM payments
    GROUP BY payment_method
    ORDER BY total_amount DESC
";
$methodBreakdown = $db->query($sqlMethods)->fetchAll();

// 3. Demographics (Gender & Age)
$totalPts = max(1, $stats['total_patients']);
$sqlGender = "
    SELECT gender, COUNT(*) AS count 
    FROM patients 
    GROUP BY gender
";
$genderData = $db->query($sqlGender)->fetchAll();

require_once BASE_DIR . '/includes/header.php';
require_once BASE_DIR . '/includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once BASE_DIR . '/includes/topbar.php'; ?>

    <main class="page-content">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
            <div>
                <h1 style="font-size: 24px; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">
                    Clinic Reports & Analytics
                </h1>
                <p style="color: var(--text-muted); font-size: 13.5px;">
                    Comprehensive statistical and financial reporting for Prime Dental Clinic.
                </p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="<?= BASE_URL ?>/pages/export.php?type=patients" class="btn btn-secondary">
                    📥 Export Patients (CSV)
                </a>
                <a href="<?= BASE_URL ?>/pages/export.php?type=payments" class="btn btn-secondary">
                    📥 Export Payments (CSV)
                </a>
            </div>
        </div>

        <!-- Metric Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Clinic Billing</h3>
                    <div class="stat-value"><?= formatCurrency($stats['total_billing']) ?></div>
                    <div class="stat-subtext">Lifetime total treatment fees</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-blue">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Collected</h3>
                    <div class="stat-value" style="color: var(--success-text);"><?= formatCurrency($stats['total_collected']) ?></div>
                    <div class="stat-subtext">This Month: <?= formatCurrency($stats['month_collection']) ?></div>
                </div>
                <div class="stat-icon-wrapper stat-icon-green">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Outstanding</h3>
                    <div class="stat-value" style="color: var(--danger-text);"><?= formatCurrency($stats['total_outstanding']) ?></div>
                    <div class="stat-subtext">Unpaid dues pending</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-red">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Patients</h3>
                    <div class="stat-value"><?= number_format($stats['total_patients']) ?></div>
                    <div class="stat-subtext">+<?= $stats['new_patients_month'] ?> added this month</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-teal">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 28px;">
            <!-- Monthly Collections -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <div class="card-icon">📊</div>
                        <div>
                            <h2 class="card-title">Monthly Collections History</h2>
                            <div class="card-subtitle">Fee collections per month</div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Transactions</th>
                                <th style="text-align: right;">Total Amount Collected</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($monthlyData)): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 24px; color: var(--text-muted);">
                                        No recent payment records found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($monthlyData as $m): ?>
                                    <tr>
                                        <td><strong><?= e($m['month_label']) ?></strong></td>
                                        <td><?= $m['tx_count'] ?> payments</td>
                                        <td style="text-align: right; font-weight: 700; color: var(--success-text);">
                                            <?= formatCurrency($m['total_collected']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment Method Distribution -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <div class="card-icon">💳</div>
                        <div>
                            <h2 class="card-title">Payment Methods</h2>
                            <div class="card-subtitle">Breakdown by channel</div>
                        </div>
                    </div>
                </div>
                <div class="card-body" style="padding: 16px;">
                    <?php if (empty($methodBreakdown)): ?>
                        <p style="color: var(--text-muted); text-align: center;">No payment data available.</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 14px;">
                            <?php foreach ($methodBreakdown as $mb): 
                                $pct = ($stats['total_collected'] > 0) ? round(($mb['total_amount'] / $stats['total_collected']) * 100, 1) : 0;
                            ?>
                                <div>
                                    <div style="display: flex; justify-content: space-between; font-size: 13.5px; margin-bottom: 4px;">
                                        <strong><?= e($mb['payment_method']) ?> (<?= $mb['count'] ?> tx)</strong>
                                        <span style="font-weight: 700;"><?= formatCurrency($mb['total_amount']) ?> (<?= $pct ?>%)</span>
                                    </div>
                                    <div style="background: #e2e8f0; height: 8px; border-radius: 9999px; overflow: hidden;">
                                        <div style="background: var(--primary); height: 100%; width: <?= $pct ?>%;"></div>
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
