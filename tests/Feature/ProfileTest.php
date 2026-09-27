<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_only_his_own_profile(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/v1/profile');

        $response
            ->assertOk()
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->assertJsonStructure(['id', 'name', 'email'])
            ->assertJsonMissingPath('password');
    }

    public function test_profile_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/profile');

        $response
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }

    public function test_profile_rejects_an_invalid_token(): void
        {
            $response = $this->withToken('token-invalido')->getJson('/api/v1/profile');

            $response
                ->assertUnauthorized()
                ->assertJsonStructure(['message']);
        }

        public function test_authenticated_user_can_update_his_own_profile(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withToken($token)->putJson('/api/v1/profile', [
            'name' => 'Nuevo Nombre',
            'email' => 'nuevoemail@example.com',
            'age' => 25,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Perfil actualizado correctamente.',
                'user' => [
                    'id' => $user->id,
                    'name' => 'Nuevo Nombre',
                    'email' => 'nuevoemail@example.com',
                    'age' => 25,
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nuevo Nombre',
            'email' => 'nuevoemail@example.com',
            'age' => 25,
        ]);
    }
    public function test_profile_update_rejects_invalid_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Nombre Original',
            'email' => 'original@example.com',
            'age' => 25,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withToken($token)->putJson('/api/v1/profile', [
            'name' => '',
            'email' => 'email-invalido',
            'age' => 0,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'age',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nombre Original',
            'email' => 'original@example.com',
            'age' => 25,
        ]);
    }
        public function test_authenticated_user_can_update_profile_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $image = UploadedFile::fake()->image('profile.jpg');

        $response = $this->withToken($token)->putJson('/api/v1/profile', [
            'image' => $image,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.image', fn ($value) => str_starts_with($value, 'profiles/'));

        Storage::disk('public')->assertExists($user->fresh()->image);
    }
    public function test_profile_update_rejects_invalid_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'image' => null,
        ]);

        $token = auth('api')->login($user);

        $invalidImage = UploadedFile::fake()->create(
            'document.pdf',
            3000,
            'application/pdf'
        );

        $response = $this->withToken($token)->putJson('/api/v1/profile', [
            'image' => $invalidImage,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'image' => null,
        ]);
    }
    public function test_authenticated_user_can_delete_his_own_profile(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this->withToken($token)->deleteJson('/api/v1/profile');

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Perfil eliminado correctamente.',
            ]);

        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    }
    public function test_profile_delete_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/v1/profile');

        $response
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }
}
