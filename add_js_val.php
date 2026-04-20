<?php

// Function to inject JS before {% endblock %}
function injectJs($filePath, $jsCode) {
    if (!file_exists($filePath)) return;
    $content = file_get_contents($filePath);
    
    // Check if script is already injected
    if (strpos($content, 'function runValidation') !== false) {
        return;
    }

    $replacement = $jsCode . "\n{% endblock %}";
    // Replace the very last closing endblock body
    $content = preg_replace('/\{\%\s*endblock\s*\%\}\s*$/', $replacement, $content);
    
    file_put_contents($filePath, $content);
    echo "Added validation to $filePath\n";
}

// Daily Routine Task JS
$dailyRoutineJs = <<<HTML
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const titleInput = document.querySelector('input[name$="[title]"]');
                if (titleInput) {
                    const title = titleInput.value.trim();
                    if (title.length < 3) {
                        e.preventDefault();
                        alert('Erreur de saisie: Le titre de la tâche doit contenir au moins 3 caractères.');
                        titleInput.focus();
                        titleInput.style.border = '2px solid #dc3545';
                        return false;
                    }
                }
                function runValidation() {} // marker
            });
        }
    });
</script>
HTML;

injectJs('templates/daily_routine_task/new.html.twig', $dailyRoutineJs);
injectJs('templates/daily_routine_task/edit.html.twig', $dailyRoutineJs);


// Wellbeing Tracker JS
$wellbeingJs = <<<HTML
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const moodInput = document.querySelector('select[name$="[mood]"]') || document.querySelector('input[name$="[mood]"]');
                const stressInput = document.querySelector('select[name$="[stress]"]') || document.querySelector('input[name$="[stress]"]');
                const energyInput = document.querySelector('select[name$="[energy]"]') || document.querySelector('input[name$="[energy]"]');
                const sleepInput = document.querySelector('input[name$="[sleepHours]"]');

                if (moodInput) {
                    const mood = parseInt(moodInput.value);
                    if (isNaN(mood) || mood < 1 || mood > 5) {
                        e.preventDefault();
                        alert('Erreur de saisie: L\'humeur doit être un nombre entre 1 et 5.');
                        return false;
                    }
                }
                if (stressInput) {
                    const stress = parseInt(stressInput.value);
                    if (isNaN(stress) || stress < 1 || stress > 5) {
                        e.preventDefault();
                        alert('Erreur de saisie: Le niveau de stress doit être un nombre entre 1 et 5.');
                        return false;
                    }
                }
                if (energyInput) {
                    const energy = parseInt(energyInput.value);
                    if (isNaN(energy) || energy < 1 || energy > 5) {
                        e.preventDefault();
                        alert('Erreur de saisie: Le niveau d\'énergie doit être un nombre entre 1 et 5.');
                        return false;
                    }
                }
                if (sleepInput) {
                    const sleep = parseFloat(sleepInput.value);
                    if (isNaN(sleep) || sleep < 0 || sleep > 24) {
                        e.preventDefault();
                        alert('Erreur de saisie: Les heures de sommeil doivent être comprises entre 0 et 24.');
                        return false;
                    }
                }
                function runValidation() {} // marker
            });
        }
    });
</script>
HTML;

injectJs('templates/wellbeing_tracker/new.html.twig', $wellbeingJs);
injectJs('templates/wellbeing_tracker/edit.html.twig', $wellbeingJs);

?>
