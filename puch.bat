@echo off
echo ==============================
echo    AUTO GIT PUSH - CREX PROJECT
echo ==============================

:: Demande si tu veux un message personnalisé
set /p choice="Voulez-vous écrire un message de commit ? (o/n) : "

if /i "%choice%"=="o" (
    set /p msg="Message du commit : "
) else (
    :: Message automatique avec date et heure
    for /f "tokens=1-5 delims=/: " %%d in ("%date% %time%") do (
        set msg=Auto-commit du %%d-%%e-%%f %%g:%%h
    )
)

:: Ajouter tous les fichiers
git add .

:: Commit avec le message
git commit -m "%msg%"

:: Choix de la branche
set /p branch="Nom de la branche (laisser vide pour master) : "
if "%branch%"=="" set branch=master

:: Pousser sur GitHub
git push -u origin %branch%

echo ==============================
echo  Modification envoyée avec succès !
echo  Message de commit : %msg%
echo ==============================
pause
