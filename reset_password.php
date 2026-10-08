<?php
/** reset_password.php - step 2 of the password reset: choose a new password using the token. */
require_once __DIR__ . '/includes/auth.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$stmt  = db()->prepare('SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()');
$stmt->execute([$token]);
$user  = $token !== '' ? $stmt->fetch() : false;

$error = '';
if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['password'] ?? '';
    if (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== ($_POST['confirm'] ?? '')) {
        $error = 'Passwords do not match.';
    } else {
        // Save new password and make the token unusable
        db()->prepare('UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?')
            ->execute([password_hash($pass, PASSWORD_DEFAULT), $user['id']]);
        flash('success', 'Password changed. Please login with your new password.');
        redirect('index.php');
    }
}

$pageTitle = 'Reset Password';
require __DIR__ . '/includes/header.php';
?>
<div class="card auth-card shadow-sm">
    <div class="card-header card-header-blue">Reset Password</div>
    <div class="card-body">
        <?php if (!$user): ?>
            <div class="alert alert-danger">This reset link is invalid or has expired.</div>
            <a href="forgot_password.php">Request a new link</a>
        <?php else: ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="mb-3"><label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm" class="form-control" required></div>
                <button class="btn btn-primary w-100">Change Password</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
