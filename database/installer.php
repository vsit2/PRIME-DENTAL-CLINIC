<?php
/**
 * Prime Dental Clinic Management System
 * Database Installer & Migration Tool
 */

define('PRIME_DENTAL', true);
require_once dirname(__DIR__) . '/config/config.php';

$isCli = (php_sapi_name() === 'cli');
$results = [];
$success = false;

try {
    $results[] = "Connecting to MySQL server at " . DB_HOST . ":" . DB_PORT . "...";
    $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
    $pdo = new PDO($rootDsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $results[] = "✓ Successfully connected to MySQL server.";

    // 1. Create database and tables
    $schemaFile = __DIR__ . '/schema.sql';
    if (file_exists($schemaFile)) {
        $results[] = "Reading schema from schema.sql...";
        $schemaSql = file_get_contents($schemaFile);
        $pdo->exec($schemaSql);
        $results[] = "✓ Database 'prime_dental_db' and all relational tables created successfully.";
    } else {
        throw new Exception("schema.sql file not found!");
    }

    // 2. Insert Seed Data
    $seedFile = __DIR__ . '/seed.sql';
    if (file_exists($seedFile)) {
        $results[] = "Inserting initial clinic data, users, and sample patient records...";
        $seedSql = file_get_contents($seedFile);
        $pdo->exec($seedSql);
        $results[] = "✓ Seed data inserted successfully (Admin user: admin / admin123, Dr. Rutuja Deshmukh: dr.rutuja / admin123, Demo Patients: PDC-0001 to PDC-0004).";
    }

    $success = true;
    $results[] = "🎉 PRIME DENTAL CLINIC SYSTEM IS READY FOR USE!";

} catch (Exception $e) {
    $results[] = "❌ ERROR: " . $e->getMessage();
    $success = false;
}

if ($isCli) {
    foreach ($results as $res) {
        echo $res . PHP_EOL;
    }
    exit($success ? 0 : 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Prime Dental Clinic</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0d9488;
            --primary-dark: #0f766e;
            --secondary: #0284c7;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
            --error: #ef4444;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0fdfa 0%, #e0f2fe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            color: var(--text);
        }
        .setup-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(13, 148, 136, 0.08);
            border: 1px solid var(--border);
            max-width: 650px;
            width: 100%;
            padding: 36px;
        }
        .header {
            text-align: center;
            margin-bottom: 28px;
        }
        .logo-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            margin-bottom: 12px;
            box-shadow: 0 8px 16px rgba(13, 148, 136, 0.25);
        }
        h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px 0;
            letter-spacing: -0.5px;
        }
        p.tagline {
            color: var(--text-muted);
            margin: 0;
            font-size: 14px;
        }
        .log-box {
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 10px;
            padding: 16px 20px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            line-height: 1.6;
            margin: 20px 0;
            max-height: 250px;
            overflow-y: auto;
        }
        .log-line {
            margin-bottom: 6px;
        }
        .log-line.success { color: #34d399; }
        .log-line.error { color: #f87171; }
        .action-btn {
            display: block;
            width: 100%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(13, 148, 136, 0.4);
        }
        .creds-box {
            background: #f0fdfa;
            border: 1px solid #ccfbf1;
            border-radius: 10px;
            padding: 16px;
            margin-top: 20px;
            font-size: 13px;
        }
        .creds-box strong { color: var(--primary-dark); }
    </style>
</head>
<body>
    <div class="setup-card">
        <div class="header">
            <div class="logo-icon">🦷</div>
            <h1>PRIME DENTAL CLINIC</h1>
            <p class="tagline">The Prime Destination For Smiles &bull; Database Setup</p>
        </div>

        <div class="log-box">
            <?php foreach ($results as $line): ?>
                <div class="log-line <?= str_contains($line, '✓') || str_contains($line, '🎉') ? 'success' : (str_contains($line, '❌') ? 'error' : '') ?>">
                    <?= htmlspecialchars($line) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($success): ?>
            <div class="creds-box">
                <strong>Default Login Credentials:</strong><br>
                &bull; Username: <code>admin</code> (or <code>dr.rutuja</code>)<br>
                &bull; Password: <code>admin123</code>
            </div>
            <div style="margin-top: 20px;">
                <a href="<?= BASE_URL ?>/login.php" class="action-btn">Launch Prime Dental System &rarr;</a>
            </div>
        <?php else: ?>
            <div style="margin-top: 20px;">
                <a href="installer.php" class="action-btn" style="background: var(--error);">Retry Setup</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
