@echo off
if "%1" == "" (
	echo "Usage: doci2htmls <cpp_file>"
	goto exit
	)
set AppPath=%~dp0
php "%AppPath%inc\doci\doci2json.php" %1 | php "%AppPath%\gen_project_htmls.php"
set AppPath=
:exit