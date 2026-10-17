@extends('layouts.gestion')

@section('title', 'Nuevo pedido')

@section('content')

@include('gestion.partials.alertas')

{{-- Título principal --}}
<section class="mb-4">
    <h1 class="h3 fw-bold mb-2">Nuevo pedido</h1>
    <p class="text-muted mb-0">
        Un pedido va dirigido a un solo proveedor: primero se elige a quién, y después se cargan sus productos.
    </p>
</section>

@if ($proveedor === null)

{{--
    Paso 1. El desplegable de productos depende del proveedor, así que
    se elige antes y la pantalla se recarga. Es un GET a la misma ruta:
    mantiene el botón «atrás» del navegador y permite entrar directo con
    ?proveedor=N desde la ficha del proveedor.

    El buscador filtra en el cliente la lista que ya viene en $proveedores.
    El id viaja en un input oculto: el campo de texto es solo la interfaz.
--}}
<section class="mb-4">
    <div class="p-4 rounded-4 bg-light shadow-sm border" style="max-width: 560px;">
        <form method="GET" action="{{ route('gestion.compras.create') }}" id="form-proveedor" autocomplete="off">
            <div class="mb-4 position-relative">
                <label for="buscador-proveedor" class="form-label fw-semibold">Proveedor</label>

                <input
                    type="text"
                    id="buscador-proveedor"
                    class="form-control rounded-3"
                    placeholder="Escribí el nombre del proveedor…"
                    autocomplete="off"
                    role="combobox"
                    aria-expanded="false"
                    aria-controls="lista-proveedores"
                    maxlength="150">

                {{-- Lo que realmente viaja al servidor --}}
                <input type="hidden" name="proveedor" id="proveedor">

                <ul
                    id="lista-proveedores"
                    class="list-group position-absolute w-100 shadow rounded-3 d-none"
                    style="z-index: 1050; max-height: 18rem; overflow-y: auto;">

                    @foreach ($proveedores as $opcion)
                    <li
                        class="list-group-item list-group-item-action"
                        role="option"
                        style="cursor: pointer;"
                        data-id="{{ $opcion->id }}"
                        data-nombre="{{ $opcion->razon_social }}">
                        {{ $opcion->razon_social }}
                    </li>
                    @endforeach

                    <li id="sin-proveedores" class="list-group-item text-muted d-none">
                        Ningún proveedor coincide con la búsqueda.
                    </li>
                </ul>

                <div class="form-text">Elegí uno de la lista para continuar.</div>
            </div>

            <button type="submit" id="btn-continuar" class="btn btn-primary" disabled>
                Continuar
            </button>
        </form>
    </div>
</section>


{{--
    OJO: este script tiene que quedar DENTRO de @section. Todo lo que una
    vista con @extends escribe fuera de una sección lo descarta el layout.
--}}
<script>
    (function() {
        'use strict';

        var input = document.getElementById('buscador-proveedor');
        var hidden = document.getElementById('proveedor');
        var lista = document.getElementById('lista-proveedores');
        var vacio = document.getElementById('sin-proveedores');
        var boton = document.getElementById('btn-continuar');
        var form = document.getElementById('form-proveedor');

        if (!input || !lista) return;

        var items = Array.prototype.slice.call(lista.querySelectorAll('li[data-id]'));
        var indice = -1;

        // Sin acentos y en minúsculas, para que "jose" encuentre "José".
        function normalizar(t) {
            return (t || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
        }

        items.forEach(function(li) {
            li.dataset.busqueda = normalizar(li.dataset.nombre);
        });

        function visibles() {
            return items.filter(function(li) {
                return !li.classList.contains('d-none');
            });
        }

        function abrir() {
            lista.classList.remove('d-none');
            input.setAttribute('aria-expanded', 'true');
        }

        function cerrar() {
            lista.classList.add('d-none');
            input.setAttribute('aria-expanded', 'false');
            marcar(-1);
        }

        function marcar(i) {
            var v = visibles();

            v.forEach(function(li) {
                li.classList.remove('active');
            });

            indice = i;

            if (i >= 0 && v[i]) {
                v[i].classList.add('active');
                v[i].scrollIntoView({ block: 'nearest' });
            }
        }

        function filtrar() {
            var q = normalizar(input.value);
            var encontrados = 0;

            items.forEach(function(li) {
                var coincide = q === '' || li.dataset.busqueda.indexOf(q) !== -1;
                li.classList.toggle('d-none', !coincide);
                if (coincide) encontrados++;
            });

            vacio.classList.toggle('d-none', encontrados > 0);
            abrir();
            marcar(-1);
        }

        function elegir(li) {
            input.value = li.dataset.nombre;
            hidden.value = li.dataset.id;
            boton.disabled = false;
            cerrar();
        }

        input.addEventListener('input', function() {
            // Mientras escribe, la selección anterior deja de valer.
            hidden.value = '';
            boton.disabled = true;
            filtrar();
        });

        input.addEventListener('focus', filtrar);

        input.addEventListener('keydown', function(e) {
            var v = visibles();

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                abrir();
                marcar(Math.min(indice + 1, v.length - 1));
                return;
            }

            if (e.key === 'ArrowUp') {
                e.preventDefault();
                marcar(Math.max(indice - 1, 0));
                return;
            }

            if (e.key === 'Enter') {
                // Con uno marcado, o con un único candidato, Enter elige.
                var elegido = indice >= 0 ? v[indice] : (v.length === 1 ? v[0] : null);

                if (elegido && !hidden.value) {
                    e.preventDefault();
                    elegir(elegido);
                }

                return;
            }

            if (e.key === 'Escape') {
                cerrar();
            }
        });

        // mousedown y no click: el blur del input no llega a cerrar la lista antes.
        lista.addEventListener('mousedown', function(e) {
            var li = e.target.closest('li[data-id]');
            if (!li) return;

            e.preventDefault();
            elegir(li);
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('#form-proveedor')) cerrar();
        });

        form.addEventListener('submit', function(e) {
            if (hidden.value) return;

            e.preventDefault();
            input.focus();
            filtrar();
        });
    })();
</script>

@else

{{-- Proveedor seleccionado --}}
<section class="mb-4">
    <div class="p-3 rounded-4 bg-light shadow-sm border d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <span class="text-muted small d-block mb-1">Pedido dirigido a</span>

            <div class="fw-semibold">
                {{ $proveedor->razon_social }}

                @if ($proveedor->email)
                <span class="text-muted fw-normal">· {{ $proveedor->email }}</span>
                @endif
            </div>
        </div>

        <a href="{{ route('gestion.compras.create') }}"
            class="btn btn-outline-secondary btn-sm">
            Cambiar proveedor
        </a>
    </div>
</section>

@if ($productos->isEmpty())

<div class="alert alert-warning shadow-sm">
    <strong>{{ $proveedor->razon_social }} no tiene productos activos asignados.</strong>
    Asignale el proveedor a los productos que le compramos desde la ficha de cada producto.
</div>

@else

@include('gestion.compras.partials.formulario', [
'accion' => route('gestion.compras.store'),
'metodo' => 'POST',
])

@endif

@endif

@endsection