<?php
/**
 * auth.php - access control.
 * Call require_role('admin') or require_role('student') at the top of a protected page.
 */
require_once __DIR__ . '/../config.php';

function require_role(string $role): void
{
    if (empty($_SESSION['user_id'])) {
        flash('warning', 'Please login first.');
        redirect('index.php');
    }
    if ($_SESSION['role'] !== $role) {
        // Logged in, but trying to open the other user type's page
        flash('danger', 'Access denied. You do not have permission to open that page.');
        redirect($_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php');
    }
}

/** If already logged in, send the user to their own dashboard. */
function redirect_if_logged_in(): void
{
    if (!empty($_SESSION['user_id'])) {
        redirect($_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php');
    }
}
