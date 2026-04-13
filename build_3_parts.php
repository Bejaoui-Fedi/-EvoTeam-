<?php

// 1. Create pro_nav_bar/index.html.twig
$adminNav = file_get_contents('templates/admin_nav_bar/index.html.twig');
$proNav = $adminNav;
$proNav = str_replace('admin-sidebar', 'pro-sidebar', $proNav);
$proNav = str_replace('admin-nav-link', 'pro-nav-link', $proNav);
$proNav = str_replace('Admin', 'Coach / Psy', $proNav);
$proNav = str_replace('app_admin_dashboard', 'app_user_dashboard', $proNav);
$proNav = str_replace('app_admin_logout', 'app_user_logout', $proNav);

// Remove Utilisateurs link
$proNav = preg_replace('/<a href="\{\{ path\(\'app_user_list\'\).*?<\/a>/s', '', $proNav);

if (!file_exists('templates/pro_nav_bar')) {
    mkdir('templates/pro_nav_bar', 0777, true);
}
file_put_contents('templates/pro_nav_bar/index.html.twig', $proNav);
echo "Created pro_nav_bar\n";

// 2. Create pro_dashboard.css from admin_dashboard.css
$adminCss = file_get_contents('public/admin_dashboard.css');
$proCss = $adminCss;
$proCss = str_replace('admin-theme', 'pro-theme', $proCss);
$proCss = str_replace('admin-sidebar', 'pro-sidebar', $proCss);
$proCss = str_replace('admin-main-content', 'pro-main-content', $proCss);
$proCss = str_replace('admin-nav-link', 'pro-nav-link', $proCss);

// Replace colors: Green to Slate Blue
// Very dark bg map: #0f1e17 -> #0f172a
$proCss = str_replace('#0f1e17', '#0f172a', $proCss);
// Darker bg map: #1a3a2e -> #1e293b
$proCss = str_replace('#1a3a2e', '#1e293b', $proCss);
// Lighter primary map: #2d5a48 -> #334155
$proCss = str_replace('#2d5a48', '#334155', $proCss);
// Accent bright map: #4ade80 -> #38bdf8
$proCss = str_replace('#4ade80', '#38bdf8', $proCss);
// Accent hover map: #22c55e -> #0ea5e9
$proCss = str_replace('#22c55e', '#0ea5e9', $proCss);
// Header tint map: #e8f3ef -> #e0f2fe
$proCss = str_replace('#e8f3ef', '#e0f2fe', $proCss);
// Sidebar gradient map: linear-gradient(180deg, #0a1410 0%, #152b22 100%) -> linear-gradient(180deg, #090e17 0%, #172236 100%)
$proCss = str_replace('#0a1410', '#090e17', $proCss);
$proCss = str_replace('#152b22', '#172236', $proCss);

file_put_contents('public/pro_dashboard.css', $proCss);
echo "Created pro_dashboard.css\n";

// 3. Update all 8 trackers TWIG templates
$files = [
    'templates/wellbeing_tracker/index.html.twig',
    'templates/wellbeing_tracker/new.html.twig',
    'templates/wellbeing_tracker/edit.html.twig',
    'templates/wellbeing_tracker/show.html.twig',
    'templates/daily_routine_task/index.html.twig',
    'templates/daily_routine_task/new.html.twig',
    'templates/daily_routine_task/edit.html.twig',
    'templates/daily_routine_task/show.html.twig'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);

    // Ensure we don't duplicate
    if (strpos($content, '{% set isProLayout =') !== false) {
        continue;
    }

    $content = str_replace(
        "{% set isAdminLayout = is_granted('ROLE_ADMIN') %}",
        "{% set isAdminLayout = is_granted('ROLE_ADMIN') %}\n{% set isProLayout = is_granted('ROLE_COACH') or is_granted('ROLE_PSYCHOLOGUE') %}",
        $content
    );

    $content = str_replace(
        "{{ isAdminLayout ? 'admin-theme' : 'user-theme' }}",
        "{{ isAdminLayout ? 'admin-theme' : (isProLayout ? 'pro-theme' : 'user-theme') }}",
        $content
    );

    $content = str_replace(
        "{{ asset(isAdminLayout ? 'admin_dashboard.css' : 'user_dashboard.css') }}",
        "{{ asset(isAdminLayout ? 'admin_dashboard.css' : (isProLayout ? 'pro_dashboard.css' : 'user_dashboard.css')) }}",
        $content
    );

    // Navbar conditional replace
    $navReplacement = <<<HTML
    {% if isAdminLayout %}
        {% include 'admin_nav_bar/index.html.twig' %}
    {% elseif isProLayout %}
        {% include 'pro_nav_bar/index.html.twig' %}
    {% else %}
        {% include 'user_nav_bar/index.html.twig' %}
    {% endif %}
HTML;
    $content = preg_replace(
        '/\{% if isAdminLayout %\}.*?\{\% else %\}.*?\{% include \'user_nav_bar\/index\.html\.twig\' %\}.*?\{% endif %\}/s',
        $navReplacement,
        $content
    );

    // Content container class
    $content = str_replace(
        "class=\"{{ isAdminLayout ? 'admin-main-content' : 'user-main-content' }}\"",
        "class=\"{{ isAdminLayout ? 'admin-main-content' : (isProLayout ? 'pro-main-content' : 'user-main-content') }}\"",
        $content
    );
    
    // Fallback for daily_routine_task
    $content = str_replace(
        "class=\"{{ isAdminLayout ? 'admin-scope' : 'user-scope' }}\"",
        "class=\"{{ isAdminLayout ? 'admin-scope' : (isProLayout ? 'pro-scope' : 'user-scope') }}\"",
        $content
    );

    file_put_contents($file, $content);
    echo "Updated $file\n";
}

