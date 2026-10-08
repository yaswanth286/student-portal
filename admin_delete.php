<?php
/** admin_delete.php - deletes a student record AND their uploaded PDF (POST only). */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin_dashboard.php');
}
csrf_check();

$id   = (int)($_POST['id'] ?? 0);
$stmt = db()->prepare("SELECT aadhaar_file FROM users WHERE id = ? AND role = 'student'");   // admin account can't be deleted
$stmt->execute([$id]);
$user = $stmt->fetch();

if ($user) {
    db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    delete_aadhaar($user['aadhaar_file']);
    flash('success', 'Student deleted.');
} else {
    flash('danger', 'Student not found.');
}
redirect('admin_dashboard.php');
