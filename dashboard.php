<?php
declare(strict_types=1);
require 'config.php';
require_login();

$page_title = 'Dashboard';

// ---- Numbers ---------------------------------------------------------
$row = $conn->query(
    'SELECT
        (SELECT COUNT(*) FROM books) AS titles,
        (SELECT COUNT(*) FROM borrowings WHERE returned_at IS NULL) AS on_loan,
        (SELECT COUNT(DISTINCT student_id) FROM borrowings WHERE returned_at IS NULL) AS borrowers,
        (SELECT COUNT(*) FROM borrowings WHERE returned_at IS NULL AND due_date < CURDATE()) AS overdue'
)->fetch_assoc();

// ---- Categories (number of titles in each) ---------------------------
$categories = $conn->query(
    "SELECT category, COUNT(*) AS n FROM books
     WHERE category IS NOT NULL AND category <> ''
     GROUP BY category ORDER BY n DESC, category LIMIT 12"
)->fetch_all(MYSQLI_ASSOC);

// ---- Current borrowers (open loans, soonest due first) ---------------
$loans = $conn->query(
    'SELECT s.student_number, s.first_name, s.last_name, b.title, br.due_date,
            DATEDIFF(br.due_date, CURDATE()) AS days_left
     FROM borrowings br
     JOIN students s ON s.id = br.student_id
     JOIN books b ON b.id = br.book_id
     WHERE br.returned_at IS NULL
     ORDER BY br.due_date, br.id
     LIMIT 8'
)->fetch_all(MYSQLI_ASSOC);

// ---- Recent activity (built from the existing tables) ----------------
$activity = $conn->query(
    "(SELECT 'borrowed' AS type, br.borrowed_at AS ts, CONCAT(s.first_name, ' ', s.last_name) AS who, b.title AS title
        FROM borrowings br JOIN students s ON s.id = br.student_id JOIN books b ON b.id = br.book_id)
     UNION ALL
     (SELECT 'returned', br.returned_at, CONCAT(s.first_name, ' ', s.last_name), b.title
        FROM borrowings br JOIN students s ON s.id = br.student_id JOIN books b ON b.id = br.book_id
        WHERE br.returned_at IS NOT NULL)
     UNION ALL
     (SELECT 'added', created_at, '', title FROM books)
     ORDER BY ts DESC LIMIT 6"
)->fetch_all(MYSQLI_ASSOC);

function when(string $ts): string
{
    $t = strtotime($ts);
    $d = date('Y-m-d', $t);
    if ($d === date('Y-m-d')) {
        return 'Today, ' . date('g:i A', $t);
    }
    if ($d === date('Y-m-d', strtotime('-1 day'))) {
        return 'Yesterday, ' . date('g:i A', $t);
    }
    return date('M j, Y', $t);
}

require 'header.php';
?>
<style>
    .dash-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 1.25rem; }
    .dash-head h3 { margin: 0; font-size: 2rem; }
    .dash-head p { margin: 2px 0 0; color: #666; }
    .stat { display: flex; align-items: center; gap: 16px; padding: 16px 18px; height: 100%; }
    .stat svg { color: var(--navy); flex-shrink: 0; }
    .stat .num { font-size: 1.9rem; line-height: 1; color: #222; }
    .stat .lbl { color: #555; font-size: .9rem; }
    .stat .sep { width: 1px; align-self: stretch; background: #e3dfd2; }
    .stat.danger .num { color: #c0414b; }
    .stat.danger svg { color: #c0414b; }
    .card-pad { padding: 20px 22px; height: 100%; }
    .card-pad h5 { font-size: 1.25rem; margin-bottom: 14px; }
    .rule { display: flex; gap: 14px; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee; }
    .rule:last-child { border-bottom: 0; }
    .rule svg { color: var(--navy); flex-shrink: 0; }
    .cat { display: inline-block; background: #fff3d6; border: 1px solid #f1d28a; color: var(--navy);
        border-radius: 20px; padding: 5px 14px; margin: 0 6px 8px 0; text-decoration: none; font-size: .9rem; }
    .cat:hover { background: #fde6a8; color: var(--navy); }
    .cat b { color: #b36b00; margin-left: 6px; font-weight: 700; }
    .act { display: flex; gap: 14px; padding: 11px 0; border-bottom: 1px solid #eee; align-items: flex-start; }
    .act:last-child { border-bottom: 0; }
    .act .ico { width: 36px; height: 36px; border-radius: 8px; background: #fff3d6; color: var(--navy);
        display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .act .when { margin-left: auto; color: #777; font-size: .85rem; white-space: nowrap; }
    .badge-soft { padding: 3px 10px; border-radius: 12px; font-size: .8rem; font-weight: 600; white-space: nowrap; }
    .b-late { background: #f8d7da; color: #842029; }
    .b-soon { background: #ffe8a8; color: #6b4a00; }
    .b-ok { background: #d1e7dd; color: #0f5132; }
    @media (max-width: 575px) { .dash-head { flex-direction: column; } }
</style>

<div class="dash-head">
    <div>
        <h3>Library overview</h3>
        <p>Welcome to the <?= e(LIBRARY_NAME) ?> Management System, <?= e($_SESSION['username'] ?? 'librarian') ?>.</p>
    </div>
    <a class="btn btn-primary" href="borrow.php">Borrow book</a>
</div>

<!-- Numbers -->
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="card stat">
        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M2 5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16a2 2 0 0 0-2-2H2z"/><path d="M22 5a2 2 0 0 0-2-2h-6a2 2 0 0 0-2 2v16a2 2 0 0 1 2-2h8z"/></svg>
        <div class="sep"></div><div><div class="num"><?= (int) $row['titles'] ?></div><div class="lbl">Book titles</div></div>
    </div></div>
    <div class="col-6 col-lg-3"><div class="card stat">
        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="17.5" cy="9" r="2.5"/><path d="M18 14c2.4.3 3.8 2 3.8 4.5"/></svg>
        <div class="sep"></div><div><div class="num"><?= (int) $row['borrowers'] ?></div><div class="lbl">Students with a book</div></div>
    </div></div>
    <div class="col-6 col-lg-3"><div class="card stat">
        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 3 7.5 12 12l9-4.5z"/><path d="m3 12 9 4.5 9-4.5"/><path d="m3 16.5 9 4.5 9-4.5"/></svg>
        <div class="sep"></div><div><div class="num"><?= (int) $row['on_loan'] ?></div><div class="lbl">Books on loan</div></div>
    </div></div>
    <div class="col-6 col-lg-3"><div class="card stat danger">
        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 20h20z"/><path d="M12 10v4M12 17.5v.01"/></svg>
        <div class="sep"></div><div><div class="num"><?= (int) $row['overdue'] ?></div><div class="lbl">Overdue</div></div>
    </div></div>
</div>

<!-- About + rules -->
<div class="row g-3 mb-3">
    <div class="col-lg-7"><div class="card card-pad">
        <h5>About <?= e(LIBRARY_NAME) ?></h5>
        <p class="mb-2">Record loans and returns, and keep the book catalog up to date.</p>
        <p class="mb-0 text-muted">Students are kept as records only. Every loan is tied to a student number and a due date.</p>
    </div></div>
    <div class="col-lg-5"><div class="card card-pad">
        <h5>Library rules</h5>
        <div class="rule"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 3 7.5 12 12l9-4.5z"/><path d="m3 12 9 4.5 9-4.5"/></svg><span>Maximum of <?= (int) MAX_ACTIVE_LOANS ?> books at a time</span></div>
        <div class="rule"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg><span><?= (int) LOAN_DAYS ?>-day borrowing period</span></div>
        <div class="rule"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7M21 4v5h-5"/></svg><span><?= (int) MAX_EXTENSIONS ?> extension allowed, not once overdue</span></div>
    </div></div>
</div>

<!-- Categories + current borrowers -->
<div class="row g-3 mb-3">
    <div class="col-lg-5"><div class="card card-pad">
        <h5>Categories</h5>
        <?php foreach ($categories as $c): ?>
            <a class="cat" href="books.php?q=<?= e(urlencode((string) $c['category'])) ?>"><?= e($c['category']) ?><b><?= (int) $c['n'] ?></b></a>
        <?php endforeach; ?>
        <?php if (!$categories): ?><p class="text-muted mb-0">No categories yet. Add one when you add a book.</p><?php endif; ?>
    </div></div>
    <div class="col-lg-7"><div class="card card-pad">
        <h5>Current borrowers</h5>
        <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Student</th><th>Book</th><th>Due date</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($loans as $l):
                $left = (int) $l['days_left'];
                if ($left < 0) { $cls = 'b-late'; $txt = 'Overdue ' . abs($left) . 'd'; }
                elseif ($left <= DUE_SOON_DAYS) { $cls = 'b-soon'; $txt = 'Due soon'; }
                else { $cls = 'b-ok'; $txt = 'On time'; } ?>
                <tr>
                    <td><?= e($l['first_name'] . ' ' . $l['last_name']) ?><div class="small text-muted"><?= e($l['student_number']) ?></div></td>
                    <td><?= e($l['title']) ?></td>
                    <td><?= e(date('M j, Y', strtotime($l['due_date']))) ?></td>
                    <td><span class="badge-soft <?= $cls ?>"><?= e($txt) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$loans): ?><tr><td colspan="4" class="text-center text-muted">No books are out right now.</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </div></div>
</div>

<!-- Recent activity -->
<div class="card card-pad">
    <h5>Recent activity</h5>
    <?php foreach ($activity as $a):
        if ($a['type'] === 'borrowed') { $head = 'Book borrowed'; $icon = '<path d="M5 12h14M13 6l6 6-6 6"/>'; }
        elseif ($a['type'] === 'returned') { $head = 'Book returned'; $icon = '<path d="M19 12H5M11 6l-6 6 6 6"/>'; }
        else { $head = 'Book added'; $icon = '<path d="M12 5v14M5 12h14"/>'; } ?>
        <div class="act">
            <div class="ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $icon ?></svg></div>
            <div>
                <div class="fw-semibold"><?= e($head) ?></div>
                <div class="text-muted small">
                    <?php if ($a['type'] === 'borrowed'): ?><?= e($a['who']) ?> borrowed <em><?= e($a['title']) ?></em>.
                    <?php elseif ($a['type'] === 'returned'): ?><?= e($a['who']) ?> returned <em><?= e($a['title']) ?></em>.
                    <?php else: ?><em><?= e($a['title']) ?></em> was added to the catalog.<?php endif; ?>
                </div>
            </div>
            <div class="when"><?= e(when((string) $a['ts'])) ?></div>
        </div>
    <?php endforeach; ?>
    <?php if (!$activity): ?><p class="text-muted mb-0">Nothing yet.</p><?php endif; ?>
</div>
<?php require 'footer.php'; ?>