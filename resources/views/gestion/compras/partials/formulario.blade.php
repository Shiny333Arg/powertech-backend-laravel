{{--
    Detalle de un pedido. Lo comparten create y edit.

    El proveedor NO es editable acá: en el alta se elige antes (create pide
    primero a quién se le pide, porque el desplegable de productos depende de
    eso) y en la edición no se cambia, porque cambiar de destinatario es otro
    pedido, no una corrección de este.

    Espera:
      $orden     OrdenCompra (nueva o existente)
      $proveedor Proveedor
      $productos Collection de los productos de ese proveedor
      $accion    URL del form
      $metodo    'POST' o 'PUT'
--}}

<form method="POST" action="{{ $accion }}">
    @csrf

    @if ($metodo === 'PUT')
        @method('PUT')
    @endif

    @if ($metodo === 'POST')
        <input type="hidden" name="proveedor_id" value="{{ $proveedor->id }}">
    @endif

    <div class="card mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Productos a pedir</h2>
            <button type="button" class="btn btn-sm btn-outline-primary" data-agregar-renglon>
                Agregar renglón
            </button>
        </div>

        @error('lineas')
            <div class="alert alert-danger rounded-0 mb-0 small">{{ $message }}</div>
        @enderror

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr class="text-secondary small text-uppercase">
                        <th scope="col" style="min-width: 260px;">Producto</th>
                        <th scope="col" class="text-end" style="width: 140px;">Cantidad</th>
                        <th scope="col" class="text-end" style="width: 160px;">Costo estimado</th>
                        <th scope="col" style="width: 48px;"></th>
                    </tr>
                </thead>

                <tbody data-renglones>
                    @php
                        // old() gana sobre las lineas guardadas: si la validacion
                        // rechazo el formulario, lo que el usuario escribio no se
                        // puede perder.
                        $renglones = old('lineas', $orden->lineas->map(fn ($linea) => [
                            'producto_id'     => $linea->producto_id,
                            'cantidad_pedida' => $linea->cantidad_pedida,
                            'costo_unitario'  => $linea->costo_unitario,
                        ])->all());

                        $renglones = $renglones ?: [['producto_id' => '', 'cantidad_pedida' => 1, 'costo_unitario' => '']];
                    @endphp

                    @foreach ($renglones as $i => $renglon)
                        <tr>
                            <td>
                                <select name="lineas[{{ $i }}][producto_id]"
                                        class="form-select @error("lineas.{$i}.producto_id") is-invalid @enderror"
                                        required>
                                    <option value="">Elegí un producto…</option>
                                    @foreach ($productos as $producto)
                                        <option value="{{ $producto->id }}"
                                                data-costo="{{ $producto->costo }}"
                                                @selected((string) ($renglon['producto_id'] ?? '') === (string) $producto->id)>
                                            {{ $producto->codigo }} — {{ $producto->nombre }}
                                            (disp. {{ $producto->stock_disponible }} / rep. {{ $producto->punto_reposicion }})
                                        </option>
                                    @endforeach
                                </select>

                                @error("lineas.{$i}.producto_id")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>

                            <td>
                                <input type="number" name="lineas[{{ $i }}][cantidad_pedida]"
                                       value="{{ $renglon['cantidad_pedida'] ?? 1 }}"
                                       min="1" max="999999" required
                                       class="form-control text-end @error("lineas.{$i}.cantidad_pedida") is-invalid @enderror">

                                @error("lineas.{$i}.cantidad_pedida")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>

                            <td>
                                <input type="number" name="lineas[{{ $i }}][costo_unitario]"
                                       value="{{ $renglon['costo_unitario'] ?? '' }}"
                                       step="0.01" min="0" placeholder="del catálogo"
                                       class="form-control text-end">
                            </td>

                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger" data-quitar-renglon
                                        aria-label="Quitar renglón">&times;</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white small text-secondary">
            El costo es una estimación para dimensionar el pedido. Si se deja vacío se toma el del
            catálogo, y en ningún caso se le informa al proveedor.
        </div>
    </div>

    <div class="mb-3">
        <label for="observaciones" class="form-label">Observaciones</label>
        <textarea id="observaciones" name="observaciones" rows="3"
                  class="form-control @error('observaciones') is-invalid @enderror"
                  placeholder="Se incluyen en el remito que recibe el proveedor.">{{ old('observaciones', $orden->observaciones) }}</textarea>

        @error('observaciones')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            {{ $metodo === 'POST' ? 'Crear el pedido' : 'Guardar los cambios' }}
        </button>

        <a href="{{ $orden->exists ? route('gestion.compras.show', $orden) : route('gestion.compras.index') }}"
           class="btn btn-outline-secondary">
            Cancelar
        </a>
    </div>
</form>

@push('scripts')
    <script>
        // Renglones dinamicos. Se clona el primero y se le reescriben los
        // indices del name: Laravel arma el array por el indice, asi que dos
        // renglones con el mismo numero se pisan.
        (function () {
            const cuerpo = document.querySelector('[data-renglones]');
            if (!cuerpo) return;

            const reindexar = () => {
                cuerpo.querySelectorAll('tr').forEach((fila, i) => {
                    fila.querySelectorAll('[name]').forEach((campo) => {
                        campo.name = campo.name.replace(/lineas\[\d+\]/, `lineas[${i}]`);
                    });
                });
            };

            document.querySelector('[data-agregar-renglon]')?.addEventListener('click', () => {
                const nueva = cuerpo.querySelector('tr').cloneNode(true);

                nueva.querySelectorAll('select, input').forEach((campo) => {
                    campo.value = campo.type === 'number' && campo.min === '1' ? 1 : '';
                    campo.classList.remove('is-invalid');
                });

                nueva.querySelectorAll('.invalid-feedback').forEach((error) => error.remove());

                cuerpo.appendChild(nueva);
                reindexar();
            });

            cuerpo.addEventListener('click', (evento) => {
                if (!evento.target.closest('[data-quitar-renglon]')) return;

                // Nunca se queda sin renglones: un pedido vacio no se puede
                // guardar, y un formulario sin filas no deja volver atras.
                if (cuerpo.querySelectorAll('tr').length === 1) return;

                evento.target.closest('tr').remove();
                reindexar();
            });

            // Al elegir producto, se precarga su costo de catalogo si el campo
            // esta vacio. Es una sugerencia, no una imposicion.
            cuerpo.addEventListener('change', (evento) => {
                const select = evento.target.closest('select');
                if (!select) return;

                const costo = select.selectedOptions[0]?.dataset.costo;
                const campo = select.closest('tr').querySelector('input[name*="costo_unitario"]');

                if (costo && campo && campo.value === '') {
                    campo.value = costo;
                }
            });
        })();
    </script>
@endpush
