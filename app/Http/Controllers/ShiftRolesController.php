<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShiftRole;
use App\Models\Schedules;
use Inertia\Inertia;

class ShiftRolesController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shiftRoles = ShiftRole::all();

        return Inertia::render('ShiftRoles/Index', [
            'ShiftRoles' => $shiftRoles
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('ShiftRoles/Create', [
            'Schedules' => Schedules::select('id', 'name')->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'shiftName' => 'required|string|max:255',
            'isDynamicShift' => 'required|boolean',
            'holidayRule' => 'required|boolean',

            // No validamos schedule aquí porque depende del modo dinámico
            'weeklySchedule' => 'required|array',
            'dynamicRules' => 'nullable|array',
        ]);

        $data = $request->all();
        $shift = new ShiftRole();

        // Datos generales
        $shift->name = $data['shiftName'];
        $shift->dynamic = $data['isDynamicShift'] ? 1 : 0;
        $shift->holiday = $data['holidayRule'];

        //----------------------------------------------------------------------
        // ⭐ 1. Si el turno es dinámico → ignorar schedules y guardar rules JSON
        //----------------------------------------------------------------------
        if ($shift->dynamic) {

            $rules = collect($data['dynamicRules'])->map(function ($rule) {
                return [
                    'rest'            => $rule['isRestDay'] ? true : false,
                    'schedule'        => $rule['isRestDay'] ? null : $rule['schedule'],
                    'each'            => $rule['every'],
                    'type'            => $rule['type'],
                    'sunday_premium'  => $rule['sundayBonus'] ? true : false,
                ];
            });

            // Guardar reglas dinámicas
            $shift->rules = json_encode($rules->toArray());

            // Todos los schedules se guardan nulos
            $shift->monday_schedule_id = null;
            $shift->tuesday_schedule_id = null;
            $shift->wednesday_schedule_id = null;
            $shift->thursday_schedule_id = null;
            $shift->friday_schedule_id = null;
            $shift->saturday_schedule_id = null;
            $shift->sunday_schedule_id = null;

            // Todos los overtime por defecto
            $overtimeDefault = json_encode([
                "double_overtime" => "0",
                "triple_overtime" => "0"
            ]);

            $shift->monday_overtimes = $overtimeDefault;
            $shift->tuesday_overtimes = $overtimeDefault;
            $shift->wednesday_overtimes = $overtimeDefault;
            $shift->thursday_overtimes = $overtimeDefault;
            $shift->friday_overtimes = $overtimeDefault;
            $shift->saturday_overtimes = $overtimeDefault;
            $shift->sunday_overtimes = $overtimeDefault;

            $shift->save();
            return redirect()->route('shift-roles.index')
                ->with('success', 'Rol de turno creado');
            
        }

        //----------------------------------------------------------------------
        // ⭐ 2. Si el turno NO es dinámico (turno fijo) → guardar horario por día
        //----------------------------------------------------------------------
        $week = $data['weeklySchedule'];

        $defaultOvertime = json_encode([
            "double_overtime" => "0",
            "triple_overtime" => "0"
        ]);

        // Función para ahorrar código
        $setDay = function ($dayData, $isSunday = false) use ($defaultOvertime) {
            if ($dayData['isRestDay']) {
                return [
                    "schedule" => null,
                    "overtime" => $defaultOvertime
                ];
            }

            if ($isSunday && isset($dayData['hasSundayBonus']) && $dayData['hasSundayBonus']) {
                return [
                    "schedule" => $dayData['schedule'],
                    "overtime" => json_encode(["sunday_premium" => true])
                ];
            }

            return [
                "schedule" => $dayData['schedule'],
                "overtime" => $defaultOvertime
            ];
        };

        // Llenamos cada día
        $monday    = $setDay($week['monday']);
        $tuesday   = $setDay($week['tuesday']);
        $wednesday = $setDay($week['wednesday']);
        $thursday  = $setDay($week['thursday']);
        $friday    = $setDay($week['friday']);
        $saturday  = $setDay($week['saturday']);
        $sunday    = $setDay($week['sunday'], true); // ⭐ domingo especial

        // Asignar en el modelo
        $shift->rules = null; // no se usa en turnos fijos

        $shift->monday_schedule_id     = $monday['schedule'];
        $shift->monday_overtimes       = $monday['overtime'];

        $shift->tuesday_schedule_id    = $tuesday['schedule'];
        $shift->tuesday_overtimes      = $tuesday['overtime'];

        $shift->wednesday_schedule_id  = $wednesday['schedule'];
        $shift->wednesday_overtimes    = $wednesday['overtime'];

        $shift->thursday_schedule_id   = $thursday['schedule'];
        $shift->thursday_overtimes     = $thursday['overtime'];

        $shift->friday_schedule_id     = $friday['schedule'];
        $shift->friday_overtimes       = $friday['overtime'];

        $shift->saturday_schedule_id   = $saturday['schedule'];
        $shift->saturday_overtimes     = $saturday['overtime'];

        $shift->sunday_schedule_id     = $sunday['schedule'];
        $shift->sunday_overtimes       = $sunday['overtime'];

        $shift->save();

        return redirect()->route('shift-roles.index')
                ->with('success', 'Rol de turno creado');
    }

    /**
     * Display the specified resource.
     */
    public function show(ShiftRole $shiftRole)
    {
        return Inertia::render('ShiftRoles/Show', [
            'shiftRole' => $shiftRole,
            'Schedules' => Schedules::select('id', 'name')->get(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ShiftRole $shiftRole)
    {
        return Inertia::render('ShiftRoles/Edit', [
            'shiftRole' => $shiftRole,
            'Schedules' => Schedules::select('id', 'name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ShiftRole $shiftRole)
    {
        $request->validate([
            'shiftName' => 'required|string|max:255',
            'isDynamicShift' => 'required|boolean',
            'holidayRule' => 'required|boolean',
            'weeklySchedule' => 'required|array',
            'dynamicRules' => 'nullable|array',
        ]);

        $data = $request->all();

        // Datos generales
        $shiftRole->name = $data['shiftName'];
        $shiftRole->dynamic = $data['isDynamicShift'] ? 1 : 0;
        $shiftRole->holiday = $data['holidayRule'];

        // ------------------ TURNO DINÁMICO ------------------
        if ($shiftRole->dynamic) {
            $rules = collect($data['dynamicRules'])->map(fn ($rule) => [
                'rest' => (bool) $rule['isRestDay'],
                'schedule' => $rule['isRestDay'] ? null : $rule['schedule'],
                'each' => $rule['every'],
                'type' => $rule['type'],
                'sunday_premium' => (bool) $rule['sundayBonus'],
            ]);

            $shiftRole->rules = json_encode($rules->toArray());

            $shiftRole->monday_schedule_id = null;
            $shiftRole->tuesday_schedule_id = null;
            $shiftRole->wednesday_schedule_id = null;
            $shiftRole->thursday_schedule_id = null;
            $shiftRole->friday_schedule_id = null;
            $shiftRole->saturday_schedule_id = null;
            $shiftRole->sunday_schedule_id = null;

            $shiftRole->save();

            return redirect()
                ->route('shift-roles.index')
                ->with('success', 'Turno dinámico actualizado');
        }

        // ------------------ TURNO FIJO ------------------
        $week = $data['weeklySchedule'];

        $defaultOvertime = json_encode([
            'double_overtime' => '0',
            'triple_overtime' => '0',
        ]);

        $setDay = function ($day, $isSunday = false) use ($defaultOvertime) {
            if ($day['isRestDay']) {
                return [null, $defaultOvertime];
            }

            if ($isSunday && !empty($day['hasSundayBonus'])) {
                return [$day['schedule'], json_encode(['sunday_premium' => true])];
            }

            return [$day['schedule'], $defaultOvertime];
        };

        [
            [$shiftRole->monday_schedule_id, $shiftRole->monday_overtimes],
            [$shiftRole->tuesday_schedule_id, $shiftRole->tuesday_overtimes],
            [$shiftRole->wednesday_schedule_id, $shiftRole->wednesday_overtimes],
            [$shiftRole->thursday_schedule_id, $shiftRole->thursday_overtimes],
            [$shiftRole->friday_schedule_id, $shiftRole->friday_overtimes],
            [$shiftRole->saturday_schedule_id, $shiftRole->saturday_overtimes],
            [$shiftRole->sunday_schedule_id, $shiftRole->sunday_overtimes],
        ] = [
            $setDay($week['monday']),
            $setDay($week['tuesday']),
            $setDay($week['wednesday']),
            $setDay($week['thursday']),
            $setDay($week['friday']),
            $setDay($week['saturday']),
            $setDay($week['sunday'], true),
        ];

        $shiftRole->rules = null;
        $shiftRole->save();

        return redirect()
            ->route('shift-roles.index')
            ->with('success', 'Turno actualizado');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShiftRole $shiftRole)
    {
        $shiftRole->delete();
        return redirect()->back()->with('success', 'Registro eliminado exitosamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:shift_roles,id',
        ]);

        ShiftRole::whereIn('id', $request->ids)->delete();

        return redirect()->back()->with('success', 'Registros eliminados');
    }

}
