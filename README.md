# Student Registration Portal (PHP + MySQL + Bootstrap)

One backend (PHP 8), one database (MySQL), one frontend (HTML/CSS/Bootstrap 5).

## Setup (XAMPP / WAMP / Laragon)
1. Copy the `student-portal` folder into `htdocs` (XAMPP) or `www` (WAMP).
2. Start **Apache** and **MySQL**.
3. Open phpMyAdmin -> **Import** -> choose `database.sql` (creates DB `student_portal`, table `users` and the admin).
4. If your MySQL password is not empty, edit `config.php` (DB_USER / DB_PASS).
5. Open http://localhost/student-portal/

Requires PHP 8.0+ with the `pdo_mysql` and `fileinfo` extensions (both on by default in XAMPP).
Make sure the `uploads/` folder is writable (Linux/Mac: `chmod 775 uploads`).

## Login details
| Role    | Email               | Password  |
|---------|---------------------|-----------|
| Admin   | admin@example.com   | Admin@123 |
| Student | register one via Signup | your own |

## Folder structure
```
student-portal/
  database.sql          tables + default admin
  config.php            DB connection, session, helper functions
  index.php             single login page (admin + student)
  signup.php            student registration (Create)
  forgot_password.php   reset step 1 (makes token + link)
  reset_password.php    reset step 2 (new password)
  student_dashboard.php student profile (Read)
  student_edit.php      student edits profile (Update, email locked)
  admin_dashboard.php   student table + instant filters
  admin_edit.php        admin edits student (name + email locked)
  admin_delete.php      delete student + file (Delete)
  logout.php
  includes/             auth.php (access control), functions.php (validation, upload, form), header/footer
  assets/style.css
  uploads/              Aadhaar PDFs (random names, scripts blocked by .htaccess)
```

## Data flow (step by step)
**Signup:** form -> `validate_student()` (required fields, email format, duplicate email -> "This email is already registered.", PDF check)
-> `save_aadhaar()` stores the file as `<32 random hex chars>.pdf` -> INSERT with `password_hash()` -> redirect to login.

**Login:** email + password -> `password_verify()` -> session stores id/role -> admin goes to `admin_dashboard.php`, student to `student_dashboard.php`.

**Access control:** every protected page begins with `require_role('admin' | 'student')`. Opening an admin URL as a student (or without login) is redirected.

**Admin filters:** each table row carries `data-name`, `data-class`, `data-age`; JavaScript hides rows on every keystroke (name, class, min age, max age).

**Locked fields:** admin edit shows name/email as read-only AND the server overwrites them with stored values and leaves them out of the UPDATE query.

**Forgot password:** token (valid 1 hour) saved in DB -> link `reset_password.php?token=...` -> new password hashed and token cleared.
Without a mail server the link is shown on screen; on a live server replace that with `mail()`.

## Quick test checklist
- [ ] Register a student with a PDF -> success message, redirected to login
- [ ] Register again with same email -> red message "This email is already registered."
- [ ] Upload a .jpg / renamed file -> rejected (PDF only)
- [ ] Check `uploads/` -> files have random names; two uploads of `aadhaar.pdf` do not overwrite
- [ ] Login as admin -> Admin Dashboard; as student -> Student Dashboard
- [ ] As student, open `admin_dashboard.php` directly -> blocked; logged out -> sent to login
- [ ] Admin: filter by name / class / min-max age; click "Open PDF"; Edit (name+email locked); Delete
- [ ] Student: "Edit Profile" (email locked), replace Aadhaar PDF
- [ ] Forgot Password -> reset link -> login with new password

## Security notes
Prepared statements (SQL injection), `htmlspecialchars` on output (XSS), bcrypt passwords, CSRF tokens on forms,
MIME + extension + size check on uploads, session ID regenerated at login.
