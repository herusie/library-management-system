<form method="post" enctype="multipart/form-data" class="card card-body shadow-sm" style="max-width: 640px;">
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

    <label class="form-label">Title</label>
    <input class="form-control mb-3" name="title" value="<?= e($f['title']) ?>" required>

    <label class="form-label">Author</label>
    <input class="form-control mb-3" name="author" value="<?= e($f['author']) ?>" required>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">ISBN (optional)</label>
            <input class="form-control" name="isbn" value="<?= e($f['isbn']) ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Category</label>
            <input class="form-control" name="category" value="<?= e($f['category']) ?>">
        </div>
    </div>

    <label class="form-label">Total copies</label>
    <input class="form-control mb-3" type="number" min="1" name="total_copies" value="<?= e($f['total_copies']) ?>" required>

    <label class="form-label">Cover image (JPG, PNG or WEBP, max 2 MB)</label>
    <?php if (!empty($book['cover_image'])): ?>
        <div class="mb-2"><img src="<?= e(cover_url($book['cover_image'])) ?>" alt="" height="80"></div>
    <?php endif; ?>
    <input class="form-control mb-3" type="file" name="cover" accept="image/jpeg,image/png,image/webp">

    <div>
        <button class="btn btn-primary">Save book</button>
        <a class="btn btn-outline-secondary" href="books.php">Cancel</a>
    </div>
</form>
