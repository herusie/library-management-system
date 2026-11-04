<?php
declare(strict_types=1);
require 'config.php';
require_login();
header('Content-Type: application/json');

// ?q=text      -> students whose number starts with it, or whose name contains it (max 8)
// ?number=X    -> one student with their open loans
$number = trim($_GET['number'] ?? '');
if ($number !== '') {
    $stmt = $conn->prepare('SELECT id, student_number, first_name, last_name FROM students WHERE student_number = ?');
    $stmt->bind_param('s', $number);
    $stmt->execute();
    $s = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$s) {
        echo json_encode(['found' => false]);
        exit;
    }
    $stmt = $conn->prepare(
        'SELECT b.title, br.due_date, DATEDIFF(br.due_date, CURDATE()) AS days_left
         FROM borrowings br JOIN books b ON b.id = br.book_id
         WHERE br.student_id = ? AND br.returned_at IS NULL
         ORDER BY br.due_date'
    );
    $stmt->bind_param('i', $s['id']);
    $stmt->execute();
    $loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $overdue = 0;
    foreach ($loans as &$l) {
        $l['days_left'] = (int) $l['days_left'];
        if ($l['days_left'] < 0) {
            $overdue++;
        }
    }
    unset($l);
    echo json_encode([
        'found' => true,
        'student_number' => $s['student_number'],
        'first_name' => $s['first_name'],
        'last_name' => $s['last_name'],
        'max_loans' => MAX_ACTIVE_LOANS,
        'overdue' => $overdue,
        'loans' => $loans,
    ]);
    exit;
}

$q = trim($_GET['q'] ?? '');
$matches = [];
if ($q !== '') {
    $esc = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
    $starts = $esc . '%';
    $has = '%' . $esc . '%';
    $stmt = $conn->prepare(
        "SELECT s.student_number, s.first_name, s.last_name,
                (SELECT COUNT(*) FROM borrowings br WHERE br.student_id = s.id AND br.returned_at IS NULL) AS open_count
         FROM students s
         WHERE s.student_number LIKE ? OR CONCAT(s.first_name, ' ', s.last_name) LIKE ? OR CONCAT(s.last_name, ' ', s.first_name) LIKE ?
         ORDER BY s.student_number LIMIT 8"
    );
    $stmt->bind_param('sss', $starts, $has, $has);
    $stmt->execute();
    $matches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
echo json_encode(['matches' => $matches]);