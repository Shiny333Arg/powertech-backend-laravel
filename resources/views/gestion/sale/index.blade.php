@extends('layouts.gestion')

@section('title', 'PowerTech')

@vite('resources/css/gestion/sale/index.css')

@section('content')


<!-- Título principal -->
<section class="mb-4 text-center">
    <h1 class="display-6 fw-bold">Gestión de ventas</h1>
    <p class="text-muted mb-0">Administre las ventas del sistema.</p>
</section>


<!-- Botones -->
<section class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-stretch gap-3">
    <a href="/sale/create"
        class="btn btn-success text-white d-flex align-items-center justify-content-center gap-2 flex-fill flex-md-grow-0">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-cart-plus"
            viewBox="0 0 16 16">
            <path d="M9 5.5a.5.5 0 0 0-1 0V7H6.5a.5.5 0 0 0 0 1H8v1.5a.5.5 0 0 0 1 0V8h1.5a.5.5 0 0 0 0-1H9z" />
            <path
                d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1zm3.915 10L3.102 4h10.796l-1.313 7zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0m7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0" />
        </svg>
        Registrar nueva venta
    </a>
    <button
        class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2 flex-fill flex-md-grow-0"
        id="btnExportarPDF" (click)="exportPDF()">
        <svg xmlns="../../../../public/app/assets/icons/file-earmark-pdf.svg" width="18" height="18" fill="currentColor"
            class="bi bi-file-earmark-pdf" viewBox="0 0 16 16">
            <path
                d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z" />
            <path
                d="M4.603 14.087a.8.8 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.102.198-.307.526-.568.897-.787a7.7 7.7 0 0 1 1.482-.645 20 20 0 0 0 1.062-2.227 7.3 7.3 0 0 1-.43-1.295c-.086-.4-.119-.796-.046-1.136.075-.354.274-.672.65-.823.192-.077.4-.12.602-.077a.7.7 0 0 1 .477.365c.088.164.12.356.127.538.007.188-.012.396-.047.614-.084.51-.27 1.134-.52 1.794a11 11 0 0 0 .98 1.686 5.8 5.8 0 0 1 1.334.05c.364.066.734.195.96.465.12.144.193.32.2.518.007.192-.047.382-.138.563a1.04 1.04 0 0 1-.354.416.86.86 0 0 1-.51.138c-.331-.014-.654-.196-.933-.417a5.7 5.7 0 0 1-.911-.95 11.7 11.7 0 0 0-1.997.406 11.3 11.3 0 0 1-1.02 1.51c-.292.35-.609.656-.927.787a.8.8 0 0 1-.58.029m1.379-1.901q-.25.115-.459.238c-.328.194-.541.383-.647.547-.094.145-.096.25-.04.361q.016.032.026.044l.035-.012c.137-.056.355-.235.635-.572a8 8 0 0 0 .45-.606m1.64-1.33a13 13 0 0 1 1.01-.193 12 12 0 0 1-.51-.858 21 21 0 0 1-.5 1.05zm2.446.45q.226.245.435.41c.24.19.407.253.498.256a.1.1 0 0 0 .07-.015.3.3 0 0 0 .094-.125.44.44 0 0 0 .059-.2.1.1 0 0 0-.026-.063c-.052-.062-.2-.152-.518-.209a4 4 0 0 0-.612-.053zM8.078 7.8a7 7 0 0 0 .2-.828q.046-.282.038-.465a.6.6 0 0 0-.032-.198.5.5 0 0 0-.145.04c-.087.035-.158.106-.196.283-.04.192-.03.469.046.822q.036.167.09.346z" />
        </svg>
        Exportar listado a PDF
    </button>
</section>


<section class="mb-4">
    <form class="row g-3 align-items-end shadow-sm p-3 rounded bg-light" (ngSubmit)="filtrar()" autocomplete="off"
        id="formFiltros">

        <!-- Estado -->
        <div class="col-12 col-md-4 col-lg-2">
            <label for="datoEstado" class="form-label fw-semibold">Estado</label>
            <select id="datoEstado" name="datoEstado" class="form-select" [(ngModel)]="filtroEstado">
                <option selected value="">Todos</option>
                <option value="presupuesto">Presupuesto</option>
                <option value="confirmada">Confirmada</option>
                <option value="cobrada">Cobrada</option>
            </select>
        </div>

        <!-- Cliente -->
        <div class="col-12 col-md-6 col-lg-2">
            <label for="datoCliente" class="form-label fw-semibold">Cliente</label>
            <input type="text" class="form-control" id="datoCliente" name="datoCliente" placeholder="Ej: Juan Pérez"
                maxlength="64" [(ngModel)]="filtroCliente">
        </div>

        <!-- Vendedor -->
        <div class="col-12 col-md-6 col-lg-2">
            <label for="datoVendedor" class="form-label fw-semibold">Vendedor</label>
            <input type="text" class="form-control" id="datoVendedor" name="datoVendedor" placeholder="Ej: Spinetta"
                maxlength="64" [(ngModel)]="filtroVendedor">
        </div>

        <!-- Desde -->
        <div class="col-6 col-md-3 col-lg-2">
            <label for="datoDesde" class="form-label fw-semibold">Desde</label>
            <input type="date" class="form-control" id="datoDesde" name="datoDesde" [(ngModel)]="filtroDesde">
        </div>

        <!-- Hasta -->
        <div class="col-6 col-md-3 col-lg-2">
            <label for="datoHasta" class="form-label fw-semibold">Hasta</label>
            <input type="date" class="form-control" id="datoHasta" name="datoHasta" [(ngModel)]="filtroHasta">
        </div>

        <!-- Botón -->
        <div class="col-12 col-md-2 col-lg-2 d-flex">
            <button type="submit"
                class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold">
                Filtrar
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-funnel"
                    viewBox="0 0 16 16">
                    <path
                        d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2z" />
                </svg>
            </button>
        </div>

    </form>
</section>


<!-- Tabla de sales -->
<section>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="tablaSales">
            <thead class="table-dark">
                <tr>
                    <th class="text-nowrap w-10">N°</th>
                    <th class="text-nowrap w-15">Fecha</th>
                    <th class="text-nowrap w-20">Cliente</th>
                    <th class="text-nowrap w-20">Vendedor</th>
                    <th class="text-nowrap w-15">Total</th>
                    <th class="text-nowrap w-10">Estado</th>
                    <th class="text-nowrap w-10">Opciones</th>
                </tr>
            </thead>
            <tbody id="contenidoTablaSales">
                <tr
                    *ngFor=" let sale of sales | paginate: { itemsPerPage: itemsPerPage, currentPage: currentPage}; trackBy: trackById">
                    <td>{'{ sale.id }}</td>
                    <td>{'{ sale.fecha }}</td>
                    <td>{'{ sale.cliente || '-' }}</td>
                    <td>{'{ sale.usuario || '-' }}</td>
                    <td>$ {'{ sale.total | number:'1.0-2' }}</td>
                    <td>{'{ sale.estado }}</td>
                    <td>
                        <a [routerLink]="['/sales/view', sale.id]" class="btn btn-sm btn-outline-primary me-1">
                            <!-- <img src="{{ asset('icons/eye.svg') }}" alt="Editar" width="16" height="16" class="icon-primary"> -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z" />
                                <path
                                    d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0" />
                            </svg>
                        </a>
                    </td>
                </tr>

                <!-- SI NO HAY DATOS -->
                <tr *ngIf="sales.length === 0">
                    <td colspan="7" class="text-center py-4 text-muted">
                        No se encontraron ventas
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-center mt-3">
        <pagination-controls previousLabel="⟵" nextLabel="⟶" (pageChange)="currentPage = $event">
        </pagination-controls>
    </div>
</section>


@endsection