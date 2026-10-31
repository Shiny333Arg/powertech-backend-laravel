{{--
    Barra de la tienda. La ven visitantes y clientes logueados.

    La busqueda es un form GET a cliente.items.index: el Enter del input y el
    click en la lupa son el mismo submit, y el resultado queda en la URL
    (compartible, marcable y navegable con el boton atras).

    El form va DENTRO del collapse para que en mobile aparezca al abrir el menu
    hamburguesa en lugar de amontonarse contra el logo.
--}}
<header>
  <nav class="navbar navbar-expand-md navbar-dark bg-primary shadow-sm">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('cliente.home') }}">
        <img src="{{ asset('images/Logo.png') }}" alt="" height="40" class="rounded">
        <span class="fw-bold fs-5">POWERTECH</span>
      </a>

      <!-- Botón hamburguesa -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTienda"
        aria-controls="navbarTienda" aria-expanded="false" aria-label="Abrir menú">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Menú colapsable -->
      <div class="collapse navbar-collapse" id="navbarTienda">

        <!-- Buscador -->
        <form class="d-flex flex-grow-1 my-2 my-md-0 mx-md-4" style="max-width: 520px;"
          role="search" method="GET" action="{{ route('cliente.items.index') }}">

          <div class="input-group">

            <label for="buscadorTienda" class="visually-hidden">Buscar productos</label>

            <input type="search" name="q" id="buscadorTienda"
              class="form-control border-0"
              placeholder="Buscar productos..."
              value="{{ request('q') }}"
              autocomplete="off"
              aria-label="Buscar productos">

            <button class="btn btn-light fw-bold text-primary" type="submit" aria-label="Buscar">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                viewBox="0 0 16 16" aria-hidden="true">
                <path
                  d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
              </svg>
            </button>

          </div>

        </form>

        <ul class="navbar-nav ms-auto gap-md-2 align-items-md-center">

          <li class="nav-item px-2">
            <a class="nav-link rounded fw-bold text-white @if (request()->routeIs('cliente.home')) active @endif"
              href="{{ route('cliente.home') }}">Inicio</a>
          </li>

          @auth
          @php $usuario = auth()->user(); @endphp
          <li class="nav-item px-2">
            <a class="nav-link rounded fw-bold text-white @if (request()->routeIs('cliente.compras.*')) active @endif"
              href="{{ route('cliente.compras.index') }}">Mis compras</a>
          </li>
          @endauth

          <li class="nav-item px-2">
            <a class="nav-link rounded fw-bold text-white @if (request()->routeIs('cliente.help.*')) active @endif"
              href="{{ route('cliente.help.index') }}">Ayuda</a>
          </li>

          @auth
          @php $usuario = auth()->user(); @endphp

          <li class="nav-item dropdown px-2">
            <a class="nav-link rounded dropdown-toggle fw-bold text-white" href="#" id="miCuentaDropdown"
              role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Mi perfil
            </a>

            <ul class="dropdown-menu dropdown-menu-end bg-primary" aria-labelledby="miCuentaDropdown">
              <li>
                @if ($usuario->esCliente())
                <a class="dropdown-item fw-bold d-flex align-items-center gap-2 text-white"
                  href="{{ route('profile.edit') }}">
                  <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path
                      d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z" />
                  </svg>Mis datos
                </a>
                @else
                {{-- Un empleado logueado que entra a la tienda: su cuenta vive en gestion. --}}
                <a class="dropdown-item fw-bold text-white" href="{{ route('gestion.home') }}">
                  Ir a la gestión
                </a>
                @endif
              </li>

              <li>
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button type="submit" class="dropdown-item fw-bold d-flex align-items-center gap-2 text-white">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                      <path fill-rule="evenodd"
                        d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z" />
                      <path fill-rule="evenodd"
                        d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z" />
                    </svg>Cerrar sesión
                  </button>
                </form>
              </li>
            </ul>
          </li>

          <li class="nav-item px-2">
            <a class="nav-link rounded fw-bold text-white" href="{{ route('cliente.carrito.index') }}">
              <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-cart3" viewBox="0 0 16 16">
                <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l.84 4.479 9.144-.459L13.89 4zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2" />
              </svg>
            </a>
          </li>


          @else
          <li class="nav-item px-2">
            <a class="nav-link rounded fw-bold text-white" href="{{ route('login') }}">Ingresar</a>
          </li>

          <li class="nav-item px-2">
            <a class="btn btn-light fw-bold text-primary" href="{{ route('register') }}">Crear cuenta</a>
          </li>
          @endauth

        </ul>
      </div>
    </div>
  </nav>
</header>
