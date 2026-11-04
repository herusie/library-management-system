<?php
// Shared top of every page. Set $page_title before including.
$current = basename($_SERVER['PHP_SELF']);
// file => [label, icon svg paths]
$nav = [
    'dashboard.php' => ['Dashboard', '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/>'],
    'books.php'     => ['Books',     '<path d="M4 19V5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2"/><path d="M8 7h7"/>'],
    'borrow.php'    => ['Borrow',    '<path d="M5 12h14M13 6l6 6-6 6"/>'],
    'return.php'    => ['Return',    '<path d="M19 12H5M11 6l-6 6 6 6"/>'],
];
// Book pages (add, edit) count as "Books"
$active = in_array($current, ['add_book.php', 'edit_book.php'], true) ? 'books.php' : $current;
$uname = (string) ($_SESSION['username'] ?? 'librarian');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title ?? 'Library') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --navy: #14213d; --navy-2: #1d2d50; --orange: #fca311; --paper: #f5f3ec; --side: 240px; }
        body { background: var(--paper); color: #222; }

        /* Sidebar */
        .sidebar { position: fixed; top: 0; bottom: 0; left: 0; width: var(--side); background: var(--navy);
            border-right: 3px solid var(--orange); display: flex; flex-direction: column; padding: 22px 18px; z-index: 100; }
        .side-brand { color: #fff; font-family: Georgia, "Times New Roman", serif; font-size: 1.3rem; text-decoration: none;
            padding: 0 0 20px; margin-bottom: 18px; border-bottom: 1px solid #2a3a63; display: block; }
        .side-brand small { color: var(--orange); font-family: -apple-system, "Segoe UI", sans-serif;
            font-size: .65rem; letter-spacing: .2em; font-weight: 700; display: block; line-height: 1.3; }
        .side-link { display: flex; align-items: center; gap: 12px; color: #b8c4e0; text-decoration: none;
            padding: 12px 14px; border-radius: 8px; margin-bottom: 6px; }
        .side-link:hover { color: #fff; background: var(--navy-2); }
        .side-link.active { background: var(--orange); color: var(--navy); font-weight: 700; }
        .side-link svg { flex-shrink: 0; }

        /* Account menu at the bottom of the sidebar */
        .user-menu { margin-top: auto; position: relative; border-top: 1px solid #2a3a63; padding-top: 14px; }
        .user-menu summary { list-style: none; cursor: pointer; display: flex; align-items: center; gap: 12px; color: #fff; padding: 4px 2px; }
        .user-menu summary::-webkit-details-marker { display: none; }
        .user-menu summary small { display: block; color: #9fb0d6; font-size: .75rem; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--orange); color: var(--navy);
            display: inline-flex; align-items: center; justify-content: center; font: 700 17px Georgia, serif;
            border: 2px solid transparent; flex-shrink: 0; }
        .avatar.active { border-color: #fff; }
        .user-menu summary:hover .avatar { border-color: #fff; }
        .user-menu-panel { position: absolute; left: 0; right: 0; bottom: 62px; background: #fff; z-index: 50;
            border-top: 3px solid var(--orange); border-radius: 8px; box-shadow: 0 8px 24px rgba(0, 0, 0, .35); overflow: hidden; }
        .user-menu-panel a { display: block; padding: .7rem 1rem; text-decoration: none; color: var(--navy); }
        .user-menu-panel a:hover { background: #fff3d6; }
        .user-menu-panel a.out { color: #b02a37; border-top: 1px solid #eee; }

        /* Page area */
        .app-main { margin-left: var(--side); min-height: 100vh; }
        .content { max-width: 1240px; margin: 0 auto; padding: 36px 48px 56px; }
        .content > h3:first-of-type { margin-bottom: 1.25rem; }
        .mobile-bar { display: none; background: var(--navy); border-bottom: 3px solid var(--orange); color: #fff;
            padding: 10px 16px; align-items: center; gap: 12px; font-family: Georgia, serif; }
        .mobile-bar button { background: none; border: 1px solid #3a4d7a; color: #fff; border-radius: 6px; padding: 4px 10px; font-size: 1.2rem; }
        @media (max-width: 767px) {
            .sidebar { transform: translateX(-100%); transition: transform .2s; }
            body.menu-open .sidebar { transform: none; }
            .app-main { margin-left: 0; }
            .mobile-bar { display: flex; }
            .content { padding: 20px 16px 40px; }
        }

        h3, h5 { font-family: Georgia, "Times New Roman", serif; color: var(--navy); }

        .card { border: 1px solid #e3dfd2; border-top: 3px solid var(--orange); border-radius: 8px; }
        .card.border-danger { border-color: #e3dfd2 !important; border-top: 3px solid #dc3545 !important; }

        .table thead th { background: var(--navy); color: #fff; font-weight: 600; border: 0; }
        .table { --bs-table-striped-bg: #faf8f2; }

        .btn-primary { --bs-btn-bg: var(--orange); --bs-btn-border-color: var(--orange); --bs-btn-color: var(--navy);
            --bs-btn-hover-bg: #e0900b; --bs-btn-hover-border-color: #e0900b; --bs-btn-hover-color: var(--navy);
            --bs-btn-active-bg: #c98200; --bs-btn-active-border-color: #c98200; --bs-btn-active-color: var(--navy);
            font-weight: 600; }
        .btn-outline-primary { --bs-btn-color: var(--navy); --bs-btn-border-color: var(--navy);
            --bs-btn-hover-bg: var(--navy); --bs-btn-hover-border-color: var(--navy); --bs-btn-hover-color: #fff;
            --bs-btn-active-bg: var(--navy); --bs-btn-active-border-color: var(--navy);
            --bs-btn-disabled-color: var(--navy); --bs-btn-disabled-border-color: var(--navy); }
        .form-control:focus, .form-select:focus { border-color: var(--orange); box-shadow: 0 0 0 .2rem rgba(252, 163, 17, .25); }
    </style>
</head>
<body>
<aside class="sidebar">
    <a class="side-brand" href="dashboard.php"><small><?= e(strtoupper(LIBRARY_NAME)) ?></small>Management System</a>
    <?php foreach ($nav as $file => [$label, $icon]): ?>
        <a class="side-link <?= $active === $file ? 'active' : '' ?>" href="<?= e($file) ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $icon ?></svg>
            <?= e($label) ?>
        </a>
    <?php endforeach; ?>

    <details class="user-menu">
        <summary>
            <span class="avatar <?= $current === 'profile.php' ? 'active' : '' ?>"><?= e(strtoupper(substr($uname, 0, 1))) ?></span>
            <span><?= e($uname) ?><small>Librarian</small></span>
        </summary>
        <div class="user-menu-panel">
            <a href="profile.php">Profile &amp; password</a>
            <a href="logout.php" class="out">Log out</a>
        </div>
    </details>
</aside>
<div class="app-main">
<div class="mobile-bar"><button type="button" id="menu-btn" aria-label="Menu">&#9776;</button><span>Library</span></div>
<div class="content">
<?php if (!empty($_SESSION['flash'])): [$flash_type, $flash_msg] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="alert alert-<?= e($flash_type) ?>"><?= e($flash_msg) ?></div>
<?php endif; ?>