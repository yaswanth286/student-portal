<?php
/**
 * forgot_password.php - step 1 of the password reset.
 * The user enters an email; we create a one-hour token and build a reset link.
 * A real site would EMAIL this link. Because a local PC has no mail server,
 * we show the link on screen so the flow can be tested (see README).
 */
require_once __DIR__ . '/includes/auth.php';

$resetLink = '';
$message   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $stmt  = db()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        db()->prepare('UPDATE users SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?')
            ->execute([$token, $user['id']]);
        $resetLink = 'reset_password.php?token=' . $token;
    }
    $message = 'If that email is registered, a reset link has been generated.';
}

$pageTitle = 'Forgot Password';
require __DIR__ . '/includes/header.php';
?>
<div class="card auth-card shadow-sm">
    <div class="card-header card-header-blue">Forgot Password</div>
    <div class="card-body">
        <?php if ($message): ?>
            <div class="alert alert-info"><?= e($message) ?></div>
            <?php if ($resetLink): ?>
                <p class="small text-muted mb-1">Demo mode (no mail server) - click the link below:</p>
                <a href="<?= e($resetLink) ?>" class="btn btn-outline-primary w-100">Reset my password</a>
            <?php endif; ?>
        <?php endif; ?>
        <form method="post" class="mt-3">
            <label class="form-label">Registered Email</label>
            <input type="email" name="email" class="form-control" placeholder="Email Address (email)" required>
            <button class="btn btn-primary w-100 mt-3">Get Reset Link</button>
        </form>
        <a href="index.php" class="small d-block mt-3">Back to login</a>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
