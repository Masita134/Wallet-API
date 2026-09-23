<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Movement;

class AdminMovementTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        /** @var \App\Models\User $admin */
        $admin = User::factory()->create(['is_admin' => true]);
        return $admin;
    }

    private function createRegularUser(): User
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['is_admin' => false]);
        return $user;
    }

    public function test_usuario_no_autorizado_o_invitado_recibe_error()
    {
        $user = $this->createRegularUser();

        // 1. Invitado sin token -> 401
        $this->getJson('/api/v1/admin/movements')
             ->assertStatus(401);

        // 2. Usuario común sin permisos de administrador -> 403
        $this->actingAs($user, 'api')
             ->getJson('/api/v1/admin/movements')
             ->assertStatus(403);
    }

    public function test_admin_puede_listar_movimientos_paginados_y_filtrados()
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();
        $account = $user->account;

        Movement::factory()->create([
            'account_id' => $account->id,
            'type' => 'deposit',
            'amount' => 500.00,
        ]);

        $response = $this->actingAs($admin, 'api')
                         ->getJson('/api/v1/admin/movements?account_id=' . $account->id);
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'account_id', 'type', 'amount', 'account']
                    ],
                    'current_page',
                    'total'
                 ]);
    }

    public function test_admin_puede_crear_movimiento_sin_alterar_saldo()
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();
        $account = $user->account;
        $account->balance = 1000.00;
        $account->save();

        $payload = [
            'account_id' => $account->id,
            'type' => 'deposit',
            'amount' => 250.00,
        ];

        $response = $this->actingAs($admin, 'api')
                         ->postJson('/api/v1/admin/movements', $payload);
        
        $response->assertStatus(201)
                 ->assertJsonFragment([
                    'account_id' => $account->id,
                    'type' => 'deposit',
                 ]);

        $this->assertEquals(1000.00, $account->fresh()->balance);
    }

    public function test_admin_puede_actualizar_movimiento_sin_alterar_saldo()
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();
        $account = $user->account;
        $account->balance = 1000.00;
        $account->save();

        $movement = Movement::factory()->create([
            'account_id' => $account->id,
            'type' => 'deposit',
            'amount' => 100.00
        ]);

        $payload = [
            'account_id' => $account->id,
            'type' => 'transfer_in',
            'amount' => 300.00,
        ];

        $response = $this->actingAs($admin, 'api')
                         ->putJson("/api/v1/admin/movements/{$movement->id}", $payload);
                        
        $response->assertStatus(200)
                 ->assertJsonFragment([
                    'type' => 'transfer_in',
                 ]);

        $this->assertEquals(1000.00, $account->fresh()->balance);
    }

    public function test_admin_puede_eliminar_movimiento_sin_alterar_saldo()
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();
        $account = $user->account;
        $account->balance = 1000.00;
        $account->save();

        $movement = Movement::factory()->create([
            'account_id' => $account->id,
            'type' => 'deposit',
            'amount' => 100.00,
        ]);

        $response = $this->actingAs($admin, 'api')
                         ->deleteJson("/api/v1/admin/movements/{$movement->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('movements', ['id' => $movement->id]);

        $this->assertEquals(1000.00, $account->fresh()->balance);
    }

    public function test_rechaza_datos_invalidos_con_422()
    {
        $admin = $this->createAdminUser();

        $payload = [
            'account_id' => 999999,
            'type' => 'tipo_invalido',
            'amount' => -50,
        ];

        $response = $this->actingAs($admin, 'api')
                         ->postJson('/api/v1/admin/movements', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['account_id', 'type', 'amount']);
    }
}