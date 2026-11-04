<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="A simple, reliable library management system for books, loans, and returns.">
    <title>Library Management System</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --accent: #047857;
            --ink: #0f172a;
            --muted: #475569;
            --surface: #ffffff;
            --canvas: #f8fafc;
            --line: #dbeafe;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            color: var(--ink);
            background: var(--canvas);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 16px;
            line-height: 1.5;
        }
        a { color: inherit; }
        .skip-link {
            position: fixed;
            top: 12px;
            left: 12px;
            z-index: 10;
            padding: 10px 14px;
            color: #fff;
            background: var(--primary-dark);
            border-radius: 8px;
            transform: translateY(-160%);
        }
        .skip-link:focus { transform: translateY(0); }
        .site-header { background: #fff; border-bottom: 1px solid var(--line); }
        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            width: min(1120px, calc(100% - 40px));
            min-height: 72px;
            margin: auto;
        }
        .brand { display: inline-flex; align-items: center; gap: 10px; color: var(--ink); font-size: 1.125rem; font-weight: 800; text-decoration: none; }
        .brand-mark { display: grid; width: 36px; height: 36px; place-items: center; color: #fff; background: var(--primary); border-radius: 9px; }
        .brand-mark svg { width: 22px; height: 22px; }
        .nav-actions { display: flex; align-items: center; gap: 18px; }
        .nav-link { color: var(--muted); font-weight: 700; text-decoration: none; }
        .nav-link:hover { color: var(--primary-dark); }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 10px 18px;
            border: 2px solid transparent;
            border-radius: 9px;
            font-weight: 800;
            text-decoration: none;
            transition: background-color .2s ease, border-color .2s ease, color .2s ease, transform .2s ease;
        }
        .button:hover { transform: translateY(-2px); }
        .button-primary { color: #fff; background: var(--primary); }
        .button-primary:hover { background: var(--primary-dark); }
        .button-secondary { color: var(--primary-dark); border-color: #93c5fd; background: #fff; }
        .button-secondary:hover { border-color: var(--primary); background: #eff6ff; }
        :is(a, button):focus-visible { outline: 3px solid #f59e0b; outline-offset: 3px; }
        .hero {
            overflow: hidden;
            position: relative;
            padding: 88px 0 74px;
            background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 52%, #ecfdf5 100%);
        }
        .hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(310px, .9fr);
            align-items: center;
            gap: 56px;
            width: min(1120px, calc(100% - 40px));
            margin: auto;
        }
        .eyebrow { margin: 0 0 16px; color: var(--accent); font-size: .875rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        h1 { max-width: 700px; margin: 0; font-size: clamp(2.4rem, 5vw, 4.5rem); line-height: 1.07; letter-spacing: -.045em; }
        .hero-copy { max-width: 610px; margin: 24px 0 0; color: var(--muted); font-size: clamp(1.0625rem, 2vw, 1.25rem); }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 32px; }
        .hero-note { margin: 16px 0 0; color: var(--muted); font-size: .9375rem; }
        .library-illustration { position: relative; min-height: 380px; }
        .card-visual {
            position: absolute;
            inset: 18px 0 0 22px;
            overflow: hidden;
            padding: 34px;
            background: #fff;
            border: 1px solid #bfdbfe;
            border-radius: 22px;
            box-shadow: 0 24px 50px rgba(30, 64, 175, .16);
        }
        .card-visual::before { content: ''; position: absolute; width: 280px; height: 280px; right: -110px; top: -105px; background: #dbeafe; border-radius: 50%; }
        .visual-label { position: relative; margin: 0 0 20px; color: var(--muted); font-size: .875rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
        .shelf { position: relative; display: flex; align-items: end; height: 190px; gap: 8px; padding: 18px 18px 11px; background: #eff6ff; border-radius: 12px; }
        .shelf::after { content: ''; position: absolute; right: 16px; bottom: 0; left: 16px; height: 10px; background: #1e3a8a; border-radius: 4px 4px 0 0; }
        .book { position: relative; z-index: 1; width: 34px; border-radius: 4px 4px 0 0; box-shadow: inset -7px 0 rgba(0, 0, 0, .08); }
        .book:nth-child(1) { height: 132px; background: #2563eb; }
        .book:nth-child(2) { height: 157px; background: #059669; }
        .book:nth-child(3) { height: 110px; background: #f59e0b; }
        .book:nth-child(4) { height: 146px; background: #7c3aed; }
        .book:nth-child(5) { height: 122px; background: #dc2626; }
        .book:nth-child(6) { height: 165px; background: #0f766e; }
        .visual-status { position: relative; display: flex; align-items: center; gap: 10px; margin: 24px 0 0; font-weight: 800; }
        .status-dot { width: 11px; height: 11px; background: var(--accent); border-radius: 50%; }
        .stats { width: min(1120px, calc(100% - 40px)); margin: -28px auto 0; position: relative; }
        .stats-inner { display: grid; grid-template-columns: repeat(3, 1fr); background: #fff; border: 1px solid var(--line); border-radius: 14px; box-shadow: 0 12px 32px rgba(15, 23, 42, .08); }
        .stat { padding: 20px 28px; text-align: center; }
        .stat + .stat { border-left: 1px solid var(--line); }
        .stat strong { display: block; color: var(--primary-dark); font-size: 1.25rem; }
        .stat span { color: var(--muted); font-size: .9375rem; }
        .features { width: min(1120px, calc(100% - 40px)); padding: 88px 0; margin: auto; }
        .section-heading { max-width: 630px; margin-bottom: 36px; }
        h2 { margin: 0; font-size: clamp(1.75rem, 3vw, 2.5rem); line-height: 1.15; letter-spacing: -.025em; }
        .section-heading p { margin: 12px 0 0; color: var(--muted); font-size: 1.0625rem; }
        .feature-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .feature { padding: 28px; background: var(--surface); border: 1px solid var(--line); border-radius: 14px; }
        .feature-icon { display: grid; width: 46px; height: 46px; place-items: center; color: var(--primary-dark); background: #dbeafe; border-radius: 10px; }
        .feature-icon svg { width: 25px; height: 25px; }
        .feature h3 { margin: 20px 0 8px; font-size: 1.1875rem; }
        .feature p { margin: 0; color: var(--muted); }
        .cta { padding: 0 20px 88px; }
        .cta-inner { width: min(1120px, 100%); padding: 50px; margin: auto; color: #fff; text-align: center; background: #1e3a8a; border-radius: 18px; }
        .cta h2 { max-width: 680px; margin: auto; }
        .cta p { max-width: 590px; margin: 14px auto 26px; color: #dbeafe; }
        .cta .button { color: #064e3b; background: #ecfdf5; }
        .cta .button:hover { background: #d1fae5; }
        footer { padding: 25px 20px; color: var(--muted); text-align: center; border-top: 1px solid var(--line); }
        @media (max-width: 760px) {
            .nav { width: min(100% - 28px, 1120px); min-height: 64px; }
            .nav-link { display: none; }
            .hero { padding: 60px 0 44px; }
            .hero-grid { grid-template-columns: 1fr; gap: 28px; width: min(100% - 40px, 1120px); }
            .library-illustration { min-height: 300px; }
            .card-visual { inset: 0; padding: 24px; }
            .stats { width: min(100% - 40px, 1120px); margin-top: -18px; }
            .stats-inner, .feature-grid { grid-template-columns: 1fr; }
            .stat + .stat { border-top: 1px solid var(--line); border-left: 0; }
            .features { width: min(100% - 40px, 1120px); padding: 64px 0; }
            .cta { padding: 0 20px 64px; }
            .cta-inner { padding: 36px 24px; }
        }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } .button { transition: none; } .button:hover { transform: none; } }
    </style>
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <header class="site-header">
        <nav class="nav" aria-label="Main navigation">
            <a class="brand" href="index.php" aria-label="Library Management System home">
                <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg></span>
                Library
            </a>
            <div class="nav-actions">
                <a class="nav-link" href="#features">Features</a>
                <a class="button button-primary" href="login.php">Librarian log in</a>
            </div>
        </nav>
    </header>

    <main id="main-content">
        <section class="hero" aria-labelledby="hero-title">
            <div class="hero-grid">
                <div>
                    <p class="eyebrow">Library management, made simple</p>
                    <h1 id="hero-title">Keep every book and borrower in reach.</h1>
                    <p class="hero-copy">Manage your collection, record loans, and process returns from one clear, dependable workspace.</p>
                    <div class="hero-actions">
                        <a class="button button-primary" href="login.php">Open library workspace</a>
                        <a class="button button-secondary" href="#features">See what it does</a>
                    </div>
                    <p class="hero-note">For authorized library staff.</p>
                </div>
                <div class="library-illustration" aria-hidden="true">
                    <div class="card-visual">
                        <p class="visual-label">Your collection</p>
                        <div class="shelf"><span class="book"></span><span class="book"></span><span class="book"></span><span class="book"></span><span class="book"></span><span class="book"></span></div>
                        <p class="visual-status"><span class="status-dot"></span>Ready for today’s lending</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="stats" aria-label="Library workspace benefits">
            <div class="stats-inner">
                <div class="stat"><strong>One place</strong><span>for your daily library work</span></div>
                <div class="stat"><strong>Quick search</strong><span>to find the right book</span></div>
                <div class="stat"><strong>Clear records</strong><span>for every loan and return</span></div>
            </div>
        </section>

        <section class="features" id="features" aria-labelledby="features-title">
            <div class="section-heading">
                <p class="eyebrow">Everything you need</p>
                <h2 id="features-title">Built around the tasks librarians do most.</h2>
                <p>Simple workflows make it easy to keep the catalogue accurate and borrowers moving.</p>
            </div>
            <div class="feature-grid">
                <article class="feature">
                    <div class="feature-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg></div>
                    <h3>Manage books</h3>
                    <p>Maintain titles, authors, categories, covers, and available copies in one catalogue.</p>
                </article>
                <article class="feature">
                    <div class="feature-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg></div>
                    <h3>Record loans</h3>
                    <p>Look up borrowers, find books by title or author, and record due dates with ease.</p>
                </article>
                <article class="feature">
                    <div class="feature-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v6h6"/><path d="m9 12 2 2 4-4"/></svg></div>
                    <h3>Handle returns</h3>
                    <p>See active loans, return books promptly, and keep an eye on overdue records.</p>
                </article>
            </div>
        </section>

        <section class="cta" aria-labelledby="cta-title">
            <div class="cta-inner">
                <h2 id="cta-title">Ready to run today’s library work?</h2>
                <p>Sign in to access the library dashboard and get started.</p>
                <a class="button" href="login.php">Librarian log in</a>
            </div>
        </section>
    </main>
    <footer>Library Management System</footer>
</body>
</html>
