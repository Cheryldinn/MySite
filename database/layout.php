<?php
// layout.php - Shared layout helpers

function get_nav_link($href, $label, $icon, $active_page) {
    $is_active = (basename($_SERVER['PHP_SELF']) === $href) ? ' active' : '';
    return "<a href='$href' class='nav-link$is_active'>$icon <span>$label</span></a>";
}

function render_sidebar($active = '') {
    $pages = basename($_SERVER['PHP_SELF']);
    $icons = [
        'index.php' => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
        'class_list.php' => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'subjects.php' => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
        'teachers.php' => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'generate.php' => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
        'view.php' => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'edit.php' => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
        'settings.php' => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
    ];

    $nav = [
        'OVERVIEW'  => ['index.php' => 'Dashboard'],
        'MANAGE'    => ['class_list.php' => 'Classes', 'subjects.php' => 'Subjects', 'teachers.php' => 'Teachers'],
        'TIMETABLE' => ['generate.php' => 'Generate', 'view.php' => 'View Timetable', 'edit.php' => 'Edit Schedule'],
        'SYSTEM'    => ['settings.php' => 'Settings'],
    ];

    echo '<aside class="sidebar">';
    echo '<div class="sidebar-brand">';
    echo '<div class="school-icon"><svg width="20" height="20" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>';
    echo '<h1>EduSchedule</h1><p>Timetable Manager</p>';
    echo '</div>';
    echo '<nav class="sidebar-nav">';

    foreach ($nav as $section => $links) {
        echo "<div class='nav-section-label'>$section</div>";
        foreach ($links as $file => $label) {
            $isActive = ($pages === $file) ? ' active' : '';
            $icon = $icons[$file] ?? '';
            echo "<a href='$file' class='nav-link$isActive'>$icon <span>$label</span></a>";
        }
    }

    echo '</nav>';
    echo '<div class="sidebar-footer">';
    echo '<a href="logout.php" class="nav-link" style="color:var(--danger)">';
    echo '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>';
    echo '<span>Logout</span></a>';
    echo '</div>';
    echo '</aside>';
}

function render_topbar($title, $subtitle = '') {
    $user = $_SESSION['username'] ?? 'Admin';
    $initial = strtoupper(substr($user, 0, 1));
    echo '<div class="topbar">';
    echo '<div class="topbar-title"><h2>' . htmlspecialchars($title) . '</h2>';
    if ($subtitle) echo '<p>' . htmlspecialchars($subtitle) . '</p>';
    echo '</div>';
    echo '<div class="topbar-user">';
    echo '<div class="avatar">' . $initial . '</div>';
    echo '<div><div class="fw-600" style="font-size:14px">' . htmlspecialchars($user) . '</div>';
    echo '<div class="text-muted">Administrator</div></div>';
    echo '</div></div>';
}
?>
