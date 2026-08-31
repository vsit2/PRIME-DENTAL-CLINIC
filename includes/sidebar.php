<?php
/**
 * Prime Dental Clinic Management System
 * Sidebar Navigation Component
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

// Dynamic count for Outstanding balance badge
try {
    $db = getDB();
    $sqlOutstandingCount = "
        SELECT COUNT(*) FROM (
            SELECT p.id
            FROM patients p
            LEFT JOIN (SELECT patient_id, SUM(treatment_cost) as total_cost FROM visits GROUP BY patient_id) v ON p.id = v.patient_id
            LEFT JOIN (SELECT patient_id, SUM(amount) as total_paid FROM payments GROUP BY patient_id) pay ON p.id = pay.patient_id
            WHERE (COALESCE(v.total_cost, 0) - COALESCE(pay.total_paid, 0)) > 0
        ) as dues
    ";
    $dueCount = (int)$db->query($sqlOutstandingCount)->fetchColumn();
} catch (Exception $e) {
    $dueCount = 0;
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="<?= BASE_URL ?>/index.php" class="sidebar-brand-link">
            <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="<?= e(CLINIC_NAME) ?>" class="sidebar-logo">
        </a>
    </div>

    <ul class="sidebar-nav">
        <li class="nav-section-title">Main Navigation</li>
        
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/index.php" class="nav-link <?= ($currentPage === 'index.php' || $currentPage === 'dashboard.php') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="nav-section-title">Patient Management</li>

        <li class="nav-item">
            <a href="<?= BASE_URL ?>/pages/patients.php" class="nav-link <?= ($currentPage === 'patients.php' || $currentPage === 'patient-view.php' || $currentPage === 'patient-edit.php') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>All Patients</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="<?= BASE_URL ?>/pages/patient-add.php" class="nav-link <?= ($currentPage === 'patient-add.php') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
                <span>Add Patient</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="<?= BASE_URL ?>/pages/visits.php" class="nav-link <?= ($currentPage === 'visits.php' || $currentPage === 'visit-add.php' || $currentPage === 'visit-view.php') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span>Visits & Clinical</span>
            </a>
        </li>

        <li class="nav-section-title">Billing & Accounts</li>

        <li class="nav-item">
            <a href="<?= BASE_URL ?>/pages/payments.php" class="nav-link <?= ($currentPage === 'payments.php' || $currentPage === 'payment-add.php') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                <span>Payments & Billing</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="<?= BASE_URL ?>/pages/outstanding.php" class="nav-link <?= ($currentPage === 'outstanding.php') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>Outstanding Payments</span>
                <?php if ($dueCount > 0): ?>
                    <span class="nav-badge"><?= $dueCount ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="nav-section-title">Administration</li>

        <li class="nav-item">
            <a href="<?= BASE_URL ?>/pages/reports.php" class="nav-link <?= ($currentPage === 'reports.php') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                <span>Reports & Analytics</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="<?= BASE_URL ?>/pages/settings.php" class="nav-link <?= ($currentPage === 'settings.php') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
                <span>Settings & Backup</span>
            </a>
        </li>

        <li class="nav-item" style="margin-top: 10px;">
            <a href="<?= BASE_URL ?>/logout.php" class="nav-link text-danger" style="color: var(--danger);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Logout</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="user-snippet">
            <div class="user-avatar">
                <?= strtoupper(substr($currentUser['full_name'] ?? 'D', 0, 1)) ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= e($currentUser['full_name'] ?? 'Dentist') ?></div>
                <div class="user-role"><?= e($clinic['dentist_qualification']) ?></div>
            </div>
        </div>
    </div>
</aside>
