<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Mail de bienvenida del empleado, con el enlace para elegir su contraseña.
 *
 * El enlace es una URL firmada: no se puede alterar (cambiar el id del
 * usuario invalida la firma) y vence sola. No usa la tabla de tokens de
 * "olvidé mi contraseña" a proposito: ese broker vence a los 60 minutos, y
 * compartir la tabla haria que un token de reset valiera como invitacion.
 *
 * Se envia en el momento. Para mandarlo en segundo plano, agregar
 * "implements ShouldQueue" y tener corriendo "php artisan queue:work".
 */
class InvitacionEmpleado extends Notification
{
    use Queueable;

    public const HORAS_DE_VIGENCIA = 72;

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $enlace = URL::temporarySignedRoute(
            'invitacion.show',
            now()->addHours(self::HORAS_DE_VIGENCIA),
            ['user' => $notifiable->getKey()],
        );

        return (new MailMessage())
            ->subject('Bienvenido a PowerTech: activá tu cuenta')
            ->greeting("¡Hola, {$notifiable->nombres}!")
            ->line('Te dieron de alta en el sistema de gestión de PowerTech.')
            ->line("Para empezar, elegí tu contraseña. Después vas a ingresar con este correo: {$notifiable->email}")
            ->action('Elegir mi contraseña', $enlace)
            ->line('El enlace vence en '.self::HORAS_DE_VIGENCIA.' horas y sirve una sola vez.')
            ->line('Si se vence, pedile a un administrativo que te lo reenvíe, o usá "¿Olvidaste tu contraseña?" en la pantalla de ingreso.');
    }
}
