<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Account;

class FixedTermSimulationTest extends TestCase
{
   use RefreshDatabase; //Sirve para reiniciar la base de datos de prueba en cada ejecución

   public function test_simulacion_exitosa_y_saldo_intacto()
   {
    /** @var \App\Models\User $user */
        $user = User::factory()->create();
        
        $account = $user->account;
        $account->balance = 1000.50;
        $account->save();

        $payload = [
            'amount' => 5000,
            'duration' => 30,
        ];

        $response = $this->actingAs($user, 'api')->postJson('/api/v1/investments/fixed-term/simulate', $payload);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'creation_date',
                     'end_date',
                     'amount_invested',
                     'interest_earned',
                     'total_to_collect',
                     'details' => ['tna', 'formula'],
                 ]);

        $this->assertEquals(5000, $response->json('amount_invested'));
        $this->assertEquals(123.29, $response->json('interest_earned'));
        $this->assertEquals(5123.29, $response->json('total_to_collect'));

        $this->assertEquals(1000.50, $account->fresh()->balance);
   }

   public function test_rechaza_datos_invalidos_con_422()
   {
    /** @var \App\Models\User $user */
    $user = User::factory()->create();

    $payload = [
        'amount' => -100,
        'duration' => 15,
    ];

    $response = $this->actingAs($user, 'api')->postJson('/api/v1/investments/fixed-term/simulate', $payload);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['amount', 'duration']);
   }

   public function test_usuario_no_autenticado_recibe_401()
   {
    $payload = [
        'amount' => 1000,
        'duration' => 30,
    ];

    $response = $this->postJson('/api/v1/investments/fixed-term/simulate', $payload);

    $response->assertStatus(401);
   }
}
