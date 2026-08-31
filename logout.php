<?php
/**
 * Prime Dental Clinic Management System
 * Logout Handler
 */

define('PRIME_DENTAL', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';

Auth::logout();
header('Location: ' . BASE_URL . '/login.php');
exit;
