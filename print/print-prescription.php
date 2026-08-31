<?php
/**
 * Prime Dental Clinic Management System
 * Official Printable Dental Rx Prescription Slip
 */

define('PRIME_DENTAL', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_DIR . '/config/database.php';
require_once BASE_DIR . '/config/auth.php';
require_once BASE_DIR . '/config/helpers.php';

Auth::requireAuth();

$visitId = (int)($_GET['id'] ?? 0);
if ($visitId <= 0) die("Invalid Visit ID.");

$db = getDB();
$clinic = getClinicSettings();

$stmt = $db->prepare("
    SELECT 
        v.*,
        p.registration_no,
        p.first_name,
        p.middle_name,
        p.last_name,
        p.age,
        p.gender,
        p.mobile,
        p.address
    FROM visits v
    JOIN patients p ON v.patient_id = p.id
    WHERE v.id = ?
    LIMIT 1
");
$stmt->execute([$visitId]);
$visit = $stmt->fetch();
if (!$visit) die("Visit not found.");

$fullName = trim($visit['first_name'] . ' ' . ($visit['middle_name'] ? $visit['middle_name'] . ' ' : '') . $visit['last_name']);

// Check drug allergy for safety alert
$stmtMed = $db->prepare("SELECT drug_allergy, drug_allergy_details FROM medical_history WHERE patient_id = ? LIMIT 1");
$stmtMed->execute([$visit['patient_id']]);
$med = $stmtMed->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription Rx - <?= e($fullName) ?> | <?= e(CLINIC_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/print.css">
    <style>
        body {
            background: #f1f5f9;
            padding: 30px 10px;
            font-family: 'Inter', sans-serif;
            color: #0f172a;
        }
        .rx-card {
            background: #ffffff;
            max-width: 750px;
            margin: 0 auto;
            padding: 36px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            min-height: 850px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .rx-symbol {
            font-family: 'Georgia', serif;
            font-size: 32px;
            font-weight: bold;
            color: #0d9488;
            margin: 16px 0 8px 0;
        }
        .rx-content {
            font-family: 'Courier New', Courier, monospace;
            font-size: 14.5px;
            line-height: 1.8;
            white-space: pre-wrap;
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            flex: 1;
        }
        @media print {
            body { padding: 0; background: #fff; }
            .rx-card { padding: 0; box-shadow: none; border: none; max-width: 100%; min-height: auto; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print" style="max-width: 750px; margin: 0 auto 20px; display: flex; justify-content: space-between; align-items: center;">
    <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $visit['patient_id'] ?>" class="btn btn-secondary">
        &larr; Back to Patient Profile
    </a>
    <button onclick="window.print()" class="btn btn-primary" style="padding: 10px 24px;">
        🖨️ Print Prescription (A4)
    </button>
</div>

<div class="rx-card">
    <div>
        <!-- CLINIC LETTERHEAD -->
        <div class="print-letterhead">
            <div>
                <h1 class="print-clinic-name"><?= e($clinic['clinic_name']) ?></h1>
                <div class="print-tagline">"<?= e($clinic['tagline']) ?>"</div>
                <div class="print-doctor-info">
                    <?= e($clinic['dentist_name']) ?> <span style="font-size: 11px; font-weight: normal;"><?= e($clinic['dentist_qualification']) ?></span>
                </div>
                <div style="font-size: 11.5px; font-weight: 600; color: #475569;">
                    Reg No: <?= e($clinic['reg_no']) ?>
                </div>
            </div>
            <div class="print-clinic-contact">
                <div>📞 <?= e($clinic['phone']) ?></div>
                <div>✉️ <?= e($clinic['email']) ?></div>
                <div style="max-width: 260px; margin-top: 4px;">
                    <?= nl2br(e($clinic['address'])) ?>
                </div>
            </div>
        </div>

        <!-- PATIENT INFO STRIP -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; flex-wrap: wrap; gap: 8px;">
            <div><strong>Patient:</strong> <?= e($fullName) ?> (<?= $visit['age'] ?>y / <?= e($visit['gender']) ?>)</div>
            <div><strong>Reg No:</strong> <?= e($visit['registration_no']) ?></div>
            <div><strong>Date:</strong> <?= formatDate($visit['visit_date']) ?></div>
        </div>

        <?php if (!empty($med['drug_allergy'])): ?>
            <div style="background: #fff1f2; border: 1px solid #fecaca; color: #991b1b; padding: 8px 12px; border-radius: 4px; font-size: 12.5px; font-weight: 700; margin-bottom: 14px;">
                ⚠️ KNOWN DRUG ALLERGIES: <?= e($med['drug_allergy_details'] ?: 'Yes (Check medical file)') ?>
            </div>
        <?php endif; ?>

        <?php if ($visit['diagnosis']): ?>
            <div style="margin-bottom: 12px; font-size: 13.5px;">
                <strong>Clinical Diagnosis:</strong> <?= e($visit['diagnosis']) ?>
                <?php if ($visit['tooth_number']): ?>
                    (Tooth #: <?= e($visit['tooth_number']) ?>)
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="rx-symbol">℞</div>

        <div class="rx-content">
<?= e($visit['prescription'] ?: "No prescription entered for this visit.") ?>
        </div>

        <?php if ($visit['follow_up_date']): ?>
            <div style="margin-top: 16px; font-size: 13px; color: #0f766e; font-weight: 600;">
                🔔 Next Follow-up Review: <?= formatDate($visit['follow_up_date']) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- SIGNATURE SECTION -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
        <div style="font-size: 11px; color: #64748b;">
            Not valid for medico-legal purposes.<br>
            Please take medications as directed with or after meals.
        </div>
        <div style="text-align: center; border-top: 1px solid #0f172a; width: 180px; padding-top: 6px; font-size: 12px;">
            <strong><?= e($clinic['dentist_name']) ?></strong><br>
            <span style="font-size: 10px; color: #64748b;">Reg No: <?= e($clinic['reg_no']) ?></span>
        </div>
    </div>
</div>

</body>
</html>
