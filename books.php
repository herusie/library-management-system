<?php
declare(strict_types=1);
require 'config.php';
require_login();

$page_title = 'Books';
$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

$stmt = $conn->prepare(
    'SELECT id, title, author, category, total_copies, available_copies, cover_image,
            created_at, updated_at
     FROM books
     WHERE title LIKE ? OR author LIKE ? OR category LIKE ?
     ORDER BY title'
);
$stmt->bind_param('sss', $like, $like, $like);
$stmt->execute();
$books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require 'header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Books</h3>
    <a class="btn btn-primary" href="add_book.php">+ Add book</a>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search by title, author or category"></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Search</button></div>
    <?php if ($q !== ''): ?><div class="col-auto"><a class="btn btn-outline-secondary" href="books.php">Clear</a></div><?php endif; ?>
</form>

<div class="table-responsive">
<table class="table table-striped align-middle bg-white">
    <thead><tr><th>Cover</th><th>Title</th><th>Author</th><th>Category</th><th>Availability</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($books as $b): ?>
        <tr>
            <td><?php if ($b['cover_image']): ?><img src="uploads/<?= e($b['cover_image']) ?>" alt="" height="60"><?php endif; ?></td>
            <td>
                <?= e($b['title']) ?>
                <?php if ($b['updated_at'] !== $b['created_at']): ?>
                    <br><span class="badge text-bg-warning">Edited <?= e(substr($b['updated_at'], 0, 16)) ?></span>
                <?php endif; ?>
            </td>
            <td><?= e($b['author']) ?></td>
            <td><?= e($b['category'] ?? '') ?></td>
            <td>
                <?php if ($b['available_copies'] > 0): ?>
                    <span class="badge text-bg-success"><?= e($b['available_copies']) ?> of <?= e($b['total_copies']) ?> available</span>
                <?php else: ?>
                    <span class="badge text-bg-danger">Unavailable</span>
                <?php endif; ?>
            </td>
            <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="edit_book.php?id=<?= (int) $b['id'] ?>">Edit</a>
                <form method="post" action="delete_book.php" class="d-inline" onsubmit="return confirm('Delete this book?');">
                    <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$books): ?>
        <tr><td colspan="6" class="text-center text-muted">No books found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
<?php require 'footer.php'; ?>