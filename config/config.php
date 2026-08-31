<?php
/**
 * Prime Dental Clinic Management System
 * Configuration & Core Constants
 */

// Prevent direct script access if needed
if (!defined('PRIME_DENTAL')) {
    define('PRIME_DENTAL', true);
}

// Timezone & Error Reporting
date_default_timezone_set('Asia/Kolkata');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Production default, can toggle for debugging
ini_set('log_errors', 1);

// Start Session securely
if (session_status() === PHP_SESSION_NONE) {
    // 8 hours session lifetime
    ini_set('session.gc_maxlifetime', 28800);
    session_set_cookie_params([
        'lifetime' => 28800,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Automatically load local .env file if present
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (strpos($line, '=') !== false) {
            list($envKey, $envVal) = explode('=', $line, 2);
            $envKey = trim($envKey);
            $envVal = trim($envVal, " \t\n\r\0\x0B\"'");
            if (getenv($envKey) === false && !isset($_ENV[$envKey])) {
                putenv("$envKey=$envVal");
                $_ENV[$envKey] = $envVal;
                $_SERVER[$envKey] = $envVal;
            }
        }
    }
}

// Database Configuration (Supports Environment Variables for Render / Cloud & local XAMPP)
$dbUrl = getenv('DATABASE_URL') ?: (getenv('MYSQL_URL') ?: '');
if (!empty($dbUrl)) {
    $parsedUrl = parse_url($dbUrl);
    define('DB_HOST', $parsedUrl['host'] ?? 'localhost');
    define('DB_PORT', (string)($parsedUrl['port'] ?? '3306'));
    define('DB_USER', $parsedUrl['user'] ?? 'root');
    define('DB_PASS', $parsedUrl['pass'] ?? '');
    define('DB_NAME', ltrim($parsedUrl['path'] ?? 'prime_dental_db', '/'));
} else {
    define('DB_HOST', getenv('DB_HOST') ?: (getenv('DB_HOSTNAME') ?: (getenv('MYSQLHOST') ?: 'localhost')));
    define('DB_PORT', (string)(getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: '3306')));
    define('DB_NAME', getenv('DB_NAME') ?: (getenv('DB_DATABASE') ?: (getenv('MYSQLDATABASE') ?: 'prime_dental_db')));
    define('DB_USER', getenv('DB_USER') ?: (getenv('DB_USERNAME') ?: (getenv('MYSQLUSER') ?: 'root')));
    
    $rawPass = getenv('DB_PASS');
    if ($rawPass === false) $rawPass = getenv('DB_PASSWORD');
    if ($rawPass === false) $rawPass = getenv('MYSQLPASSWORD');
    define('DB_PASS', $rawPass !== false ? $rawPass : '');
}
define('DB_CHARSET', 'utf8mb4');

// Base Paths & URLs
define('APP_NAME', 'PRIME DENTAL CLINIC');
define('APP_TAGLINE', 'The Prime Destination For Smiles');
define('BASE_DIR', dirname(__DIR__));

// Determine dynamic base URL for XAMPP or PHP built-in server
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseUrl = rtrim($protocol . $host . $scriptName, '/');
if (substr($baseUrl, -6) === '/pages' || substr($baseUrl, -4) === '/api' || substr($baseUrl, -6) === '/print') {
    $baseUrl = dirname($baseUrl);
}
define('BASE_URL', rtrim($baseUrl, '/'));

// Static Clinic Information
define('CLINIC_NAME', 'PRIME DENTAL CLINIC');
define('CLINIC_TAGLINE', 'The Prime Destination For Smiles');
define('DENTIST_NAME', 'Dr. Rutuja Deshmukh');
define('DENTIST_QUALIFICATION', 'B.D.S (Mumbai)');
define('DENTIST_REG_NO', 'A44351');
define('CLINIC_PHONE', '9892429014');
define('CLINIC_EMAIL', 'rutujadeshmukh0124@gmail.com');
define('CLINIC_ADDRESS_LINE1', 'Shop No. 01, Plot No. 30, Ground Floor, Matruchhaya Building');
define('CLINIC_ADDRESS_LINE2', 'Vallabhbaug Lane, Ghatkopar East, Mumbai - 400077');
define('CLINIC_FULL_ADDRESS', 'Shop No. 01, Plot No. 30, Ground Floor, Matruchhaya Building, Vallabhbaug Lane, Ghatkopar East, Mumbai - 400077');
define('CURRENCY_SYMBOL', '₹');

// List of all 24 Reason for Visit options
define('REASONS_FOR_VISIT_LIST', [
    'Pain in Teeth',
    'Teeth Whitening',
    'Dental Implant',
    'Crown and Bridge',
    'Pain in Gums',
    'Removal of the Teeth',
    'Replacement of Missing Tooth',
    'Night Guards',
    'Bad Breath',
    'Teeth Cleaning',
    'Smile Designing',
    'Mouth Guards',
    'Decayed Teeth',
    'Gum Diseases',
    'Full Mouth Rehabilitation',
    'Maxillofacial Prosthesis',
    'Fractured Teeth',
    'Preventive Treatment',
    'Complete Denture',
    'Dental Jewellery',
    'Tooth Sensitivity',
    'Tobacco Cessation Counseling',
    'Partial Denture',
    'Any Other'
]);

// List of all 16 Medical Conditions
define('MEDICAL_CONDITIONS_LIST', [
    'asthma' => 'Asthma',
    'bleeding_disorder' => 'Bleeding Disorder',
    'cardiovascular_disorders' => 'Cardiovascular Disorders',
    'drug_allergy' => 'Known Drug Allergy',
    'endocrine_disorders' => 'Endocrine Disorders (e.g. Diabetes, Thyroid)',
    'fits_fainting' => 'Fits and Fainting',
    'gastrointestinal_disorder' => 'Gastrointestinal Disorder',
    'hospitalization' => 'History of Hospitalization',
    'habits' => 'Habits (Tobacco, Smoking, Alcohol)',
    'hiv_aids' => 'Infection (HIV/AIDS)',
    'hepatitis' => 'Hepatitis',
    'tb' => 'TB (Tuberculosis)',
    'kidney_disorder' => 'Kidney Disorders',
    'pregnancy_lactation' => 'Pregnancy and Lactation',
    'current_medication' => 'Any Current Medication',
    'other_conditions' => 'Any Other Medical Condition'
]);
