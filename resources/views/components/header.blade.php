{{--
  Barra de la aplicación de gestión.

  Cada sección se muestra solo si el cargo del empleado tiene permiso para
  verla (@can contra las policies, que consultan rol_permiso).

  Productos funciona como un menú desplegable que contiene:
  - Productos
  - Categorías
  - Movimientos de stock

  Usuarios contiene:
  - Empleados
  - Clientes

  Pedidos contiene:
  - Compras
  - Proveedores

  Ojo: esconder el enlace no protege la pantalla. Eso lo hacen las policies en
  los controladores (authorizeResource), que todavía están comentadas.
--}}

@php
$usuario = auth()->user();

// [texto, ruta, patrón para marcar la sección activa, modelo de la policy
// (null = sin restricción)]
$secciones = [
['Inicio', 'gestion.home', 'gestion.home', null],
['Ventas', 'gestion.ventas.index', 'gestion.ventas.*', null],
['Promociones', 'gestion.promociones.index', 'gestion.promociones.*', \App\Models\Promocion::class],
];
@endphp


<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">

            <a class="navbar-brand d-flex align-items-center gap-2"
                href="{{ route('gestion.home') }}">

                <img src="{{ asset('images/Logo.png') }}"
                    alt=""
                    height="40"
                    class="rounded">

                <span class="fw-bold fs-5">POWERTECH</span>
            </a>


            {{-- Botón hamburguesa --}}
            <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarGestion"
                aria-controls="navbarGestion"
                aria-expanded="false"
                aria-label="Abrir menú">

                <span class="navbar-toggler-icon"></span>
            </button>


            {{-- Menú colapsable --}}
            <div class="collapse navbar-collapse" id="navbarGestion">

                <ul class="navbar-nav ms-auto gap-lg-2">


                    {{-- ==================================================
                         SECCIONES PRINCIPALES
                         ================================================== --}}

                    @foreach ($secciones as [$texto, $ruta, $patron, $modelo])

                    @if ($modelo === null || $usuario->can('viewAny', $modelo))

                    @php
                    $activa = request()->routeIs($patron);
                    @endphp

                    <li class="nav-item px-2">

                        <a class="nav-link rounded fw-bold text-white @if ($activa) active @endif"
                            href="{{ route($ruta) }}"
                            @if ($activa) aria-current="page" @endif>

                            {{ $texto }}

                        </a>

                    </li>

                    @endif

                    @endforeach


                    {{-- ==================================================
                         PRODUCTOS
                         ================================================== --}}

                    @php
                    $productosActivos =
                    request()->routeIs('gestion.items.*') ||
                    request()->routeIs('gestion.categorias.*') ||
                    request()->routeIs('gestion.stock.*');
                    @endphp

                    @if (
                    $usuario->can('viewAny', \App\Models\Producto::class) ||
                    $usuario->can('viewAny', \App\Models\Categoria::class)
                    )

                    <li class="nav-item dropdown px-2">

                        <a class="nav-link rounded dropdown-toggle fw-bold text-white
                                @if ($productosActivos) active @endif"
                            href="#"
                            id="productosDropdown"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">

                            Productos

                        </a>


                        <ul class="dropdown-menu dropdown-menu-end bg-primary"
                            aria-labelledby="productosDropdown">

                            {{-- Productos --}}
                            @can('viewAny', \App\Models\Producto::class)

                            <li>
                                <a class="dropdown-item fw-bold text-white
                                            @if (request()->routeIs('gestion.items.*')) active @endif"
                                    href="{{ route('gestion.items.index') }}">

                                    Stock

                                </a>
                            </li>

                            @endcan


                            {{-- Categorías --}}
                            @can('viewAny', \App\Models\Categoria::class)

                            <li>
                                <a class="dropdown-item fw-bold text-white
                                            @if (request()->routeIs('gestion.categorias.*')) active @endif"
                                    href="{{ route('gestion.categorias.index') }}">

                                    Categorías

                                </a>
                            </li>

                            @endcan


                            {{-- Movimientos de stock --}}
                            <li>
                                <a class="dropdown-item fw-bold text-white
                                        @if (request()->routeIs('gestion.stock.*')) active @endif"
                                    href="{{ route('gestion.stock.index') }}">

                                    Movimientos

                                </a>
                            </li>

                        </ul>

                    </li>

                    @endif


                    {{-- ==================================================
                         USUARIOS
                         ================================================== --}}

                    @php
                    $usuariosActivos =
                    request()->routeIs('gestion.empleados.*') ||
                    request()->routeIs('gestion.clientes.*');
                    @endphp

                    @if (
                    $usuario->can('viewAny', \App\Models\Empleado::class) &&
                    $usuario->can('viewAny', \App\Models\Cliente::class)
                    )

                    <li class="nav-item dropdown px-2">

                        <a class="nav-link rounded dropdown-toggle fw-bold text-white
                                @if ($usuariosActivos) active @endif"
                            href="#"
                            id="usuariosDropdown"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">

                            Usuarios

                        </a>


                        <ul class="dropdown-menu dropdown-menu-end bg-primary"
                            aria-labelledby="usuariosDropdown">

                            {{-- Empleados --}}
                            @can('viewAny', \App\Models\Empleado::class)

                            <li>
                                <a class="dropdown-item fw-bold text-white
                                            @if (request()->routeIs('gestion.empleados.*')) active @endif"
                                    href="{{ route('gestion.empleados.index') }}">

                                    Empleados

                                </a>
                            </li>

                            @endcan


                            {{-- Clientes --}}
                            @can('viewAny', \App\Models\Cliente::class)

                            <li>
                                <a class="dropdown-item fw-bold text-white
                                            @if (request()->routeIs('gestion.clientes.*')) active @endif"
                                    href="{{ route('gestion.clientes.index') }}">

                                    Clientes

                                </a>
                            </li>

                            @endcan

                        </ul>

                    </li>

                    @else
                    <li class="nav-item px-2">
                        <a class="dropdown-item fw-bold text-white nav-link rounded fw-bold text-white
                            @if (request()->routeIs('gestion.clientes.*')) active @endif"
                            href="{{ route('gestion.clientes.index') }}">

                            Clientes

                        </a>
                    </li>
                    @endif


                    {{-- ==================================================
                         PEDIDOS
                         ================================================== --}}

                    @php
                    $pedidosActivos =
                    request()->routeIs('gestion.compras.*') ||
                    request()->routeIs('gestion.proveedores.*');
                    @endphp

                    @if (
                    $usuario->can('viewAny', \App\Models\OrdenCompra::class) ||
                    $usuario->can('viewAny', \App\Models\Proveedor::class)
                    )

                    <li class="nav-item dropdown px-2">

                        <a class="nav-link rounded dropdown-toggle fw-bold text-white
                                @if ($pedidosActivos) active @endif"
                            href="#"
                            id="pedidosDropdown"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">

                            Pedidos

                        </a>


                        <ul class="dropdown-menu dropdown-menu-end bg-primary"
                            aria-labelledby="pedidosDropdown">

                            {{-- Compras --}}
                            @can('viewAny', \App\Models\OrdenCompra::class)

                            <li>
                                <a class="dropdown-item fw-bold text-white
                                            @if (request()->routeIs('gestion.compras.*')) active @endif"
                                    href="{{ route('gestion.compras.index') }}">

                                    Compras

                                </a>
                            </li>

                            @endcan


                            {{-- Proveedores --}}
                            @can('viewAny', \App\Models\Proveedor::class)

                            <li>
                                <a class="dropdown-item fw-bold text-white
                                            @if (request()->routeIs('gestion.proveedores.*')) active @endif"
                                    href="{{ route('gestion.proveedores.index') }}">

                                    Proveedores

                                </a>
                            </li>

                            @endcan

                        </ul>

                    </li>

                    @endif


                    {{-- ==================================================
                         MI PERFIL
                         ================================================== --}}

                    <li class="nav-item dropdown px-2">

                        <a class="nav-link rounded dropdown-toggle fw-bold text-white
                            @if (request()->routeIs('gestion.mi-cuenta')) active @endif"
                            href="#"
                            id="miCuentaDropdown"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">

                            Mi perfil

                        </a>


                        <ul class="dropdown-menu dropdown-menu-end bg-primary"
                            aria-labelledby="miCuentaDropdown">

                            <li class="px-3 py-2 text-white">

                                <div class="fw-bold">
                                    {{ $usuario->nombre_completo }}
                                </div>

                                <div class="small opacity-75">
                                    {{ ucfirst($usuario->rol->nombre) }}
                                </div>

                            </li>


                            <li>
                                <hr class="dropdown-divider border-light opacity-25">
                            </li>


                            <li>

                                <a class="dropdown-item fw-bold d-flex align-items-center gap-2 text-white"
                                    href="{{ route('gestion.mi-cuenta') }}">

                                    <svg width="16"
                                        height="16"
                                        fill="currentColor"
                                        viewBox="0 0 16 16"
                                        aria-hidden="true">

                                        <path
                                            d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z" />

                                    </svg>

                                    Mis datos

                                </a>

                            </li>


                            <li>

                                {{-- El logout es POST: un enlace GET permitiría
                                     cerrar la sesión de alguien con una imagen o un link. --}}

                                <form method="POST"
                                    action="{{ route('logout') }}">

                                    @csrf

                                    <button type="submit"
                                        class="dropdown-item fw-bold d-flex align-items-center gap-2 text-white">

                                        <svg width="16"
                                            height="16"
                                            fill="currentColor"
                                            viewBox="0 0 16 16"
                                            aria-hidden="true">

                                            <path fill-rule="evenodd"
                                                d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z" />

                                            <path fill-rule="evenodd"
                                                d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z" />

                                        </svg>

                                        Cerrar sesión

                                    </button>

                                </form>

                            </li>

                        </ul>

                    </li>

                </ul>

            </div>

        </div>
    </nav>
</header>