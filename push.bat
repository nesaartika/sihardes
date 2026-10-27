@echo off
REM Pemakaian:  push.bat "pesan perubahan"
REM Menyimpan semua perubahan lalu mengirimnya ke GitHub.
if "%~1"=="" (
  echo Tulis pesan perubahan, contoh: push.bat "perbaiki rekap"
  exit /b 1
)
git add .
git commit -m "%~1"
git push
