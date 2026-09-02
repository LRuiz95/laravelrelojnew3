<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\OperationsController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware('guest')->name('login.store');
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('kpis/json', [DashboardController::class, 'kpisJson'])->name('dashboard.kpisJson');

    Route::prefix('devices')->name('devices.')->group(function () {
        Route::get('/', [DeviceController::class, 'index'])->name('index');
        Route::get('/create', [DeviceController::class, 'create'])->middleware('admin')->name('create');
        Route::post('/', [DeviceController::class, 'store'])->middleware('admin')->name('store');
        Route::get('/{device}', [DeviceController::class, 'show'])->name('show');
        Route::get('/{device}/sync-status', [DeviceController::class, 'syncStatus'])->name('sync-status');
        Route::get('/{device}/refresh-data', [DeviceController::class, 'refreshData'])->name('refresh-data');
        Route::get('/{device}/progress', [DeviceController::class, 'progress'])->name('progress');
        Route::get('/{device}/edit', [DeviceController::class, 'edit'])->middleware('admin')->name('edit');
        Route::put('/{device}', [DeviceController::class, 'update'])->middleware('admin')->name('update');
        Route::delete('/{device}', [DeviceController::class, 'destroy'])->middleware('admin')->name('destroy');

        Route::post('/{device}/check-status', [DeviceController::class, 'checkStatus'])->name('check-status');
        Route::middleware('admin')->group(function () {
            Route::post('/deduplicate', [DeviceController::class, 'deduplicate'])->name('deduplicate');
            Route::post('/{device}/sync-users', [DeviceController::class, 'syncUsers'])->name('sync-users');
            Route::post('/{device}/sync-fingerprints', [DeviceController::class, 'syncFingerprints'])->name('sync-fingerprints');
            Route::post('/{device}/sync-attendances', [DeviceController::class, 'syncAttendances'])->name('sync-attendances');
            Route::post('/{device}/sync-all', [DeviceController::class, 'syncAll'])->name('sync-all');
            Route::post('/{device}/employees/{employee}/upload-fingerprints', [EmployeeController::class, 'uploadFingerprintsOnDevice'])->name('employees.upload-fingerprints');
            Route::delete('/{device}/employees/{employee}', [EmployeeController::class, 'removeFromDevice'])->name('employees.remove');
            Route::post('/{device}/set-time', [DeviceController::class, 'setTime'])->name('set-time');
            Route::post('/{device}/sync-now', [DeviceController::class, 'syncNow'])->name('sync-now');
            Route::post('/{device}/clear-attendance', [DeviceController::class, 'clearAttendance'])->name('clear-attendance');
            Route::post('/{device}/restore', [DeviceController::class, 'restore'])->name('restore');
        });
    });

    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/create', [EmployeeController::class, 'create'])->middleware('admin')->name('create');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->middleware('admin')->name('edit');
        Route::middleware('admin')->group(function () {
            Route::post('/', [EmployeeController::class, 'store'])->name('store');
            Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
            Route::post('/{employee}/upload-fingerprints', [EmployeeController::class, 'uploadFingerprints'])->name('upload-fingerprints');
            Route::post('/{employee}/assign-fingerprint', [EmployeeController::class, 'assignFingerprint'])->name('assign-fingerprint');
            Route::post('/{employee}/fingerprints/{fingerprint}/copy', [EmployeeController::class, 'copyFingerprint'])->name('copy-fingerprint');
            Route::delete('/{employee}/fingerprints/{fingerprint}', [EmployeeController::class, 'deleteFingerprint'])->name('delete-fingerprint');
            Route::post('/{employee}/card', [EmployeeController::class, 'updateCard'])->name('update-card');
            Route::post('/{employee}/enroll-device', [EmployeeController::class, 'enrollOnDevice'])->name('enroll-device');
            Route::post('/{employee}/sync-devices', [EmployeeController::class, 'syncToDevices'])->name('sync-devices');
            Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
        });
    });

    Route::get('/fingerprints', [EmployeeController::class, 'fingerprints'])->name('fingerprints.index');

    Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendances.index');
    Route::get('/attendances/export', [AttendanceController::class, 'export'])->name('attendances.export');
    Route::get('/attendances/print', [AttendanceController::class, 'print'])->name('attendances.print');
    Route::get('/sync-queue', [OperationsController::class, 'queue'])->middleware('admin')->name('operations.queue');
    Route::get('/sync-queue/data', [OperationsController::class, 'queueData'])->middleware('admin')->name('operations.queue.data');
    Route::post('/sync-queue/{sync}/cancel', [OperationsController::class, 'cancel'])->middleware('admin')->name('operations.cancel');
    Route::post('/sync-queue/{sync}/retry', [OperationsController::class, 'retry'])->middleware('admin')->name('operations.retry');
    Route::delete('/sync-queue/{sync}', [OperationsController::class, 'delete'])->middleware('admin')->name('operations.delete');
    Route::get('/notifications', [OperationsController::class, 'notifications'])->name('operations.notifications');
});
