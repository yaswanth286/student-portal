<?php /* Common top part of every page. Set $pageTitle before including. */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Student Portal') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand navbar-light bg-light-blue px-3">
    <span class="navbar-brand fw-semibold"><?= e($pageTitle ?? 'Student Portal') ?></span>
    <ul class="navbar-nav me-auto">
        <?php if (empty($_SESSION['user_id'])): ?>
            <li class="nav-item"><a class="nav-link" href="index.php">Login</a></li>
            <li class="nav-item"><a class="nav-link" href="signup.php">Signup</a></li>
        <?php else: ?>
            <li class="nav-item">
                <a class="nav-link" href="<?= $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php' ?>">Home</a>
            </li>
        <?php endif; ?>
    </ul>
    <?php if (!empty($_SESSION['user_id'])): ?>
        <a class="nav-link" href="logout.php">Logout</a>
    <?php endif; ?>
</nav>
<main class="container py-4">
<?php show_flash(); ?>
