@echo off
set "PY_CMD=python"
where %PY_CMD% >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    set "PY_CMD=py"
    where %PY_CMD% >nul 2>nul
    if %ERRORLEVEL% NEQ 0 (
        echo Erreur : Python ou 'py' non trouve. Veuillez installer Python.
        pause
        exit /b 1
    )
)
echo Utilisation de : %PY_CMD%
%PY_CMD% -m pip install -r requirements.txt
%PY_CMD% chatbot_api.py
pause
