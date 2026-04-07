<?php

namespace App\Http\Controllers;

use App\Models\Benefit;
use App\Models\CategoryIncidence;
use App\Models\BenefitCategoryIncidence;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BenefitsController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $benefits = Benefit::index();

        return Inertia::render('Benefits/Index', [
            'Benefits' => $benefits,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $categoryIncidences = CategoryIncidence::select('name', 'id')->get();

        return Inertia::render('Benefits/Create', [
            'CategoryIncidences' => $categoryIncidences,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // ------------------ BENEFIT ------------------
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['days', 'months'])],
            'each' => ['required', 'integer', 'min:0', 'max:30'],
            'cut_day' => ['required', 'integer', 'min:0', 'max:31'],

            'conditioned' => ['required', 'boolean'],
            'conditioned_seniority' => ['required', 'boolean'],
            'conditioned_efficiency' => ['required', 'boolean'],

            // ------------------ EFICIENCIA ------------------
            'efficiency_rules' => [
                'nullable',
                Rule::requiredIf(fn () => $request->boolean('conditioned_efficiency')),
                'array'
            ],
            'efficiency_rules.*.amount' => [
                'nullable',
                Rule::requiredIf(fn () => $request->boolean('conditioned_efficiency')),
                'numeric',
                'min:0'
            ],
            'efficiency_rules.*.operator' => [
                'nullable',
                Rule::requiredIf(fn () => $request->boolean('conditioned_efficiency')),
                Rule::in(['>', '>=', '=', '<=', '<'])
            ],
            'efficiency_rules.*.percent' => [
                'nullable',
                Rule::requiredIf(fn () => $request->boolean('conditioned_efficiency')),
                'numeric',
                'min:0'
            ],

            // ------------------ VINCULAR ------------------
            'link' => ['boolean'],

            'incidence_category' => [
                'nullable',
                Rule::requiredIf(fn () => $request->boolean('link')),
                'integer'
            ],
            'quantity' => [
                'nullable',
                Rule::requiredIf(fn () => $request->boolean('link')),
                'numeric',
                'min:0'
            ],
            'active' => [
                'nullable',
                Rule::requiredIf(fn () => $request->boolean('link')),
                'boolean',
            ],
        ],
        [
            // ---------------- BENEFIT ----------------
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',

            'description.required' => 'La descripción es obligatoria.',
            'description.max' => 'La descripción no debe exceder los 255 caracteres.',

            'type.required' => 'El tipo es obligatorio.',
            'type.in' => 'El tipo seleccionado no es válido.',

            'each.required' => 'El campo "Cada" es obligatorio.',
            'each.integer' => 'El campo "Cada" debe ser un número entero.',
            'each.min' => 'El campo "Cada" no puede ser menor a 0.',
            'each.max' => 'El campo "Cada" no puede ser mayor a 30.',

            'cut_day.required' => 'El día de corte es obligatorio.',
            'cut_day.integer' => 'El día de corte debe ser un número entero.',
            'cut_day.min' => 'El día de corte no puede ser menor a 0.',
            'cut_day.max' => 'El día de corte no puede ser mayor a 31.',

            'conditioned.required' => 'Debe indicar si la prestación está condicionada.',
            'conditioned.boolean' => 'El valor de condicionado no es válido.',

            'conditioned_seniority.required' => 'Debe indicar si la prestación depende de la antigüedad.',
            'conditioned_seniority.boolean' => 'El valor de antigüedad no es válido.',

            'conditioned_efficiency.required' => 'Debe indicar si la prestación depende de la eficiencia.',
            'conditioned_efficiency.boolean' => 'El valor de eficiencia no es válido.',

            // ---------------- EFICIENCIA ----------------
            'efficiency_rules.required' =>
                'Debe agregar al menos una regla de eficiencia cuando la prestación está condicionada por eficiencia.',

            'efficiency_rules.array' =>
                'Las reglas de eficiencia deben enviarse en formato válido.',

            'efficiency_rules.*.amount.required' =>
                'El monto es obligatorio en cada regla de eficiencia.',
            'efficiency_rules.*.amount.numeric' =>
                'El monto debe ser un valor numérico.',
            'efficiency_rules.*.amount.min' =>
                'El monto no puede ser negativo.',

            'efficiency_rules.*.operator.required' =>
                'El operador es obligatorio en cada regla de eficiencia.',
            'efficiency_rules.*.operator.in' =>
                'El operador seleccionado no es válido.',

            'efficiency_rules.*.percent.required' =>
                'El valor de comparación es obligatorio.',
            'efficiency_rules.*.percent.numeric' =>
                'El valor de comparación debe ser numérico.',
            'efficiency_rules.*.percent.min' =>
                'El valor de comparación no puede ser negativo.',

            // ---------------- VINCULAR ----------------
            'incidence_category.required' =>
                'La categoría de incidencia es obligatoria cuando se activa la opción de vincular.',

            'quantity.required' =>
                'La cantidad es obligatoria cuando se activa la opción de vincular.',
            'quantity.numeric' =>
                'La cantidad debe ser un valor numérico.',
            'quantity.min' =>
                'La cantidad no puede ser negativa.',

            'active.required' =>
                'Debe indicar si la relación está activa.',
            'active.boolean' =>
                'El valor de activo no es válido.',
        ]
        );

        DB::beginTransaction();

        try {

            // ------------------ CREAR BENEFIT ------------------
            $benefit = Benefit::create([
                'name'                     => $validated['name'],
                'description'              => $validated['description'],
                'type'                     => $validated['type'],
                'each'                     => $validated['each'],
                'day_cutoff'               => $validated['cut_day'],
                'conditioned'              => $validated['conditioned'],
                'conditioned_seniority'    => $validated['conditioned_seniority'],
                'conditioned_efficiency'   => $validated['conditioned_efficiency'],
                'efficiency_rules'         => $validated['conditioned_efficiency']
                    ? $validated['efficiency_rules']
                    : null,
            ]);

            // ------------------ VINCULAR CATEGORÍA ------------------
            if ($request->boolean('link')) {
                BenefitCategoryIncidence::create([
                    'benefit_id'              => $benefit->id,
                    'category_incidence_id'   => $validated['incidence_category'],
                    'quantity'                => $validated['quantity'],
                    'active'                  => $validated['active'],
                ]);
            }

            DB::commit();

            return redirect()
                ->route('benefits.index')
                ->with('success', 'Prestación creada correctamente');

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Error al crear prestación', [
                'payload' => $validated,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al crear la prestación.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $categoryIncidences = CategoryIncidence::select('name', 'id')->get();

        return Inertia::render('Benefits/Show', [
            'Benefits' => Benefit::show($id),
            'CategoryIncidences' => $categoryIncidences,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $categoryIncidences = CategoryIncidence::select('name', 'id')->get();

        return Inertia::render('Benefits/Edit', [
            'Benefits' => Benefit::show($id),
            'CategoryIncidences' => $categoryIncidences,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Benefit $benefit)
    {

        $validated = $request->validate([
            // ------------------ BENEFIT ------------------
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['days', 'months'])],
            'each' => ['required', 'integer', 'min:0', 'max:30'],
            'cut_day' => ['required', 'integer', 'min:0', 'max:31'],

            'conditioned' => ['required', 'boolean'],
            'conditioned_seniority' => ['required', 'boolean'],
            'conditioned_efficiency' => ['required', 'boolean'],

            // ------------------ EFICIENCIA ------------------
            'efficiency_rules' => [
                'nullable',
                Rule::requiredIf(fn () => $request->boolean('conditioned_efficiency')),
                'array'
            ],
            'efficiency_rules.*.amount' => [
                'nullable',
                Rule::requiredIf(fn () => $request->boolean('conditioned_efficiency')),
                'numeric',
                'min:0'
            ],
            'efficiency_rules.*.operator' => [
                'nullable',
                Rule::requiredIf(fn () => $request->boolean('conditioned_efficiency')),
                Rule::in(['>', '>=', '=', '<=', '<'])
            ],
            'efficiency_rules.*.percent' => [
                'nullable',
                Rule::requiredIf(fn () => $request->boolean('conditioned_efficiency')),
                'numeric',
                'min:0'
            ],

            // ------------------ VINCULAR ------------------
            'link' => ['boolean'],

            'incidence_category' => [
                'nullable',
                Rule::requiredIf(fn () => $request->boolean('link')),
                'integer'
            ],
            'quantity' => [
                'nullable',
                Rule::requiredIf(fn () => $request->boolean('link')),
                'numeric',
                'min:0'
            ],
            'active' => [
                'nullable',
                Rule::requiredIf(fn () => $request->boolean('link')),
                'boolean',
            ],
        ],
        [
            // ---------------- BENEFIT ----------------
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',

            'description.required' => 'La descripción es obligatoria.',
            'description.max' => 'La descripción no debe exceder los 255 caracteres.',

            'type.required' => 'El tipo es obligatorio.',
            'type.in' => 'El tipo seleccionado no es válido.',

            'each.required' => 'El campo "Cada" es obligatorio.',
            'each.integer' => 'El campo "Cada" debe ser un número entero.',
            'each.min' => 'El campo "Cada" no puede ser menor a 0.',
            'each.max' => 'El campo "Cada" no puede ser mayor a 30.',

            'cut_day.required' => 'El día de corte es obligatorio.',
            'cut_day.integer' => 'El día de corte debe ser un número entero.',
            'cut_day.min' => 'El día de corte no puede ser menor a 0.',
            'cut_day.max' => 'El día de corte no puede ser mayor a 31.',

            'conditioned.required' => 'Debe indicar si la prestación está condicionada.',
            'conditioned.boolean' => 'El valor de condicionado no es válido.',

            'conditioned_seniority.required' => 'Debe indicar si la prestación depende de la antigüedad.',
            'conditioned_seniority.boolean' => 'El valor de antigüedad no es válido.',

            'conditioned_efficiency.required' => 'Debe indicar si la prestación depende de la eficiencia.',
            'conditioned_efficiency.boolean' => 'El valor de eficiencia no es válido.',

            // ---------------- EFICIENCIA ----------------
            'efficiency_rules.required' =>
                'Debe agregar al menos una regla de eficiencia cuando la prestación está condicionada por eficiencia.',

            'efficiency_rules.array' =>
                'Las reglas de eficiencia deben enviarse en formato válido.',

            'efficiency_rules.*.amount.required' =>
                'El monto es obligatorio en cada regla de eficiencia.',
            'efficiency_rules.*.amount.numeric' =>
                'El monto debe ser un valor numérico.',
            'efficiency_rules.*.amount.min' =>
                'El monto no puede ser negativo.',

            'efficiency_rules.*.operator.required' =>
                'El operador es obligatorio en cada regla de eficiencia.',
            'efficiency_rules.*.operator.in' =>
                'El operador seleccionado no es válido.',

            'efficiency_rules.*.percent.required' =>
                'El valor de comparación es obligatorio.',
            'efficiency_rules.*.percent.numeric' =>
                'El valor de comparación debe ser numérico.',
            'efficiency_rules.*.percent.min' =>
                'El valor de comparación no puede ser negativo.',

            // ---------------- VINCULAR ----------------
            'incidence_category.required' =>
                'La categoría de incidencia es obligatoria cuando se activa la opción de vincular.',

            'quantity.required' =>
                'La cantidad es obligatoria cuando se activa la opción de vincular.',
            'quantity.numeric' =>
                'La cantidad debe ser un valor numérico.',
            'quantity.min' =>
                'La cantidad no puede ser negativa.',

            'active.required' =>
                'Debe indicar si la relación está activa.',
            'active.boolean' =>
                'El valor de activo no es válido.',
        ]
        );

        DB::beginTransaction();

        try {

            $benefit->update([
                'name'                   => $validated['name'],
                'description'            => $validated['description'],
                'type'                   => $validated['type'],
                'each'                   => $validated['each'],
                'day_cutoff'             => $validated['cut_day'],
                'conditioned'            => $validated['conditioned'],
                'conditioned_seniority'  => $validated['conditioned_seniority'],
                'conditioned_efficiency' => $validated['conditioned_efficiency'],
                'efficiency_rules'       => $validated['conditioned_efficiency']
                    ? $validated['efficiency_rules']
                    : null,
            ]);

            if ($request->boolean('link')) {

                BenefitCategoryIncidence::updateOrCreate(
                    [
                        'benefit_id' => $benefit->id
                    ],
                    [
                        'category_incidence_id' => $validated['incidence_category'],
                        'quantity'              => $validated['quantity'],
                        'active'                => $validated['active']
                    ]
                );

            } else {
                BenefitCategoryIncidence::where('benefit_id', $benefit->id)->delete();
            }

            DB::commit();

            return redirect()
                ->route('benefits.index')
                ->with('success', 'Prestación actualizada correctamente');

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error al actualizar beneficio', [
                'benefit_id' => $benefit->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al actualizar la prestación.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Benefit $benefit)
    {
        $benefit->delete();
        BenefitCategoryIncidence::where('benefit_id', $benefit->id)->delete();
        
        return redirect()->back()->with('success', 'Registro eliminado exitosamente.');
    }
}
