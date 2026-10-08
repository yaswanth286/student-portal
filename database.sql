-- Student Registration Portal - database setup
-- Import with:  mysql -u root -p < database.sql   (or use phpMyAdmin -> Import)

CREATE DATABASE IF NOT EXISTS student_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_portal;

CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role            ENUM('admin','student') NOT NULL DEFAULT 'student',
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,      -- UNIQUE also protects against duplicates at DB level
    password        VARCHAR(255) NOT NULL,             -- bcrypt hash, never plain text
    dob             DATE NULL,
    gender          ENUM('Male','Female','Other') NULL,
    qualification   VARCHAR(100) NULL,
    interests       VARCHAR(255) NULL,                 -- comma separated, e.g. "Coding,Sports"
    class           VARCHAR(50) NULL,
    subject         VARCHAR(100) NULL,
    marks           INT NULL,
    aadhaar_file    VARCHAR(100) NULL,                 -- randomised file name stored in /uploads
    reset_token     VARCHAR(64) NULL,                  -- used by Forgot Password
    reset_expires   DATETIME NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin account  ->  email: admin@example.com   password: Admin@123
INSERT INTO users (role, name, email, password)
VALUES ('admin', 'Administrator', 'admin@example.com', '$2y$10$ciPhVu0ja.sYyAVhdpacOujjACIPc3bfYjOytWGkadQIJcpabhNtS')
ON DUPLICATE KEY UPDATE email = email;
