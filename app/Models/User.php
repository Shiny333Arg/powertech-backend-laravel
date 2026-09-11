<?php

namespace App\Models;

use App\Enums\ProveedorAuth;
use App\Models\Concerns\NoSeBorraFisicamente;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * A.1 users. Empleados y clientes inician sesion con la misma mecanica, asi
 * que las credenciales viven en una sola tabla. Los datos propios de cada
 * poblacion estan en `empleados` y `clientes`.
 *
 * UN USUARIO NO SE ELIMINA NUNCA. Solo baja logica: toda la trazabilidad del
 * sistema cuelga de esta tabla.
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes, NoSeBorraFisicamente;

    protected $table = 'users';

    protected $fillable = [
        'rol_id', 'usuario', 'email', 'password', 'nombres', 'apellido',
        'proveedor_auth', 'google_id', 'forzar_cambio_password', 'activo',
    ];

    protected $hidden = ['password', 'remember_token', 'google_id'];

    protected function casts(): array
    {
        return [
            'password'               => 'hashed',
            'proveedor_auth'         => ProveedorAuth::class,
            'forzar_cambio_password' => 'boolean',
            'activo'                 => 'boolean',
            'email_verified_at'      => 'datetime',
            'ultimo_acceso_at'       => 'datetime',
        ];
    }

    // ---------------------------------------------------------------
    // Relaciones
    // ---------------------------------------------------------------

    public function rol(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    public function empleado(): HasOne
    {
        return $this->hasOne(Empleado::class, 'user_id');
    }

    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class, 'user_id');
    }

    /** Ventas registradas como vendedor. */
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'usuario_id');
    }

    /** Presupuestos que habilito para cobro. */
    public function ventasValidadas(): HasMany
    {
        return $this->hasMany(Venta::class, 'usuario_valido_id');
    }

    /** Cobros tomados como cajero. */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'usuario_id');
    }

    public function movimientosStock(): HasMany
    {
        return $this->hasMany(MovimientoStock::class, 'usuario_id');
    }

    public function ordenesCreadas(): HasMany
    {
        return $this->hasMany(OrdenCompra::class, 'usuario_creo_id');
    }

    public function ordenesAprobadas(): HasMany
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

    // ---------------------------------------------------------------
    // Autorizacion
    // ---------------------------------------------------------------

    /**
     * `activo` y `deleted_at` son cosas distintas y conviven: se puede
     * suspender el acceso de un empleado sin darlo de baja.
     */
    public function puedeIngresar(): bool
    {
        return $this->activo && $this->deleted_at === null;
    }

    public function tienePermiso(string $clave): bool
    {
        return $this->rol
            ->permisos()
            ->where('clave', $clave)
            ->exists();
    }

    public function esEmpleado(): bool
    {
        return $this->rol->esDeGestion();
    }
}
