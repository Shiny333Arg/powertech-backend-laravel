<?php

namespace Database\Factories;

use App\Enums\ProveedorAuth;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Por defecto crea un CLIENTE activo, verificado y con contraseña 'password'.
 *
 * Los roles no se fabrican: se buscan por nombre, asi que los tests tienen que
 * correr antes RolSeeder ($this->seed(RolSeeder::class)).
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /** Se hashea una sola vez por corrida: bcrypt es lento a proposito. */
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'rol_id'            => fn () => $this->idDelRol('cliente'),
            'email'             => fake()->unique()->safeEmail(),
            'password'          => static::$password ??= Hash::make('password'),
            'nombres'           => fake()->firstName(),
            'apellido'          => fake()->lastName(),
            'proveedor_auth'    => ProveedorAuth::Local->value,
            'google_id'         => null,
            'activo'            => true,
            'email_verified_at' => now(),
            'remember_token'    => Str::random(10),
        ];
    }

    /** Empleado con el cargo indicado (administrador, administrativo, cajero, vendedor). */
    public function empleado(string $rol = 'vendedor'): static
    {
        return $this->state(fn () => ['rol_id' => $this->idDelRol($rol)]);
    }

    public function cliente(): static
    {
        return $this->state(fn () => ['rol_id' => $this->idDelRol('cliente')]);
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    /** Empleado dado de alta que todavia no eligio su contraseña. */
    public function invitacionPendiente(): static
    {
        return $this->empleado()->state(fn () => [
            'password'          => null,
            'email_verified_at' => null,
            'remember_token'    => null,
        ]);
    }

    private function idDelRol(string $nombre): int
    {
        return Rol::where('nombre', $nombre)->value('id')
            ?? throw new \RuntimeException("UserFactory: no existe el rol '{$nombre}'. Corré RolSeeder antes.");
    }
}
