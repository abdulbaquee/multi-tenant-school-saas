<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'role_id' => $this->systemRoleId(),
            'school_id' => null,
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->optional()->numerify('9#########'),
            'status' => 'active',
            'last_login_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    private function systemRoleId(): int
    {
        $roleId = DB::table('roles')->where('code', 'super_admin')->value('id');

        if ($roleId) {
            return (int) $roleId;
        }

        return (int) DB::table('roles')->insertGetId([
            'name' => 'Super Admin',
            'code' => 'super_admin',
            'description' => 'Platform administrator with authorized system-wide access.',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
