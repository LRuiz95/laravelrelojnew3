<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user for authentication
        $this->user = User::factory()->create(['role' => 'admin']);

        // Create some employees with diverse data for filter tests
        Employee::create([
            'name' => 'Juan Perez',
            'user_id' => 'USER-001',
            'cargo' => 'Gerente',
            'departamento' => 'Ventas',
            'id_campus' => '1',
            'status_actual' => 'A',
            'contrato' => 'Indefinido',
            'nivel' => 'N1',
        ]);
    }

    public function test_employees_returns_200(): void
    {
        $response = $this->actingAs($this->user)->get('/employees');

        // Try to get the exception
        $errorMessage = '';
        if ($response->status() === 500) {
            $content = $response->getContent();
            // Extract error info from the response
            if (preg_match('/<code>([^<]+)<\/code>/', $content, $matches)) {
                $errorMessage = $matches[1];
            }
        }

        $this->assertEquals(200, $response->status(), "Status should be 200 but was 500. Error: $errorMessage");
    }
}