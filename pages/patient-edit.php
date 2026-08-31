<?php
/**
 * Prime Dental Clinic Management System
 * Edit Patient & Medical History
 */

define('PRIME_DENTAL', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_DIR . '/config/database.php';
require_once BASE_DIR . '/config/auth.php';
require_once BASE_DIR . '/config/helpers.php';

Auth::requireAuth();

$patientId = (int)($_GET['id'] ?? 0);
if ($patientId <= 0) {
    header("Location: " . BASE_URL . "/pages/patients.php");
    exit;
}

$db = getDB();

// Fetch patient
$stmt = $db->prepare("SELECT * FROM patients WHERE id = ? LIMIT 1");
$stmt->execute([$patientId]);
$patient = $stmt->fetch();
if (!$patient) die("Patient not found.");

// Fetch medical history
$stmtMed = $db->prepare("SELECT * FROM medical_history WHERE patient_id = ? LIMIT 1");
$stmtMed->execute([$patientId]);
$medHistory = $stmtMed->fetch() ?: [];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $dob = !empty($_POST['dob']) ? trim($_POST['dob']) : null;
    $age = (int)($_POST['age'] ?? 0);
    $gender = trim($_POST['gender'] ?? 'Male');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $placeOfWork = trim($_POST['place_of_work'] ?? '');
    $education = trim($_POST['education'] ?? '');

    $physicianName = trim($_POST['physician_name'] ?? '');
    $physicianContact = trim($_POST['physician_contact'] ?? '');
    $emergencyContact = trim($_POST['emergency_contact'] ?? '');
    $emergencyPerson = trim($_POST['emergency_person'] ?? '');
    $emergencyRelationship = trim($_POST['emergency_relationship'] ?? '');

    // Medical conditions
    $asthma = isset($_POST['med_asthma']) ? 1 : 0;
    $bleedingDisorder = isset($_POST['med_bleeding_disorder']) ? 1 : 0;
    $cardiovascular = isset($_POST['med_cardiovascular_disorders']) ? 1 : 0;
    $drugAllergy = isset($_POST['med_drug_allergy']) ? 1 : 0;
    $drugAllergyDetails = trim($_POST['drug_allergy_details'] ?? '');
    $endocrine = isset($_POST['med_endocrine_disorders']) ? 1 : 0;
    $fitsFainting = isset($_POST['med_fits_fainting']) ? 1 : 0;
    $gastrointestinal = isset($_POST['med_gastrointestinal_disorder']) ? 1 : 0;
    $hospitalization = isset($_POST['med_hospitalization']) ? 1 : 0;
    $habits = isset($_POST['med_habits']) ? 1 : 0;
    $habitsDetails = trim($_POST['habits_details'] ?? '');
    $hivAids = isset($_POST['med_hiv_aids']) ? 1 : 0;
    $hepatitis = isset($_POST['med_hepatitis']) ? 1 : 0;
    $tb = isset($_POST['med_tb']) ? 1 : 0;
    $kidneyDisorder = isset($_POST['med_kidney_disorder']) ? 1 : 0;
    $pregnancyLactation = isset($_POST['med_pregnancy_lactation']) ? 1 : 0;
    $currentMedication = isset($_POST['med_current_medication']) ? 1 : 0;
    $medicationDetails = trim($_POST['medication_details'] ?? '');
    $otherConditions = isset($_POST['med_other_conditions']) ? 1 : 0;
    $otherDetails = trim($_POST['other_details'] ?? '');
    $additionalNotes = trim($_POST['additional_notes'] ?? '');

    if (empty($firstName)) $errors[] = "First Name is required.";
    if (empty($lastName)) $errors[] = "Last Name is required.";
    if ($age <= 0) $errors[] = "Valid Age is required.";
    if (empty($mobile)) $errors[] = "Mobile Number is required.";

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $stmtUpdPatient = $db->prepare("
                UPDATE patients SET
                    first_name = ?, middle_name = ?, last_name = ?,
                    dob = ?, age = ?, gender = ?, mobile = ?, email = ?,
                    address = ?, place_of_work = ?, education = ?,
                    physician_name = ?, physician_contact = ?,
                    emergency_contact = ?, emergency_person = ?, emergency_relationship = ?
                WHERE id = ?
            ");
            $stmtUpdPatient->execute([
                $firstName, $middleName, $lastName,
                $dob, $age, $gender, $mobile, $email,
                $address, $placeOfWork, $education,
                $physicianName, $physicianContact,
                $emergencyContact, $emergencyPerson, $emergencyRelationship,
                $patientId
            ]);

            // Update Medical History (Upsert)
            $stmtUpdMed = $db->prepare("
                INSERT INTO medical_history (
                    patient_id, asthma, bleeding_disorder, cardiovascular_disorders,
                    drug_allergy, drug_allergy_details, endocrine_disorders, fits_fainting,
                    gastrointestinal_disorder, hospitalization, habits, habits_details,
                    hiv_aids, hepatitis, tb, kidney_disorder, pregnancy_lactation,
                    current_medication, medication_details, other_conditions, other_details, additional_notes
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?
                ) ON DUPLICATE KEY UPDATE
                    asthma = VALUES(asthma),
                    bleeding_disorder = VALUES(bleeding_disorder),
                    cardiovascular_disorders = VALUES(cardiovascular_disorders),
                    drug_allergy = VALUES(drug_allergy),
                    drug_allergy_details = VALUES(drug_allergy_details),
                    endocrine_disorders = VALUES(endocrine_disorders),
                    fits_fainting = VALUES(fits_fainting),
                    gastrointestinal_disorder = VALUES(gastrointestinal_disorder),
                    hospitalization = VALUES(hospitalization),
                    habits = VALUES(habits),
                    habits_details = VALUES(habits_details),
                    hiv_aids = VALUES(hiv_aids),
                    hepatitis = VALUES(hepatitis),
                    tb = VALUES(tb),
                    kidney_disorder = VALUES(kidney_disorder),
                    pregnancy_lactation = VALUES(pregnancy_lactation),
                    current_medication = VALUES(current_medication),
                    medication_details = VALUES(medication_details),
                    other_conditions = VALUES(other_conditions),
                    other_details = VALUES(other_details),
                    additional_notes = VALUES(additional_notes)
            ");
            $stmtUpdMed->execute([
                $patientId, $asthma, $bleedingDisorder, $cardiovascular,
                $drugAllergy, $drugAllergyDetails, $endocrine, $fitsFainting,
                $gastrointestinal, $hospitalization, $habits, $habitsDetails,
                $hivAids, $hepatitis, $tb, $kidneyDisorder, $pregnancyLactation,
                $currentMedication, $medicationDetails, $otherConditions, $otherDetails, $additionalNotes
            ]);

            $db->commit();
            setFlash('success', "Patient record updated successfully!");
            header("Location: " . BASE_URL . "/pages/patient-view.php?id=" . $patientId);
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Update failed: " . $e->getMessage();
        }
    }
}

$pageTitle = "Edit Patient: " . $patient['first_name'] . " " . $patient['last_name'];
$extraScripts = ['assets/js/registration.js'];
require_once BASE_DIR . '/includes/header.php';
require_once BASE_DIR . '/includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once BASE_DIR . '/includes/topbar.php'; ?>

    <main class="page-content">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
            <div>
                <h1 style="font-size: 24px; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">
                    Edit Patient: <?= e($patient['first_name'] . ' ' . $patient['last_name']) ?> (<?= e($patient['registration_no']) ?>)
                </h1>
                <p style="color: var(--text-muted); font-size: 13.5px;">Update patient demographic or medical condition records.</p>
            </div>
            <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $patientId ?>" class="btn btn-secondary">
                &larr; Back to Profile
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="medical-alert-box" style="margin-bottom: 20px;">
                <div class="medical-alert-title">⚠️ Errors:</div>
                <ul style="margin: 4px 0 0 20px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Patient Demographics</h2>
                </div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Registration No</label>
                                <input type="text" class="form-control" value="<?= e($patient['registration_no']) ?>" readonly>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="first_name">First Name <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" class="form-control" value="<?= e($patient['first_name']) ?>" required>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="middle_name">Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name" class="form-control" value="<?= e($patient['middle_name']) ?>">
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="last_name">Last Name <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" class="form-control" value="<?= e($patient['last_name']) ?>" required>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="dob">Date of Birth (DOB)</label>
                                <input type="date" id="dob" name="dob" class="form-control" value="<?= e($patient['dob']) ?>" max="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="age">Age <span class="required">*</span></label>
                                <input type="number" id="age" name="age" class="form-control" value="<?= e($patient['age']) ?>" required>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="gender">Gender <span class="required">*</span></label>
                                <select id="gender" name="gender" class="form-control" required>
                                    <option value="Male" <?= $patient['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $patient['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= $patient['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="mobile">Mobile Number <span class="required">*</span></label>
                                <input type="tel" id="mobile" name="mobile" class="form-control" value="<?= e($patient['mobile']) ?>" required>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?= e($patient['email']) ?>">
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="place_of_work">Place of Work</label>
                                <input type="text" id="place_of_work" name="place_of_work" class="form-control" value="<?= e($patient['place_of_work']) ?>">
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="education">Education</label>
                                <input type="text" id="education" name="education" class="form-control" value="<?= e($patient['education']) ?>">
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label" for="address">Address</label>
                                <textarea id="address" name="address" class="form-control" rows="2"><?= e($patient['address']) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MEDICAL CONDITIONS -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Medical History & Conditions</h2>
                </div>
                <div class="card-body">
                    <div class="checkbox-chip-grid">
                        <?php foreach (MEDICAL_CONDITIONS_LIST as $k => $label): 
                            $checked = !empty($medHistory[$k]);
                        ?>
                            <label class="checkbox-chip <?= $checked ? 'active' : '' ?>">
                                <input type="checkbox" name="med_<?= $k ?>" id="med_<?= $k ?>" value="1" <?= $checked ? 'checked' : '' ?>>
                                <span class="checkbox-custom-box"></span>
                                <span class="chip-label"><?= e($label) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div id="habits_details_container" style="margin-top:16px; display: <?= !empty($medHistory['habits']) ? 'block' : 'none' ?>;">
                        <label class="form-label">Habits Details:</label>
                        <input type="text" id="habits_details" name="habits_details" class="form-control" value="<?= e($medHistory['habits_details'] ?? '') ?>">
                    </div>

                    <div id="drug_allergy_container" style="margin-top:16px; display: <?= !empty($medHistory['drug_allergy']) ? 'block' : 'none' ?>;">
                        <label class="form-label" style="color:var(--danger-text);font-weight:700;">⚠️ Known Drug Allergy Details:</label>
                        <input type="text" id="drug_allergy_details" name="drug_allergy_details" class="form-control" value="<?= e($medHistory['drug_allergy_details'] ?? '') ?>">
                    </div>

                    <div id="medication_details_container" style="margin-top:16px; display: <?= !empty($medHistory['current_medication']) ? 'block' : 'none' ?>;">
                        <label class="form-label">Current Medication Details:</label>
                        <input type="text" id="medication_details" name="medication_details" class="form-control" value="<?= e($medHistory['medication_details'] ?? '') ?>">
                    </div>

                    <div id="other_med_container" style="margin-top:16px; display: <?= !empty($medHistory['other_conditions']) ? 'block' : 'none' ?>;">
                        <label class="form-label">Specify Other Conditions:</label>
                        <textarea id="other_details" name="other_details" class="form-control"><?= e($medHistory['other_details'] ?? '') ?></textarea>
                    </div>

                    <div style="margin-top:16px;">
                        <label class="form-label">Additional Medical Notes:</label>
                        <textarea id="additional_notes" name="additional_notes" class="form-control"><?= e($medHistory['additional_notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- PHYSICIAN & EMERGENCY -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Physician & Emergency Contacts</h2>
                </div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="physician_name">Physician Name</label>
                                <input type="text" id="physician_name" name="physician_name" class="form-control" value="<?= e($patient['physician_name']) ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="physician_contact">Physician Contact</label>
                                <input type="text" id="physician_contact" name="physician_contact" class="form-control" value="<?= e($patient['physician_contact']) ?>">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="emergency_person">Emergency Contact Person</label>
                                <input type="text" id="emergency_person" name="emergency_person" class="form-control" value="<?= e($patient['emergency_person']) ?>">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="emergency_relationship">Relationship</label>
                                <input type="text" id="emergency_relationship" name="emergency_relationship" class="form-control" value="<?= e($patient['emergency_relationship']) ?>">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="emergency_contact">Emergency Contact No</label>
                                <input type="text" id="emergency_contact" name="emergency_contact" class="form-control" value="<?= e($patient['emergency_contact']) ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?= BASE_URL ?>/pages/patient-view.php?id=<?= $patientId ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" style="padding: 12px 28px;">
                        Save Changes &rarr;
                    </button>
                </div>
            </div>
        </form>
    </main>

<?php require_once BASE_DIR . '/includes/footer.php'; ?>
