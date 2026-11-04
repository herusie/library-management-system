<?php
declare(strict_types=1);
require 'config.php';
require_login();

header('Content-Type: application/json');

$number = trim($_GET['student_number'] ?? '');
$stmt = $conn->prepare('SELECT first_name, last_name FROM students WHERE student_number = ?');
$stmt->bind_param('s', $number);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode($row ? ['found' => true] + $row : ['found' => false]);