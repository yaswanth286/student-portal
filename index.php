<?php
/** index.php - the single login page for admins AND students. */
require_once __DIR__ . '/includes/auth.php';
redirect_if_logged_in();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);               // protects against session fixation
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['name']    = $user['name'];

        // Role decides where the user lands
        redirect($user['role'] === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php');
    }
    $error = 'Invalid email or password.';
}

$pageTitle = 'Unified Login';
require __DIR__ . '/includes/header.php';
?>
<div class="card auth-card shadow-sm">
    <div class="card-header card-header-blue">Account Login</div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Email Address (email)" value="<?= e($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="mb-2">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Password (password)" required>
            </div>
            <a href="forgot_password.php" class="small">Forgot Password?</a>
            <button class="btn btn-primary w-100 mt-3">Login</button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
