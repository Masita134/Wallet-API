<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
        ]);
    }

    private function createUser(): User
    {
        return User::factory()->create([
            'is_admin' => false,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_admin_users(): void
    {
        $response = $this->getJson('/api/v1/admin/users');

        $response->assertUnauthorized();
    }

    public function test_regular_user_cannot_access_admin_users(): void
    {
        $user = $this->createUser();

        $response = $this
            ->actingAs($user, 'api')
            ->getJson('/api/v1/admin/users');

        $response->assertForbidden();
    }

    public function test_admin_can_create_user_with_account(): void
    {
        $admin = $this->createAdmin();

        $payload = [
            'name' => 'Ana Test',
            'email' => 'ana.test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'age' => 28,
            'image' => 'profiles/ana.jpg',
        ];

        $response = $this
            ->actingAs($admin, 'api')
            ->postJson('/api/v1/admin/users', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Usuario creado correctamente.')
            ->assertJsonPath('user.name', 'Ana Test')
            ->assertJsonPath('user.email', 'ana.test@example.com')
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.remember_token');

        $this->assertDatabaseHas('users', [
            'email' => 'ana.test@example.com',
            'is_admin' => false,
            'age' => 28,
            'image' => 'profiles/ana.jpg',
        ]);

        $user = User::where(
            'email',
            'ana.test@example.com'
        )->firstOrFail();

        $this->assertNotNull($user->account);

        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'balance' => '0.00',
            'type' => 'savings',
            'currency' => 'ARS',
        ]);

        $this->assertDatabaseCount('accounts', 2);

        $this->assertSame(
            22,
            strlen($user->account->cbu)
        );

        $this->assertTrue(
            Hash::check('password123', $user->password)
        );
    }

    public function test_admin_cannot_create_user_with_duplicate_email(): void
    {
        $admin = $this->createAdmin();

        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this
            ->actingAs($admin, 'api')
            ->postJson('/api/v1/admin/users', [
                'name' => 'Otro Usuario',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_admin_cannot_create_user_without_valid_data(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin, 'api')
            ->postJson('/api/v1/admin/users', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'password',
            ]);
    }

    public function test_admin_can_list_users_with_pagination(): void
    {
        $admin = $this->createAdmin();

        User::factory()->count(20)->create();

        $response = $this
            ->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/users?per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('per_page', 10)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('total', 21);

        $this->assertCount(
            10,
            $response->json('data')
        );
    }

    public function test_admin_can_sort_users(): void
    {
        $admin = $this->createAdmin();

        User::factory()->create([
            'name' => 'AAA User',
            'email' => 'aaa@example.com',
        ]);

        User::factory()->create([
            'name' => 'ZZZ User',
            'email' => 'zzz@example.com',
        ]);

        $response = $this
            ->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/users?sort=name&order=asc');

        $response->assertOk();

        $names = collect($response->json('data'))
            ->pluck('name')
            ->values();

        $this->assertSame('AAA User', $names->first());
    }

    public function test_admin_can_show_user(): void
    {
        $admin = $this->createAdmin();

        $user = User::factory()->create([
            'name' => 'Usuario Detalle',
            'email' => 'detalle@example.com',
        ]);

        $response = $this
            ->actingAs($admin, 'api')
            ->getJson("/api/v1/admin/users/{$user->id}");

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.name', 'Usuario Detalle')
            ->assertJsonPath('user.email', 'detalle@example.com')
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'account',
                ],
            ])
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.remember_token');
    }

    public function test_admin_can_update_user(): void
    {
        $admin = $this->createAdmin();

        $user = User::factory()->create([
            'name' => 'Nombre Original',
            'email' => 'original@example.com',
        ]);

        $response = $this
            ->actingAs($admin, 'api')
            ->putJson("/api/v1/admin/users/{$user->id}", [
                'name' => 'Nombre Modificado',
                'email' => 'modificado@example.com',
                'age' => 30,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath(
                'user.name',
                'Nombre Modificado'
            )
            ->assertJsonPath(
                'user.email',
                'modificado@example.com'
            )
            ->assertJsonPath(
                'user.age',
                30
            );

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nombre Modificado',
            'email' => 'modificado@example.com',
            'age' => 30,
        ]);
    }

    public function test_admin_can_update_password(): void
    {
        $admin = $this->createAdmin();

        $user = User::factory()->create([
            'password' => 'oldpassword',
        ]);

        $response = $this
            ->actingAs($admin, 'api')
            ->putJson("/api/v1/admin/users/{$user->id}", [
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertOk();

        $this->assertTrue(
            Hash::check(
                'newpassword123',
                $user->fresh()->password
            )
        );

        $response->assertJsonMissingPath('user.password');
    }

    public function test_admin_cannot_update_user_with_existing_email(): void
    {
        $admin = $this->createAdmin();

        $userA = User::factory()->create([
            'email' => 'user-a@example.com',
        ]);

        $userB = User::factory()->create([
            'email' => 'user-b@example.com',
        ]);

        $response = $this
            ->actingAs($admin, 'api')
            ->putJson("/api/v1/admin/users/{$userB->id}", [
                'email' => $userA->email,
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_admin_cannot_change_is_admin_through_user_update(): void
    {
        $admin = $this->createAdmin();

        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $response = $this
            ->actingAs($admin, 'api')
            ->putJson("/api/v1/admin/users/{$user->id}", [
                'name' => 'Usuario Modificado',
                'is_admin' => true,
            ]);

        $response->assertOk();

        $this->assertFalse(
            $user->fresh()->is_admin
        );
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = $this->createAdmin();

        $user = User::factory()->create();

        $response = $this
            ->actingAs($admin, 'api')
            ->deleteJson("/api/v1/admin/users/{$user->id}");

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Usuario eliminado correctamente.'
            );

        $this->assertSoftDeleted(
            'users',
            ['id' => $user->id]
        );
    }

    public function test_deleted_user_cannot_be_found_again(): void
    {
        $admin = $this->createAdmin();

        $user = User::factory()->create();

        $user->delete();

        $response = $this
            ->actingAs($admin, 'api')
            ->getJson("/api/v1/admin/users/{$user->id}");

        $response->assertNotFound();
    }

    public function test_invalid_pagination_is_rejected(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/users?per_page=101');

        $response->assertUnprocessable();
    }

    public function test_invalid_sort_is_rejected(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/users?sort=password');

        $response->assertUnprocessable();
    }
}