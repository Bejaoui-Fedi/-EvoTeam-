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

    // Remove isProLayout definition
    $content = preg_replace('/\{\% set isProLayout = is_granted\(\'ROLE_COACH\'\) or is_granted\(\'ROLE_PSYCHOLOGUE\'\) \%\}/', '', $content);

    // Revert body_class
    $content = str_replace(
        "{{ isAdminLayout ? 'admin-theme' : (isProLayout ? 'pro-theme' : 'user-theme') }}",
        "{{ isAdminLayout ? 'admin-theme' : 'user-theme' }}",
        $content
    );

    // Revert stylesheet
    $content = str_replace(
        "{{ asset(isAdminLayout ? 'admin_dashboard.css' : (isProLayout ? 'pro_dashboard.css' : 'user_dashboard.css')) }}",
        "{{ asset(isAdminLayout ? 'admin_dashboard.css' : 'user_dashboard.css') }}",
        $content
    );

    // Revert navbar inclusion
    $content = preg_replace(
        '/\{% if isAdminLayout %\}.*?{% include \'admin_nav_bar\/index\.html\.twig\' %}.*?{% elseif isProLayout %\}.*?{% include \'pro_nav_bar\/index\.html\.twig\' %}.*?{% else %\}.*?{% include \'user_nav_bar\/index\.html\.twig\' %}.*?{% endif %}/s',
        "{% if isAdminLayout %}\n        {% include 'admin_nav_bar/index.html.twig' %}\n    {% else %}\n        {% include 'user_nav_bar/index.html.twig' %}\n    {% endif %}",
        $content
    );

    // Revert main-content class
    $content = str_replace(
        "class=\"{{ isAdminLayout ? 'admin-main-content' : (isProLayout ? 'pro-main-content' : 'user-main-content') }}\"",
        "class=\"{{ isAdminLayout ? 'admin-main-content' : 'user-main-content' }}\"",
        $content
    );
    
    // Revert scopes
    $content = str_replace(
        "class=\"{{ isAdminLayout ? 'admin-scope' : (isProLayout ? 'pro-scope' : 'user-scope') }}\"",
        "class=\"{{ isAdminLayout ? 'admin-scope' : 'user-scope' }}\"",
        $content
    );

    file_put_contents($file, $content);
    echo "Reverted $file\n";
}
