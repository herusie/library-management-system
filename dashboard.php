<?php
declare(strict_types=1);
require 'config.php';
require_login();

$page_title = 'Dashboard';

$totals = $conn->query(
    'SELECT
        (SELECT COUNT(*) FROM books) AS titles,
        (SELECT COUNT(*) FROM students) AS students,
        (SELECT COUNT(*) FROM borrowings WHERE returned_at IS NULL) AS active,
        (SELECT COUNT(*) FROM borrowings WHERE returned_at IS NULL AND due_date < CURDATE()) AS overdue'
)->fetch_assoc();

$overdue = $conn->query(
    'SELECT b.title, s.student_number, s.first_name, s.last_name, br.due_date,
            DATEDIFF(CURDATE(), br.due_date) AS days_late
     FROM borrowings br
     JOIN books b ON b.id = br.book_id
     JOIN students s ON s.id = br.student_id
     WHERE br.returned_at IS NULL AND br.due_date < CURDATE()
     ORDER BY br.due_date'
)->fetch_all(MYSQLI_ASSOC);

require 'header.php';
?>
<h3 class="mb-1">Dashboard</h3>
<p class="text-muted">Welcome, <?= e($_SESSION['username'] ?? 'Librarian') ?>.</p>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="card card-body text-center"><div class="fs-2"><?= e($totals['titles']) ?></div>Book titles</div></div>
    <div class="col-6 col-md-3"><div class="card card-body text-center"><div class="fs-2"><?= e($totals['students']) ?></div>Borrowers</div></div>
    <div class="col-6 col-md-3"><div class="card card-body text-center"><div class="fs-2"><?= e($totals['active']) ?></div>Books on loan</div></div>
    <div class="col-6 col-md-3"><div class="card card-body text-center border-danger"><div class="fs-2 text-danger"><?= e($totals['overdue']) ?></div>Overdue</div></div>
</div>

<h5>Overdue loans</h5>
<div class="table-responsive">
<table class="table table-striped bg-white">
    <thead><tr><th>Student</th><th>Book</th><th>Due</th><th>Days late</th></tr></thead>
    <tbody>
    <?php foreach ($overdue as $o): ?>
        <tr>
            <td><?= e($o['student_number']) ?> - <?= e($o['first_name'] . ' ' . $o['last_name']) ?></td>
            <td><?= e($o['title']) ?></td>
            <td><?= e($o['due_date']) ?></td>
            <td><span class="badge text-bg-danger"><?= e($o['days_late']) ?></span></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$overdue): ?>
        <tr><td colspan="4" class="text-center text-muted">No overdue loans.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
<?php require 'footer.php'; ?>