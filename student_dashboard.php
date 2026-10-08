<?php
/** student_dashboard.php - student's own profile (Read). */
require_once __DIR__ . '/includes/auth.php';
require_role('student');

$stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$u = $stmt->fetch();
if (!$u) { redirect('logout.php'); }   // account was deleted by the admin

$pageTitle = 'Student Dashboard';
require __DIR__ . '/includes/header.php';
?>
<div class="welcome-banner mb-3">
    Welcome, <?= e($u['name']) ?> (User ID: #<?= e(student_code((int)$u['id'])) ?>)
</div>
<div class="card shadow-sm">
    <div class="card-body">
        <div class="text-end mb-2"><a href="student_edit.php" class="btn btn-sm btn-info text-white">Edit Profile</a></div>
        <div class="detail-row"><strong>Email:</strong> <?= e($u['email']) ?></div>
        <div class="detail-row"><strong>Date of Birth:</strong> <?= e(date('d/m/Y', strtotime($u['dob']))) ?> (Age <?= calc_age($u['dob']) ?>)</div>
        <div class="detail-row"><strong>Gender:</strong> <?= e($u['gender']) ?></div>
        <div class="detail-row"><strong>Qualification:</strong> <?= e($u['qualification']) ?></div>
        <div class="detail-row"><strong>Interests:</strong> <?= e(str_replace(',', ', ', $u['interests'] ?: 'None')) ?></div>
        <div class="detail-row"><strong>Class:</strong> <?= e($u['class']) ?></div>
        <div class="detail-row"><strong>Subject:</strong> <?= e($u['subject']) ?></div>
        <div class="detail-row"><strong>Marks:</strong> <?= e($u['marks']) ?></div>
        <div class="detail-row"><strong>Aadhaar Document:</strong>
            <a href="uploads/<?= e($u['aadhaar_file']) ?>" target="_blank">Open PDF</a></div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
