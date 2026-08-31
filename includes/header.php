<?php
/**
 * Prime Dental Clinic Management System
 * Global HTML Header & Head Tags
 */

if (!defined('PRIME_DENTAL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once BASE_DIR . '/config/helpers.php';
require_once BASE_DIR . '/config/auth.php';

// Check Authentication
Auth::requireAuth();
$currentUser = Auth::user();
$clinic = getClinicSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?><?= e(CLINIC_NAME) ?> - <?= e(CLINIC_TAGLINE) ?></title>
    
    <!-- Meta SEO & Aesthetics -->
    <meta name="description" content="Prime Dental Clinic Management System - <?= e(DENTIST_NAME) ?>, Ghatkopar East, Mumbai.">
    <meta name="theme-color" content="#0d9488">
    
    <!-- Google Fonts: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dental-chart.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/print.css" media="print">

    <!-- Global Base URL for JS AJAX calls -->
    <script>
        window.PRIME_BASE_URL = '<?= BASE_URL ?>';
    </script>
</head>
<body>
<div class="app-wrapper">
