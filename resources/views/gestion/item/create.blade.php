@extends('layouts.app')

@section('title', 'PowerTech')

@section('content')

<div class="container py-4">


    {{-- Título --}}
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-2">Alta de nuevo producto</h1>
        <p class="text-muted">
            Complete los datos para registrar un nuevo producto en el sistema.
        </p>
    </section>

    {{-- Formulario --}}
    <section class="mb-4">

        <form
            action="{{ route('gestion.items.store') }}"
            method="POST"
            enctype="multipart/form-data"
            autocomplete="off"
            class="p-4 rounded-4 bg-light shadow-sm border">

            @csrf

            {{-- Errores generales de validación --}}
            @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Revisá estos campos:</strong>

                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="row g-4">

                {{-- Código --}}
                <div class="col-md-4">
                    <label for="codigo" class="form-label fw-semibold">
                        Código
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3 @error('codigo') is-invalid @enderror"
                        id="codigo"
                        name="codigo"
                        value="{{ old('codigo', $producto->codigo) }}"
                        maxlength="50"
                        placeholder="Ej: PRD-001"
                        required>

                    @error('codigo')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Nombre --}}
                <div class="col-md-8">
                    <label for="nombre" class="form-label fw-semibold">
                        Nombre del producto
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3 @error('nombre') is-invalid @enderror"
                        id="nombre"
                        name="nombre"
                        value="{{ old('nombre', $producto->nombre) }}"
                        maxlength="100"
                        placeholder="Ej: Mouse Gamer RGB"
                        required>

                    @error('nombre')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Marca --}}
                <div class="col-md-6">
                    <label for="marca" class="form-label fw-semibold">
                        Marca
                    </label>

                    <input
                        type="text"
                        class="form-control rounded-3 @error('marca') is-invalid @enderror"
                        id="marca"
                        name="marca"
                        value="{{ old('marca', $producto->marca) }}"
                        maxlength="100"
                        placeholder="Ej: Logitech">

                    @error('marca')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Categoría --}}
                <div class="col-md-6">
                    <label for="categoria_id" class="form-label fw-semibold">
                        Categoría
                    </label>

                    <select
                        id="categoria_id"
                        name="categoria_id"
                        class="form-select rounded-3 @error('categoria_id') is-invalid @enderror"
                        required>
                        <option value="">Seleccione una categoría</option>

                        @foreach($categorias as $categoria)
                        <option
                            value="{{ $categoria->id }}"
                            @selected(old('categoria_id', $producto->categoria_id) == $categoria->id)
                            >
                            {{ $categoria->nombre }}
                        </option>
                        @endforeach
                    </select>

                    @error('categoria_id')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Descripción --}}
                <div class="col-12">
                    <label for="descripcion" class="form-label fw-semibold">
                        Descripción
                    </label>

                    <textarea
                        class="form-control rounded-3 @error('descripcion') is-invalid @enderror"
                        id="descripcion"
                        name="descripcion"
                        rows="4"
                        placeholder="Breve descripción del producto."
                        style="resize: none">{{ old('descripcion', $producto->descripcion) }}</textarea>

                    @error('descripcion')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <hr class="my-4">

                {{-- Precio --}}
                <div class="col-md-4">
                    <label for="precio" class="form-label fw-semibold">
                        Precio
                    </label>

                    <input
                        type="number"
                        class="form-control rounded-3 @error('precio') is-invalid @enderror"
                        id="precio"
                        name="precio"
                        value="{{ old('precio', $producto->precio) }}"
                        min="0"
                        step="0.01"
                        placeholder="Ej: 35000"
                        required>

                    @error('precio')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Costo --}}
                <div class="col-md-4">
                    <label for="costo" class="form-label fw-semibold">
                        Costo
                    </label>

                    <input
                        type="number"
                        class="form-control rounded-3 @error('costo') is-invalid @enderror"
                        id="costo"
                        name="costo"
                        value="{{ old('costo', $producto->costo) }}"
                        min="0"
                        step="0.01"
                        placeholder="Ej: 25000">

                    @error('costo')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- IVA --}}
                <div class="col-md-4">
                    <label for="alicuota_iva_id" class="form-label fw-semibold">
                        Alícuota de IVA
                    </label>

                    <select
                        id="alicuota_iva_id"
                        name="alicuota_iva_id"
                        class="form-select rounded-3 @error('alicuota_iva_id') is-invalid @enderror"
                        required>
                        <option value="">Seleccione IVA</option>

                        @foreach($alicuotas as $alicuota)
                        <option
                            value="{{ $alicuota->id }}"
                            @selected(old('alicuota_iva_id', $producto->alicuota_iva_id) == $alicuota->id)
                            >
                            {{ $alicuota->nombre ?? $alicuota->porcentaje . '%' }}
                        </option>
                        @endforeach
                    </select>

                    @error('alicuota_iva_id')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Proveedor --}}
                <div class="col-md-6">
                    <label for="proveedor_id" class="form-label fw-semibold">
                        Proveedor
                    </label>

                    <select
                        id="proveedor_id"
                        name="proveedor_id"
                        class="form-select rounded-3 @error('proveedor_id') is-invalid @enderror">
                        <option value="">Sin proveedor</option>

                        @foreach($proveedores as $proveedor)
                        <option
                            value="{{ $proveedor->id }}"
                            @selected(old('proveedor_id', $producto->proveedor_id) == $proveedor->id)
                            >
                            {{ $proveedor->razon_social }}
                        </option>
                        @endforeach
                    </select>

                    @error('proveedor_id')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Stock inicial --}}
                <div class="col-md-6">
                    <label for="stock_inicial" class="form-label fw-semibold">
                        Stock inicial
                    </label>

                    <input
                        type="number"
                        class="form-control rounded-3 @error('stock_inicial') is-invalid @enderror"
                        id="stock_inicial"
                        name="stock_inicial"
                        value="{{ old('stock_inicial', 0) }}"
                        min="0"
                        placeholder="Ej: 20">

                    <div class="form-text">
                        Se registrará como movimiento inicial de stock.
                    </div>

                    @error('stock_inicial')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Punto de reposición --}}
                <div class="col-md-6">
                    <label for="punto_reposicion" class="form-label fw-semibold">
                        Punto de reposición
                    </label>

                    <input
                        type="number"
                        class="form-control rounded-3 @error('punto_reposicion') is-invalid @enderror"
                        id="punto_reposicion"
                        name="punto_reposicion"
                        value="{{ old('punto_reposicion', $producto->punto_reposicion) }}"
                        min="0"
                        placeholder="Ej: 5"
                        required>

                    @error('punto_reposicion')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Cantidad de reposición --}}
                <div class="col-md-6">
                    <label for="cantidad_reposicion" class="form-label fw-semibold">
                        Cantidad de reposición
                    </label>

                    <input
                        type="number"
                        class="form-control rounded-3 @error('cantidad_reposicion') is-invalid @enderror"
                        id="cantidad_reposicion"
                        name="cantidad_reposicion"
                        value="{{ old('cantidad_reposicion', $producto->cantidad_reposicion) }}"
                        min="0"
                        placeholder="Ej: 10"
                        required>

                    @error('cantidad_reposicion')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <hr class="my-4">

                {{-- Peso --}}
                <div class="col-md-6">
                    <label for="peso_gramos" class="form-label fw-semibold">
                        Peso (gramos)
                    </label>

                    <input
                        type="number"
                        class="form-control rounded-3 @error('peso_gramos') is-invalid @enderror"
                        id="peso_gramos"
                        name="peso_gramos"
                        value="{{ old('peso_gramos', $producto->peso_gramos) }}"
                        min="0"
                        placeholder="Ej: 500">

                    @error('peso_gramos')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Imagen --}}
                <div class="col-md-6">
                    <label for="imagen" class="form-label fw-semibold">
                        Imagen del producto
                    </label>

                    <input
                        type="file"
                        class="form-control rounded-3 @error('imagen') is-invalid @enderror"
                        id="imagen"
                        name="imagen"
                        accept=".jpg,.jpeg,.png,.webp">

                    <div class="form-text">
                        Formatos permitidos: JPG, JPEG, PNG o WEBP. Máximo 2 MB.
                    </div>

                    @error('imagen')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Reposición automática --}}
                <div class="col-md-6">
                    <div class="form-check form-switch mt-2">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            role="switch"
                            id="reposicion_automatica"
                            name="reposicion_automatica"
                            value="1"
                            @checked(old('reposicion_automatica', $producto->reposicion_automatica))
                        >

                        <label
                            class="form-check-label fw-semibold"
                            for="reposicion_automatica">
                            Reposición automática
                        </label>

                    </div>

                    <div class="form-text">
                        Requiere seleccionar un proveedor.
                    </div>

                    @error('reposicion_automatica')
                    <div class="text-danger small mt-2">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- Publicado en la aplicación --}}
                <div class="col-md-6">
                    <div class="form-check form-switch mt-2">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            role="switch"
                            id="publicado_app"
                            name="publicado_app"
                            value="1"
                            @checked(old('publicado_app', $producto->publicado_app))
                        >

                        <label
                            class="form-check-label fw-semibold"
                            for="publicado_app">
                            Publicado en la aplicación
                        </label>

                    </div>

                    <div class="form-text">
                        El producto podrá estar disponible para los clientes.
                    </div>
                </div>

            </div>

            {{-- Botones --}}
            <div class="mt-5 d-flex flex-wrap gap-3">

                <button
                    type="submit"
                    class="btn btn-success px-4 fw-semibold shadow-sm">
                    Guardar
                </button>

                <a
                    href="{{ route('gestion.items.index') }}"
                    class="btn btn-outline-secondary px-4 fw-semibold shadow-sm">
                    Volver al listado
                </a>

            </div>

        </form>

    </section>


</div>

@endsection