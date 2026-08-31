-- Seed Data for Prime Dental Clinic Management System
USE `prime_dental_db`;

-- Default Clinic Settings
INSERT INTO `clinic_settings` (`id`, `clinic_name`, `tagline`, `dentist_name`, `dentist_qualification`, `reg_no`, `phone`, `email`, `address`, `currency_symbol`)
VALUES (
    1,
    'PRIME DENTAL CLINIC',
    'The Prime Destination For Smiles',
    'Dr. Rutuja Deshmukh',
    'B.D.S (Mumbai)',
    'A44351',
    '9892429014',
    'rutujadeshmukh0124@gmail.com',
    'Shop No. 01, Plot No. 30, Ground Floor, Matruchhaya Building, Vallabhbaug Lane, Ghatkopar East, Mumbai - 400077',
    '₹'
) ON DUPLICATE KEY UPDATE
    `clinic_name` = VALUES(`clinic_name`),
    `tagline` = VALUES(`tagline`),
    `dentist_name` = VALUES(`dentist_name`),
    `dentist_qualification` = VALUES(`dentist_qualification`),
    `reg_no` = VALUES(`reg_no`),
    `phone` = VALUES(`phone`),
    `email` = VALUES(`email`),
    `address` = VALUES(`address`);

-- Users: Admin & Dentist
-- Passwords: 
-- 1. admin / admin123 -> $2y$10$wN7JqR4G7i3mZ7QdD0oee.3d0pE9nQO2Z1B4yNqkK8h3C5E9zF8aK (bcrypt)
-- 2. dr.rutuja / Prime@2026
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`)
VALUES
(1, 'admin', 'admin@primedental.com', '$2y$10$fA1hHkLhM.c/9T.zS0z0Eel1pBwKz1k9E7xZq5i4g8qW6yR2tK2iW', 'Clinic Administrator', 'admin'),
(2, 'dr.rutuja', 'rutujadeshmukh0124@gmail.com', '$2y$10$fA1hHkLhM.c/9T.zS0z0Eel1pBwKz1k9E7xZq5i4g8qW6yR2tK2iW', 'Dr. Rutuja Deshmukh', 'dentist')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- Sample Patient 1: Rahul Sharma (PDC-0001) - Demonstrating Dental Implant ₹30,000 with Partial Payments
INSERT INTO `patients` (`id`, `registration_no`, `reg_date`, `first_name`, `middle_name`, `last_name`, `dob`, `age`, `gender`, `mobile`, `email`, `address`, `place_of_work`, `education`, `physician_name`, `physician_contact`, `emergency_contact`, `emergency_person`, `emergency_relationship`, `initial_reasons`, `created_at`)
VALUES
(1, 'PDC-0001', '2026-08-15', 'Rahul', 'Kumar', 'Sharma', '1992-05-14', 34, 'Male', '9820145678', 'rahul.sharma@gmail.com', 'Flat 402, Neelkanth Valley, Ghatkopar East, Mumbai - 400077', 'TCS, Vikhroli', 'B.Tech IT', 'Dr. S. Mehta', '9820011223', '9820145679', 'Pooja Sharma', 'Wife', '["Dental Implant", "Pain in Teeth", "Replacement of Missing Tooth"]', '2026-08-15 10:00:00')
ON DUPLICATE KEY UPDATE `registration_no` = VALUES(`registration_no`);

INSERT INTO `medical_history` (`patient_id`, `asthma`, `bleeding_disorder`, `cardiovascular_disorders`, `drug_allergy`, `drug_allergy_details`, `endocrine_disorders`, `fits_fainting`, `gastrointestinal_disorder`, `hospitalization`, `habits`, `habits_details`, `hiv_aids`, `hepatitis`, `tb`, `kidney_disorder`, `pregnancy_lactation`, `current_medication`, `medication_details`, `other_conditions`, `other_details`, `additional_notes`)
VALUES
(1, 0, 0, 0, 1, 'Penicillin / Amoxicillin', 0, 0, 0, 0, 1, 'Non-smoker, occasional alcohol', 0, 0, 0, 0, 0, 0, '', 0, '', 'Patient has slight dental anxiety. Advised premedication if needed.')
ON DUPLICATE KEY UPDATE `patient_id` = VALUES(`patient_id`);

-- Visit 1 for Rahul Sharma
INSERT INTO `visits` (`id`, `patient_id`, `visit_date`, `chief_complaint`, `reason_for_visit`, `diagnosis`, `treatment`, `tooth_number`, `dentist_notes`, `prescription`, `follow_up_date`, `treatment_cost`, `created_at`)
VALUES
(1, 1, '2026-08-15', 'Missing lower right first molar and mild discomfort in adjacent tooth.', '["Dental Implant", "Replacement of Missing Tooth"]', 'Class I Kennedy partially edentulous space in #46 region. Favorable bone density.', 'Osstem Dental Implant Fixture Placement (4.5 x 10mm) under local anesthesia. Healing abutment placed.', '46', 'Procedure completed uneventfully. Primary stability achieved (35 Ncm). Advised soft diet and ice application.', 'Tab Augmentin 625mg (substituted with Tab Cefixime 200mg BD x 5 days due to penicillin allergy)\nTab Zerodol-SP TID x 3 days\nHexidine mouthwash gargle BD', '2026-08-22', 30000.00, '2026-08-15 11:30:00')
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`);

-- Payments for Rahul Sharma (Total cost 30,000; Payment 1 = 10,000; Payment 2 = 5,000; Balance = 15,000)
INSERT INTO `payments` (`id`, `receipt_no`, `patient_id`, `visit_id`, `payment_date`, `amount`, `payment_method`, `notes`, `created_at`)
VALUES
(1, 'REC-0001', 1, 1, '2026-08-15', 10000.00, 'UPI', 'Initial booking and surgical stage deposit via GPay', '2026-08-15 11:45:00'),
(2, 'REC-0002', 1, 1, '2026-08-22', 5000.00, 'Card', 'Suture removal & post-op review installment', '2026-08-22 17:00:00')
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`);

-- Sample Patient 2: Anita Patil (PDC-0002) - Root Canal & Zirconia Crown ₹12,000 (Paid in Full)
INSERT INTO `patients` (`id`, `registration_no`, `reg_date`, `first_name`, `middle_name`, `last_name`, `dob`, `age`, `gender`, `mobile`, `email`, `address`, `place_of_work`, `education`, `physician_name`, `physician_contact`, `emergency_contact`, `emergency_person`, `emergency_relationship`, `initial_reasons`, `created_at`)
VALUES
(2, 'PDC-0002', '2026-08-18', 'Anita', 'Suresh', 'Patil', '1988-11-20', 37, 'Female', '9819234567', 'anita.patil@yahoo.com', 'B-12, Sai Krupa CHS, Tilak Road, Ghatkopar East, Mumbai - 400077', 'HDFC Bank, Ghatkopar', 'M.Com', 'Dr. Joshi', '9820556677', '9819234568', 'Suresh Patil', 'Husband', '["Pain in Teeth", "Crown and Bridge", "Decayed Teeth"]', '2026-08-18 09:30:00')
ON DUPLICATE KEY UPDATE `registration_no` = VALUES(`registration_no`);

INSERT INTO `medical_history` (`patient_id`, `asthma`, `bleeding_disorder`, `cardiovascular_disorders`, `drug_allergy`, `drug_allergy_details`, `endocrine_disorders`, `fits_fainting`, `gastrointestinal_disorder`, `hospitalization`, `habits`, `habits_details`, `hiv_aids`, `hepatitis`, `tb`, `kidney_disorder`, `pregnancy_lactation`, `current_medication`, `medication_details`, `other_conditions`, `other_details`, `additional_notes`)
VALUES
(2, 0, 0, 0, 0, '', 1, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 1, 'Metformin 500mg OD for Type 2 Diabetes', 0, '', 'Diabetic control good (HbA1c 6.4%). Regular followups.')
ON DUPLICATE KEY UPDATE `patient_id` = VALUES(`patient_id`);

INSERT INTO `visits` (`id`, `patient_id`, `visit_date`, `chief_complaint`, `reason_for_visit`, `diagnosis`, `treatment`, `tooth_number`, `dentist_notes`, `prescription`, `follow_up_date`, `treatment_cost`, `created_at`)
VALUES
(2, 2, '2026-08-18', 'Severe throbbing pain in upper left back tooth radiating to temple.', '["Pain in Teeth", "Decayed Teeth"]', 'Irreversible Pulpitis with apical periodontitis #26.', 'Single sitting Root Canal Treatment #26 + Core Build-up + Crown Preparation for Cad-Cam Zirconia Crown.', '26', 'Canals thoroughly cleaned, shaped with rotary ProTaper Gold, obturated with gutta percha. Temporary restoration placed.', 'Tab Ketorol-DT SOS\nTab Pantodac 40mg OD\nTab Augmentin 625mg BD x 5 days', '2026-08-25', 12000.00, '2026-08-18 10:45:00')
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`);

INSERT INTO `payments` (`id`, `receipt_no`, `patient_id`, `visit_id`, `payment_date`, `amount`, `payment_method`, `notes`, `created_at`)
VALUES
(3, 'REC-0003', 2, 2, '2026-08-18', 12000.00, 'UPI', 'Paid in full via PhonePe on day of procedure', '2026-08-18 11:00:00')
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`);

-- Sample Patient 3: Vikram Joshi (PDC-0003) - Teeth Cleaning & Polishing ₹2,500 (Paid)
INSERT INTO `patients` (`id`, `registration_no`, `reg_date`, `first_name`, `middle_name`, `last_name`, `dob`, `age`, `gender`, `mobile`, `email`, `address`, `place_of_work`, `education`, `physician_name`, `physician_contact`, `emergency_contact`, `emergency_person`, `emergency_relationship`, `initial_reasons`, `created_at`)
VALUES
(3, 'PDC-0003', '2026-08-25', 'Vikram', 'Dattatray', 'Joshi', '1995-03-08', 31, 'Male', '9769012345', 'vikram.joshi@gmail.com', 'Flat 101, Shanti Heights, Pant Nagar, Ghatkopar East, Mumbai - 400075', 'Reliance Jio', 'MBA Finance', '', '', '9769012346', 'Dattatray Joshi', 'Father', '["Teeth Cleaning", "Bad Breath", "Pain in Gums"]', '2026-08-25 14:00:00')
ON DUPLICATE KEY UPDATE `registration_no` = VALUES(`registration_no`);

INSERT INTO `medical_history` (`patient_id`, `asthma`, `bleeding_disorder`, `cardiovascular_disorders`, `drug_allergy`, `drug_allergy_details`, `endocrine_disorders`, `fits_fainting`, `gastrointestinal_disorder`, `hospitalization`, `habits`, `habits_details`, `hiv_aids`, `hepatitis`, `tb`, `kidney_disorder`, `pregnancy_lactation`, `current_medication`, `medication_details`, `other_conditions`, `other_details`, `additional_notes`)
VALUES
(3, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 0, '', 0, '', 'No medical contraindications.')
ON DUPLICATE KEY UPDATE `patient_id` = VALUES(`patient_id`);

INSERT INTO `visits` (`id`, `patient_id`, `visit_date`, `chief_complaint`, `reason_for_visit`, `diagnosis`, `treatment`, `tooth_number`, `dentist_notes`, `prescription`, `follow_up_date`, `treatment_cost`, `created_at`)
VALUES
(3, 3, '2026-08-25', 'Yellow stains and bleeding gums while brushing.', '["Teeth Cleaning", "Bad Breath"]', 'Generalized marginal gingivitis with supragingival and subgingival calculus.', 'Full mouth ultrasonic scaling and prophy polishing with mint paste.', 'Full Mouth', 'Reinforced brushing technique (Modified Bass method) and daily flossing instructions.', 'Peridex Chlorhexidine 0.12% Oral Rinse x 14 days\nSensodyne paste', '2027-02-25', 2500.00, '2026-08-25 15:00:00')
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`);

INSERT INTO `payments` (`id`, `receipt_no`, `patient_id`, `visit_id`, `payment_date`, `amount`, `payment_method`, `notes`, `created_at`)
VALUES
(4, 'REC-0004', 3, 3, '2026-08-25', 2500.00, 'Cash', 'Cash payment at reception', '2026-08-25 15:15:00')
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`);

-- Sample Patient 4: Sneha Kulkarni (PDC-0004) - Smile Designing & Veneers ₹45,000 (Balance ₹20,000)
INSERT INTO `patients` (`id`, `registration_no`, `reg_date`, `first_name`, `middle_name`, `last_name`, `dob`, `age`, `gender`, `mobile`, `email`, `address`, `place_of_work`, `education`, `physician_name`, `physician_contact`, `emergency_contact`, `emergency_person`, `emergency_relationship`, `initial_reasons`, `created_at`)
VALUES
(4, 'PDC-0004', '2026-08-28', 'Sneha', 'Ramesh', 'Kulkarni', '1998-07-12', 28, 'Female', '9833445566', 'sneha.kulkarni@outlook.com', 'Flat 801, Emerald Towers, R-Odeon Mall Road, Ghatkopar East, Mumbai - 400077', 'Fashion Designer', 'B.Des', '', '', '9833445567', 'Ramesh Kulkarni', 'Father', '["Smile Designing", "Teeth Whitening", "Tooth Sensitivity"]', '2026-08-28 16:30:00')
ON DUPLICATE KEY UPDATE `registration_no` = VALUES(`registration_no`);

INSERT INTO `medical_history` (`patient_id`, `asthma`, `bleeding_disorder`, `cardiovascular_disorders`, `drug_allergy`, `drug_allergy_details`, `endocrine_disorders`, `fits_fainting`, `gastrointestinal_disorder`, `hospitalization`, `habits`, `habits_details`, `hiv_aids`, `hepatitis`, `tb`, `kidney_disorder`, `pregnancy_lactation`, `current_medication`, `medication_details`, `other_conditions`, `other_details`, `additional_notes`)
VALUES
(4, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 0, '', 0, '', 'Aesthetic concern for upper anterior spacing.')
ON DUPLICATE KEY UPDATE `patient_id` = VALUES(`patient_id`);

INSERT INTO `visits` (`id`, `patient_id`, `visit_date`, `chief_complaint`, `reason_for_visit`, `diagnosis`, `treatment`, `tooth_number`, `dentist_notes`, `prescription`, `follow_up_date`, `treatment_cost`, `created_at`)
VALUES
(4, 4, '2026-08-28', 'Gaps between upper front teeth and discolored enamel.', '["Smile Designing", "Teeth Whitening"]', 'Midline diastema & fluorosis stains on #11, #12, #21, #22.', 'Diagnostic wax-up approved. Minimal prep E-max porcelain veneers (6 units: 13 to 23). Impressions sent to dental lab.', '13, 12, 11, 21, 22, 23', 'Temporary composite veneers fabricated and cemented. Shade chosen: BL2.', 'Tab Enzoflam SOS for sensitivity\nHexidine mouthwash', '2026-09-05', 45000.00, '2026-08-28 18:00:00')
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`);

INSERT INTO `payments` (`id`, `receipt_no`, `patient_id`, `visit_id`, `payment_date`, `amount`, `payment_method`, `notes`, `created_at`)
VALUES
(5, 'REC-0005', 4, 4, '2026-08-28', 25000.00, 'Bank Transfer', 'Advance payment for lab work and veneers', '2026-08-28 18:15:00')
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`);
