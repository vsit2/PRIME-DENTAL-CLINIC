# Prime Dental Clinic Management System
**"The Prime Destination For Smiles"**  
**Dentist:** Dr. Rutuja Deshmukh B.D.S (Mumbai) | **Reg No:** A44351  
**Phone:** 9892429014 | **Email:** rutujadeshmukh0124@gmail.com  
**Address:** Shop No. 01, Plot No. 30, Ground Floor, Matruchhaya Building, Vallabhbaug Lane, Ghatkopar East, Mumbai - 400077

---

## 🌟 Overview & System Highlights

A complete, production-grade Dental Clinic Management System built for real day-to-day use by Dr. Rutuja Deshmukh and clinic staff. 

### Key Features:
1. **Prominent Clinic Branding**: Complete clinic and dentist credentials, full address in Ghatkopar East, contact numbers, and elegant modern dental design.
2. **Dashboard & Real-time Live Search**: Automatic calculation of Total Patients, Today's Patients, Total Collected, and Outstanding Balance. Instant debounced search for Patient Name, PDC Reg Number, or Mobile with real-time balance previews.
3. **Comprehensive Patient Registration**: Auto-generated sequential Registration Numbers (`PDC-0001`, `PDC-0002`...), automatic DOB-to-Age calculation with manual override, 24 Reason for Visit options, 16 Medical Condition checkboxes with Habit, Allergy, and Medication detail fields, plus Family Physician and Emergency contact info.
4. **Complete Patient Profiles**: Medical alert warning badges, demographic cards, chronological visit histories, and full payment ledger.
5. **Clinical Records & Interactive FDI Tooth Chart**: Visual interactive tooth chart (Permanent teeth 11–48) with quick selection shortcuts, Chief Complaint, Diagnosis, Treatment Notes, Prescriptions (Rx), and Follow-up reminders.
6. **Financial & Multi-Payment Management**: Track multi-installment payments (Cash, UPI, Card, Net Banking), automated real-time balance calculations (`Total Bill - Sum(Paid)`), `PAID IN FULL` / `BALANCE DUE` status badges.
7. **Outstanding Payments Tracker**: Filterable list sorted by highest unpaid balances with direct payment recording.
8. **Reports & Analytics**: Lifetime billing, collections, monthly trends, payment method breakdowns, and gender statistics.
9. **A4 Printable Documents**: Official print-optimized clinical records, Rx prescription slips, and payment receipts with Dr. Rutuja Deshmukh's official letterhead and signature block.
10. **Data Backup & Security**: Full SQL database dump generator, CSV exports for Patients, Payments, and Outstanding balances, secure password hashing (`BCRYPT`), session management, and PDO prepared statements against SQL injection.

---

## 🚀 Setup & Installation Instructions (XAMPP)

### Prerequisites:
- **XAMPP** (with Apache, PHP 7.4 / 8.x, and MySQL / MariaDB).

### Step 1: Copy to XAMPP htdocs (or run directly from workspace)
If deploying under XAMPP:
Copy the project folder to `C:\xampp\htdocs\prime-dental`

### Step 2: Start Apache and MySQL in XAMPP
Open the **XAMPP Control Panel** and click **Start** next to **Apache** and **MySQL**.

### Step 3: Initialize Database (Automatic or 1-Click)
The system includes an automated installer:
1. Open your browser and navigate to:
   ```
   http://localhost/prime-dental/database/installer.php
   ```
   *(Or if running via PHP CLI: `php database/installer.php`)*
2. The installer will create the database `prime_dental_db`, build all relational tables, and insert sample data (including Rahul Sharma PDC-0001 with Dental Implant ₹30,000 and payments, Anita Patil PDC-0002, Vikram Joshi PDC-0003, and Sneha Kulkarni PDC-0004).

### Step 4: Login Credentials
- **URL**: `http://localhost/prime-dental/login.php` (or `http://localhost:8080/login.php`)
- **Username**: `admin` (or `dr.rutuja`)
- **Password**: `admin123` (or `Prime@2026`)

---

## 💻 Running via PHP Built-in Server (Alternative Quick Launch)

You can also run the system directly from this workspace folder using the command line:

```powershell
& "C:\xampp\php\php.exe" -S localhost:8080
```

Then visit:
```
http://localhost:8080
```

---

## 📂 Project Directory Structure

```
Dental/
├── config/
│   ├── config.php            # Clinic constants, app configuration, reason & medical lists
│   ├── database.php          # PDO connection singleton with auto-database creation
│   ├── auth.php              # Secure login session, auth checks, and password verification
│   └── helpers.php           # Indian currency formatting (₹), age calculator, sequential IDs
├── database/
│   ├── schema.sql            # Relational MySQL schema (users, patients, medical_history, visits, payments, clinic_settings)
│   ├── seed.sql              # Official clinic settings and demo patients
│   └── installer.php         # Browser and CLI database migration tool
├── includes/
│   ├── header.php            # HTML head, Google Fonts, stylesheets
│   ├── topbar.php            # Global live search bar & quick actions
│   ├── sidebar.php           # Responsive navigation menu with dynamic dues badge
│   └── footer.php            # Core JavaScript scripts and closing tags
├── assets/
│   ├── css/
│   │   ├── style.css         # Modern medical dental CSS design system
│   │   ├── dental-chart.css  # Interactive tooth chart styles
│   │   └── print.css         # High-resolution A4 printable stylesheet
│   ├── js/
│   │   ├── main.js           # Core UI logic, modals, sidebar toggle, toast alerts
│   │   ├── search.js         # Real-time AJAX live patient search
│   │   ├── registration.js   # Dynamic DOB-to-Age calculation & condition toggles
│   │   └── dental-chart.js   # Interactive FDI tooth chart click handler
│   └── images/
│       └── logo.svg          # Premium Prime Dental Clinic logo & tooth emblem
├── api/
│   ├── search_patients.php   # JSON live patient search endpoint
│   └── save_quick_payment.php# Quick payment recording API
├── pages/
│   ├── dashboard.php         # Main clinic dashboard with real-time stats & instant search
│   ├── patients.php          # All Patients list with filters, sorting, and action menus
│   ├── patient-add.php       # Complete patient registration form
│   ├── patient-view.php      # Full patient profile (clinical history, billing, medical alerts)
│   ├── patient-edit.php      # Edit patient demographics and medical history
│   ├── visits.php            # Clinical visits & treatment logs
│   ├── visit-add.php         # Record clinical visit with FDI tooth chart & prescription
│   ├── payments.php          # Payments & accounts ledger
│   ├── payment-add.php       # Record payments with auto remaining balance calculator
│   ├── outstanding.php       # Unpaid balances tracker sorted by highest due
│   ├── reports.php           # Financial & Patient reports and analytics
│   ├── settings.php          # Clinic profile settings, password change, data backups
│   └── export.php            # CSV & SQL backup download engine
├── print/
│   ├── print-record.php      # Printable full patient medical & dental record
│   ├── print-receipt.php     # Printable official payment receipt
│   └── print-prescription.php# Printable dental Rx prescription slip
├── index.php                 # Root entry router
├── login.php                 # Staff & Dentist authentication
├── logout.php                # Secure logout handler
└── README.md                 # Project documentation & manual
```
