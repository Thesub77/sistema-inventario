<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    /**
     * Valida que el usuario sea Administrador o posea el permiso explícito.
     */
    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        $esAdmin = $user && method_exists($user, 'esAdmin') ? $user->esAdmin() : (strtolower($user->rol->nombre_rol ?? '') === 'administrador');
        if (! $esAdmin && ! ($user && method_exists($user, 'tienePermiso') && $user->tienePermiso('proveedores.gestionar'))) {
            abort(response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo administradores pueden gestionar proveedores y cuentas por pagar.',
            ], 403));
        }
    }

    /**
     * Listado de proveedores con estadísticas de saldo adeudado.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $query = Proveedor::withCount(['cuentasPorPagar as total_facturas'])
            ->withSum(['cuentasPorPagar as saldo_total_pendiente' => function ($q) {
                $q->whereIn('estado', ['Pendiente', 'Parcial']);
            }], 'saldo_pendiente')
            ->orderBy('nombre_comercial');

        if ($request->filled('buscar')) {
            $term = trim($request->input('buscar'));
            $query->where(function ($q) use ($term) {
                $q->where('nombre_comercial', 'like', "%{$term}%")
                    ->orWhere('contacto_vendedor', 'like', "%{$term}%")
                    ->orWhere('telefono', 'like', "%{$term}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', (int) $request->input('estado'));
        }

        if ($request->filled('por_pagina')) {
            $perPage = (int) $request->input('por_pagina', 10);

            return response()->json($query->paginate($perPage)->withQueryString());
        }

        $proveedores = $query->get()->map(function ($p) {
            $p->saldo_total_pendiente = (float) ($p->saldo_total_pendiente ?? 0.00);

            return $p;
        });

        return response()->json($proveedores);
    }

    /**
     * Registro de nuevo proveedor.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'nombre_comercial' => 'required|string|min:2|max:128',
            'contacto_vendedor' => 'nullable|string|max:128',
            'telefono' => 'nullable|string|max:32',
            'plazo_credito_dias' => 'nullable|integer|min:0|max:365',
        ], [
            'nombre_comercial.required' => 'El nombre comercial del proveedor es obligatorio.',
            'nombre_comercial.min' => 'El nombre comercial debe tener al menos 2 caracteres.',
            'plazo_credito_dias.integer' => 'El plazo de crédito debe ser un número entero de días.',
        ]);

        $proveedor = Proveedor::create([
            'nombre_comercial' => trim($validated['nombre_comercial']),
            'contacto_vendedor' => ! empty($validated['contacto_vendedor']) ? trim($validated['contacto_vendedor']) : null,
            'telefono' => ! empty($validated['telefono']) ? trim($validated['telefono']) : null,
            'plazo_credito_dias' => (int) ($validated['plazo_credito_dias'] ?? 0),
            'estado' => 1,
        ]);

        Bitacora::create([
            'id_usuario' => $request->user()->usuario_id,
            'accion_bitacora' => 'PROVEEDOR_CREADO',
            'descripcion_bitacora' => "Se registró al proveedor '{$proveedor->nombre_comercial}' (ID: {$proveedor->proveedor_id}).",
            'fecha_hora_bitacora' => now(),
            'estado' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Proveedor registrado exitosamente.',
            'proveedor' => $proveedor,
        ], 201);
    }

    /**
     * Detalle de un proveedor específico junto a sus facturas.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $proveedor = Proveedor::with(['cuentasPorPagar' => function ($q) {
            $q->orderByDesc('fecha_emision');
        }])->findOrFail($id);

        return response()->json($proveedor);
    }

    /**
     * Actualización de datos del proveedor.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $proveedor = Proveedor::findOrFail($id);

        $validated = $request->validate([
            'nombre_comercial' => 'required|string|min:2|max:128',
            'contacto_vendedor' => 'nullable|string|max:128',
            'telefono' => 'nullable|string|max:32',
            'plazo_credito_dias' => 'nullable|integer|min:0|max:365',
            'estado' => 'nullable|integer|in:0,1',
        ]);

        $proveedor->update([
            'nombre_comercial' => trim($validated['nombre_comercial']),
            'contacto_vendedor' => array_key_exists('contacto_vendedor', $validated) ? trim((string) $validated['contacto_vendedor']) : $proveedor->contacto_vendedor,
            'telefono' => array_key_exists('telefono', $validated) ? trim((string) $validated['telefono']) : $proveedor->telefono,
            'plazo_credito_dias' => array_key_exists('plazo_credito_dias', $validated) ? (int) $validated['plazo_credito_dias'] : $proveedor->plazo_credito_dias,
            'estado' => array_key_exists('estado', $validated) ? (int) $validated['estado'] : $proveedor->estado,
        ]);

        Bitacora::create([
            'id_usuario' => $request->user()->usuario_id,
            'accion_bitacora' => 'PROVEEDOR_ACTUALIZADO',
            'descripcion_bitacora' => "Se actualizaron los datos del proveedor '{$proveedor->nombre_comercial}' (ID: {$proveedor->proveedor_id}).",
            'fecha_hora_bitacora' => now(),
            'estado' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Proveedor actualizado exitosamente.',
            'proveedor' => $proveedor,
        ]);
    }

    /**
     * Desactivación o eliminación lógica del proveedor.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $proveedor = Proveedor::findOrFail($id);

        // Validar si tiene deudas pendientes activas
        $deudaPendiente = $proveedor->cuentasPorPagar()
            ->whereIn('estado', ['Pendiente', 'Parcial'])
            ->where('saldo_pendiente', '>', 0)
            ->sum('saldo_pendiente');

        if ($deudaPendiente > 0) {
            return response()->json([
                'success' => false,
                'message' => "No se puede desactivar al proveedor '{$proveedor->nombre_comercial}' porque mantiene facturas con saldo pendiente por pagar (C$ ".number_format($deudaPendiente, 2).').',
            ], 422);
        }

        $proveedor->update(['estado' => 0]);

        Bitacora::create([
            'id_usuario' => $request->user()->usuario_id,
            'accion_bitacora' => 'PROVEEDOR_DESACTIVADO',
            'descripcion_bitacora' => "Se desactivó al proveedor '{$proveedor->nombre_comercial}' (ID: {$proveedor->proveedor_id}).",
            'fecha_hora_bitacora' => now(),
            'estado' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Proveedor desactivado correctamente.',
        ]);
    }
}
