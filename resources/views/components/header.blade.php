<header>
  <nav class="navbar navbar-expand-md navbar-dark bg-primary shadow-sm">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2" routerLink="/">
        <img src="{{ asset('images/Logo.png') }}" alt="Logo" height="40" class="rounded">
        <span class="fw-bold fs-5">POWERTECH</span>
      </a>
      <!-- Botón hamburguesa -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown"
        aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <!-- Menú colapsable -->
      <div class="collapse navbar-collapse" id="navbarNavDropdown">
        <ul class="navbar-nav ms-auto gap-2" id="mainNavbar">
          <li class="nav-item px-2">
            <a class="nav-link rounded fw-bold text-white" href="/gestion">Inicio</a>
          </li>

          <li class="nav-item px-2" *ngIf="isAdmin() || isOperator()">
            <a class="nav-link rounded fw-bold text-white" href="/gestion/promotion">Promociones</a>
          </li>

          <li class="nav-item px-2" *ngIf="isAdmin() || isOperator()">
            <a class="nav-link rounded fw-bold text-white" href="/gestion/item">Productos</a>
          </li>

          <li class="nav-item px-2" *ngIf="isAdmin() || isOperator()">
            <a class="nav-link rounded fw-bold text-white" href="/gestion/category">Categorías</a>
          </li>

          <li class="nav-item px-2" *ngIf="isAdmin() || isOperator()">
            <a class="nav-link rounded fw-bold text-white" href="/gestion/proveedor">Proveedores</a>
          </li>

          <li class="nav-item px-2" *ngIf="isAdmin()">
            <a class="nav-link rounded fw-bold text-white" href="/gestion/empleado" id="onlyAdmin">Empleados</a>
          </li>

          <li class="nav-item px-2" *ngIf="isAdmin()">
            <a class="nav-link rounded fw-bold text-white" href="/" id="onlyAdmin">Clientes</a>
          </li>

          <li class="nav-item px-2" *ngIf="isAdmin() || isSeller()">
            <a class="nav-link rounded fw-bold text-white" href="/gestion/sale">Ventas</a>
          </li>

          <li class="nav-item dropdown px-2">
            <a class="nav-link rounded dropdown-toggle fw-bold text-white" href="#" id="miCuentaDropdown" role="button"
              data-bs-toggle="dropdown" aria-expanded="false">Mi cuenta</a>

            <ul class="dropdown-menu dropdown-menu-end bg-primary" aria-labelledby="miCuentaDropdown">
              <li>
                <a class="dropdown-item fw-bold d-flex align-items-center gap-2 text-white"
                  href="/gestion/user/my-account">
                  <svg width="16" height="16" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
                    <path
                      d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z" />
                  </svg>Mis datos
                </a>
              </li>
              <li>
                <a class="dropdown-item fw-bold d-flex align-items-center gap-2 text-white" href="/authentication/logout">
                  <svg class="bi bi-box-arrow-right" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd"
                      d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z" />
                    <path fill-rule="evenodd"
                      d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z" />
                  </svg>Cerrar sesión
                </a>
              </li>
            </ul>

          </li>
        </ul>
      </div>
    </div>
  </nav>
</header>