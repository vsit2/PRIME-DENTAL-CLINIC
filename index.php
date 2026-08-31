<?php
/**
 * Prime Dental Clinic Management System
 * App Root - Directs to Dashboard
 */

define('PRIME_DENTAL', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';

Auth::requireAuth();

// Forward to dashboard
require_once __DIR__ . '/pages/dashboard.php';
