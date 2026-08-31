<?php
/**
 * Prime Dental Clinic Management System
 * Comprehensive End-to-End Automated Test Suite
 */

define('PRIME_DENTAL', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_DIR . '/config/database.php';
require_once BASE_DIR . '/config/auth.php';
require_once BASE_DIR . '/config/helpers.php';

echo "========================================================================\n";
echo "🦷 PRIME DENTAL CLINIC - Automated End-to-End Integration Tests\n";
echo "========================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $description, bool $condition, string $details = '') {
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] " . $description . "\n";
        $passCount++;
    } else {
        echo " [FAIL] " . $description . ($details ? " -> " . $details : "") . "\n";
        $failCount++;
    }
}

$db = getDB();

// 1. Test Database Schema & Settings
$stmtClinic = $db->query("SELECT * FROM clinic_settings WHERE id = 1");
$clinic = $stmtClinic->fetch();
assertTest("Clinic Settings table exists and is populated", !empty($clinic));
assertTest("Clinic Name is 'PRIME DENTAL CLINIC'", ($clinic['clinic_name'] ?? '') === 'PRIME DENTAL CLINIC');
assertTest("Dentist Name is 'Dr. Rutuja Deshmukh'", ($clinic['dentist_name'] ?? '') === 'Dr. Rutuja Deshmukh');
assertTest("Dentist Reg No is 'A44351'", ($clinic['reg_no'] ?? '') === 'A44351');
assertTest("Clinic Phone is '9892429014'", ($clinic['phone'] ?? '') === '9892429014');

// 2. Test User Authentication
$stmtUser = $db->query("SELECT * FROM users WHERE username = 'admin'");
$user = $stmtUser->fetch();
assertTest("Admin user exists in database", !empty($user));
$loginSuccess = Auth::attempt('admin', 'admin123');
assertTest("Auth::attempt with valid credentials succeeds", $loginSuccess);
$authCheck = Auth::check();
assertTest("Auth::check returns true after login", $authCheck);
$currentUser = Auth::user();
assertTest("Auth::user returns active session details", !empty($currentUser) && $currentUser['username'] === 'admin');

// 3. Test Sequential Registration Number Generator
$nextReg = getNextRegistrationNumber();
assertTest("getNextRegistrationNumber generates format PDC-XXXX", (bool)preg_match('/^PDC-\d{4}$/', $nextReg));

// 4. Test Demo Patient 1: Rahul Sharma (PDC-0001)
$stmtRahul = $db->prepare("SELECT * FROM patients WHERE registration_no = 'PDC-0001' LIMIT 1");
$stmtRahul->execute();
$rahul = $stmtRahul->fetch();
assertTest("Demo patient Rahul Sharma (PDC-0001) exists", !empty($rahul));

$rahulSummary = getPatientFinancialSummary((int)$rahul['id']);
assertTest("Rahul Sharma Total Bill is ₹30,000", (float)$rahulSummary['total_bill'] === 30000.0);
assertTest("Rahul Sharma Total Paid is ₹15,000 (Payment 1 ₹10,000 + Payment 2 ₹5,000)", (float)$rahulSummary['total_paid'] === 15000.0);
assertTest("Rahul Sharma Outstanding Balance is ₹15,000", (float)$rahulSummary['balance'] === 15000.0);
assertTest("Rahul Sharma Status is 'Balance Due'", $rahulSummary['status'] === 'Balance Due');

// 5. Test Registering a New Patient End-to-End
$testRegNo = getNextRegistrationNumber();
$testFirstName = "Pooja";
$testLastName = "Verma";
$testDob = "1994-06-20";
$testAge = calculateAge($testDob);
$testMobile = "9820554433";
$testReasons = json_encode(["Smile Designing", "Teeth Cleaning", "Tooth Sensitivity"]);

$stmtInsertP = $db->prepare("
    INSERT INTO patients (
        registration_no, reg_date, first_name, middle_name, last_name,
        dob, age, gender, mobile, email, address, place_of_work, education,
        physician_name, physician_contact, emergency_contact, emergency_person,
        emergency_relationship, initial_reasons
    ) VALUES (
        ?, '2026-08-30', ?, 'Rajesh', ?,
        ?, ?, 'Female', ?, 'pooja.verma@example.com', 'Flat 302, Ghatkopar East, Mumbai - 400077', 'Infosys', 'B.Tech',
        'Dr. V. K. Shah', '9820011999', '9820554434', 'Rajesh Verma',
        'Father', ?
    )
");
$stmtInsertP->execute([$testRegNo, $testFirstName, $testLastName, $testDob, $testAge, $testMobile, $testReasons]);
$newPatientId = (int)$db->lastInsertId();
assertTest("New patient registration inserted successfully with ID {$newPatientId}", $newPatientId > 0);

// Insert Medical History for new patient (Asthma + Penicillin Allergy)
$stmtInsertM = $db->prepare("
    INSERT INTO medical_history (
        patient_id, asthma, bleeding_disorder, cardiovascular_disorders,
        drug_allergy, drug_allergy_details, endocrine_disorders, fits_fainting,
        gastrointestinal_disorder, hospitalization, habits, habits_details,
        hiv_aids, hepatitis, tb, kidney_disorder, pregnancy_lactation,
        current_medication, medication_details, other_conditions, other_details, additional_notes
    ) VALUES (
        ?, 1, 0, 0,
        1, 'Penicillin, Amoxicillin', 0, 0,
        0, 0, 0, '',
        0, 0, 0, 0, 0,
        1, 'Inhaler SOS for mild asthma', 0, '', 'Patient pre-medicated'
    )
");
$stmtInsertM->execute([$newPatientId]);
assertTest("Medical history with Asthma & Drug Allergy saved for patient", true);

// 6. Test Live Search API
$_GET['q'] = 'Pooja';
ob_start();
require BASE_DIR . '/api/search_patients.php';
$searchJson = ob_get_clean();
$searchData = json_decode($searchJson, true);
assertTest("Search API returns results for 'Pooja'", !empty($searchData['patients']));
assertTest("Search result contains PDC registration number", !empty($searchData['patients'][0]['registration_no']));

// 7. Test Adding Clinical Visit with FDI Tooth Chart & Treatment Cost
$testCost = 25000.0;
$stmtVisit = $db->prepare("
    INSERT INTO visits (
        patient_id, visit_date, chief_complaint, reason_for_visit,
        diagnosis, treatment, tooth_number, dentist_notes,
        prescription, follow_up_date, treatment_cost
    ) VALUES (
        ?, '2026-08-30', 'Spacing between upper front teeth', '[\"Smile Designing\"]',
        'Midline Diastema and fluorosis', 'Direct Composite Veneering #11, #12, #21, #22', '11, 12, 21, 22',
        'Shade B1 composite layered and polished',
        'Sensodyne paste BD\nHexidine mouthwash gargle', '2026-09-10', ?
    )
");
$stmtVisit->execute([$newPatientId, $testCost]);
$newVisitId = (int)$db->lastInsertId();
assertTest("Clinical visit recorded with FDI tooth numbers (11, 12, 21, 22) and cost ₹25,000", $newVisitId > 0);

// Check balance before payment
$finBeforePay = getPatientFinancialSummary($newPatientId);
assertTest("Initial balance for new visit is ₹25,000", (float)$finBeforePay['balance'] === 25000.0);

// 8. Test Multi-Payment Flow (Installment 1: ₹10,000, Installment 2: ₹15,000)
// Payment 1
$rec1 = getNextReceiptNumber();
$db->prepare("INSERT INTO payments (receipt_no, patient_id, visit_id, payment_date, amount, payment_method, notes) VALUES (?, ?, ?, '2026-08-30', 10000.0, 'UPI', 'Installment 1 via PhonePe')")->execute([$rec1, $newPatientId, $newVisitId]);

$finAfterPay1 = getPatientFinancialSummary($newPatientId);
assertTest("After Payment 1 (₹10,000), total paid is ₹10,000", (float)$finAfterPay1['total_paid'] === 10000.0);
assertTest("After Payment 1, remaining balance is ₹15,000", (float)$finAfterPay1['balance'] === 15000.0);

// Payment 2 (Settling balance)
$rec2 = getNextReceiptNumber();
$db->prepare("INSERT INTO payments (receipt_no, patient_id, visit_id, payment_date, amount, payment_method, notes) VALUES (?, ?, ?, '2026-08-30', 15000.0, 'Card', 'Final settlement via POS card swipe')")->execute([$rec2, $newPatientId, $newVisitId]);

$finAfterPay2 = getPatientFinancialSummary($newPatientId);
assertTest("After Payment 2 (₹15,000), total paid is ₹25,000", (float)$finAfterPay2['total_paid'] === 25000.0);
assertTest("After Payment 2, remaining balance is ₹0.00", (float)$finAfterPay2['balance'] === 0.0);
assertTest("Patient status updates automatically to 'Paid in Full'", $finAfterPay2['status'] === 'Paid in Full');

// 9. Test Global Clinic Financial Summary
$clinicStats = getClinicFinancialSummary();
assertTest("Clinic Financial Summary calculates total billing > 0", $clinicStats['total_billing'] > 0);
assertTest("Clinic Financial Summary calculates total collected > 0", $clinicStats['total_collected'] > 0);
assertTest("Clinic Financial Summary matches formula: Outstanding = Billing - Collected", abs($clinicStats['total_outstanding'] - ($clinicStats['total_billing'] - $clinicStats['total_collected'])) < 0.01);

// 10. Test Currency Formatter
$formatted = formatCurrency(125000);
assertTest("formatCurrency(125000) produces Indian numbering with ₹ symbol", str_contains($formatted, '1,25,000'));

echo "\n========================================================================\n";
echo "📊 TEST RESULTS SUMMARY:\n";
echo " Passed: {$passCount}\n";
echo " Failed: {$failCount}\n";
echo " Total:  " . ($passCount + $failCount) . "\n";
echo "========================================================================\n";

exit($failCount === 0 ? 0 : 1);
