<?php

namespace App\Http\Controllers\gestion;

use App\Enums\CanalPromocion;
use App\Enums\TipoPromocion;
use App\Http\Requests\PromocionRequest;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * CRUD de promociones, sin delete.
 *
 * La promocion esta referenciada por promociones_aplicadas, que es el registro
 * de por que una linea de venta salio mas barata: borrarla dejaria descuentos
 * sin explicacion. Solo baja logica.
 *
 * Editar una promocion con aplicaciones SI es seguro: promociones_aplicadas
 * guarda el importe_descuento de cada aplicacion, asi que el historial no
 * depende de los valores actuales de esta fila.
 */
class PromocionController extends Controller
{
    private const DIRECTORIO_BANNERS = 'promociones';

    public function __construct()
    {
        // Descomentar cuando exista el login:
        $this->authorizeResource(Promocion::class, 'promocion');
    }

    public function index(Request $request): View
    {
        $promociones = Promocion::query()
            ->with('creadoPor:id,nombres,apellido')
            ->withCount('productos')
            // Rendimiento real: cuantas veces se aplico y cuanto descuento
            // costo. Es para lo que existe promociones_aplicadas.
            ->withCount('aplicaciones')
            ->withSum('aplicaciones as descuento_total', 'importe_descuento')
            ->when($request->filled('q'), fn ($query) => $query
                ->where('nombre', 'like', '%'.$request->string('q')->trim()->value().'%'))
            ->when($request->filled('canal'), fn ($query) => $query->where('canal', $request->query('canal')))
            ->when($request->query('estado') === 'activas', fn ($query) => $query->activos())
            ->when($request->query('estado') === 'inactivas', fn ($query) => $query->inactivos())
            // Vigencia y estado son cosas distintas: una promocion activa puede
            // estar vencida, y una vencida no se desactiva sola.
            ->when($request->query('vigencia') === 'vigentes', fn ($query) => $query->vigentes())
            ->when($request->query('vigencia') === 'programadas', fn ($query) => $query->programadas())
            ->when($request->query('vigencia') === 'vencidas', fn ($query) => $query->vencidas())
            ->orderByDesc('vigencia_desde')
            ->paginate(20)
            ->withQueryString();

        return view('gestion.promotion.index', [
            'promociones' => $promociones,
            'canales'     => CanalPromocion::opciones(),
            'filtros'     => $request->only('q', 'canal', 'estado', 'vigencia'),
        ]);
    }

    public function create(): View
    {
        return view('gestion.promotion.create', [
            'promocion' => new Promocion([
                'tipo'           => TipoPromocion::Porcentaje->value,
                'canal'          => CanalPromocion::Ambos->value,
                'prioridad'      => 0,
                'acumulable'     => false,
                'activo'         => true,
                'vigencia_desde' => now()->startOfDay(),
                'vigencia_hasta' => now()->addMonth()->endOfDay(),
            ]),
            'seleccionados' => [],
        ] + $this->opcionesDeFormulario());
    }

    /**
     * Alta.
     *
     * Promocion y productos en una transaccion: una promocion guardada sin sus
     * productos es una promocion que no descuenta nada y que nadie va a
     * detectar hasta que un cliente reclame.
     */
    public function store(PromocionRequest $request): RedirectResponse
    {
        $promocion = DB::transaction(function () use ($request) {
            $datos = $request->datosDeLaPromocion();

            if ($request->hasFile('banner')) {
                $datos['banner'] = $this->guardarBanner($request->file('banner'));
            }

            // Quien crea el descuento queda asentado: los descuentos son dinero.
            $datos['creado_por'] = $this->usuarioResponsable();

            $promocion = Promocion::create($datos);
            $promocion->productos()->sync($request->productosSeleccionados());

            return $promocion;
        });

        return redirect()
            ->route('gestion.promociones.index')
            ->with('exito', "Se creó la promoción «{$promocion->nombre}».")
            ->with('advertencia', $this->avisoDeConflictos($promocion));
    }

    public function edit(Promocion $promocion): View
    {
        return view('gestion.promotion.edit', [
            'promocion'     => $promocion,
            'seleccionados' => $promocion->productos()->pluck('productos.id')->all(),
        ] + $this->opcionesDeFormulario());
    }

    public function update(PromocionRequest $request, Promocion $promocion): RedirectResponse
    {
        DB::transaction(function () use ($request, $promocion) {
            $datos          = $request->datosDeLaPromocion();
            $bannerAnterior = $promocion->banner;
            $borrarAnterior = false;

            if ($request->hasFile('banner')) {
                $datos['banner'] = $this->guardarBanner($request->file('banner'));
                $borrarAnterior  = true;
            } elseif ($request->boolean('quitar_banner')) {
                $datos['banner'] = null;
                $borrarAnterior  = true;
            }

            $promocion->update($datos);

            // sync() deja exactamente los productos elegidos: agrega los nuevos
            // y quita los que se destildaron, en una sola operacion.
            $promocion->productos()->sync($request->productosSeleccionados());

            if ($borrarAnterior) {
                $this->borrarBanner($bannerAnterior); // recien despues de guardar
            }
        });

        return redirect()
            ->route('gestion.promociones.index')
            ->with('exito', "Se guardaron los cambios en «{$promocion->nombre}».")
            ->with('advertencia', $this->avisoDeConflictos($promocion->refresh()));
    }

    /**
     * Baja logica.
     *
     * Es distinta de que la promocion venza: vencer es que pase la fecha, dar
     * de baja es sacarla de circulacion antes. Las dos cortan la aplicacion del
     * descuento, pero solo la baja es una decision de alguien.
     */
    public function darDeBaja(Promocion $promocion): RedirectResponse
    {
        $promocion->darDeBaja();

        return back()->with('exito', "«{$promocion->nombre}» dejó de aplicarse. Las ventas que ya la usaron conservan su descuento.");
    }

    public function reactivar(Promocion $promocion): RedirectResponse
    {
        $promocion->reactivar();

        $aviso = "«{$promocion->nombre}» vuelve a estar activa.";

        // Reactivar una promocion vencida no la hace aplicar: el motor de
        // precios mira activo Y vigencia. Conviene decirlo acá y no dejar que
        // lo descubran preguntandose por que no descuenta.
        if ($promocion->vigencia_hasta->isPast()) {
            $aviso .= ' Ojo: su vigencia ya terminó, así que no va a aplicarse hasta que le extiendas la fecha.';
        }

        return back()->with('exito', $aviso);
    }

    /**
     * Dos promociones no acumulables, con la MISMA prioridad, vigentes a la vez
     * sobre el mismo producto y en el mismo canal: no hay criterio para decidir
     * cual gana. Es exactamente el caso que prioridad y acumulable existen para
     * evitar, asi que conviene avisarlo al guardar.
     *
     * No bloquea: puede ser intencional mientras una de las dos se vaya a dar
     * de baja enseguida.
     */
    private function avisoDeConflictos(Promocion $promocion): ?string
    {
        if ($promocion->acumulable || ! $promocion->activo) {
            return null;
        }

        $enConflicto = $this->promocionesEnConflicto($promocion);

        if ($enConflicto->isEmpty()) {
            return null;
        }

        $nombres = $enConflicto->pluck('nombre')->implode('», «');

        return "Hay productos alcanzados también por «{$nombres}», con la misma prioridad y sin ser acumulables. "
            .'Mientras las dos estén vigentes, cuál se aplica queda indefinido: cambiá la prioridad de una.';
    }

    private function promocionesEnConflicto(Promocion $promocion): Collection
    {
        $productos = $promocion->productos()->pluck('productos.id');

        if ($productos->isEmpty()) {
            return new Collection;
        }

        return Promocion::query()
            ->whereKeyNot($promocion->getKey())
            ->where('activo', true)
            ->where('acumulable', false)
            ->where('prioridad', $promocion->prioridad)
            // Vigencias que se pisan: una empieza antes de que la otra termine.
            ->where('vigencia_desde', '<=', $promocion->vigencia_hasta)
            ->where('vigencia_hasta', '>=', $promocion->vigencia_desde)
            // Canales que se cruzan: 'ambos' se cruza con cualquiera.
            ->when(
                $promocion->canal !== CanalPromocion::Ambos,
                fn ($query) => $query->whereIn('canal', [
                    $promocion->canal->value,
                    CanalPromocion::Ambos->value,
                ]),
            )
            ->whereHas('productos', fn ($query) => $query->whereIn('productos.id', $productos))
            ->get(['id', 'nombre']);
    }

    /** @return array<string, mixed> */
    private function opcionesDeFormulario(): array
    {
        return [
            'tipos'  => TipoPromocion::opciones(),
            'canales'=> CanalPromocion::opciones(),
            // Solo productos en operacion: promocionar uno dado de baja no
            // tendria efecto, porque no se puede cargar en una venta.
            'productos' => Producto::activos()
                ->with('categoria:id,nombre')
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre', 'precio', 'categoria_id']),
            'categorias' => Categoria::activos()->orderBy('nombre')->get(['id', 'nombre']),
        ];
    }

    /**
     * TEMPORAL: mientras no exista el login, auth()->id() es null y creado_por
     * es NOT NULL. Cae en el administrador inicial para poder probar.
     * Cuando esté la autenticación, esto es simplemente auth()->id().
     */
    private function usuarioResponsable(): int
    {
        return auth()->id() ?? User::whereHas('rol', fn ($query) => $query->where('nombre', 'administrador'))
            ->orderBy('id')
            ->value('id');
    }

    private function guardarBanner(UploadedFile $archivo): string
    {
        return $archivo->store(self::DIRECTORIO_BANNERS, 'public');
    }

    private function borrarBanner(?string $ruta): void
    {
        if ($ruta && Storage::disk('public')->exists($ruta)) {
            Storage::disk('public')->delete($ruta);
        }
    }
}
