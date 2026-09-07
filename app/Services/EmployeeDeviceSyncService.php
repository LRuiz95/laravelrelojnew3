<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\EmployeeCredentialPackage;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Fingerprint;
use Illuminate\Support\Collection;

final class EmployeeDeviceSyncService
{
    public function packageFor(Employee $employee, Device $device): EmployeeCredentialPackage
    {
        $enrollment = $device->employees()->whereKey($employee->getKey())->first();
        $sourceEnrollment = $enrollment ?: $employee->devices()->first();
        $fingerprints = $employee->fingerprints()
            ->get(['id', 'employee_id', 'device_id', 'finger', 'template', 'template_hash', 'created_at', 'updated_at'])
            ->sortBy(fn (Fingerprint $fingerprint): int => $fingerprint->device_id === $device->id
                ? 0
                : ($fingerprint->device_id === null ? 1 : 2))
            ->unique('finger')
            ->values();

        return new EmployeeCredentialPackage(
            employee: $employee,
            fingerprints: $fingerprints,
            password: $sourceEnrollment?->pivot->password,
            cardNumber: $sourceEnrollment?->pivot->card_number,
            role: (int) ($sourceEnrollment?->pivot->role ?? 0),
        );
    }

    public function synchronize(EmployeeCredentialPackage $package, Device $device): array
    {
        $employee = $package->employee;
        $service = new ZktecoService($device);
        $enrollment = $device->employees()->whereKey($employee->getKey())->first();

        $userSaved = $service->setUser([
            'uid' => $enrollment?->pivot->device_uid,
            'user_id' => $employee->user_id,
            'name' => $employee->name,
            'password' => $package->password ?: $device->password,
            'role' => $package->role,
            'card_number' => $package->cardNumber,
        ]);

        if (! $userSaved) {
            return ['user' => false, 'fingerprints' => 0, 'total_fingerprints' => $package->fingerprintCount()];
        }

        $enrollment = $device->employees()->whereKey($employee->getKey())->first();
        if (! $enrollment || $package->fingerprints->isEmpty()) {
            return ['user' => true, 'fingerprints' => 0, 'total_fingerprints' => $package->fingerprintCount()];
        }

        $fingerprintsUploaded = $service->uploadFingerprints($employee)
            ? $package->fingerprintCount()
            : 0;

        return [
            'user' => true,
            'fingerprints' => $fingerprintsUploaded,
            'total_fingerprints' => $package->fingerprintCount(),
        ];
    }
}
