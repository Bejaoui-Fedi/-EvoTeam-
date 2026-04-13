import re
import os

files = [
    'templates/wellbeing_tracker/index.html.twig',
    'templates/wellbeing_tracker/new.html.twig',
    'templates/wellbeing_tracker/edit.html.twig',
    'templates/wellbeing_tracker/show.html.twig',
    'templates/daily_routine_task/index.html.twig',
    'templates/daily_routine_task/new.html.twig',
    'templates/daily_routine_task/edit.html.twig',
    'templates/daily_routine_task/show.html.twig'
]

for file_path in files:
    if not os.path.exists(file_path):
        print(f"File not found: {file_path}")
        continue

    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. Add definition and update body_class
    if '{% set isProf =' not in content:
        content = re.sub(
            r'({% extends \'(?:base|base_front)\.html\.twig\' %})',
            r'\1\n\n{% set isProf = isProfessional|default(is_granted(\'ROLE_ADMIN\') or is_granted(\'ROLE_COACH\') or is_granted(\'ROLE_PSYCHOLOGUE\')) %}',
            content
        )
        content = re.sub(
            r'{% block body_class %}user-theme{% endblock %}',
            r'{% block body_class %}{{ isProf ? \'admin-theme\' : \'user-theme\' }}{% endblock %}',
            content
        )

    # Update body.user-theme
    content = re.sub(
        r'body\.user-theme\b',
        r'body.user-theme, body.admin-theme',
        content
    )
        
    # 2. Update stylesheet
    content = re.sub(
        r'<link rel=\"stylesheet\" href=\"{{ asset\(\'user_dashboard\.css\'\) }}\">',
        r'<link rel=\"stylesheet\" href=\"{{ asset(isProf ? \'admin_dashboard.css\' : \'user_dashboard.css\') }}\">',
        content
    )
    
    # 3. Update body elements
    # Some use include '.../index.html.twig' and some might use include('...')
    # Let's target the exact string instead of complex regexes if possible.
    content = content.replace(
        '<div class="user-scope">\n    {% include \'user_nav_bar/index.html.twig\' %}',
        '<div class="{{ isProf ? \'admin-scope\' : \'user-scope\' }}">\n    {% if isProf %}\n        {% include \'admin_nav_bar/index.html.twig\' %}\n    {% else %}\n        {% include \'user_nav_bar/index.html.twig\' %}\n    {% endif %}'
    )
    
    # Also for user-main-content
    content = content.replace(
        '<div class="user-main-content">',
        '<div class="{{ isProf ? \'admin-main-content\' : \'user-main-content\' }}">'
    )

    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)
        
    print(f"Processed: {file_path}")
