<?php
/**
 * Prime Dental Clinic Management System
 * Patient Registration Form
 */

define('PRIME_DENTAL', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_DIR . '/config/database.php';
require_once BASE_DIR . '/config/auth.php';
require_once BASE_DIR . '/config/helpers.php';

Auth::requireAuth();

$pageTitle = "Patient Registration";
$errors = [];
$nextRegNo = getNextRegistrationNumber();

// Process POST Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();

    // 1. Demographics
    $regNo = trim($_POST['registration_no'] ?? '');
    $regDate = trim($_POST['reg_date'] ?? date('Y-m-d'));
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

    // 2. Physician / Emergency Info
    $physicianName = trim($_POST['physician_name'] ?? '');
    $physicianContact = trim($_POST['physician_contact'] ?? '');
    $emergencyContact = trim($_POST['emergency_contact'] ?? '');
    $emergencyPerson = trim($_POST['emergency_person'] ?? '');
    $emergencyRelationship = trim($_POST['emergency_relationship'] ?? '');

    // 3. Reasons for Visit
    $reasonsSelected = $_POST['reasons'] ?? [];
    if (!is_array($reasonsSelected)) $reasonsSelected = [];
    $otherReason = trim($_POST['other_reason_text'] ?? '');
    if (in_array('Any Other', $reasonsSelected) && !empty($otherReason)) {
        $reasonsSelected[] = "Other: " . $otherReason;
    }
    $initialReasonsJson = json_encode(array_values($reasonsSelected));

    // 4. Medical History Checkboxes & Details
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

    // Form Validations
    if (empty($regNo)) {
        $errors[] = "Registration Number is required.";
    } else {
        // Check uniqueness
        $stmtCheck = $db->prepare("SELECT id FROM patients WHERE registration_no = ? LIMIT 1");
        $stmtCheck->execute([$regNo]);
        if ($stmtCheck->fetch()) {
            $errors[] = "Registration Number '{$regNo}' already exists in the system.";
        }
    }

    if (empty($firstName)) $errors[] = "Patient First Name is required.";
    if (empty($lastName)) $errors[] = "Patient Last Name is required.";
    if ($age <= 0) $errors[] = "Please provide a valid Patient Age.";
    if (empty($mobile)) {
        $errors[] = "Mobile Number is required.";
    } elseif (!preg_match('/^[0-9+\-\s]{7,15}$/', $mobile)) {
        $errors[] = "Please enter a valid Mobile Number.";
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid Email Address.";
    }

    // Save to Database
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Insert Patient
            $stmtPatient = $db->prepare("
                INSERT INTO patients (
                    registration_no, reg_date, first_name, middle_name, last_name,
                    dob, age, gender, mobile, email, address, place_of_work, education,
                    physician_name, physician_contact, emergency_contact, emergency_person,
                    emergency_relationship, initial_reasons
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?
                )
            ");
            $stmtPatient->execute([
                $regNo, $regDate, $firstName, $middleName, $lastName,
                $dob, $age, $gender, $mobile, $email, $address, $placeOfWork, $education,
                $physicianName, $physicianContact, $emergencyContact, $emergencyPerson,
                $emergencyRelationship, $initialReasonsJson
            ]);
            $patientId = $db->lastInsertId();

            // Insert Medical History
            $stmtMed = $db->prepare("
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
                )
            ");
            $stmtMed->execute([
                $patientId, $asthma, $bleedingDisorder, $cardiovascular,
                $drugAllergy, $drugAllergyDetails, $endocrine, $fitsFainting,
                $gastrointestinal, $hospitalization, $habits, $habitsDetails,
                $hivAids, $hepatitis, $tb, $kidneyDisorder, $pregnancyLactation,
                $currentMedication, $medicationDetails, $otherConditions, $otherDetails, $additionalNotes
            ]);

            $db->commit();

            setFlash('success', "Patient {$regNo} ({$firstName} {$lastName}) registered successfully!");
            header("Location: " . BASE_URL . "/pages/patient-view.php?id=" . $patientId);
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Failed to save patient: " . $e->getMessage();
        }
    }
}

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
                    Patient Registration
                </h1>
                <p style="color: var(--text-muted); font-size: 13.5px;">
                    Enter complete patient demographics, reason for visit, medical conditions, and emergency contacts.
                </p>
            </div>
            <a href="<?= BASE_URL ?>/pages/patients.php" class="btn btn-secondary">
                &larr; Back to Patient List
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="medical-alert-box" style="margin-bottom: 24px;">
                <div class="medical-alert-title">⚠️ Please correct the following errors:</div>
                <ul style="margin: 6px 0 0 20px; font-size: 13.5px; color: var(--danger-text);">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="patientRegistrationForm">
            <!-- SECTION 1: PATIENT INFORMATION -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <div class="card-icon">📋</div>
                        <div>
                            <h2 class="card-title">1. Patient Information</h2>
                            <div class="card-subtitle">Basic details and demographics</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="registration_no">Registration No <span class="required">*</span></label>
                                <input type="text" 
                                       id="registration_no" 
                                       name="registration_no" 
                                       class="form-control" 
                                       value="<?= e($_POST['registration_no'] ?? $nextRegNo) ?>" 
                                       required 
                                       style="font-family:monospace;font-weight:700;background:#f0fdfa;border-color:#99f6e4;">
                                <span class="form-hint">Auto-generated sequence</span>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="reg_date">Registration Date <span class="required">*</span></label>
                                <input type="date" 
                                       id="reg_date" 
                                       name="reg_date" 
                                       class="form-control" 
                                       value="<?= e($_POST['reg_date'] ?? date('Y-m-d')) ?>" 
                                       required>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="gender">Gender <span class="required">*</span></label>
                                <select id="gender" name="gender" class="form-control" required>
                                    <option value="Male" <?= (($_POST['gender'] ?? 'Male') === 'Male') ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= (($_POST['gender'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Name Fields -->
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="first_name">First Name <span class="required">*</span></label>
                                <input type="text" 
                                       id="first_name" 
                                       name="first_name" 
                                       class="form-control" 
                                       placeholder="e.g. Rahul" 
                                       value="<?= e($_POST['first_name'] ?? '') ?>" 
                                       required 
                                       autofocus>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="middle_name">Middle Name</label>
                                <input type="text" 
                                       id="middle_name" 
                                       name="middle_name" 
                                       class="form-control" 
                                       placeholder="e.g. Kumar" 
                                       value="<?= e($_POST['middle_name'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="last_name">Last Name <span class="required">*</span></label>
                                <input type="text" 
                                       id="last_name" 
                                       name="last_name" 
                                       class="form-control" 
                                       placeholder="e.g. Sharma" 
                                       value="<?= e($_POST['last_name'] ?? '') ?>" 
                                       required>
                            </div>
                        </div>

                        <!-- DOB and Auto Age -->
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="dob">Date of Birth (DOB)</label>
                                <input type="date" 
                                       id="dob" 
                                       name="dob" 
                                       class="form-control" 
                                       value="<?= e($_POST['dob'] ?? '') ?>" 
                                       max="<?= date('Y-m-d') ?>">
                                <span class="form-hint">Automatically calculates age</span>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="age">Age (Years) <span class="required">*</span></label>
                                <input type="number" 
                                       id="age" 
                                       name="age" 
                                       class="form-control" 
                                       placeholder="Age" 
                                       min="0" 
                                       max="125" 
                                       value="<?= e($_POST['age'] ?? '') ?>" 
                                       required>
                                <span class="form-hint">Editable if DOB is unknown</span>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="mobile">Mobile Number <span class="required">*</span></label>
                                <input type="tel" 
                                       id="mobile" 
                                       name="mobile" 
                                       class="form-control" 
                                       placeholder="10-digit mobile number" 
                                       value="<?= e($_POST['mobile'] ?? '') ?>" 
                                       required>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="email">Email Address</label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       class="form-control" 
                                       placeholder="patient@example.com" 
                                       value="<?= e($_POST['email'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-3">
                            <div class="form-group">
                                <label class="form-label" for="place_of_work">Place of Work / Occupation</label>
                                <input type="text" 
                                       id="place_of_work" 
                                       name="place_of_work" 
                                       class="form-control" 
                                       placeholder="e.g. Company name / Business" 
                                       value="<?= e($_POST['place_of_work'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-3">
                            <div class="form-group">
                                <label class="form-label" for="education">Education</label>
                                <input type="text" 
                                       id="education" 
                                       name="education" 
                                       class="form-control" 
                                       placeholder="e.g. Graduate / B.Com" 
                                       value="<?= e($_POST['education'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label" for="address">Residential Address</label>
                                <textarea id="address" 
                                          name="address" 
                                          class="form-control" 
                                          rows="2" 
                                          placeholder="Full street address, building/flat no, area, pin code..."><?= e($_POST['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: REASON FOR VISIT -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <div class="card-icon">🦷</div>
                        <div>
                            <h2 class="card-title">2. Reason for the Visit</h2>
                            <div class="card-subtitle">Select all dental concerns that apply to the patient</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="checkbox-chip-grid">
                        <?php 
                        $postedReasons = $_POST['reasons'] ?? [];
                        foreach (REASONS_FOR_VISIT_LIST as $reason): 
                            $isOther = ($reason === 'Any Other');
                            $isChecked = in_array($reason, $postedReasons);
                        ?>
                            <label class="checkbox-chip <?= $isChecked ? 'active' : '' ?>">
                                <input type="checkbox" 
                                       name="reasons[]" 
                                       value="<?= e($reason) ?>" 
                                       <?= $isChecked ? 'checked' : '' ?>
                                       <?= $isOther ? 'id="reason_other_check"' : '' ?>>
                                <span class="checkbox-custom-box"></span>
                                <span class="chip-label"><?= e($reason) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- "Any Other" Text Field -->
                    <div id="other_reason_container" style="margin-top: 16px; display: <?= in_array('Any Other', $postedReasons) ? 'block' : 'none' ?>;">
                        <label class="form-label" for="other_reason_text">Specify Other Reason Details:</label>
                        <textarea id="other_reason_text" 
                                  name="other_reason_text" 
                                  class="form-control" 
                                  placeholder="Describe any other dental reason or complaint in detail..."><?= e($_POST['other_reason_text'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: MEDICAL HISTORY -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <div class="card-icon">🩺</div>
                        <div>
                            <h2 class="card-title">3. Medical History</h2>
                            <div class="card-subtitle">Do you have any of the following Medical Conditions?</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="checkbox-chip-grid">
                        <?php foreach (MEDICAL_CONDITIONS_LIST as $key => $label): 
                            $isChecked = isset($_POST['med_' . $key]);
                        ?>
                            <label class="checkbox-chip <?= $isChecked ? 'active' : '' ?>">
                                <input type="checkbox" 
                                       name="med_<?= $key ?>" 
                                       id="med_<?= $key ?>" 
                                       value="1" 
                                       <?= $isChecked ? 'checked' : '' ?>>
                                <span class="checkbox-custom-box"></span>
                                <span class="chip-label"><?= e($label) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- Habit Details Input -->
                    <div id="habits_details_container" style="margin-top: 16px; display: <?= isset($_POST['med_habits']) ? 'block' : 'none' ?>;">
                        <label class="form-label" for="habits_details">Habits Details (Frequency / Duration / Type):</label>
                        <input type="text" 
                               id="habits_details" 
                               name="habits_details" 
                               class="form-control" 
                               placeholder="e.g. Tobacco chewing 2x/day for 5 years, Smoking 4 cigarettes/day" 
                               value="<?= e($_POST['habits_details'] ?? '') ?>">
                    </div>

                    <!-- Known Drug Allergy Details Input -->
                    <div id="drug_allergy_container" style="margin-top: 16px; display: <?= isset($_POST['med_drug_allergy']) ? 'block' : 'none' ?>;">
                        <label class="form-label" for="drug_allergy_details" style="color: var(--danger-text); font-weight: 700;">
                            ⚠️ Known Drug Allergy Details:
                        </label>
                        <input type="text" 
                               id="drug_allergy_details" 
                               name="drug_allergy_details" 
                               class="form-control" 
                               placeholder="e.g. Penicillin, Sulfa drugs, Local Anesthetic, NSAIDs" 
                               value="<?= e($_POST['drug_allergy_details'] ?? '') ?>"
                               style="border-color: var(--danger-border); background: var(--danger-bg);">
                    </div>

                    <!-- Current Medication Details Input -->
                    <div id="medication_details_container" style="margin-top: 16px; display: <?= isset($_POST['med_current_medication']) ? 'block' : 'none' ?>;">
                        <label class="form-label" for="medication_details">Any Current Medication Details:</label>
                        <input type="text" 
                               id="medication_details" 
                               name="medication_details" 
                               class="form-control" 
                               placeholder="e.g. Blood thinners (Ecosprin), Blood Pressure drugs, Insulin" 
                               value="<?= e($_POST['medication_details'] ?? '') ?>">
                    </div>

                    <!-- Other Medical Conditions Details -->
                    <div id="other_med_container" style="margin-top: 16px; display: <?= isset($_POST['med_other_conditions']) ? 'block' : 'none' ?>;">
                        <label class="form-label" for="other_details">Specify Other Medical Conditions:</label>
                        <textarea id="other_details" 
                                  name="other_details" 
                                  class="form-control" 
                                  placeholder="Provide any additional medical conditions or past surgical history..."><?= e($_POST['other_details'] ?? '') ?></textarea>
                    </div>

                    <!-- Additional Clinical Notes -->
                    <div style="margin-top: 16px;">
                        <label class="form-label" for="additional_notes">Additional Medical / Patient Notes:</label>
                        <textarea id="additional_notes" 
                                  name="additional_notes" 
                                  class="form-control" 
                                  rows="2" 
                                  placeholder="Internal dentist notes regarding patient's general health or treatment considerations..."><?= e($_POST['additional_notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: PHYSICIAN & EMERGENCY INFORMATION -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <div class="card-icon">🚨</div>
                        <div>
                            <h2 class="card-title">4. Physician & Emergency Information</h2>
                            <div class="card-subtitle">Treating doctor and emergency contact details</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="physician_name">Family Physician Name</label>
                                <input type="text" 
                                       id="physician_name" 
                                       name="physician_name" 
                                       class="form-control" 
                                       placeholder="e.g. Dr. A. K. Joshi" 
                                       value="<?= e($_POST['physician_name'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="physician_contact">Physician Contact No</label>
                                <input type="tel" 
                                       id="physician_contact" 
                                       name="physician_contact" 
                                       class="form-control" 
                                       placeholder="Physician phone number" 
                                       value="<?= e($_POST['physician_contact'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="emergency_person">Emergency Contact Person</label>
                                <input type="text" 
                                       id="emergency_person" 
                                       name="emergency_person" 
                                       class="form-control" 
                                       placeholder="e.g. Spouse / Parent / Relative" 
                                       value="<?= e($_POST['emergency_person'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="emergency_relationship">Relationship</label>
                                <input type="text" 
                                       id="emergency_relationship" 
                                       name="emergency_relationship" 
                                       class="form-control" 
                                       placeholder="e.g. Father / Mother / Spouse / Friend" 
                                       value="<?= e($_POST['emergency_relationship'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="emergency_contact">Emergency Contact No</label>
                                <input type="tel" 
                                       id="emergency_contact" 
                                       name="emergency_contact" 
                                       class="form-control" 
                                       placeholder="Emergency phone number" 
                                       value="<?= e($_POST['emergency_contact'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?= BASE_URL ?>/pages/patients.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" style="padding: 12px 28px; font-size: 15px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        <span>Save Patient & Open Profile &rarr;</span>
                    </button>
                </div>
            </div>
        </form>
    </main>

<?php require_once BASE_DIR . '/includes/footer.php'; ?>
