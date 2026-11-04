<?php
declare(strict_types=1);
require 'config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    try {
        $stmt = $conn->prepare('DELETE FROM books WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash'] = ['success', 'Book deleted.'];
    } catch (mysqli_sql_exception $ex) {
        $_SESSION['flash'] = ['danger', $ex->getCode() === 1451
            ? "This book has loan records and can't be deleted."
            : 'Could not delete the book.'];
    }
}
header('Location: books.php');
exit;