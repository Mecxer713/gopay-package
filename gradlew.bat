@echo off
REM Forwarding Gradle wrapper for Android SDK
set ROOT_DIR=%~dp0
call "%ROOT_DIR%android-sdk\gradlew.bat" -p "%ROOT_DIR%android-sdk" %*
