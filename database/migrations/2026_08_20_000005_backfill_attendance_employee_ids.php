<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('attendances')
            ->whereNull('employee_id')
            ->orderBy('id')
            ->chunkById(500, function ($attendances): void {
                $userIds = $attendances->pluck('user_id')->map(fn ($userId) => (string) $userId)->unique();
                $employees = DB::table('employees')
                    ->whereIn('user_id', $userIds)
                    ->pluck('id', 'user_id');

                foreach ($attendances as $attendance) {
                    $employeeId = $employees[(string) $attendance->user_id] ?? null;
                    if ($employeeId) {
                        DB::table('attendances')
                            ->where('id', $attendance->id)
                            ->update(['employee_id' => $employeeId]);
                    }
                }
            });
    }

    public function down(): void
    {
        // La relación reconstruida no debe eliminarse al revertir la migración.
    }
};
