<?php
declare(strict_types=1);
require 'config.php';
require_login();

$page_title = 'Borrow a book';
$error = '';
$f = [
    'student_number' => '', 'first_name' => '', 'last_name' => '',
    'book_id' => '',
    'borrowed_date' => date('Y-m-d'),
    'due_date' => date('Y-m-d', strtotime('+' . LOAN_DAYS . ' days')),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($f as $k => $_) {
        $f[$k] = trim($_POST[$k] ?? '');
    }
    $book_id = (int) $f['book_id'];
    $borrowed = DateTime::createFromFormat('Y-m-d', $f['borrowed_date']);
    $due = DateTime::createFromFormat('Y-m-d', $f['due_date']);

    if ($f['student_number'] === '' || $f['first_name'] === '' || $f['last_name'] === '') {
        $error = 'Enter the student number, first name and last name.';
    } elseif ($book_id < 1) {
        $error = 'Select a book.';
    } elseif (!$borrowed || $f['borrowed_date'] > date('Y-m-d')) {
        $error = 'Choose a borrowed date that is today or earlier.';
    } elseif (!$due || $f['due_date'] < $f['borrowed_date']) {
        $error = 'The due date cannot be before the borrowed date.';
    } else {
        $borrowed_at = $f['borrowed_date'] . ' ' . date('H:i:s');
        $conn->begin_transaction();
        try {
            // Find the student by number; save them if they are new
            $stmt = $conn->prepare('SELECT id FROM students WHERE student_number = ?');
            $stmt->bind_param('s', $f['student_number']);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($student) {
                $student_id = (int) $student['id'];
            } else {
                $stmt = $conn->prepare('INSERT INTO students (student_number, first_name, last_name) VALUES (?, ?, ?)');
                $stmt->bind_param('sss', $f['student_number'], $f['first_name'], $f['last_name']);
                $stmt->execute();
                $student_id = (int) $conn->insert_id;
                $stmt->close();
            }

            // A student can hold at most MAX_ACTIVE_LOANS books at once
            $stmt = $conn->prepare('SELECT COUNT(*) AS open_loans FROM borrowings WHERE student_id = ? AND returned_at IS NULL');
            $stmt->bind_param('i', $student_id);
            $stmt->execute();
            $open = (int) $stmt->get_result()->fetch_assoc()['open_loans'];
            $stmt->close();
            if ($open >= MAX_ACTIVE_LOANS) {
                throw new RuntimeException('This student already has ' . MAX_ACTIVE_LOANS . ' books on loan. Return at least one before borrowing again.');
            }

            // Lock the book row and check copies
            $stmt = $conn->prepare('SELECT title, available_copies FROM books WHERE id = ? FOR UPDATE');
            $stmt->bind_param('i', $book_id);
            $stmt->execute();
            $book = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$book) {
                throw new RuntimeException('Book not found.');
            }
            if ((int) $book['available_copies'] < 1) {
                throw new RuntimeException('No copies of "' . $book['title'] . '" are available right now.');
            }

            $stmt = $conn->prepare('INSERT INTO borrowings (book_id, student_id, borrowed_at, due_date) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('iiss', $book_id, $student_id, $borrowed_at, $f['due_date']);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare('UPDATE books SET available_copies = available_copies - 1 WHERE id = ?');
            $stmt->bind_param('i', $book_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $_SESSION['flash'] = ['success', 'Recorded. Borrowed on ' . $f['borrowed_date'] . ', due on ' . $f['due_date'] . '.'];
            header('Location: borrow.php');
            exit;
        } catch (mysqli_sql_exception $ex) {
            $conn->rollback();
            $error = 'Could not save the record.';
        } catch (RuntimeException $ex) {
            $conn->rollback();
            $error = $ex->getMessage();
        }
    }
}

$books = $conn->query('SELECT id, title, author, available_copies FROM books ORDER BY title')->fetch_all(MYSQLI_ASSOC);

require 'header.php';
?>
<h3 class="mb-3">Borrow a book</h3>
<form method="post" class="card card-body shadow-sm" style="max-width: 640px;">
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

    <label class="form-label">Student number</label>
    <input class="form-control" id="student_number" name="student_number" value="<?= e($f['student_number']) ?>" autocomplete="off" required>
    <div class="form-text mb-3" id="student_note">&nbsp;</div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">First name</label>
            <input class="form-control" id="first_name" name="first_name" value="<?= e($f['first_name']) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Last name</label>
            <input class="form-control" id="last_name" name="last_name" value="<?= e($f['last_name']) ?>" required>
        </div>
    </div>

    <label class="form-label">Book</label>
    <input class="form-control" id="book_search" placeholder="Type to search by title or author" autocomplete="off" required>
    <input type="hidden" id="book_id" name="book_id" value="<?= e($f['book_id']) ?>">
    <div class="list-group mb-3" id="book_results" aria-live="polite"></div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Date borrowed</label>
            <input class="form-control" type="date" name="borrowed_date" max="<?= e(date('Y-m-d')) ?>" value="<?= e($f['borrowed_date']) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Due date</label>
            <input class="form-control" type="date" name="due_date" value="<?= e($f['due_date']) ?>" required>
        </div>
    </div>

    <button class="btn btn-primary">Record</button>
</form>

<script>
// Auto-fill the student's name from the student number
const num = document.getElementById('student_number');
const first = document.getElementById('first_name');
const last = document.getElementById('last_name');
const note = document.getElementById('student_note');
let timer;

async function lookup() {
    const v = num.value.trim();
    if (v === '') {
        if (first.readOnly) { first.value = ''; last.value = ''; }
        first.readOnly = last.readOnly = false;
        note.innerHTML = '&nbsp;';
        return;
    }
    try {
        const res = await fetch('lookup_student.php?student_number=' + encodeURIComponent(v));
        const d = await res.json();
        if (d.found) {
            first.value = d.first_name;
            last.value = d.last_name;
            first.readOnly = last.readOnly = true;
            note.textContent = 'Existing student: name filled in.';
        } else {
            if (first.readOnly) { first.value = ''; last.value = ''; }
            first.readOnly = last.readOnly = false;
            note.textContent = 'New student: enter the name.';
        }
    } catch (err) {
        note.textContent = '';
    }
}
num.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(lookup, 300); });
if (num.value.trim() !== '') lookup();

// Show matching books and select one when it is clicked.
const search = document.getElementById('book_search');
const bookId = document.getElementById('book_id');
const results = document.getElementById('book_results');
const books = <?= json_encode($books, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

function renderBookResults() {
    const q = search.value.toLowerCase();
    results.replaceChildren();
    bookId.value = '';
    if (q === '') return;

    const matches = books.filter((book) =>
        `${book.title} ${book.author}`.toLowerCase().includes(q)
    );
    for (const book of matches) {
        const option = document.createElement('button');
        option.type = 'button';
        option.className = 'list-group-item list-group-item-action';
        option.textContent = `${book.title} - ${book.author} (${book.available_copies} available)`;
        option.addEventListener('click', () => {
            bookId.value = book.id;
            search.value = `${book.title} - ${book.author}`;
            results.replaceChildren();
        });
        results.append(option);
    }
    if (matches.length === 0) {
        results.textContent = 'No matching books found.';
    }
}
search.addEventListener('input', renderBookResults);
</script>
<?php require 'footer.php'; ?>
