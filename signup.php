<?php
/** signup.php - Student registration (Create). */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
redirect_if_logged_in();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // 1. Validate everything (includes the duplicate-email check)
    $errors = validate_student($_POST, $_FILES['aadhaar'] ?? ['error' => UPLOAD_ERR_NO_FILE], ['is_new' => true]);

    // 2. Only save if there are no errors
    if (!$errors) {
        try {
            $fileName = save_aadhaar($_FILES['aadhaar']);   // random name
            $sql = 'INSERT INTO users (role, name, email, password, dob, gender, qualification, interests, class, subject, marks, aadhaar_file)
                    VALUES (\'student\', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            db()->prepare($sql)->execute([
                trim($_POST['name']),
                trim($_POST['email']),
                password_hash($_POST['password'], PASSWORD_DEFAULT),
                $_POST['dob'],
                $_POST['gender'],
                $_POST['qualification'],
                interests_to_string($_POST),
                trim($_POST['class']),
                trim($_POST['subject']),
                (int)$_POST['marks'],
                $fileName,
            ]);
            flash('success', 'Registration successful! You can login now.');
            redirect('index.php');
        } catch (PDOException $ex) {
            delete_aadhaar($fileName ?? null);
            // 23000 = duplicate key (two people registering the same email at the same moment)
            $errors[] = $ex->getCode() === '23000' ? 'This email is already registered.' : 'Database error. Please try again.';
        }
    }
}

$pageTitle = 'Student Signup';
require __DIR__ . '/includes/header.php';
?>
<div class="card form-card shadow-sm">
    <div class="card-header card-header-blue">Student Signup Form</div>
    <div class="card-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger mb-3">
                <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <?php student_form_fields($_POST, ['show_credentials' => true, 'file_required' => true]); ?>
            <button class="btn btn-primary w-100 mt-4">Register</button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
