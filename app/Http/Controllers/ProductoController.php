<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Bitacora;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::with('categoria')->get();
        return response()->json($productos);
    }

    /**
     * RF-05: Retorna solo productos activos (estado = 1) para el POS.
     */
    public function activos()
    {
        $productos = Producto::with('categoria')->where('estado', 1)->get();
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

    /**
     * RF-05: Alterna el estado de un producto entre Activo (1) e Inactivo (0).
     * Registra el cambio en bitácora para auditoría.
     */
    public function toggleEstado($id)
    {
        $producto = Producto::findOrFail($id);
        $nuevoEstado = $producto->estado == 1 ? 0 : 1;
        $producto->update(['estado' => $nuevoEstado]);

        Bitacora::create([
            'id_usuario' => 1,
            'accion_bitacora' => 'CAMBIO_ESTADO_PRODUCTO',
            'descripcion_bitacora' => "Producto \"{$producto->nombre_producto}\" " . ($nuevoEstado ? 'activado' : 'desactivado'),
            'fecha_hora_bitacora' => now(),
            'estado' => 1,
        ]);

        return response()->json([
            'message' => 'Estado del producto actualizado correctamente',
            'producto' => $producto->load('categoria'),
        ]);
    }

    /**
     * RF-05: Borrado lógico — desactiva el producto en vez de eliminarlo de la BD.
     * Preserva el historial de ventas pasadas.
     */
    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);

        if ($producto->estado == 0) {
            return response()->json(['message' => 'El producto ya se encuentra inactivo'], 422);
        }

        $producto->update(['estado' => 0]);

        Bitacora::create([
            'id_usuario' => 1,
            'accion_bitacora' => 'DESACTIVAR_PRODUCTO',
            'descripcion_bitacora' => "Producto \"{$producto->nombre_producto}\" desactivado (borrado lógico)",
            'fecha_hora_bitacora' => now(),
            'estado' => 1,
        ]);

        return response()->json(['message' => 'Producto desactivado correctamente']);
    }
}
