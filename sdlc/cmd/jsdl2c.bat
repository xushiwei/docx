@echo off
set AppPath=%~dp0
php "%AppPath%\jspt" "%AppPath%\inc\sdl2c.php" %1 %2 %3 %4
set AppPath=
