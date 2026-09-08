@echo off
cd C:\xampp\htdocs\laravelrelojnew
php artisan route:list > routes_output.txt 2>&1
findstr /i "sync-fingerprint employees" routes_output.txt