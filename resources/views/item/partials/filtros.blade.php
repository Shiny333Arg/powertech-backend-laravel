{{--
    Panel de filtros de la vista de resultados.

    Es un unico form GET: asi el resultado queda en la URL y se puede compartir,
    marcar como favorito y volver con el boton atras del navegador. Nada de esto
    funciona si los filtros viajan por POST.

    El termino de busqueda y la promocion viajan como hidden: se setean en el
    header o en el banner, no aca, pero tienen que sobrevivir a cada cambio de
    filtro. Sin estos dos hidden, filtrar por marca borraria la busqueda.
--}}

<form method="GET" action="{{ route('cliente.items.index') }}" class="card border-0 shadow-sm rounded-4">

    <input type="hidden" name="q" value="{{ $filtros['q'] }}">

    @if ($filtros['promocion'])
        <input type="hidden" name="promocion" value="{{ $filtros['promocion'] }}">
    @endif

    <div class="card-body p-3">

        <h2 class="h6 fw-bold mb-3">Filtros</h2>


        {{-- Promocion activa: no es un filtro editable, es de donde vino el
             cliente. Se muestra para que sepa por que ve una lista recortada y
             pueda salirse. --}}
        @if ($promocion)
            <div class="d-flex align-items-center justify-content-between gap-2 bg-light rounded-3 p-2 mb-3">

                <span class="small fw-semibold text-truncate">
                    Promoción: {{ $promocion->nombre }}
                </span>

                <a class="btn btn-sm btn-link p-0 text-decoration-none"
                   href="{{ route('cliente.items.index', collect(request()->query())->except('promocion', 'page')->all()) }}"
                   aria-label="Quitar el filtro de promoción">
                    Quitar
                </a>

            </div>
        @endif


        {{-- Categoria --}}
        <div class="mb-3">

            <label for="filtro-categoria" class="form-label small fw-semibold mb-1">
                Categoría
            </label>

            <select name="categoria" id="filtro-categoria" class="form-select form-select-sm">

                <option value="">Todas</option>

                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected($filtros['categoria'] === $categoria->id)>
                        {{ $categoria->nombre }}
                    </option>
                @endforeach

            </select>

        </div>


        {{-- Marca --}}
        <div class="mb-3">

            <label for="filtro-marca" class="form-label small fw-semibold mb-1">
                Marca
            </label>

            <select name="marca" id="filtro-marca" class="form-select form-select-sm">

                <option value="">Todas</option>

                @foreach ($marcas as $marca)
                    <option value="{{ $marca }}" @selected($filtros['marca'] === $marca)>
                        {{ $marca }}
                    </option>
                @endforeach

            </select>

        </div>


        {{-- Precio --}}
        <div class="mb-3">

            <span class="form-label small fw-semibold mb-1 d-block">Precio</span>

            <div class="row g-2">

                <div class="col-6">
                    <label for="filtro-precio-min" class="visually-hidden">Precio mínimo</label>
                    <input type="number" inputmode="decimal" min="0" step="any"
                           name="precio_min" id="filtro-precio-min"
                           class="form-control form-control-sm"
                           placeholder="Mín."
                           value="{{ $filtros['precio_min'] }}">
                </div>

                <div class="col-6">
                    <label for="filtro-precio-max" class="visually-hidden">Precio máximo</label>
                    <input type="number" inputmode="decimal" min="0" step="any"
                           name="precio_max" id="filtro-precio-max"
                           class="form-control form-control-sm"
                           placeholder="Máx."
                           value="{{ $filtros['precio_max'] }}">
                </div>

            </div>

        </div>


        {{-- Orden --}}
        <div class="mb-3">

            <label for="filtro-orden" class="form-label small fw-semibold mb-1">
                Ordenar por
            </label>

            <select name="orden" id="filtro-orden" class="form-select form-select-sm"
                    onchange="this.form.submit()">

                @foreach ($ordenes as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected($filtros['orden']->value === $valor)>
                        {{ $etiqueta }}
                    </option>
                @endforeach

            </select>

        </div>


        {{-- El boton existe igual que el onchange del select: sin JS, el form
             se envia desde aca y todo sigue funcionando. --}}
        <div class="d-grid gap-2">

            <button type="submit" class="btn btn-sm btn-primary fw-semibold">
                Aplicar filtros
            </button>

            @if ($filtros['categoria'] || $filtros['marca'] || $filtros['precio_min'] || $filtros['precio_max'])
                <a class="btn btn-sm btn-outline-secondary fw-semibold"
                   href="{{ route('cliente.items.index', array_filter([
                       'q'         => $filtros['q'],
                       'promocion' => $filtros['promocion'],
                       'orden'     => $filtros['orden']->value,
                   ])) }}">
                    Limpiar filtros
                </a>
            @endif

        </div>

    </div>

</form>
