<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::with('categoria')->get();
        return response()->json($productos);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_categoria' => 'required|exists:categoria,categoria_id',
            'codigo_producto' => 'required|string|max:32',
            'nombre_producto' => 'required|string|max:128',
            'descripcion_producto' => 'required|string|max:128',
            'costo_compra' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'existencia_bodega' => 'required|integer|min:0',
            'existencia_minima' => 'required|integer|min:0',
            'estado' => 'required|integer',
        ]);

        $producto = Producto::create($validated);
        return response()->json($producto, 201);
    }

    public function show($id)
    {
        $producto = Producto::with(['categoria', 'movimiento_inventarios', 'venta_detalles'])->findOrFail($id);
        return response()->json($producto);
    }

    public function update(Request $request, $id)
    {
        $producto = Producto::findOrFail($id);
        $validated = $request->validate([
            'id_categoria' => 'sometimes|exists:categoria,categoria_id',
            'codigo_producto' => 'sometimes|string|max:32',
            'nombre_producto' => 'sometimes|string|max:128',
            'descripcion_producto' => 'sometimes|string|max:128',
            'costo_compra' => 'sometimes|numeric|min:0',
            'precio_venta' => 'sometimes|numeric|min:0',
            'existencia_bodega' => 'sometimes|integer|min:0',
            'existencia_minima' => 'sometimes|integer|min:0',
            'estado' => 'sometimes|integer',
        ]);

        $producto->update($validated);
        return response()->json($producto);
    }

    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);
        $producto->delete();
        return response()->json(['message' => 'Producto eliminado correctamente']);
    }
}
