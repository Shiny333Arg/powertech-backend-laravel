@extends('layouts.app')

@section('title', 'PowerTech')

@section('content')

<!-- Contenido principal -->
<div class="container py-4">

    <!-- Título -->
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-2">Detalle de venta</h1>
        <p class="text-muted">Visualice y administre la venta seleccionada.</p>
    </section>

    <form [formGroup]="form" autocomplete="off">

        <!-- ===================== CABECERA ===================== -->
        <section class="mb-4">
            <div class="card rounded-4 shadow-sm border">
                <div class="card-body p-4">

                    <div class="row g-4">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Cliente
                            </label>

                            <input type="text" class="form-control rounded-3" formControlName="cliente"
                                [disabled]="!modoEdicion">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">
                                Estado
                            </label>

                            <div class="">

                                @'if (sale.estado === 'presupuesto') {
                                <span class="badge bg-secondary">
                                    Presupuesto
                                </span>
                                }

                                @'if (sale.estado === 'confirmada') {
                                <span class="badge bg-warning text-dark">
                                    Confirmada
                                </span>
                                }

                                @'if (sale.estado === 'cobrada') {
                                <span class="badge bg-success">
                                    Cobrada
                                </span>
                                }

                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">
                                Descuento (%)
                            </label>

                            <input type="number" class="form-control rounded-3" formControlName="descuentoPorcentaje"
                                min="0" max="100" step="0.01" [disabled]="!modoEdicion" (input)="limitarDescuento()">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Observaciones
                            </label>

                            <textarea class="form-control rounded-3" rows="2" formControlName="observaciones"
                                style="resize:none" [disabled]="!modoEdicion"></textarea>
                        </div>

                    </div>

                </div>
            </div>
        </section>

        <!-- ===================== PRODUCTOS ===================== -->
        <section class="mb-4">
            <div class="card rounded-4 shadow-sm border">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <h2 class="h5 fw-bold mb-0">
                            Productos
                        </h2>

                        @'if (sale.estado === 'presupuesto'){

                        <button type="button" class="btn btn-sm btn-success d-flex align-items-center gap-2"
                            [disabled]="!modoEdicion" (click)="addLinea()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path fill-rule="evenodd"
                                    d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2" />
                            </svg>
                            Agregar producto
                        </button>

                        }

                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th style="min-width: 280px;">
                                        Producto
                                    </th>

                                    <th style="width: 120px;">
                                        Cantidad
                                    </th>

                                    <th style="width: 170px;">
                                        Precio unit.
                                    </th>

                                    <th style="width: 170px;">
                                        Importe
                                    </th>

                                    <th style="width: 60px;">
                                        Quitar
                                    </th>
                                </tr>
                            </thead>
                            <tbody>

                                <tr *ngFor="let detalle of detalles; let i = index; trackBy: trackByIndex">

                                    <!-- ===== Buscador dinámico de producto ===== -->
                                    <td class="text-start position-relative" style="min-width: 280px;">

                                        <input type="text" class="form-control" [name]="'buscadorProducto_' + i"
                                            placeholder="Escriba nombre o código…" [(ngModel)]="detalle.busqueda"
                                            [ngModelOptions]="{standalone: true}" [disabled]="!modoEdicion"
                                            (input)="buscarProductos(detalle)"
                                            (focus)="onFocusBuscador(detalle, $event)"
                                            (blur)="ocultarSugerencias(detalle)" autocomplete="off">

                                        <!-- Lista de sugerencias (en flujo, no se recorta) -->
                                        <div *ngIf="detalle.mostrarSugerencias" class="list-group mt-1 shadow-sm"
                                            style="max-height: 220px; overflow-y: auto;">

                                            <button type="button" *ngFor="let p of detalle.sugerencias"
                                                class="list-group-item list-group-item-action text-start"
                                                (mousedown)="seleccionarProducto(detalle, p)">
                                                <span class="fw-semibold">{'{ p.nombre }}</span>
                                                <small class="text-muted d-block">
                                                    {'{ p.codigo }} — $ {'{ p.precio | number:'1.2-2' }}
                                                </small>
                                            </button>

                                            <div *ngIf="detalle.sugerencias?.length === 0"
                                                class="list-group-item text-muted text-start">
                                                Sin coincidencias
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Cantidad -->
                                    <td>
                                        <input type="number" class="form-control text-center" min="1"
                                            [(ngModel)]="detalle.cantidad" [disabled]="!modoEdicion"
                                            [ngModelOptions]="{standalone: true}">
                                    </td>

                                    <!-- Precio unitario (solo lectura) -->
                                    <td class="text-end">
                                        $ {'{ detalle.precio_unitario | number:'1.2-2' }}
                                    </td>

                                    <!-- Importe (solo lectura) -->
                                    <td class="text-end fw-semibold">
                                        $ {'{ importeLinea(detalle) | number:'1.2-2' }}
                                    </td>

                                    <!-- Quitar línea -->
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger" [disabled]="!modoEdicion"
                                            (click)="removeLinea(i)">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                fill="currentColor" viewBox="0 0 16 16">
                                                <path
                                                    d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5" />
                                            </svg>
                                        </button>
                                    </td>

                                </tr>

                                <tr *ngIf="detalles.length === 0">
                                    <td colspan="5" class="text-muted py-3">
                                        No hay productos cargados
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>
            </div>

        </section>

        <!-- ===================== TOTALES ===================== -->
        <section class="mb-4">

            <div class="card rounded-4 shadow-sm border">
                <div class="card-body p-4">

                    <div class="row justify-content-end">

                        <div class="col-md-5 col-lg-4">

                            <ul class="list-group">

                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Subtotal</span>
                                    <strong>
                                        $ {'{ subtotal() | number:'1.2-2' }}
                                    </strong>
                                </li>

                                <li class="list-group-item d-flex justify-content-between">
                                    <span>
                                        Descuento
                                    </span>

                                    <strong>
                                        - $
                                        {'{ descuentoMonto() | number:'1.2-2' }}
                                    </strong>
                                </li>

                                <li class="list-group-item d-flex justify-content-between bg-dark text-white">
                                    <span class="fw-bold">
                                        Total
                                    </span>

                                    <strong class="fw-bold">
                                        $ {'{ total() | number:'1.2-2' }}
                                    </strong>
                                </li>

                            </ul>

                        </div>

                    </div>

                </div>
            </div>

        </section>

    </form>

    <!-- ===================== BOTONES ===================== -->

    <div class="mt-4 d-flex flex-wrap gap-3">

        @'if (sale.estado === 'presupuesto') {

        <button type="button" class="btn btn-warning px-4 fw-semibold shadow-sm" (click)="editar()"
            [disabled]="modoEdicion">
            Editar
        </button>

        <button type="button" class="btn btn-success px-4 fw-semibold shadow-sm" (click)="guardar()"
            [disabled]="!modoEdicion">
            Actualizar
        </button>

        <button type="button" class="btn btn-secondary px-4 fw-semibold shadow-sm" (click)="cancelar()"
            [disabled]="!modoEdicion">
            Cancelar
        </button>

        <button type="button" class="btn btn-success px-4 fw-semibold shadow-sm" (click)="confirmar()">
            Confirmar compra
        </button>

        <button type="button" class="btn btn-outline-danger px-4 fw-semibold shadow-sm" (click)="anular()">
            Anular
        </button>

        }

        @'if (sale.estado === 'confirmada'){

        <button type="button" class="btn btn-success px-4 fw-semibold shadow-sm" (click)="confirmar()">
            Confirmar pago
        </button>

        }

        <a routerLink="/sales" class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
            Volver al listado
        </a>

        <button type="button" class="btn btn-outline-dark px-4 fw-semibold shadow-sm" (click)="exportarPdf()">
            Exportar PDF
        </button>

    </div>

</div>

@endsection