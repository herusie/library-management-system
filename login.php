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
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; background: #14213d; overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            font-family: -apple-system, "Segoe UI", Helvetica, Arial, sans-serif;
        }
        #shelves {
            position: fixed; inset: 0; opacity: .35;
            display: flex; flex-direction: column; justify-content: space-around;
        }
          #shelves svg { display: block; }
        .card {
            position: relative; width: 100%; max-width: 390px; margin: 16px;
            background: #1d2d50; border: 1px solid #2e4170; border-radius: 12px;
            padding: 34px; color: #fff; box-shadow: 0 20px 50px rgba(0, 0, 0, .4);
        }
        .card::before {
            content: ""; position: absolute; left: 0; top: 24px; bottom: 24px;
            width: 5px; background: #fca311; border-radius: 0 4px 4px 0;
        }
        .eyebrow { color: #fca311; font-size: 12px; letter-spacing: .2em; font-weight: 700; margin-bottom: 6px; }
        h1 { margin: 0; font: normal 26px Georgia, "Times New Roman", serif; }
        .sub { color: #9fb0d6; margin: 4px 0 22px; }
        label { display: block; font-size: 13px; color: #9fb0d6; margin-bottom: 4px; }
        input {
            width: 100%; background: #14213d; border: 1px solid #2e4170; border-radius: 8px;
            color: #fff; padding: 12px; margin-bottom: 16px; font-size: 15px;
        }
        input:focus { outline: 2px solid #fca311; outline-offset: 1px; }
        button {
            width: 100%; padding: 12px; background: #fca311; color: #14213d; border: 0;
            border-radius: 8px; font-size: 16px; font-weight: 700; cursor: pointer;
        }
        button:hover { background: #ffb327; }
        .error {
            background: #4a1f2a; border: 1px solid #8b3a4a; color: #ffd7dd;
            border-radius: 8px; padding: 10px 12px; margin-bottom: 16px; font-size: 14px;
        }
    </style>
</head>
<body>
    <div id="shelves" aria-hidden="true"></div>

    <main class="card">
        <div class="eyebrow">LIBRARY</div>
        <h1>Management System</h1>
        <div class="sub">Librarian login</div>

        <?php if ($error): ?>
            <div class="error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <label for="username">Username</label>
            <input id="username" name="username" autocomplete="username" required autofocus>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>

            <button type="submit">Log in</button>
        </form>
    </main>

        <script>
    // Draws rows of book spines at a fixed size, so they look the same on any screen
    (function () {
        var colors = ['#1d2d50', '#2e4170', '#243660', '#3a5190', '#fca311'];
        var ROW_HEIGHT = 110;
        var ROW_GAP = 30;

        function draw() {
            var seed = 3;
            function rnd() { seed = (seed * 9301 + 49297) % 233280; return seed / 233280; }

            var w = window.innerWidth;
            var rows = Math.max(3, Math.ceil(window.innerHeight / (ROW_HEIGHT + ROW_GAP)));
            var html = '';

            for (var r = 0; r < rows; r++) {
                var x = 0, out = '';
                while (x < w) {
                    var bw = 14 + Math.floor(rnd() * 22);
                    var bh = ROW_HEIGHT * (0.55 + rnd() * 0.42);
                    var c = colors[Math.floor(rnd() * colors.length)];
                    out += '<rect x="' + x + '" y="' + (ROW_HEIGHT - bh) + '" width="' + (bw - 2) + '" height="' + bh + '" fill="' + c + '" rx="1.5"/>';
                    if (rnd() > 0.4) out += '<rect x="' + x + '" y="' + (ROW_HEIGHT - bh + 10) + '" width="' + (bw - 2) + '" height="3" fill="rgba(255,255,255,.25)"/>';
                    if (rnd() > 0.55) out += '<rect x="' + (x + 3) + '" y="' + (ROW_HEIGHT - bh * 0.45) + '" width="' + (bw - 8) + '" height="10" fill="rgba(255,255,255,.18)"/>';
                    x += bw;
                }
                html += '<svg width="' + w + '" height="' + ROW_HEIGHT + '">' + out + '</svg>';
            }
            document.getElementById('shelves').innerHTML = html;
        }

        var timer;
        window.addEventListener('resize', function () { clearTimeout(timer); timer = setTimeout(draw, 150); });
        draw();
    })();
    </script>
    
</body>
</html>