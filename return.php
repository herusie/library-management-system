<?php
declare(strict_types=1);
require 'config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'return';
    $loan_id = (int) ($_POST['loan_id'] ?? 0);
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            'SELECT book_id, extensions, (due_date < CURDATE()) AS overdue
             FROM borrowings WHERE id = ? AND returned_at IS NULL FOR UPDATE'
        );
        $stmt->bind_param('i', $loan_id);
        $stmt->execute();
        $loan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$loan) {
            throw new RuntimeException('That loan was already returned or does not exist.');
        }

        if ($action === 'extend') {
            if ((int) $loan['extensions'] >= MAX_EXTENSIONS) {
                throw new RuntimeException('This loan was already extended. Please return the book.');
            }
            if ((int) $loan['overdue'] === 1) {
                throw new RuntimeException('An overdue loan cannot be extended. Please return the book.');
            }
            $days = LOAN_DAYS;
            $stmt = $conn->prepare('UPDATE borrowings SET due_date = DATE_ADD(due_date, INTERVAL ? DAY), extensions = extensions + 1 WHERE id = ?');
            $stmt->bind_param('ii', $days, $loan_id);
            $stmt->execute();
            $stmt->close();
            $conn->commit();
            $_SESSION['flash'] = ['success', 'Loan extended by ' . LOAN_DAYS . ' days.'];
        } else {
            $book_id = (int) $loan['book_id'];

            $stmt = $conn->prepare('UPDATE borrowings SET returned_at = NOW() WHERE id = ?');
            $stmt->bind_param('i', $loan_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare('UPDATE books SET available_copies = available_copies + 1 WHERE id = ?');
            $stmt->bind_param('i', $book_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $_SESSION['flash'] = ['success', 'Book returned.'];
        }
    } catch (mysqli_sql_exception $ex) {
        $conn->rollback();
        $_SESSION['flash'] = ['danger', 'Could not save the change.'];
    } catch (RuntimeException $ex) {
        $conn->rollback();
        $_SESSION['flash'] = ['danger', $ex->getMessage()];
    }
    header('Location: return.php');
    exit;
}

$page_title = 'Return a book';
$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

$stmt = $conn->prepare(
    'SELECT br.id, br.borrowed_at, br.due_date, br.extensions, (br.due_date < CURDATE()) AS overdue,
            b.title, s.student_number, s.first_name, s.last_name
     FROM borrowings br
     JOIN books b ON b.id = br.book_id
     JOIN students s ON s.id = br.student_id
     WHERE br.returned_at IS NULL AND (s.student_number LIKE ? OR b.title LIKE ?)
     ORDER BY br.due_date'
);
$stmt->bind_param('ss', $like, $like);
$stmt->execute();
$loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require 'header.php';
?>
<h3 class="mb-3">Return a book</h3>
<form class="row g-2 mb-3" method="get">
    <div class="col"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search by student number or book title"></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Search</button></div>
</form>

<div class="table-responsive">
<table class="table table-striped align-middle bg-white">
    <thead><tr><th>Student</th><th>Book</th><th>Borrowed</th><th>Due</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($loans as $l): ?>
        <?php $can_extend = (int) $l['extensions'] < MAX_EXTENSIONS && (int) $l['overdue'] === 0; ?>
        <tr>
            <td><?= e($l['student_number']) ?> - <?= e($l['first_name'] . ' ' . $l['last_name']) ?></td>
            <td><?= e($l['title']) ?></td>
            <td><?= e(substr($l['borrowed_at'], 0, 10)) ?></td>
            <td><?= e($l['due_date']) ?></td>
            <td>
                <?= (int) $l['overdue'] === 1 ? '<span class="badge text-bg-danger">Overdue</span>' : '<span class="badge text-bg-secondary">On loan</span>' ?>
                <?php if ((int) $l['extensions'] > 0): ?><span class="badge text-bg-info">Extended</span><?php endif; ?>
            </td>
            <td class="text-end">
                <form method="post" class="d-inline">
                    <input type="hidden" name="loan_id" value="<?= (int) $l['id'] ?>">
                    <input type="hidden" name="action" value="extend">
                    <button class="btn btn-sm btn-outline-primary" <?= $can_extend ? '' : 'disabled' ?>>Extend <?= (int) LOAN_DAYS ?> days</button>
                </form>
                <form method="post" class="d-inline">
                    <input type="hidden" name="loan_id" value="<?= (int) $l['id'] ?>">
                    <input type="hidden" name="action" value="return">
                    <button class="btn btn-sm btn-success">Mark returned</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$loans): ?>
        <tr><td colspan="6" class="text-center text-muted">No open loans found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
<?php require 'footer.php'; ?>