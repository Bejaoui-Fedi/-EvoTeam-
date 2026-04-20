<?php

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

    // Replace the definition of isProf with isAdmin
    $content = preg_replace(
        '/\{% set isProf = isProfessional\|default\(is_granted\(\'ROLE_ADMIN\'\) or is_granted\(\'ROLE_COACH\'\) or is_granted\(\'ROLE_PSYCHOLOGUE\'\)\) %\}/',
        '{% set isAdminLayout = is_granted(\'ROLE_ADMIN\') %}',
        $content
    );
    
    // In case there's another pattern of that definition
    $content = preg_replace(
        '/\{% set isProf = is_granted\(\'ROLE_ADMIN\'\) or is_granted\(\'ROLE_COACH\'\) or is_granted\(\'ROLE_PSYCHOLOGUE\'\) %\}/',
        '{% set isAdminLayout = is_granted(\'ROLE_ADMIN\') %}',
        $content
    );

    // Safely replace isProf conditionals but only in layout elements mapping
    $content = str_replace("isProf ? 'admin-theme'", "isAdminLayout ? 'admin-theme'", $content);
    $content = str_replace("isProf ? 'admin_dashboard.css'", "isAdminLayout ? 'admin_dashboard.css'", $content);
    $content = str_replace("isProf ? 'admin-scope'", "isAdminLayout ? 'admin-scope'", $content);
    $content = str_replace("{% if isProf %}\n        {% include 'admin_nav_bar/index.html.twig' %}", "{% if isAdminLayout %}\n        {% include 'admin_nav_bar/index.html.twig' %}", $content);
    $content = str_replace("isProf ? 'admin-main-content'", "isAdminLayout ? 'admin-main-content'", $content);

    file_put_contents($file, $content);
    echo "Processed $file\n";
}
