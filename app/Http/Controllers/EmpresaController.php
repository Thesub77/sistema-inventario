<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    /**
     * Obtiene la información del negocio para facturación y comprobantes.
     */
    public function show(): JsonResponse
    {
        $empresa = Empresa::where('estado', 1)->first() ?? Empresa::first();

        if (! $empresa) {
            return response()->json([
                'success' => false,
                'message' => 'No hay datos de negocio configurados.',
            ], 404);
        }

        return response()->json($empresa);
    }

    /**
     * Actualiza la información del negocio (identidad comercial y fiscal).
     */
    public function update(Request $request): JsonResponse
    {
        $empresa = Empresa::where('estado', 1)->first() ?? Empresa::first();

        $validated = $request->validate([
            'nombre_comercial' => 'sometimes|required|string|max:128',
            'razon_social' => 'nullable|string|max:128',
            'numero_ruc' => 'sometimes|required|string|max:32',
            'telefono_contacto' => 'sometimes|required|string|max:32',
            'correo_contacto' => 'nullable|email|max:128',
            'direccion_fisica' => 'sometimes|required|string|max:255',
            'mensaje_pie_ticket' => 'nullable|string|max:255',
            'moneda_simbolo' => 'sometimes|required|string|max:8',
            'regimen_tributario' => 'sometimes|required|string|max:64',
            'techo_mensual_cuota_fija' => 'sometimes|required|numeric|min:0',
        ], [
            'nombre_comercial.required' => 'El nombre comercial de la empresa es obligatorio.',
            'numero_ruc.required' => 'El número RUC o identificación tributaria es obligatorio.',
            'telefono_contacto.required' => 'El teléfono de contacto es obligatorio.',
            'direccion_fisica.required' => 'La dirección física es obligatoria.',
            'correo_contacto.email' => 'El correo electrónico debe tener un formato válido.',
            'regimen_tributario.required' => 'El régimen tributario es obligatorio.',
            'techo_mensual_cuota_fija.required' => 'El techo mensual de cuota fija es obligatorio.',
            'techo_mensual_cuota_fija.numeric' => 'El techo mensual debe ser un monto numérico válido.',
            'techo_mensual_cuota_fija.min' => 'El techo mensual no puede ser negativo.',
        ]);

        if (! $empresa) {
            $empresa = Empresa::create($validated + ['estado' => 1]);
        } else {
            $empresa->update($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Datos del negocio actualizados correctamente.',
            'empresa' => $empresa,
        ]);
    }
}
