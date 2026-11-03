<?php
declare(strict_types=1);
require 'config.php';
require_login();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Library dashboard</title>
</head>
<body>
    <header>
        <h1>Library dashboard</h1>
        <p>Welcome, <?= e($_SESSION['username'] ?? 'Librarian') ?>.</p>
        <p><a href="logout.php">Log out</a></p>
    </header>
    <main>
        <p>The catalog, borrowing, return, and overdue pages will be added next.</p>
    </main>
</body>
</html>
