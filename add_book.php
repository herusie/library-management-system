<?php
declare(strict_types=1);
require 'config.php';
require 'upload.php';
require_login();

$page_title = 'Add book';
$error = '';
$f = ['title' => '', 'author' => '', 'isbn' => '', 'category' => '', 'total_copies' => '1'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($f as $k => $_) {
        $f[$k] = trim($_POST[$k] ?? '');
    }
    $copies = (int) $f['total_copies'];

    if ($f['title'] === '' || $f['author'] === '') {
        $error = 'Title and author are required.';
    } elseif ($f['category'] !== '' && !in_array($f['category'], BOOK_CATEGORIES, true)) {
        $error = 'Please choose a category from the list.';
    } elseif ($copies < 1) {
        $error = 'Copies must be at least 1.';
    } else {
        try {
            $cover = save_cover($_FILES['cover'] ?? []);
            $isbn = $f['isbn'] === '' ? null : $f['isbn'];
            $category = $f['category'] === '' ? null : $f['category'];

            $stmt = $conn->prepare(
                'INSERT INTO books (title, author, isbn, category, total_copies, available_copies, cover_image)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('ssssiis', $f['title'], $f['author'], $isbn, $category, $copies, $copies, $cover);
            $stmt->execute();
            $stmt->close();

            $_SESSION['flash'] = ['success', 'Book added.'];
            header('Location: books.php');
            exit;
        } catch (mysqli_sql_exception $ex) {
            $error = $ex->getCode() === 1062 ? 'A book with that ISBN already exists.' : 'Could not save the book.';
        } catch (RuntimeException $ex) {
            $error = $ex->getMessage();
        }
    }
}

require 'header.php';
?>
<h3 class="mb-3">Add book</h3>
<?php require 'book_form.php'; ?>
<?php require 'footer.php'; ?>