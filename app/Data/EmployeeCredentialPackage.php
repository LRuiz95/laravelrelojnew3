<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Employee;
use App\Models\Fingerprint;
use Illuminate\Support\Collection;

final class EmployeeCredentialPackage
{
    /**
     * @param Collection<int, Fingerprint> $fingerprints
     */
    public function __construct(
        public Employee $employee,
        public Collection $fingerprints,
        public ?string $password,
        public ?string $cardNumber,
        public int $role,
    ) {}

    public function fingerprintCount(): int
    {
        return $this->fingerprints->count();
    }
}
