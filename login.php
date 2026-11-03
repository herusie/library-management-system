<?php
declare(strict_types=1);
require 'config.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $statement = $conn->prepare('SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1');
    $statement->bind_param('s', $username);
    $statement->execute();
    $user = $statement->get_result()->fetch_assoc();
    $statement->close();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: dashboard.php');
        exit;
    }

    $error = 'Invalid username or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Library Management System - Login</title>
</head>
<body>
    <main>
        <h1>Library Management System</h1>
        <h2>Librarian login</h2>
        <?php if ($error): ?>
            <p role="alert"><?= e($error) ?></p>
        <?php endif; ?>
        <form method="post">
            <p>
                <label for="username">Username</label><br>
                <input id="username" name="username" autocomplete="username" required autofocus>
            </p>
            <p>
                <label for="password">Password</label><br>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </p>
            <button type="submit">Log in</button>
        </form>
    </main>
</body>
</html>
