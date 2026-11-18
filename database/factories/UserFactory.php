<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_code' => fake()->unique()->bothify('MHS-#####'),
            'name' => fake()->name(),
            'password' => static::$password ??= Hash::make(config('app.demo_user_password') ?? Str::random(40)),
            'role' => UserRole::MAHASISWA,
            'unit_or_prodi' => fake()->randomElement(['Teknologi rekayasa perangkat lunak', 'Komputer jaringan', 'Media grafis']),
            'is_active' => true,
            'must_change_password' => true,
            'is_dorm_resident' => false,
            'remember_token' => Str::random(10),
        ];
    }
}
