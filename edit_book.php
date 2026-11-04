<?php
declare(strict_types=1);
require 'config.php';
require 'upload.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM books WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    $_SESSION['flash'] = ['danger', 'Book not found.'];
    header('Location: books.php');
    exit;
}

$page_title = 'Edit book';
$error = '';
$f = [
    'title' => $book['title'],
    'author' => $book['author'],
    'isbn' => $book['isbn'] ?? '',
    'category' => $book['category'] ?? '',
    'total_copies' => (string) $book['total_copies'],
];
$borrowed = (int) $book['total_copies'] - (int) $book['available_copies'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($f as $k => $_) {
        $f[$k] = trim($_POST[$k] ?? '');
    }
    $total = (int) $f['total_copies'];

    if ($f['title'] === '' || $f['author'] === '') {
        $error = 'Title and author are required.';
    } elseif ($total < max(1, $borrowed)) {
        $error = "Total copies cannot be lower than the $borrowed copies currently borrowed.";
    } else {
        try {
            $cover = save_cover($_FILES['cover'] ?? []) ?? $book['cover_image'];
            $available = $total - $borrowed;
            $isbn = $f['isbn'] === '' ? null : $f['isbn'];
            $category = $f['category'] === '' ? null : $f['category'];

            $stmt = $conn->prepare(
                'UPDATE books SET title = ?, author = ?, isbn = ?, category = ?,
                 total_copies = ?, available_copies = ?, cover_image = ? WHERE id = ?'
            );
            $stmt->bind_param('ssssiisi', $f['title'], $f['author'], $isbn, $category, $total, $available, $cover, $id);
            $stmt->execute();
            $stmt->close();

            $_SESSION['flash'] = ['success', 'Book updated.'];
            header('Location: books.php');
            exit;
        } catch (mysqli_sql_exception $ex) {
            $error = $ex->getCode() === 1062 ? 'A book with that ISBN already exists.' : 'Could not update the book.';
        } catch (RuntimeException $ex) {
            $error = $ex->getMessage();
        }
    }
}

require 'header.php';
?>
<h3 class="mb-3">Edit book</h3>
<?php require 'book_form.php'; ?>
<?php require 'footer.php'; ?>