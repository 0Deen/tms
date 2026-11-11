<?php
function nav_link(string $file, string $label, string $icon, array $roles = []): void {
    if ($roles && !has_role($roles)) return;
    $active = basename($_SERVER['PHP_SELF'] ?? '') === $file ? ' active' : '';
    echo '<a class="top-nav-link'.$active.'" href="'.e($file).'"><span class="nav-icon">'.$icon.'</span><span>'.e($label).'</span></a>';
}
function page_header(string $title): void {
    $u = current_user();
    $flash = flash();
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<meta name="theme-color" content="#e9e6e3"><title>'.e($title).' · '.e(APP_NAME).'</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="assets/style.css"></head><body>';
    if ($u) {
        echo '<div class="app-shell"><header class="app-header"><a class="brand" href="dashboard.php"><span class="brand-mark">T</span><span class="brand-copy"><b>TransitFlow</b><small>MOVE PEOPLE. MOVE BUSINESS.</small></span></a>';
        echo '<button class="mobile-menu" id="menuToggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>';
        echo '<nav class="top-nav" id="topNav"><div class="top-nav-inner">';
        nav_link('dashboard.php','Overview','◫');
        nav_link('trips.php','Trip operations','⇄');
        nav_link('vehicles.php','Fleet','▣',['Administrator','SACCO Official']);
        nav_link('staff.php','People','♙',['Administrator','SACCO Official']);
        nav_link('routes.php','Routes & fares','⌁',['Administrator','SACCO Official']);
        nav_link('passengers.php','Passengers','♧',['Administrator','SACCO Official','Conductor']);
        nav_link('complaints.php','Service desk','◇',['Administrator','SACCO Official']);
        nav_link('reports.php','Reports','▥',['Administrator','SACCO Official']);
        if (has_role(['Administrator'])) {
            echo '<div class="nav-more"><button class="top-nav-link nav-more-trigger" type="button" aria-expanded="false">More <span>⌄</span></button><div class="nav-more-menu">';
            nav_link('users.php','User access','♙',['Administrator']);
            nav_link('activity.php','Audit activity','◷',['Administrator']);
            nav_link('settings.php','Settings','⚙',['Administrator']);
            echo '</div></div>';
        }
        echo '</div></nav><div class="header-actions"><a class="header-icon" href="complaints.php" aria-label="Service desk">♧</a><span class="header-divider"></span><span class="header-user"><span class="header-avatar">'.e(strtoupper(substr($u['name'],0,1))).'</span><span class="header-user-copy"><b>'.e($u['name']).'</b><small>'.e($u['role']).'</small></span></span><a class="signout" href="logout.php">Sign out ↗</a></div></header>';
        echo '<main class="content">';
        echo '<div class="page-heading"><div><div class="eyebrow">TRANSITFLOW <span> / </span> OPERATIONS</div><h1>'.e($title).'</h1><p>One connected view of your fleet, people and daily performance.</p></div><div class="page-date"><span class="date-dot"></span><span><b>'.date('l, d F Y').'</b><small>East Africa Time · EAT</small></span></div></div>';
        if ($flash) echo '<div class="notice success"><span>✓</span>'.e($flash).'<button onclick="this.parentElement.remove()" aria-label="Dismiss">×</button></div>';
    } else {
        echo '<main class="content standalone">';
    }
}
function show_errors(array $errors): void {
    if ($errors) { echo '<div class="notice error"><span>!</span><div><b>Please review the following</b><ul>'; foreach ($errors as $x) echo '<li>'.e($x).'</li>'; echo '</ul></div></div>'; }
}
function page_footer(): void {
    if (current_user()) echo '</main><footer class="app-footer"><span><b>TransitFlow</b> · Transport operations platform</span><span>Designed for better journeys <b>✳</b></span></footer></div>';
    else echo '</main>';
    echo '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script><script src="assets/app.js"></script></body></html>';
}
