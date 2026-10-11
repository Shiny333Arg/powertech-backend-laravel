<?php

namespace App\Models;

use App\Enums\AmbitoRol;
use App\Enums\ProveedorAuth;
use App\Models\Concerns\ProtegeContraBorrado;
use App\Models\Concerns\TieneBajaLogica;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Credenciales unificadas de empleados y clientes. Todos ingresan con
 * correo + contraseña; el ambito del rol decide a que aplicacion entran.
 *
 * Nunca se elimina: toda la trazabilidad del sistema cuelga de esta tabla
 * (ventas, cobros, movimientos de stock, aprobaciones de compra).
 *
 * MustVerifyEmail solo tiene efecto donde una ruta usa el middleware
 * 'verified' (la tienda). A los empleados no les afecta: quedan verificados
 * al aceptar la invitacion.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use Notifiable; // mails de invitacion, reset de contraseña y verificacion
    use ProtegeContraBorrado;
    use TieneBajaLogica;

    protected $table = 'users';

    protected $fillable = [
        'rol_id', 'email', 'password', 'nombres', 'apellido',
        'proveedor_auth', 'google_id', 'activo',
        'email_verified_at', 'ultimo_acceso_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password'          => 'hashed',
        'proveedor_auth'    => ProveedorAuth::class,
        'activo'            => 'boolean',
        'email_verified_at' => 'datetime',
        'ultimo_acceso_at'  => 'datetime',
    ];

    // ------------------------------------------------------------------
    // Relaciones
    // ------------------------------------------------------------------

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function empleado(): HasOne
    {
        return $this->hasOne(Empleado::class, 'user_id');
    }

    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class, 'user_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'usuario_id');
    }

    public function ventasValidadas(): HasMany
    {
        return $this->hasMany(Venta::class, 'usuario_valido_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'usuario_id');
    }

    public function movimientosStock(): HasMany
    {
        return $this->hasMany(MovimientoStock::class, 'usuario_id');
    }

    public function ordenesCompraCreadas(): HasMany
    {
        return $this->hasMany(OrdenCompra::class, 'usuario_creo_id');
    }

    public function ordenesCompraAprobadas(): HasMany
    {
        return $this->hasMany(OrdenCompra::class, 'usuario_aprobo_id');
    }

    public function promociones(): HasMany
    {
        return $this->hasMany(Promocion::class, 'creado_por');
    }

    public function carritos(): HasMany
    {
        return $this->hasMany(Carrito::class, 'user_id');
    }

    // ------------------------------------------------------------------
    // Atributos calculados
    // ------------------------------------------------------------------

    /** "Juan Pérez". Reemplaza al $user->name que usan las vistas de Breeze. */
    protected function nombreCompleto(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->nombres} {$this->apellido}"));
    }

    // ------------------------------------------------------------------
    // Permisos y ambito
    // ------------------------------------------------------------------

    /** Lo que no se asigna explicitamente, no se puede hacer. */
    public function tienePermiso(string $clave): bool
    {
        return $this->rol?->permisos->contains('clave', $clave) ?? false;
    }

    public function ambitoRol(): ?AmbitoRol
    {
        return $this->rol?->ambito;
    }

    public function esCliente(): bool
    {
        return $this->ambitoRol() === AmbitoRol::Tienda;
    }

    public function esDeGestion(): bool
    {
        return $this->ambitoRol() === AmbitoRol::Gestion;
    }

    /** Nombre de la ruta de inicio segun el ambito del rol. */
    public function rutaInicio(): string
    {
        return ($this->ambitoRol() ?? AmbitoRol::Tienda)->rutaInicio();
    }

    /**
     * Empleado dado de alta que todavia no eligio su contraseña.
     *
     * En el listado de empleados la columna password no se carga (no tiene
     * sentido traer hashes a una tabla); ahi el controlador agrega el alias
     * 'invitacion_pendiente' calculado por la base, y se usa ese.
     */
    public function invitacionPendiente(): bool
    {
        if (array_key_exists('invitacion_pendiente', $this->attributes)) {
            return (bool) $this->attributes['invitacion_pendiente'];
        }

        return $this->esDeGestion() && $this->password === null;
    }
}
