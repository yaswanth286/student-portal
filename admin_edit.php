<?php
/** admin_edit.php - admin updates a student. Name and Email are LOCKED (read-only + ignored on save). */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role('admin');

$id   = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND role = 'student'");
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) {
    flash('danger', 'Student not found.');
    redirect('admin_dashboard.php');
}

$errors = [];
$form   = $user;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    // Force locked fields back to the stored values, even if someone edits the HTML in the browser
    $_POST['name']  = $user['name'];
    $_POST['email'] = $user['email'];
    $form = array_merge($user, $_POST);
    $file = $_FILES['aadhaar'] ?? ['error' => UPLOAD_ERR_NO_FILE];

    $errors = validate_student($_POST, $file, ['check_name' => false]);
    if (!$errors) {
        $fileName = $user['aadhaar_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $fileName = save_aadhaar($file);
            delete_aadhaar($user['aadhaar_file']);
        }
        // Note: name and email are NOT in this query
        $sql = 'UPDATE users SET dob=?, gender=?, qualification=?, interests=?, class=?, subject=?, marks=?, aadhaar_file=? WHERE id=?';
        db()->prepare($sql)->execute([
            $_POST['dob'], $_POST['gender'], $_POST['qualification'], interests_to_string($_POST),
            trim($_POST['class']), trim($_POST['subject']), (int)$_POST['marks'], $fileName, $id,
        ]);
        flash('success', 'Student details updated.');
        redirect('admin_dashboard.php');
    }
    $form['interests'] = interests_to_string($_POST);
}

$pageTitle = 'Edit Student';
require __DIR__ . '/includes/header.php';
?>
<div class="card form-card shadow-sm">
    <div class="card-header card-header-blue">Edit Student <?= e(student_code($id)) ?></div>
    <div class="card-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
        <?php endif; ?>
        <p class="text-muted small">Name and email are locked and cannot be changed.</p>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <?php student_form_fields($form, ['lock_name' => true, 'lock_email' => true]); ?>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary flex-fill">Update Student</button>
                <a href="admin_dashboard.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
