@echo off
setlocal enabledelayedexpansion
echo Starting WebSocket Server...
echo.
echo IMPORTANT: Keep this window open for real-time updates to work
echo.
cd /d "%~dp0"

REM Check if server is already running
netstat -an | find "8080" | find "LISTENING" >nul
if errorlevel 1 goto :start_server

echo WARNING: Port 8080 is already in use!
echo.
echo Attempting to find and close the process using port 8080...
echo.

set FOUND=0
netstat -ano | findstr ":8080" | findstr "LISTENING" > "%TEMP%\port8080.tmp"
if exist "%TEMP%\port8080.tmp" (
    for /f "tokens=5" %%a in ('type "%TEMP%\port8080.tmp"') do (
        set PID=%%a
        if not "!PID!"=="" (
            echo Found process with PID: !PID!
            echo Checking if it's a PHP process...
            
            tasklist /FI "PID eq !PID!" 2>nul | find "php.exe" >nul
            if !errorlevel! equ 0 (
                echo This appears to be a PHP/WebSocket server process.
                echo Terminating process !PID!...
                taskkill /PID !PID! /F >nul 2>&1
                if !errorlevel! equ 0 (
                    echo Successfully terminated the process.
                    set FOUND=1
                ) else (
                    echo Failed to terminate the process. Please close it manually.
                )
            ) else (
                echo WARNING: Port 8080 is being used by a non-PHP process (PID: !PID!).
                echo This might be another application. Please close it manually.
            )
            goto :check_done
        )
    )
    del "%TEMP%\port8080.tmp" >nul 2>&1
)

:check_done
if !FOUND! equ 1 (
    echo Waiting 2 seconds for port to be released...
    timeout /t 2 /nobreak >nul
    goto :start_server
) else (
    echo Could not automatically resolve the port conflict.
    echo Please close any application using port 8080 and try again.
    pause
    exit /b 1
)

:start_server

REM Clear old messages from queue
if exist message_queue.json (
    echo Clearing old messages from queue...
    echo [] > message_queue.json
)

php server.php
pause
