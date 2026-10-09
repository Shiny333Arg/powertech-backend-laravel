{{--
    Detalle de un pedido. Lo comparten create y edit.

    El proveedor NO es editable acá: en el alta se elige antes (create pide
    primero a quién se le pide, porque el desplegable de productos depende de
    eso) y en la edición no se cambia, porque cambiar de destinatario es otro
    pedido, no una corrección de este.

    El costo unitario tampoco se edita: sale del catálogo (producto->costo). Se
    muestra para que el encargado vea cuánto pesa cada renglón, pero el dato
    tiene un solo dueño, que es la ficha del producto.

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

    <section class="p-4 rounded-4 bg-light shadow-sm border mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1">Productos a pedir</h2>
                <p class="text-muted small mb-0">
                    Agregá los productos y las cantidades. El costo lo toma del catálogo.
                </p>
            </div>

            <button type="button" class="btn btn-sm btn-outline-primary" data-agregar-renglon>
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="16"
                     height="16"
                     fill="currentColor"
                     class="bi bi-plus-lg me-1"
                     viewBox="0 0 16 16"
                     aria-hidden="true">
                    <path fill-rule="evenodd"
                          d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/>
                </svg>
                Agregar renglón
            </button>
        </div>

        @error('lineas')
            <div class="alert alert-danger small mb-3 rounded-3">{{ $message }}</div>
        @enderror

        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center mb-0" id="tablaProductos">

                <thead class="table-dark">

                    <tr>
                        <th class="text-nowrap">Producto</th>
                        <th class="text-nowrap" style="width: 12rem;">Cantidad</th>
                        <th class="text-nowrap" style="width: 10rem;">Costo unitario</th>
                        <th class="text-nowrap" style="width: 10rem;">Subtotal</th>
                        <th class="text-nowrap" style="width: 5rem;">Quitar</th>
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

                        $renglones = $renglones ?: [['producto_id' => '', 'cantidad_pedida' => 1]];
                    @endphp

                    @foreach ($renglones as $i => $renglon)
                        @php
                            // El costo sale del producto. El guardado en la linea
                            // queda solo de red para el caso raro de un producto
                            // que ya no figura entre los del proveedor.
                            $elegido  = $productos->firstWhere('id', (int) ($renglon['producto_id'] ?? 0));
                            $costo    = $elegido?->costo ?? ($renglon['costo_unitario'] ?? null);
                            $cantidad = (int) ($renglon['cantidad_pedida'] ?? 1);
                            $subtotal = $costo === null ? null : (float) $costo * max($cantidad, 0);
                        @endphp

                        <tr>
                            <td>
                                <select name="lineas[{{ $i }}][producto_id]"
                                        class="form-select rounded-3 @error("lineas.{$i}.producto_id") is-invalid @enderror"
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
                                       class="form-control rounded-3 text-end @error("lineas.{$i}.cantidad_pedida") is-invalid @enderror">

                                @error("lineas.{$i}.cantidad_pedida")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>

                            {{-- Solo lectura: el valor viaja en el hidden, que la pantalla no deja tocar. --}}
                            <td class="text-end text-nowrap">
                                <span data-costo-texto>
                                    {{ $costo === null ? '—' : '$ ' . number_format((float) $costo, 2, ',', '.') }}
                                </span>

                                <input type="hidden"
                                       name="lineas[{{ $i }}][costo_unitario]"
                                       value="{{ $costo }}"
                                       data-costo-campo>
                            </td>

                            <td class="text-end text-nowrap fw-semibold" data-subtotal>
                                {{ $subtotal === null ? '—' : '$ ' . number_format($subtotal, 2, ',', '.') }}
                            </td>

                            <td class="text-center">
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-quitar-renglon
                                        aria-label="Quitar renglón"
                                        title="Quitar renglón">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                         width="16"
                                         height="16"
                                         fill="currentColor"
                                         class="bi bi-trash"
                                         viewBox="0 0 16 16"
                                         aria-hidden="true">
                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m5 0A.5.5 0 0 1 11 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m-3 0A.5.5 0 0 1 8 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5"/>
                                        <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1 0-2h3.5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1H14a1 1 0 0 1 .5 1M5 4v9a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1V4zM6.5 2a.5.5 0 0 0-.5.5V3h4v-.5a.5.5 0 0 0-.5-.5z"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <td colspan="3" class="text-end fw-semibold">Total estimado</td>
                        <td class="text-end fw-bold text-nowrap" data-total>—</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="form-text mt-2">
            El costo es el del catálogo y acá no se edita: para cambiarlo, andá a la ficha del
            producto. Los importes son una estimación para dimensionar el pedido, y en ningún caso
            se le informan al proveedor.
        </div>
    </section>

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

            const celdaTotal = document.querySelector('[data-total]');

            // Mismo formato que el resto de la pantalla: 1.234,56.
            const formato = new Intl.NumberFormat('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const pesos = (numero) => '$ ' + formato.format(numero);

            const reindexar = () => {
                cuerpo.querySelectorAll('tr').forEach((fila, i) => {
                    fila.querySelectorAll('[name]').forEach((campo) => {
                        campo.name = campo.name.replace(/lineas\[\d+\]/, `lineas[${i}]`);
                    });
                });
            };

            // El costo lo manda el catalogo, no el usuario: se lee del
            // data-costo de la opcion elegida y se escribe en el hidden, que es
            // lo unico que viaja al servidor.
            const recalcularFila = (fila) => {
                const select   = fila.querySelector('select');
                const cantidad = parseInt(fila.querySelector('input[name*="cantidad_pedida"]')?.value, 10);
                const costo    = parseFloat(select?.selectedOptions[0]?.dataset.costo ?? '');

                const texto    = fila.querySelector('[data-costo-texto]');
                const campo    = fila.querySelector('[data-costo-campo]');
                const subtotal = fila.querySelector('[data-subtotal]');

                if (Number.isNaN(costo)) {
                    if (texto) texto.textContent = '—';
                    if (campo) campo.value = '';
                    if (subtotal) subtotal.textContent = '—';
                    return;
                }

                if (texto) texto.textContent = pesos(costo);
                if (campo) campo.value = costo;

                if (subtotal) {
                    subtotal.textContent = Number.isNaN(cantidad) || cantidad < 1
                        ? '—'
                        : pesos(costo * cantidad);
                }
            };

            const recalcularTotal = () => {
                if (!celdaTotal) return;

                let total = 0;
                let hayAlguno = false;

                cuerpo.querySelectorAll('tr').forEach((fila) => {
                    const costo    = parseFloat(fila.querySelector('[data-costo-campo]')?.value ?? '');
                    const cantidad = parseInt(fila.querySelector('input[name*="cantidad_pedida"]')?.value, 10);

                    if (Number.isNaN(costo) || Number.isNaN(cantidad) || cantidad < 1) return;

                    total += costo * cantidad;
                    hayAlguno = true;
                });

                celdaTotal.textContent = hayAlguno ? pesos(total) : '—';
            };

            const recalcularTodo = () => {
                cuerpo.querySelectorAll('tr').forEach(recalcularFila);
                recalcularTotal();
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
                recalcularFila(nueva);
                recalcularTotal();
            });

            cuerpo.addEventListener('click', (evento) => {
                if (!evento.target.closest('[data-quitar-renglon]')) return;

                // Nunca se queda sin renglones: un pedido vacio no se puede
                // guardar, y un formulario sin filas no deja volver atras.
                if (cuerpo.querySelectorAll('tr').length === 1) return;

                evento.target.closest('tr').remove();
                reindexar();
                recalcularTotal();
            });

            // Cambia el producto (otro costo) o la cantidad (otro subtotal).
            const alEditar = (evento) => {
                const fila = evento.target.closest('tr');
                if (!fila) return;

                recalcularFila(fila);
                recalcularTotal();
            };

            cuerpo.addEventListener('change', alEditar);
            cuerpo.addEventListener('input', alEditar);

            recalcularTodo();
        })();
    </script>
@endpush