<?php
declare(strict_types=1);
require 'config.php';
require_login();

$page_title = 'Profile';
$error = '';
$user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare('SELECT username, password_hash, created_at FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password_hash'])) {
        $error = 'Your current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $error = 'The new password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $error = 'The new password and the confirmation do not match.';
    } elseif ($new === $current) {
        $error = 'Choose a password that is different from the current one.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->bind_param('si', $hash, $user_id);
        $stmt->execute();
        $stmt->close();

        session_regenerate_id(true);
        $_SESSION['flash'] = ['success', 'Password changed.'];
        header('Location: profile.php');
        exit;
    }
}

require 'header.php';
?>
<h3 class="mb-3">Profile</h3>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card card-body">
            <h5 class="mb-3">Account</h5>
            <dl class="row mb-0">
                <dt class="col-5 text-muted fw-normal">Username</dt>
                <dd class="col-7"><?= e($user['username']) ?></dd>
                <dt class="col-5 text-muted fw-normal">Role</dt>
                <dd class="col-7">Librarian</dd>
                <dt class="col-5 text-muted fw-normal">Account created</dt>
                <dd class="col-7 mb-0"><?= e(substr($user['created_at'], 0, 10)) ?></dd>
            </dl>
        </div>
    </div>

    <div class="col-lg-7">
        <form method="post" class="card card-body">
            <h5 class="mb-3">Change password</h5>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

            <label class="form-label">Current password</label>
            <input class="form-control mb-3" type="password" name="current_password" autocomplete="current-password" required>

            <label class="form-label">New password (at least 8 characters)</label>
            <input class="form-control mb-3" type="password" name="new_password" minlength="8" autocomplete="new-password" required>

            <label class="form-label">Confirm new password</label>
            <input class="form-control mb-3" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required>

            <div><button class="btn btn-primary">Change password</button></div>
        </form>
    </div>
</div>
<?php require 'footer.php'; ?>