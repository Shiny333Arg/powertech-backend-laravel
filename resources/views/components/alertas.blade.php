{{--
    Mensajes flash de una sola vez. Uso: <x-alertas />

    Lee cuatro claves de sesion:
      status  lo que devuelven los controladores de Breeze (a veces un
              codigo, como 'password-updated', que aca se traduce)
      exito   operacion terminada
      info    aviso sin error (p. ej. "le enviamos la invitacion")
      error   la operacion no se pudo hacer

    Los errores de validacion NO van aca: cada campo muestra el suyo.
--}}
@php
    $codigos = [
        'password-updated'       => 'Tu contraseña se actualizó.',
        'profile-updated'        => 'Tus datos se guardaron.',
        'verification-link-sent' => 'Te enviamos un enlace para confirmar tu correo. Revisá tu bandeja de entrada.',
    ];

    $alertas = [];

    if ($status = session('status')) {
        $alertas[] = ['success', $codigos[$status] ?? $status];
    }

    foreach (['exito' => 'success', 'info' => 'info', 'error' => 'danger'] as $clave => $tipo) {
        if ($mensaje = session($clave)) {
            $alertas[] = [$tipo, $mensaje];
        }
    }
@endphp

@foreach ($alertas as [$tipo, $mensaje])
    <div class="alert alert-{{ $tipo }}" role="{{ $tipo === 'danger' ? 'alert' : 'status' }}">
        {{ $mensaje }}
    </div>
@endforeach
