<?php
/**
 * Prime Dental Clinic Management System
 * Topbar Header with Instant Live Search & Action Bar
 */

require_once BASE_DIR . '/config/helpers.php';
$flash = getFlash();
?>
<header class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle" aria-label="Toggle Menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <!-- Prominent Global Search Bar -->
        <div class="global-search-container">
            <div class="search-input-wrapper">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" 
                       class="search-input global-patient-search-input" 
                       placeholder="Search patient by Name, PDC No, or Mobile..." 
                       autocomplete="off"
                       id="topbarPatientSearch">
            </div>
            <!-- Results drop dynamically populated via search.js -->
        </div>
    </div>

    <div class="topbar-right">
        <a href="<?= BASE_URL ?>/pages/patient-add.php" class="quick-action-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Add Patient</span>
        </a>
    </div>
</header>

<!-- Toast Flash Alert Container -->
<?php if ($flash): ?>
    <div class="toast-container">
        <div class="toast toast-<?= e($flash['type']) ?>">
            <div style="font-size:18px;">
                <?= $flash['type'] === 'success' ? '✅' : ($flash['type'] === 'error' ? '❌' : 'ℹ️') ?>
            </div>
            <div style="flex:1;font-size:13.5px;font-weight:500;">
                <?= e($flash['message']) ?>
            </div>
        </div>
    </div>
<?php endif; ?>
