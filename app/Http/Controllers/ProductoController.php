<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoComboItem;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'buscar' => 'sometimes|required|string|max:128',
            'id_categoria' => 'sometimes|required|integer|exists:categoria,categoria_id',
            'tipo_producto' => 'sometimes|required|string|in:estandar,combo',
            'estado' => 'sometimes|required|integer|in:0,1',
            'stock_bajo' => 'sometimes|required|boolean',
            'por_pagina' => 'sometimes|required|integer|min:1|max:100',
            'page' => 'sometimes|required|integer|min:1',
        ]);

        $productos = Producto::with(['categoria', 'comboItems.componente'])->orderBy('producto_id');

        if (isset($validated['buscar'])) {
            $productos->where(function ($query) use ($validated) {
                $query->whereLike('nombre_producto', '%'.$validated['buscar'].'%')
                    ->orWhereLike('codigo_producto', '%'.$validated['buscar'].'%');
            });
        }

        if (array_key_exists('id_categoria', $validated)) {
            $productos->where('id_categoria', $validated['id_categoria']);
        }

        if (array_key_exists('tipo_producto', $validated)) {
            $productos->where('tipo_producto', $validated['tipo_producto']);
        }

        if (array_key_exists('estado', $validated)) {
            $productos->where('estado', $validated['estado']);
        } else {
            $productos->where('estado', 1);
        }

        if ($validated['stock_bajo'] ?? false) {
            $productos->where(function ($q) {
                $q->where('tipo_producto', '!=', 'combo')
                    ->whereColumn('existencia_bodega', '<=', 'existencia_minima');
            });
        }

        if (isset($validated['por_pagina'])) {
            return response()->json($productos->paginate((int) $validated['por_pagina'])->withQueryString()
                ->through(fn (Producto $producto) => $this->conEstadoStock($producto)));
        }

        return response()->json($productos->get()->map(fn (Producto $producto) => $this->conEstadoStock($producto)));
    }

    public function store(Request $request)
    {
        $validated = $this->validateProducto($request);
        $validated['estado'] = $validated['estado'] ?? 1;

        try {
            $producto = DB::transaction(function () use ($validated, $request) {
                $esCombo = ($validated['tipo_producto'] ?? 'estandar') === 'combo';
                if ($esCombo) {
                    $validated['existencia_bodega'] = 0;
                }

                $producto = Producto::create($validated);

                if ($esCombo && $request->has('componentes')) {
                    foreach ($request->input('componentes') as $item) {
                        ProductoComboItem::create([
                            'id_producto_combo' => $producto->producto_id,
                            'id_producto_componente' => $item['id_producto_componente'],
                            'cantidad' => $item['cantidad'],
                        ]);
                    }
                }

                return $producto->load(['categoria', 'comboItems.componente']);
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages([
                'codigo_producto' => 'El código de producto ya está registrado.',
            ]);
        }

        return response()->json($this->conEstadoStock($producto), 201);
    }

    public function show($id)
    {
        $producto = Producto::with(['categoria', 'movimiento_inventarios', 'venta_detalles', 'comboItems.componente'])->findOrFail($id);

        return response()->json($this->conEstadoStock($producto));
    }

    public function update(Request $request, $id)
    {
        try {
            $producto = DB::transaction(function () use ($request, $id) {
                $producto = Producto::lockForUpdate()->findOrFail($id);
                $validated = $this->validateProducto($request, $producto);

                $esCombo = ($validated['tipo_producto'] ?? $producto->tipo_producto) === 'combo';

                if (! $esCombo) {
                    if (array_key_exists('existencia_bodega', $validated)
                        && (int) $validated['existencia_bodega'] !== (int) $producto->existencia_bodega) {
                        throw ValidationException::withMessages([
                            'existencia_bodega' => 'Registre una entrada, salida o ajuste en movimientos-inventario para cambiar las existencias.',
                        ]);
                    }
                    if (array_key_exists('estado', $validated) && (int) $validated['estado'] === 0 && (int) $producto->existencia_bodega > 0) {
                        throw ValidationException::withMessages([
                            'estado' => "No se puede eliminar el producto porque tiene stock físico disponible ({$producto->existencia_bodega} unidades). Ajuste o descargue el stock a 0 antes de eliminarlo.",
                        ]);
                    }
                } else {
                    $validated['existencia_bodega'] = 0;
                }

                if (array_key_exists('estado', $validated) && (int) $validated['estado'] === 0) {
                    if ($producto->combosDondeEsComponente()->whereHas('combo', fn ($q) => $q->where('estado', 1))->exists()) {
                        throw ValidationException::withMessages([
                            'estado' => 'No se puede eliminar el producto porque forma parte de un combo activo.',
                        ]);
                    }
                }

                $producto->update($validated);

                if ($esCombo && $request->has('componentes')) {
                    $producto->comboItems()->delete();
                    foreach ($request->input('componentes') as $item) {
                        ProductoComboItem::create([
                            'id_producto_combo' => $producto->producto_id,
                            'id_producto_componente' => $item['id_producto_componente'],
                            'cantidad' => $item['cantidad'],
                        ]);
                    }
                }

                return $producto->load(['categoria', 'comboItems.componente']);
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages([
                'codigo_producto' => 'El código de producto ya está registrado.',
            ]);
        }

        return response()->json($this->conEstadoStock($producto));
    }

    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);

        if ($producto->combosDondeEsComponente()->whereHas('combo', fn ($q) => $q->where('estado', 1))->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el producto porque forma parte de un combo activo.',
            ], 409);
        }

        if (! $producto->esCombo() && $producto->existencia_bodega > 0) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar el producto porque tiene stock físico disponible ({$producto->existencia_bodega} unidades). Ajuste o descargue el stock a 0 antes de eliminarlo.",
            ], 409);
        }

        $producto->update(['estado' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado correctamente',
            'producto' => $producto,
        ]);
    }

    private function conEstadoStock(Producto $producto): Producto
    {
        if ($producto->esCombo()) {
            $stockVirtual = $producto->calcularStockCombo();
            $producto->setAttribute('existencia_bodega', $stockVirtual);
            // Los combos dependen de sus componentes y no disparan alertas de stock físico bajo
            $producto->setAttribute('estado_stock', 'combo');
        } else {
            // Dato calculado para RF-12; no es una columna ni se persiste.
            $producto->setAttribute('estado_stock', $producto->existencia_bodega == 0
                ? 'agotado'
                : ($producto->existencia_bodega <= $producto->existencia_minima ? 'bajo' : 'normal'));
        }

        return $producto;
    }

    private function rules(?Producto $producto = null, bool $esCombo = false): array
    {
        $presencia = $producto ? ['sometimes', 'required'] : ['required'];
        $codigo = Rule::unique('producto', 'codigo_producto')->where(fn ($query) => $query->where('estado', 1));
        $nombre = Rule::unique('producto', 'nombre_producto')->where(fn ($query) => $query->where('estado', 1));
        $categoria = Rule::exists('categoria', 'categoria_id')->where(function ($query) use ($producto) {
            $query->where(function ($query) use ($producto) {
                $query->where('estado', 1);

                // La categoría actual se conserva aunque posteriormente se haya desactivado.
                if ($producto) {
                    $query->orWhere('categoria_id', $producto->id_categoria);
                }
            });
        });

        if ($producto) {
            $codigo->ignore($producto->producto_id, 'producto_id');
            $nombre->ignore($producto->producto_id, 'producto_id');
        }

        return [
            'id_categoria' => [...$presencia, 'integer', $categoria],
            'codigo_producto' => [...$presencia, 'string', 'max:32', 'regex:/^[a-zA-Z0-9\-]+$/', $codigo],
            'nombre_producto' => [...$presencia, 'string', 'max:128', $nombre],
            'tipo_producto' => ['sometimes', 'required', 'string', 'in:estandar,combo'],
            'descripcion_producto' => [...$presencia, 'string', 'max:128'],
            'costo_compra' => [...$presencia, 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'precio_venta' => [...$presencia, 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'existencia_bodega' => $esCombo ? ['nullable', 'integer', 'min:0', 'max:2147483647'] : [...$presencia, 'integer', 'min:0', 'max:2147483647'],
            'existencia_minima' => [...$presencia, 'integer', 'min:0', 'max:2147483647'],
            'estado' => ['sometimes', 'integer', 'in:0,1'],
            'componentes' => [$esCombo && ! $producto ? 'required' : 'sometimes', 'array', 'min:1'],
            'componentes.*.id_producto_componente' => ['required', 'integer', 'exists:producto,producto_id'],
            'componentes.*.cantidad' => ['required', 'integer', 'min:1', 'max:10000'],
        ];
    }

    private function validateProducto(Request $request, ?Producto $producto = null): array
    {
        $tipoProducto = $request->input('tipo_producto', $producto?->tipo_producto ?? 'estandar');
        $esCombo = $tipoProducto === 'combo';

        $validator = Validator::make($request->all(), $this->rules($producto, $esCombo), [
            'codigo_producto.unique' => 'El código de producto ya está registrado.',
            'codigo_producto.regex' => 'El código de producto solo debe contener letras, números y guiones.',
            'nombre_producto.unique' => 'Ya existe un producto registrado con este nombre.',
            'id_categoria.exists' => 'La categoría seleccionada no es válida o está inactiva.',
            'estado.in' => 'El estado debe ser 1 (Activo) o 0 (Inactivo).',
            'componentes.required' => 'Un producto compuesto debe tener al menos un producto componente.',
            'componentes.min' => 'Un producto compuesto debe tener al menos un producto componente.',
        ]);

        $validator->after(function ($validator) use ($request, $producto, $esCombo) {
            if ($esCombo) {
                $componentes = $request->input('componentes');
                if (is_array($componentes) && count($componentes) > 0) {
                    $componenteIds = [];
                    foreach ($componentes as $idx => $comp) {
                        $idComp = $comp['id_producto_componente'] ?? null;
                        if ($idComp) {
                            if (in_array($idComp, $componenteIds, true)) {
                                $validator->errors()->add("componentes.{$idx}.id_producto_componente", 'No puede repetir el mismo componente en el combo.');
                            }
                            $componenteIds[] = $idComp;

                            if ($producto && (int) $idComp === (int) $producto->producto_id) {
                                $validator->errors()->add("componentes.{$idx}.id_producto_componente", 'Un combo no puede ser componente de sí mismo.');
                            }

                            $componenteProd = Producto::find($idComp);
                            if ($componenteProd && $componenteProd->esCombo()) {
                                $validator->errors()->add("componentes.{$idx}.id_producto_componente", 'Un combo no puede contener otro combo como componente.');
                            }
                        }
                    }
                }
            }

            if ($validator->errors()->has('costo_compra') || $validator->errors()->has('precio_venta')) {
                return;
            }

            $costo = $request->input('costo_compra', $producto?->costo_compra);
            $precio = $request->input('precio_venta', $producto?->precio_venta);

            if ($costo !== null && $precio !== null && is_numeric($costo) && is_numeric($precio)) {
                if ((float) $precio < (float) $costo) {
                    if ($request->has('costo_compra') && ! $request->has('precio_venta')) {
                        $validator->errors()->add('costo_compra', 'El costo de compra no puede ser mayor que el precio de venta actual.');
                    } else {
                        $validator->errors()->add('precio_venta', 'El precio de venta no puede ser menor que el costo de compra.');
                    }
                }
            }
        });

        return $validator->validate();
    }
}
