<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

/**
 * Actualiza users.ultimo_acceso_at en cada ingreso (login con contraseña,
 * aceptacion de invitacion, registro y "recordarme").
 *
 * Laravel lo registra solo: descubre los listeners de app/Listeners por el
 * tipo del parametro de handle().
 */
class RegistrarUltimoAcceso
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $user = $event->user;

        // Sin tocar updated_at (entrar al sistema no es modificar la cuenta)
        // y sin disparar eventos del modelo.
        $user->timestamps = false;
        $user->forceFill(['ultimo_acceso_at' => now()])->saveQuietly();
        $user->timestamps = true;
    }
}
