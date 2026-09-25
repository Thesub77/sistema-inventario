<?php

namespace App\Http\Controllers;

use App\Models\Producto;
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
            'estado' => 'sometimes|required|integer|in:0,1',
            'stock_bajo' => 'sometimes|required|boolean',
            'por_pagina' => 'sometimes|required|integer|min:1|max:100',
            'page' => 'sometimes|required|integer|min:1',
        ]);

        $productos = Producto::with('categoria')->orderBy('producto_id');

        if (isset($validated['buscar'])) {
            $productos->where(function ($query) use ($validated) {
                $query->whereLike('nombre_producto', '%'.$validated['buscar'].'%')
                    ->orWhereLike('codigo_producto', '%'.$validated['buscar'].'%');
            });
        }

        foreach (['id_categoria', 'estado'] as $campo) {
            if (array_key_exists($campo, $validated)) {
                $productos->where($campo, $validated[$campo]);
            }
        }

        if ($validated['stock_bajo'] ?? false) {
            $productos->whereColumn('existencia_bodega', '<=', 'existencia_minima');
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

        try {
            $producto = Producto::create($validated);
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages([
                'codigo_producto' => 'El código de producto ya está registrado.',
            ]);
        }

        return response()->json($producto, 201);
    }

    public function show($id)
    {
        $producto = Producto::with(['categoria', 'movimiento_inventarios', 'venta_detalles'])->findOrFail($id);

        return response()->json($this->conEstadoStock($producto));
    }

    public function update(Request $request, $id)
    {
        try {
            $producto = DB::transaction(function () use ($request, $id) {
                $producto = Producto::lockForUpdate()->findOrFail($id);
                $validated = $this->validateProducto($request, $producto);
                if (array_key_exists('existencia_bodega', $validated)
                    && (int) $validated['existencia_bodega'] !== (int) $producto->existencia_bodega) {
                    throw ValidationException::withMessages([
                        'existencia_bodega' => 'Registre una entrada, salida o ajuste en movimientos-inventario para cambiar las existencias.',
                    ]);
                }
                $producto->update($validated);

                return $producto;
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages([
                'codigo_producto' => 'El código de producto ya está registrado.',
            ]);
        }

        return response()->json($producto);
    }

    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);
        $producto->update(['estado' => 0]);

        return response()->json(['message' => 'Producto desactivado correctamente']);
    }

    private function conEstadoStock(Producto $producto): Producto
    {
        // Dato calculado para RF-12; no es una columna ni se persiste.
        $producto->setAttribute('estado_stock', $producto->existencia_bodega == 0
            ? 'agotado'
            : ($producto->existencia_bodega <= $producto->existencia_minima ? 'bajo' : 'normal'));

        return $producto;
    }

    private function rules(?Producto $producto = null): array
    {
        $presencia = $producto ? ['sometimes', 'required'] : ['required'];
        $codigo = Rule::unique('producto', 'codigo_producto');
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
        }

        return [
            'id_categoria' => [...$presencia, 'integer', $categoria],
            'codigo_producto' => [...$presencia, 'string', 'max:32', $codigo],
            'nombre_producto' => [...$presencia, 'string', 'max:128'],
            'descripcion_producto' => [...$presencia, 'string', 'max:128'],
            'costo_compra' => [...$presencia, 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'precio_venta' => [...$presencia, 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'existencia_bodega' => [...$presencia, 'integer', 'min:0', 'max:2147483647'],
            'existencia_minima' => [...$presencia, 'integer', 'min:0', 'max:2147483647'],
            'estado' => [...$presencia, 'integer', 'in:0,1'],
        ];
    }

    private function validateProducto(Request $request, ?Producto $producto = null): array
    {
        $validator = Validator::make($request->all(), $this->rules($producto), [
            'codigo_producto.unique' => 'El código de producto ya está registrado.',
            'id_categoria.exists' => 'La categoría seleccionada no es válida o está inactiva.',
            'estado.in' => 'El estado debe ser 1 (Activo) o 0 (Inactivo).',
        ]);

        $validator->after(function ($validator) use ($request, $producto) {
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
