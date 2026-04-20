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
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }

    $content = file_get_contents($file);

    // 1. Add definition and update body_class
    if (strpos($content, '{% set isProf =') === false) {
        $content = preg_replace(
            '/({% extends \'(?:base|base_front)\.html\.twig\' %})/',
            "$1\n\n{% set isProf = isProfessional|default(is_granted('ROLE_ADMIN') or is_granted('ROLE_COACH') or is_granted('ROLE_PSYCHOLOGUE')) %}",
            $content
        );
        $content = str_replace(
            '{% block body_class %}user-theme{% endblock %}',
            "{% block body_class %}{{ isProf ? 'admin-theme' : 'user-theme' }}{% endblock %}",
            $content
        );
    }

    // Update body.user-theme
    $content = str_replace(
        'body.user-theme {',
        'body.user-theme, body.admin-theme {',
        $content
    );

    // 2. Update stylesheet
    $content = str_replace(
        '<link rel="stylesheet" href="{{ asset(\'user_dashboard.css\') }}">',
        '<link rel="stylesheet" href="{{ asset(isProf ? \'admin_dashboard.css\' : \'user_dashboard.css\') }}">',
        $content
    );

    // 3. Update body elements
    $content = str_replace(
        '<div class="user-scope">' . "\n    " . '{% include \'user_nav_bar/index.html.twig\' %}',
        '<div class="{{ isProf ? \'admin-scope\' : \'user-scope\' }}">' . "\n    " . '{% if isProf %}' . "\n        " . '{% include \'admin_nav_bar/index.html.twig\' %}' . "\n    " . '{% else %}' . "\n        " . '{% include \'user_nav_bar/index.html.twig\' %}' . "\n    " . '{% endif %}',
        $content
    );

    $content = str_replace(
        '<div class="user-main-content">',
        '<div class="{{ isProf ? \'admin-main-content\' : \'user-main-content\' }}">',
        $content
    );

    file_put_contents($file, $content);
    echo "Processed: $file\n";
}
