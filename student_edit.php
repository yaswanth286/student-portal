<?php
/** student_edit.php - student updates own profile (Update). Email is locked. */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role('student');

$id   = (int)$_SESSION['user_id'];
$stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) { redirect('logout.php'); }

$errors = [];
$form   = $user;     // values shown in the form

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $_POST['email'] = $user['email'];                 // ignore any tampering: email can never change
    $form = array_merge($user, $_POST);
    $file = $_FILES['aadhaar'] ?? ['error' => UPLOAD_ERR_NO_FILE];

    $errors = validate_student($_POST, $file);
    if (!$errors) {
        $fileName = $user['aadhaar_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {        // a new PDF was chosen -> replace old one
            $fileName = save_aadhaar($file);
            delete_aadhaar($user['aadhaar_file']);
        }
        $sql = 'UPDATE users SET name=?, dob=?, gender=?, qualification=?, interests=?, class=?, subject=?, marks=?, aadhaar_file=? WHERE id=?';
        db()->prepare($sql)->execute([
            trim($_POST['name']), $_POST['dob'], $_POST['gender'], $_POST['qualification'],
            interests_to_string($_POST), trim($_POST['class']), trim($_POST['subject']),
            (int)$_POST['marks'], $fileName, $id,
        ]);
        $_SESSION['name'] = trim($_POST['name']);
        flash('success', 'Profile updated successfully.');
        redirect('student_dashboard.php');
    }
    $form['interests'] = interests_to_string($_POST);   // so ticked boxes stay ticked
}

$pageTitle = 'Edit Profile';
require __DIR__ . '/includes/header.php';
?>
<div class="card form-card shadow-sm">
    <div class="card-header card-header-blue">Edit Profile</div>
    <div class="card-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <?php student_form_fields($form, ['lock_email' => true]); ?>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary flex-fill">Save Changes</button>
                <a href="student_dashboard.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
