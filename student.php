<?php
declare(strict_types=1);
require 'config.php';
require_login();

$number = trim($_GET['number'] ?? '');
$stmt = $conn->prepare('SELECT id, student_number, first_name, last_name FROM students WHERE student_number = ?');
$stmt->bind_param('s', $number);
$stmt->execute();
$s = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$s) {
    $_SESSION['flash'] = ['danger', 'Student not found.'];
    header('Location: borrow.php');
    exit;
}

$stmt = $conn->prepare(
    'SELECT b.title, br.borrowed_at, br.due_date, br.returned_at, br.extensions,
            DATEDIFF(br.due_date, CURDATE()) AS days_left
     FROM borrowings br JOIN books b ON b.id = br.book_id
     WHERE br.student_id = ?
     ORDER BY br.borrowed_at DESC, br.id DESC'
);
$stmt->bind_param('i', $s['id']);
$stmt->execute();
$loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = 'Student loans';
require 'header.php';
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h3 class="mb-0"><?= e($s['first_name'] . ' ' . $s['last_name']) ?></h3>
        <div class="text-muted"><?= e($s['student_number']) ?> &middot; <?= count($loans) ?> loan<?= count($loans) === 1 ? '' : 's' ?> on record</div>
    </div>
    <a class="btn btn-outline-primary" href="borrow.php">Back to Borrow</a>
</div>

<div class="table-responsive">
<table class="table table-striped align-middle bg-white">
    <thead><tr><th>Book</th><th>Borrowed</th><th>Due</th><th>Returned</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($loans as $l): ?>
        <tr>
            <td><?= e($l['title']) ?></td>
            <td><?= e(substr((string) $l['borrowed_at'], 0, 10)) ?></td>
            <td><?= e($l['due_date']) ?><?= (int) $l['extensions'] > 0 ? ' <span class="badge text-bg-info">Extended</span>' : '' ?></td>
            <td><?= $l['returned_at'] ? e(substr((string) $l['returned_at'], 0, 10)) : '-' ?></td>
            <td>
                <?php if ($l['returned_at']): ?><span class="badge text-bg-success">Returned</span>
                <?php elseif ((int) $l['days_left'] < 0): ?><span class="badge text-bg-danger">Overdue <?= abs((int) $l['days_left']) ?>d</span>
                <?php else: ?><span class="badge text-bg-secondary">On loan</span><?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$loans): ?><tr><td colspan="5" class="text-center text-muted">No loans yet.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
<?php require 'footer.php'; ?>