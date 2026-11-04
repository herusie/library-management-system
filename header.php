<?php
// Shared top of every page. Set $page_title before including.
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title ?? 'Library') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Library</a>
        <div class="navbar-nav me-auto">
            <a class="nav-link" href="dashboard.php">Dashboard</a>
            <a class="nav-link" href="books.php">Books</a>
            <a class="nav-link" href="borrow.php">Borrow</a>
            <a class="nav-link" href="return.php">Return</a>
        </div>
        <a class="btn btn-light btn-sm" href="logout.php">Log out</a>
    </div>
</nav>
<div class="container pb-5">
<?php if (!empty($_SESSION['flash'])): [$flash_type, $flash_msg] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="alert alert-<?= e($flash_type) ?>"><?= e($flash_msg) ?></div>
<?php endif; ?>