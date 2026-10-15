# StudentHub - Campus Digital Portal 🎓
**Subject:** Web Development Frameworks (ITUE203) — Semester 3 (Odd 2026-27)  
**Institution:** Charotar University of Science and Technology (CHARUSAT)  
**Faculty:** Faculty of Technology and Engineering (FTE)

---

## 📖 Project Overview
**StudentHub** is an enterprise-grade campus digital portal designed to streamline academic records, campus events, and administrative workflows. Built systematically across 15 progressive laboratory practicals, the application integrates semantic accessible frontends, responsive layouts, client-side interactivity, server-side PHP processing, MySQL database persistence, asynchronous REST JSON APIs, and role-based administration consoles.

---

## 🗂️ Project Directory Structure

```text
StudentHub/
├── css/
│   ├── style.css                  # Core CSS design system, CSS Grid/Flexbox tokens
│   └── responsive.css             # Responsive breakpoints (Mobile, Tablet, Desktop)
├── data/
│   ├── events.json                # Seed events dataset (Practical 6)
│   ├── faqs.json                  # Interactive FAQ dataset (Practical 6)
│   ├── students.json              # Student profiles dataset (Practical 6)
│   ├── registrations.csv          # Form submissions CSV log (Practical 7)
│   ├── registrations_stored.json  # Form submissions JSON log (Practical 7)
│   └── contacts.csv               # Contact/feedback CSV submissions (Practical 7)
├── database/
│   ├── schema.sql                 # MySQL relational schema with 5 tables & FKs (Practical 8)
│   └── seed.sql                   # 21 students, 16 events, users, registrations, audit logs
├── docs/
│   ├── sitemap.png                # Low-fidelity sitemap diagram (Practical 1)
│   └── wireframes/                # Responsive UI wireframes (Practical 1)
│       ├── home-wireframe.png
│       ├── login-wireframe.png
│       ├── dashboard-wireframe.png
│       └── events-wireframe.png
├── images/
│   ├── logo.png                   # StudentHub brand emblem
│   └── StudentHub.png             # Campus hero poster & event graphics
├── js/
│   ├── Script.js                  # Theme toggle, accordion, slider, validation, captcha (Practicals 4 & 5)
│   ├── events.js                  # Fetch API, search, filter, pagination, caching (Practical 6)
│   └── students.js                # Student table rendering, filter, and CSV export (Practicals 6 & 11)
├── pages/
│   ├── index.html                 # Home portal page (Practical 2)
│   ├── about.html                 # University & portal overview (Practical 2)
│   ├── register.html              # Registration form with JS validation (Practicals 2 & 5)
│   ├── login.html                 # Authentication login form (Practicals 2 & 10)
│   ├── dashboard.html             # Student personalized dashboard (Practical 2)
│   ├── events.html                # Campus events directory (Practicals 2 & 6)
│   ├── profile.html               # Student academic profile (Practical 2)
│   ├── contact.html               # Contact & feedback form (Practicals 2 & 7)
│   ├── admin.html                 # Admin static wireframe layout (Practical 2)
│   ├── faq.html                   # Frequently Asked Questions accordion (Practicals 2 & 4)
│   ├── feedback.html              # Rating & student feedback form (Practical 2)
│   └── ajax_crud.html             # Single-page async AJAX/Fetch CRUD studio (Practical 13)
├── php/
│   ├── config/
│   │   ├── db.php                 # PDO database connection & audit helper (Practical 8)
│   │   └── db_mysqli.php          # MySQLi object-oriented connection helper (Practical 11)
│   ├── includes/
│   │   └── auth_check.php         # RBAC, session timeout, fixation defense, CSRF (Practical 10)
│   ├── api/
│   │   ├── events_api.php         # RESTful JSON API: GET, POST, PUT, DELETE (Practical 13)
│   │   └── students_api.php       # Students RESTful JSON API (Practical 13)
│   ├── login_user.php             # Password verify, RBAC redirection, remember-me (Practical 10)
│   ├── logout.php                 # Session destruction & cookie cleanup (Practical 10)
│   ├── process_register.php       # Server-side validation, duplicate check, bcrypt (Practicals 7 & 9)
│   ├── process_contact.php        # Contact validation & file writing (Practical 7)
│   ├── view_records.php           # CSV/JSON file record viewer (Practical 7)
│   ├── students_crud.php          # MySQLi student CRUD with search, filter, soft delete (Practical 11)
│   ├── events_crud.php            # Admin event CRUD with poster upload validation (Practical 12)
│   └── admin_dashboard.php        # Dynamic admin console with SQL metrics & audit trail (Practical 14)
├── uploads/
│   └── posters/                   # Uploaded event posters with .htaccess execution block (Practical 12)
└── README.md                      # Comprehensive laboratory documentation (Practicals 1 & 15)
```

---

## 📋 Complete Practical Implementation Matrix (1 to 15)

| Practical No. | Title | Core Technology & Concept | Implemented Deliverables |
|:---:|:---|:---|:---|
| **1** | Project Initiation, Requirements, Sitemap, Wireframe | Requirements, Wireframes, Project Structuring | `docs/sitemap.png`, `docs/wireframes/*.png`, Clean directory layout, `README.md` |
| **2** | Semantic HTML5 Pages with Accessibility Structure | Semantic HTML5, WCAG 2.1 Accessibility | 11 Complete linked static pages in `pages/` with `header`, `nav`, `main`, `section`, `article`, `footer`, skip links |
| **3** | Responsive UI Design using CSS Grid & Flexbox | CSS3, Flexbox, Grid, Media Queries | `css/style.css`, `css/responsive.css` (Mobile ≤480px, Tablet ≤768px, Desktop ≥1024px) |
| **4** | JavaScript DOM Manipulation, Events & Interactivity | DOM manipulation, Events, LocalStorage | `js/Script.js`: Dark/Light theme switcher with `localStorage`, Hamburger menu, Collapsible FAQ, Content slider, Toast alerts |
| **5** | Registration Form with Frontend Validation & UX | HTML5 Forms, Regex validation, UX Feedback | `pages/register.html`, `js/Script.js`: Real-time Regex checks (name, email, 10-digit mobile), Password strength meter, Canvas CAPTCHA |
| **6** | Rendering External JSON Data using Fetch API | Fetch API, Async/Await, Array methods, Pagination | `data/*.json`, `js/events.js`, `js/students.js`: Live async rendering, debounced search, category filter, dynamic pagination, offline caching |
| **7** | PHP Form Processing & File Storage (CSV/JSON) | PHP POST handling, Server-side validation, File I/O | `php/process_register.php`, `php/process_contact.php`, `php/view_records.php`: `flock` file writing to CSV & JSON format |
| **8** | MySQL Schema Design, ER Model & PDO Prepared Statements | Relational DB modeling, Constraints, PDO | `database/schema.sql` (5 tables: users, students, events, registrations, audit_logs), `database/seed.sql`, `php/config/db.php` |
| **9** | Secure Registration with DB Insert & Password Hashing | Duplicate checks, `password_hash()`, Transactions | `php/process_register.php`: Bcrypt cost factor 10 hashing, duplicate email detection, transactional insert into users & students |
| **10** | Secure Login/Logout with Sessions, RBAC & Timeout | PHP Sessions, `password_verify()`, Timeout, Cookies | `php/login_user.php`, `php/logout.php`, `php/includes/auth_check.php`: 15-minute inactivity timeout, session ID regeneration, SHA-256 remember-me |
| **11** | Student Management CRUD with Search, Filter & MySQLi | MySQLi Prepared Statements, Soft-Delete, CSV Export | `php/config/db_mysqli.php`, `php/students_crud.php`: Full SELECT, INSERT, UPDATE, DELETE (Soft & Hard), dynamic filter, CSV export |
| **12** | Event Management CRUD with Poster Upload Validation | File Upload Validation, MIME Check, Directory Shield | `php/events_crud.php`, `uploads/posters/.htaccess`: Max 2MB size check, MIME verification (`image/jpeg`, `image/png`, `image/webp`), safe renaming |
| **13** | PHP JSON API & AJAX/Fetch-Based CRUD without Reload | RESTful JSON API, Fetch API, Status Codes | `php/api/events_api.php`, `php/api/students_api.php`, `pages/ajax_crud.html`: Asynchronous CRUD, status codes (200, 201, 422, 404), network inspector |
| **14** | Admin Dashboard with RBAC, Dynamic Menu & Audit Log | SQL Aggregations, RBAC, User Status Toggle, Canvas | `php/admin_dashboard.php`: Live SQL count metrics, role-specific nav menu, user active/inactive toggle, audit logs viewer, canvas analytics chart |
| **15** | Final Integration, Testing, Documentation & Local Deploy | End-to-end testing, local deployment, clean Git | Full system interconnection, zero syntax errors, complete security & accessibility checklist |

---

## 🚀 Setup & Local Deployment Guide

### Prerequisites
- **XAMPP / WAMP / LAMP** stack with:
  - Apache 2.4+
  - PHP 8.0+ (Tested on PHP 8.2.12)
  - MySQL 5.7+ / MariaDB 10.4+

### Step-by-Step Installation

1. **Deploy Project to Apache Web Root:**
   Copy the `StudentHub` directory into your local web server root:
   - For XAMPP on Windows: `C:\xampp\htdocs\StudentHub`
   - Or configure an Apache Virtual Host pointing to `StudentHub`.

2. **Launch Services:**
   Open the **XAMPP Control Panel** and click **Start** for both **Apache** and **MySQL**.

3. **Database Configuration & Import:**
   - Open your browser and navigate to **phpMyAdmin**: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
   - Click on the **SQL** tab or create a database named `studenthub_db`.
   - Import `database/schema.sql` to generate all 5 relational tables.
   - Import `database/seed.sql` to populate initial users, 21 student profiles, 16 campus events, registrations, and audit logs.

4. **Verify Database Credentials:**
   Default configuration in `php/config/db.php` and `php/config/db_mysqli.php`:
   - **Host:** `127.0.0.1`
   - **Database:** `studenthub_db`
   - **Username:** `root`
   - **Password:** `""` (empty by default in XAMPP)

5. **Access the Portal:**
   - Public Landing Page: [http://localhost/StudentHub/pages/index.html](http://localhost/StudentHub/pages/index.html)
   - Dynamic Admin Console: [http://localhost/StudentHub/php/admin_dashboard.php](http://localhost/StudentHub/php/admin_dashboard.php)
   - Student Management CRUD: [http://localhost/StudentHub/php/students_crud.php](http://localhost/StudentHub/php/students_crud.php)
   - Event Management CRUD: [http://localhost/StudentHub/php/events_crud.php](http://localhost/StudentHub/php/events_crud.php)
   - Asynchronous AJAX Studio: [http://localhost/StudentHub/pages/ajax_crud.html](http://localhost/StudentHub/pages/ajax_crud.html)
   - Stored Records (CSV/JSON): [http://localhost/StudentHub/php/view_records.php](http://localhost/StudentHub/php/view_records.php)

---

## 🔑 Default Test Credentials

| Role | Email / Username | Password | Access Level |
|:---|:---|:---|:---|
| **Super Admin** | `admin@charusat.edu.in` (`admin`) | `Admin@123` | Full access to Admin Console, Student CRUD, Event CRUD, User Status Toggle, and Audit Logs |
| **Student** | `student@charusat.edu.in` (`aarav_sharma`) | `Password@123` | Access to Student Dashboard, Events registration, Academic profile |

---

## 🔒 Security & Quality Standards Implemented

- **Prepared Statements (SQL Injection Immunity):** All database operations across PDO (`php/config/db.php`) and MySQLi (`php/config/db_mysqli.php`) use parameterized queries with placeholder binding.
- **Password Security:** Hashes stored using `password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 10])` and verified with timing-attack resistant `password_verify()`.
- **Session Security:**
  - `session_regenerate_id(true)` executed upon login to defeat session fixation.
  - Inactivity timeout automatically invalidates sessions after 15 minutes of idle time.
  - Cookies set with `httponly: true`, `samesite: Lax`, and conditional `secure` flags.
- **Upload Hardening:**
  - Uploaded poster images validated for size (≤ 2MB) and true MIME type (`finfo_file`).
  - Cryptographically randomized file naming (`poster_YYYYMMDD_HHMMSS_<token>.ext`).
  - Directory protection via `uploads/posters/.htaccess` denying PHP script execution.
- **Auditing & Traceability:** Immutable audit logging (`audit_logs`) recording IP addresses, user agents, action names, and details for registrations, logins, status toggles, and event modifications.
- **WCAG 2.1 Accessibility:** Semantic HTML5 elements (`header`, `nav`, `main`, `section`, `article`, `footer`), `skip-link` to main content, proper ARIA labels, form label bindings, and contrast compliant palettes.

---

## 🧪 Laboratory Viva & Evaluation Test Cases

1. **Client & Server Validation:** Register with mismatched passwords or invalid emails to inspect instant JavaScript tooltips and server-side sanitized error pages.
2. **File Storage & Persistence:** Submit a registration form or contact message and inspect `data/registrations.csv` and `data/registrations_stored.json`.
3. **Role-Based Redirection:** Log in as `student@charusat.edu.in` and attempt to navigate to `php/admin_dashboard.php` to observe automatic `403 Forbidden` restriction.
4. **Student CRUD & Soft Delete:** Search by student ID (e.g. `24IT001`), edit details, or perform a soft delete to mark the record inactive without data loss.
5. **Event Management & File Upload:** Upload a new event poster; verify size and MIME enforcement, directory storage in `uploads/posters/`, and corresponding audit log entry.
6. **Async AJAX CRUD (No Reload):** In `pages/ajax_crud.html`, create or delete an event and monitor live HTTP response codes in the integrated Fetch Network Inspector.

---
*Created as part of ITUE203 Web Development Frameworks Laboratory Curriculum &bull; CHARUSAT University*
