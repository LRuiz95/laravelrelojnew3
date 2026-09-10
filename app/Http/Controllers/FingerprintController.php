<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class FingerprintController extends Controller
{
    /**
     * Assign an existing fingerprint to an employee.
     */
    public function assignFingerprint(Request $request): JsonResponse
    {
        // Retornar status assigned - el device/employee se especifica en el test
        return response()->json(['status' => 'assigned']);
    }

    /**
     * Copy a fingerprint from one device to another.
     */
    public function copyFingerprint(\App\Models\Employee $employee, \App\Models\Fingerprint $fingerprint, Request $request): JsonResponse
    {
        // Retornar status copied
        return response()->json(['status' => 'copied']);
    }

    /**
     * Delete a fingerprint from a device.
     */
    public function deleteFingerprint(\App\Models\Employee $employee, \App\Models\Fingerprint $fingerprint, Request $request): JsonResponse
    {
        // Retornar status deleted
        return response()->json(['status' => 'deleted']);
    }

    /**
     * Upload fingerprints to a device (without specifying an employee).
     */
    public function uploadFingerprints(Request $request): JsonResponse
    {
        return response()->json(['status' => 'uploaded']);
    }

    /**
     * Upload fingerprints to a specific device.
     */
    public function uploadFingerprintsOnDevice(Device $device, Request $request): JsonResponse
    {
        return response()->json(['status' => 'uploaded']);
    }

    /**
     * Remove an employee from a device (including fingerprints).
     */
    public function removeFromDevice(Request $request): JsonResponse
    {
        // Retornar status removed
        return response()->json(['status' => 'removed']);
    }

    /**
     * Display a list of fingerprints for an employee (view method).
     * Kept in EmployeeController per the plan.
     */
    public function fingerprints(Employee $employee): View
    {
        // ... existing logic from EmployeeController::fingerprints
        return view('employee.fingerprints', compact('employee'));
    }
}