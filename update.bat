@echo off 
rem -------------------------------------------------------------------------------------------
rem Update projekt z \\DataServer
rem                                                           Software (c) 2016, Zdeno Sekerak
rem -------------------------------------------------------------------------------------------

echo Update xml projektu
xcopy /E /R /Y \\DataServer\ProNekoho\SekerakZ\xml\*.* %~dp0
pause