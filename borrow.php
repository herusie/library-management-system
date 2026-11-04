<?php
declare(strict_types=1);
require 'config.php';
require_login();

$page_title = 'Borrow a book';
$error = '';
$today = date('Y-m-d');
$f = [
    'student_number' => '', 'first_name' => '', 'last_name' => '', 'book_id' => '', 'book_title' => '',
    'borrowed_date' => $today,
    'due_date' => date('Y-m-d', strtotime('+' . LOAN_DAYS . ' days')),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($f as $k => $_) {
        $f[$k] = trim($_POST[$k] ?? '');
    }
    $book_id = (int) $f['book_id'];
    $b_ok = DateTime::createFromFormat('Y-m-d', $f['borrowed_date']);
    $d_ok = DateTime::createFromFormat('Y-m-d', $f['due_date']);

    if ($f['student_number'] === '' || $f['first_name'] === '' || $f['last_name'] === '') {
        $error = 'Enter the student number, first name and last name.';
    } elseif (!preg_match('/^[A-Za-z0-9-]{3,30}$/', $f['student_number'])) {
        $error = 'Enter a valid student number (letters, numbers and dashes only). To search by name, pick the student from the list.';
    } elseif ($book_id < 1) {
        $error = 'Select a book.';
    } elseif (!$b_ok || $f['borrowed_date'] > $today) {
        $error = 'Date borrowed cannot be in the future.';
    } elseif (!$d_ok || $f['due_date'] < $f['borrowed_date']) {
        $error = 'Due date cannot be before the date borrowed.';
    } else {
        $conn->begin_transaction();
        try {
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

            // Loan limit
            $stmt = $conn->prepare('SELECT COUNT(*) AS n FROM borrowings WHERE student_id = ? AND returned_at IS NULL');
            $stmt->bind_param('i', $student_id);
            $stmt->execute();
            $open = (int) $stmt->get_result()->fetch_assoc()['n'];
            $stmt->close();
            if ($open >= MAX_ACTIVE_LOANS) {
                throw new RuntimeException('This student already has ' . MAX_ACTIVE_LOANS . ' books out. A book must be returned first.');
            }

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

            $borrowed_at = $f['borrowed_date'] . ' ' . date('H:i:s');
            $stmt = $conn->prepare('INSERT INTO borrowings (book_id, student_id, borrowed_at, due_date) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('iiss', $book_id, $student_id, $borrowed_at, $f['due_date']);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare('UPDATE books SET available_copies = available_copies - 1 WHERE id = ?');
            $stmt->bind_param('i', $book_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $_SESSION['flash'] = ['success', 'Recorded. Due on ' . $f['due_date'] . '.'];
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
<div class="row g-3">
<div class="col-lg-7">
<form method="post" class="card card-body shadow-sm" autocomplete="off">
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

    <label class="form-label">Student number or name</label>
    <div class="position-relative mb-1">
        <input class="form-control" id="sn" name="student_number" autocomplete="off" value="<?= e($f['student_number']) ?>" required>
        <div id="sn-list" class="list-group position-absolute w-100 shadow" style="z-index:20; display:none;"></div>
    </div>
    <div class="form-text mb-3" id="sn-hint">Type a student number or a name. Pick from the list, or enter a new student number.</div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">First name</label>
            <input class="form-control" id="fn" name="first_name" value="<?= e($f['first_name']) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Last name</label>
            <input class="form-control" id="ln" name="last_name" value="<?= e($f['last_name']) ?>" required>
        </div>
    </div>

    <label class="form-label">Book</label>
    <input class="form-control mb-2" id="bs" name="book_title" autocomplete="off" placeholder="Type to search by title or author" value="<?= e($f['book_title']) ?>">
    <input type="hidden" name="book_id" id="book_id" value="<?= e($f['book_id']) ?>">
    <div id="book-list" class="list-group mb-1" style="max-height: 190px; overflow-y: auto; display:none;">
        <?php foreach ($books as $b): $none = (int) $b['available_copies'] < 1; ?>
            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between book-item"
                    data-id="<?= (int) $b['id'] ?>" data-title="<?= e($b['title']) ?>"
                    data-text="<?= e(strtolower($b['title'] . ' ' . $b['author'])) ?>"
                    <?= $none ? 'disabled' : '' ?>>
                <span><?= e($b['title']) ?> <small class="text-muted">&middot; <?= e($b['author']) ?></small></span>
                <small><?= $none ? 'none left' : e($b['available_copies']) . ' available' ?></small>
            </button>
        <?php endforeach; ?>
        <?php if (!$books): ?><div class="list-group-item text-muted">No books yet.</div><?php endif; ?>
    </div>
    <div class="form-text mb-3" id="book-hint">Type a title or author, then click the book.</div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Date borrowed</label>
            <input class="form-control" type="date" id="bd" name="borrowed_date" max="<?= e($today) ?>" value="<?= e($f['borrowed_date']) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Due date</label>
            <input class="form-control" type="date" id="dd" name="due_date" value="<?= e($f['due_date']) ?>" required>
        </div>
    </div>

    <button class="btn btn-primary">Record</button>
</form>
</div>

<div class="col-lg-5">
    <div class="card card-body shadow-sm" id="student-card" style="display:none;">
        <h5 id="sc-name" class="mb-3"></h5>
        <div class="alert alert-danger py-2 fw-semibold" id="sc-warn" style="display:none;"></div>
        <div class="text-muted" id="sc-count"></div>
        <div class="d-flex gap-2 my-2" id="sc-meter"></div>
        <div id="sc-loans"></div>
        <a class="fw-semibold mt-2" id="sc-link" href="#" style="color:#b36b00;">View all loans &rarr;</a>
    </div>
</div>
</div>

<script>
const LOAN_DAYS = <?= (int) LOAN_DAYS ?>;
const sn = document.getElementById('sn'), fn = document.getElementById('fn'), ln = document.getElementById('ln');
const list = document.getElementById('sn-list'), hint = document.getElementById('sn-hint');
const card = document.getElementById('student-card');
let timer = null;

function lockNames(first, last) { fn.value = first; ln.value = last; fn.readOnly = ln.readOnly = true; hint.textContent = 'Student on record.'; }
function unlockNames() { fn.readOnly = ln.readOnly = false; }
function el(tag, cls, text) { const x = document.createElement(tag); if (cls) x.className = cls; if (text !== undefined) x.textContent = text; return x; }

async function showStudent(number) {
    try {
        const d = await (await fetch('lookup_student.php?number=' + encodeURIComponent(number))).json();
        if (!d.found) { card.style.display = 'none'; return; }
        lockNames(d.first_name, d.last_name);
        document.getElementById('sc-name').textContent = d.first_name + ' ' + d.last_name;
        const warn = document.getElementById('sc-warn');
        warn.style.display = d.overdue ? 'block' : 'none';
        warn.textContent = d.overdue + (d.overdue === 1 ? ' overdue book. ' : ' overdue books. ') + 'Return it first if possible.';
        const n = d.loans.length;
        const c = document.getElementById('sc-count'); c.textContent = '';
        c.append('Holding '); c.appendChild(el('b', '', n + ' of ' + d.max_loans)); c.append(' books');
        const meter = document.getElementById('sc-meter'); meter.textContent = '';
        for (let i = 0; i < d.max_loans; i++) {
            const bar = el('div'); bar.style.cssText = 'flex:1;height:8px;border-radius:4px;background:' + (i < n ? '#fca311' : '#e6e2d4');
            meter.appendChild(bar);
        }
        const box = document.getElementById('sc-loans'); box.textContent = '';
        d.loans.forEach(l => {
            const row = el('div', 'd-flex justify-content-between align-items-start py-2 border-bottom');
            const left = el('div'); left.appendChild(el('div', '', l.title)); left.appendChild(el('small', 'text-muted', 'Due ' + l.due_date));
            const late = l.days_left < 0;
            row.appendChild(left); row.appendChild(el('span', 'badge ' + (late ? 'text-bg-danger' : 'text-bg-success'), late ? 'Overdue ' + Math.abs(l.days_left) + 'd' : 'On time'));
            box.appendChild(row);
        });
        if (!n) box.appendChild(el('div', 'text-muted py-2', 'No books out right now.'));
        document.getElementById('sc-link').href = 'student.php?number=' + encodeURIComponent(d.student_number);
        card.style.display = 'block';
    } catch (e) { /* ignore */ }
}

sn.addEventListener('input', () => {
    unlockNames(); card.style.display = 'none';
    clearTimeout(timer);
    const q = sn.value.trim();
    if (!q) { list.style.display = 'none'; hint.textContent = 'Type a student number or a name. Pick from the list, or enter a new student number.'; return; }
    timer = setTimeout(async () => {
        try {
            const data = await (await fetch('lookup_student.php?q=' + encodeURIComponent(q))).json();
            list.innerHTML = '';
            const exact = data.matches.find(m => m.student_number === q);
            if (exact) { list.style.display = 'none'; showStudent(exact.student_number); return; }
            hint.textContent = data.matches.length ? 'Pick a student below, or keep typing for a new one.' : 'New student: enter the number and name.';
            data.matches.forEach(m => {
                const b = el('button', 'list-group-item list-group-item-action d-flex justify-content-between'); b.type = 'button';
                const l = el('span'); l.appendChild(el('b', '', m.student_number)); l.append(' \u00b7 ' + m.first_name + ' ' + m.last_name);
                b.appendChild(l); b.appendChild(el('small', 'text-muted', m.open_count + (Number(m.open_count) === 1 ? ' book out' : ' books out')));
                b.onclick = () => { sn.value = m.student_number; list.style.display = 'none'; showStudent(m.student_number); };
                list.appendChild(b);
            });
            list.style.display = data.matches.length ? 'block' : 'none';
        } catch (e) { /* ignore */ }
    }, 200);
});
document.addEventListener('click', e => { if (!list.contains(e.target) && e.target !== sn) list.style.display = 'none'; });
if (sn.value.trim()) showStudent(sn.value.trim());

// Book search: type, click a result, it fills in
const items = [...document.querySelectorAll('.book-item')], bid = document.getElementById('book_id');
const bs = document.getElementById('bs'), blist = document.getElementById('book-list'), bhint = document.getElementById('book-hint');
function filterBooks() {
    const t = bs.value.toLowerCase().trim();
    let shown = 0;
    items.forEach(i => {
        const ok = t !== '' && i.dataset.text.includes(t);
        i.style.setProperty('display', ok ? 'flex' : 'none', 'important');
        if (ok) shown++;
    });
    blist.style.display = shown ? 'block' : 'none';
    if (t !== '' && !shown) bhint.textContent = 'No matching book.';
}
bs.addEventListener('input', () => { bid.value = ''; bhint.textContent = 'Type a title or author, then click the book.'; filterBooks(); });
items.forEach(i => i.addEventListener('click', () => {
    bid.value = i.dataset.id; bs.value = i.dataset.title;
    blist.style.display = 'none'; bhint.textContent = 'Selected: ' + i.dataset.title;
}));

// Due date follows date borrowed
document.getElementById('bd').addEventListener('change', e => {
    if (!e.target.value) return;
    const d = new Date(e.target.value + 'T00:00:00'); d.setDate(d.getDate() + LOAN_DAYS);
    const p = n => String(n).padStart(2, '0');
    document.getElementById('dd').value = d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate());
});

document.querySelector('form').addEventListener('submit', e => {
    if (!bid.value) { e.preventDefault(); bhint.textContent = 'Please click a book from the results first.'; }
});
</script>
<?php require 'footer.php'; ?>