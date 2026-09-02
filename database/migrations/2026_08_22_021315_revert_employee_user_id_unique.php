<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if ($this->indexExists('employees', 'employees_user_id_unique')) {
                $table->dropUnique(['user_id']);
            }
            if (Schema::hasColumn('employees', 'device_id')
                && ! $this->indexExists('employees', 'employees_device_id_user_id_unique')) {
                $table->unique(['device_id', 'user_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if ($this->indexExists('employees', 'employees_device_id_user_id_unique')) {
                $table->dropUnique(['device_id', 'user_id']);
            }
            if (! $this->indexExists('employees', 'employees_user_id_unique')) {
                $table->unique('user_id');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'mysql') {
            return DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', $table)
                ->where('index_name', $index)
                ->exists();
        }

        return collect(DB::select("PRAGMA index_list('{$table}')"))
            ->contains(fn (object $row): bool => ($row->name ?? '') === $index);
    }
};
