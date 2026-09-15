<?php

namespace App\Models;

use App\Enums\AmbitoRol;
use App\Enums\ProveedorAuth;
use App\Models\Concerns\ProtegeContraBorrado;
use App\Models\Concerns\TieneBajaLogica;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Credenciales unificadas de empleados y clientes.
 *
 * Nunca se elimina: toda la trazabilidad del sistema cuelga de esta tabla
 * (ventas, cobros, movimientos de stock, aprobaciones de compra).
 */
class User extends Authenticatable
{
    use ProtegeContraBorrado;
    use TieneBajaLogica;

    protected $table = 'users';

    protected $fillable = [
        'rol_id', 'usuario', 'email', 'password', 'nombres', 'apellido',
        'proveedor_auth', 'google_id', 'forzar_cambio_password', 'activo',
        'email_verified_at', 'ultimo_acceso_at',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'password'               => 'hashed',
        'proveedor_auth'         => ProveedorAuth::class,
        'forzar_cambio_password' => 'boolean',
        'activo'                 => 'boolean',
        'email_verified_at'      => 'datetime',
        'ultimo_acceso_at'       => 'datetime',
    ];

    /**
     * La tabla no tiene remember_token: devolver null desactiva el
     * "recordarme" de Laravel sin romper el guard de sesion.
     */
    public function getRememberTokenName(): ?string
    {
        return null;
    }

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

    /** Lo que no se asigna explicitamente, no se puede hacer. */
    public function tienePermiso(string $clave): bool
    {
        return $this->rol?->permisos->contains('clave', $clave) ?? false;
    }

    public function esCliente(): bool
    {
        return $this->rol?->ambito === AmbitoRol::Tienda;
    }
}
