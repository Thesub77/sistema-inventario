<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    private function defaultConfiguration(): array
    {
        return [
            'productos' => [
                'modo_codigo' => 'manual',
                'prefijo_codigo' => 'PROD',
                'modo_codigo_combo' => 'manual',
                'prefijo_codigo_combo' => 'COMBO',
                'stock_minimo_defecto' => 5,
                'categoria_sugerir_prefijo' => false,
                'prefijo_categoria' => 'CAT',
            ],
            'sistema' => [
                'nivel_bitacora' => 'completa',
            ],
        ];
    }

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

        $config = array_replace_recursive($this->defaultConfiguration(), $empresa->configuracion ?? []);
        $empresa->setAttribute('configuracion', $config);

        return response()->json($empresa);
    }

    /**
     * Actualiza la información del negocio (identidad comercial, fiscal y configuración).
     */
    public function update(Request $request): JsonResponse
    {
        $empresa = Empresa::where('estado', 1)->first() ?? Empresa::first();

        $validated = $request->validate([
            'nombre_comercial' => 'sometimes|required|string|max:128',
            'razon_social' => 'nullable|string|max:128',
            'numero_ruc' => ['sometimes', 'nullable', 'string', 'max:32', 'regex:/^[a-zA-Z0-9]+$/'],
            'telefono_contacto' => ['sometimes', 'required', 'string', 'regex:/^(\d{8}|\d{4}-\d{4})$/'],
            'correo_contacto' => 'nullable|email|max:128',
            'direccion_fisica' => 'sometimes|required|string|max:255',
            'mensaje_pie_ticket' => 'nullable|string|max:255',
            'moneda_simbolo' => 'sometimes|required|string|max:8',
            'regimen_tributario' => 'sometimes|required|string|max:64',
            'techo_mensual_cuota_fija' => 'sometimes|required|numeric|min:0',
            'configuracion' => 'nullable|array',
            'configuracion.productos' => 'nullable|array',
            'configuracion.productos.modo_codigo' => 'nullable|string|in:manual,automatico',
            'configuracion.productos.prefijo_codigo' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z0-9\-]+$/'],
            'configuracion.productos.modo_codigo_combo' => 'nullable|string|in:manual,automatico',
            'configuracion.productos.prefijo_codigo_combo' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z0-9\-]+$/'],
            'configuracion.productos.stock_minimo_defecto' => 'nullable|integer|min:0|max:9999',
            'configuracion.productos.categoria_sugerir_prefijo' => 'nullable|boolean',
            'configuracion.productos.prefijo_categoria' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z0-9\-]+$/'],
            'configuracion.sistema' => 'nullable|array',
            'configuracion.sistema.nivel_bitacora' => 'nullable|string|in:completa,esencial',
        ], [
            'configuracion.productos.prefijo_codigo.regex' => 'El prefijo de productos solo debe contener letras, números y guiones.',
            'configuracion.productos.prefijo_codigo_combo.regex' => 'El prefijo de combos solo debe contener letras, números y guiones.',
            'configuracion.productos.prefijo_categoria.regex' => 'El prefijo de categorías solo debe contener letras, números y guiones.',
            'nombre_comercial.required' => 'El nombre comercial de la empresa es obligatorio.',
            'numero_ruc.regex' => 'El número RUC solo debe contener letras y números.',
            'telefono_contacto.required' => 'El teléfono de contacto es obligatorio.',
            'telefono_contacto.regex' => 'El teléfono de contacto debe tener 8 dígitos numéricos (ej. 8888-8888).',
            'direccion_fisica.required' => 'La dirección física es obligatoria.',
            'correo_contacto.email' => 'El correo electrónico debe tener un formato válido.',
            'regimen_tributario.required' => 'El régimen tributario es obligatorio.',
            'techo_mensual_cuota_fija.required' => 'El techo mensual de cuota fija es obligatorio.',
            'techo_mensual_cuota_fija.numeric' => 'El techo mensual debe ser un monto numérico válido.',
            'techo_mensual_cuota_fija.min' => 'El techo mensual no puede ser negativo.',
        ]);

        if (isset($validated['telefono_contacto'])) {
            $validated['telefono_contacto'] = preg_replace('/\D/', '', $validated['telefono_contacto']);
        }
        if (isset($validated['numero_ruc']) && $validated['numero_ruc'] !== null) {
            $validated['numero_ruc'] = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $validated['numero_ruc']));
        }
        $validated['regimen_tributario'] = 'Cuota Fija';

        if (isset($validated['configuracion'])) {
            $existingConfig = $empresa ? ($empresa->configuracion ?? $this->defaultConfiguration()) : $this->defaultConfiguration();
            $validated['configuracion'] = array_replace_recursive($existingConfig, $validated['configuracion']);
        }

        if (! $empresa) {
            $empresa = Empresa::create($validated + ['estado' => 1]);
        } else {
            $empresa->update($validated);
        }

        $config = array_replace_recursive($this->defaultConfiguration(), $empresa->configuracion ?? []);
        $empresa->setAttribute('configuracion', $config);

        return response()->json([
            'success' => true,
            'message' => 'Datos del negocio actualizados correctamente.',
            'empresa' => $empresa,
        ]);
    }
}
