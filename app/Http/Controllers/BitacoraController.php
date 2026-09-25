<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use Illuminate\Http\Request;

class BitacoraController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'buscar' => 'sometimes|string|max:128',
            'id_usuario' => 'sometimes|integer|exists:usuario,usuario_id',
            'accion' => 'sometimes|string|max:64',
            'fecha_desde' => 'sometimes|date',
            'fecha_hasta' => 'sometimes|date',
            'por_pagina' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);

        $query = Bitacora::with('usuario')->orderByDesc('id_bitacora');

        if (! empty($validated['buscar'])) {
            $query->where(function ($q) use ($validated) {
                $q->whereLike('accion_bitacora', '%'.$validated['buscar'].'%')
                    ->orWhereLike('descripcion_bitacora', '%'.$validated['buscar'].'%');
            });
        }

        if (! empty($validated['id_usuario'])) {
            $query->where('id_usuario', $validated['id_usuario']);
        }

        if (! empty($validated['accion'])) {
            $query->whereLike('accion_bitacora', "%{$validated['accion']}%");
        }

        if (! empty($validated['fecha_desde'])) {
            $query->whereDate('fecha_hora_bitacora', '>=', $validated['fecha_desde']);
        }

        if (! empty($validated['fecha_hasta'])) {
            $query->whereDate('fecha_hora_bitacora', '<=', $validated['fecha_hasta']);
        }

        if ($request->has('por_pagina')) {
            return response()->json($query->paginate((int) $validated['por_pagina'])->withQueryString());
        }

        return response()->json($query->get());
    }

    public function show($id)
    {
        $bitacora = Bitacora::with('usuario')->findOrFail($id);

        return response()->json($bitacora);
    }
}
