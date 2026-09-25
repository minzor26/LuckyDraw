# Mobile Gallery Lucky Draw / Prize Wheel Web Application

A complete, production-ready, mobile-responsive **Lucky Draw & Prize Wheel** application designed for mobile and electronics retail shops running customer promotional campaigns.

---

## 🌟 Key Features

### 1. Public Lucky Draw Page (`/index.html`)
- **Dynamic HTML5 Canvas Wheel**: Visually renders active coupons and prizes with smooth physics (acceleration, deceleration curve, exact stopping alignment).
- **Authoritative Backend Execution**: Frontend never selects the winner randomly. All draw logic runs securely on the PHP backend using atomic database transactions.
- **Synthesized Audio Engine**: Realistic mechanical wheel ticking and winner fanfare sound effects built with pure Web Audio API (no external MP3 dependencies).
- **Step-by-Step Customer Flow**:
  1. Input Coupon Number.
  2. Verify eligibility & customer name / masked mobile number (`98******25`).
  3. "SPIN NOW" button triggers authoritative spin.
  4. Celebratory overlay with canvas confetti, prize card, print receipt button, and restart view.
- **Mobile-First Indian Retail Theme**: Metallic gold/yellow highlights, red/orange festive accents, and Hindi + English typography ("भाग्य आज़माएँ", "SPIN NOW", "बधाई हो!").

### 2. Winning & Winner Protection Logic
- **Special Major Prizes (Car, Scooty)**:
  - Admin pre-defines exact winning coupons (e.g. **Car &rarr; Coupon 125**, **Scooty &rarr; Coupon 276**).
  - Public APIs never reveal pre-assignments until the customer spins.
- **Regular Prizes (Headphones, Smart Watch, Power Bank, etc.)**:
  - Automatically selected from available active inventory (`quantity > 0`).
  - Decrements stock by 1 in an atomic SQL transaction.
  - Automatically skipped once stock hits 0.
- **Anti-Duplicate Winner Protection**:
  - Coupons cannot win twice unless explicitly reopened or reset by admin.
  - Concurrent spin protection via PDO transactions.

### 3. Secure Admin Panel (`/admin/`)
- **Authentication**: Password hashed with `password_hash()`, secure PHP sessions, CSRF token validation on all POST APIs.
- **Dashboard**: Live statistics for total coupons, remaining coupons, winner counts, Car & Scooty pre-assignment status cards, and prize stock.
- **Coupon Management**: Add/Edit single coupon, search & status filtering, CSV bulk import (`coupon_number,name,mobile,address,city,state`).
- **Prize Catalog**: Manage special and regular prizes, edit total stock and remaining stock.
- **Special Winners Page**: Dedicated interface to bind Car and Scooty to specific coupon numbers with audit logging history.
- **Winner History**: Comprehensive winner list, search, filter by prize, **Export CSV**, and **Print Layout**.
- **Settings & Reset Controls**: Toggle public draw, sound defaults, Test Mode toggle, **Reset Test Data**, and **Reset Production Draw** (requires confirmation code `RESET_PRODUCTION_CONFIRM`).

---

## 📁 Directory Structure

```text
/mobile-gallery-lucky-draw
│
├── index.html                   # Public Lucky Draw Page
├── .htaccess                    # Security & Directory Protection Rules
├── README.md                    # System Documentation
│
├── assets/
│   ├── css/
│   │   └── style.css            # Master Responsive Stylesheet
│   └── js/
│       ├── app.js               # Canvas Wheel Engine & API Client
│       └── confetti.js          # Canvas Confetti Particle System
│
├── api/
│   ├── coupon.php               # Public Coupon Verification Endpoint
│   ├── spin.php                 # Authoritative Backend Draw Engine
│   ├── prizes.php               # Public Active Prizes Endpoint
│   ├── winners.php              # Public Recent Winners Ticker Endpoint
│   ├── login.php                # Admin Login API
│   ├── logout.php               # Admin Logout API
│   └── admin/
│       ├── add-coupon.php       # Add/Edit Single Coupon API
│       ├── import-coupons.php   # CSV Import API
│       ├── add-prize.php        # Create Prize API
│       ├── update-prize.php     # Update/Delete Prize API
│       ├── assign-special-prize.php # Special Prize Winner Assignment API
│       ├── reset-draw.php       # Test vs Production Draw Reset API
│       └── settings.php         # Campaign Settings API
│
├── admin/
│   ├── index.php                # Admin Login View
│   ├── dashboard.php            # Admin Overview Dashboard
│   ├── coupons.php              # Coupon Management View
│   ├── prizes.php               # Prize Catalog Management View
│   ├── special-winners.php      # Special Prize Winner Binding View
│   ├── winners.php              # Winner Log & CSV Export View
│   ├── settings.php             # Campaign Settings & Reset Controls
│   └── includes/
│       ├── header.php           # Admin Navigation Header Template
│       └── footer.php           # Admin Footer Template
│
├── config/
│   └── database.php             # PDO Connection & SQLite Auto-Bootstrap
│
├── includes/
│   ├── auth.php                 # Authentication Middleware & Session Control
│   ├── csrf.php                 # Anti-CSRF Token Generators
│   └── functions.php            # Core Utilities & Draw Engine
│
└── database/
    ├── schema.sql               # MySQL DDL Schema
    └── seed.sql                 # Sample Seed Data (Coupons 1-357, Car/Scooty assignments)
```

---

## 🚀 Deployment Instructions (Hostinger / cPanel / Shared Hosting)

### Step 1: Create Database
1. Log in to your hosting panel (cPanel or Hostinger hPanel).
2. Go to **MySQL Databases** and create a new database named `mobile_gallery_draw`.
3. Create a MySQL user with full privileges and assign it to the database.

### Step 2: Import Database Schema & Seed Data
1. Open **phpMyAdmin**.
2. Select `mobile_gallery_draw`.
3. Go to the **Import** tab and upload `database/schema.sql`.
4. Next, import `database/seed.sql` to populate sample coupons (1 to 357), special prizes (Car assigned to Coupon 125, Scooty assigned to Coupon 276), regular prizes, and initial settings.

### Step 3: Configure Database Credentials
Edit `config/database.php` with your database credentials:

```php
define('DB_DRIVER', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'mobile_gallery_draw');
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');
```

### Step 4: Upload Files
Upload all files to your web hosting root directory (usually `public_html/`).

---

## 🔑 Default Admin Credentials

- **URL**: `https://yourdomain.com/admin/`
- **Username**: `admin`
- **Password**: `admin123`

*(Note: Passwords can be changed or new admin users created directly in the database using `password_hash()`)*

---

## 📊 CSV Import Reference

To bulk import coupons from Excel or CSV, prepare a file with the following column header:

```csv
coupon_number,name,mobile,address,city,state
357,Rahul Sharma,9876543210,Main Road,Rewa,Madhya Pradesh
```

Upload this via **Admin &rarr; Coupons &rarr; Import CSV**.

---

## 🛡️ Security Features
- **PDO Prepared Statements**: Eliminates SQL injection risks.
- **CSRF Token Verification**: Protects all state-changing admin POST requests.
- **Password Hashing**: Passwords stored using standard `password_hash()`.
- **Sensitive File Protection**: `.htaccess` blocks public access to `.sql`, `.sqlite`, `config/`, and `includes/`.
- **Input Sanitization**: All HTML output sanitized with `htmlspecialchars()`.
