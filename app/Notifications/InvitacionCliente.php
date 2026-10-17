<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Mail de bienvenida del cliente dado de alta por un empleado, con el enlace
 * para elegir su contraseña.
 *
 * Es gemela de InvitacionEmpleado y usa el mismo mecanismo: una URL firmada,
 * que no se puede alterar (cambiar el id del usuario invalida la firma) y que
 * vence sola. Comparten la ruta 'invitacion.show'.
 *
 * Son dos clases y no una con un if porque lo que cambia es TODO el texto. El
 * empleado ya sabe que lo dieron de alta; el cliente, en cambio, puede estar
 * recibiendo este correo sin haber pedido nada mas que una factura en el
 * mostrador, y necesita que le expliquen de donde salio la cuenta y que no
 * esta obligado a usarla.
 *
 * Se envia en el momento. Para mandarlo en segundo plano, agregar
 * "implements ShouldQueue" y tener corriendo "php artisan queue:work".
 */
class InvitacionCliente extends Notification
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
            ->subject('Tu cuenta de PowerTech: elegí tu contraseña')
            ->greeting("¡Hola, {$notifiable->nombres}!")
            ->line('Te creamos una cuenta en PowerTech desde el local. Con ella vas a poder ver '
                .'tus compras y tus facturas, y comprar en la tienda online.')
            ->line("Para activarla, elegí tu contraseña. Después vas a ingresar con este correo: {$notifiable->email}")
            ->action('Elegir mi contraseña', $enlace)
            ->line('El enlace vence en '.self::HORAS_DE_VIGENCIA.' horas y sirve una sola vez.')
            ->line('Si se te vence, usá "¿Olvidaste tu contraseña?" en la pantalla de ingreso, o pedinos '
                .'que te lo reenviemos.')
            ->salutation('Gracias por tu compra, PowerTech.');
    }
}