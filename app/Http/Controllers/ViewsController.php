<?php

namespace App\Http\Controllers;

use App\Models\Views;
use App\Notifications\RegistroEliminado;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class ViewsController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $views = Views::select('id', 'name', 'url', 'modulo')->get();
        
        return Inertia::render('Views/Index', [
            'views' => $views,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Views/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //dd($request->all());
        $validated = $request->validate([
            'name' => 'required',
            'url' => 'required|unique:system_views,url',
        ]);

        $view = Views::create([
            'name' => $validated['name'],
            'url' => $validated['url'],
        ]);

        if ($request->assignPermissions) {
            $path = parse_url($view->url, PHP_URL_PATH) ?? $view->url;
            $last = basename(trim($path, '/'));
            $base = Str::slug($last, '-');

            $acciones = ['index', 'create', 'edit', 'delete', 'export', 'import', 'approve', 'reject', 'validate','multiple-approve', 'multiple-reject', 'multiple-delete', 'export-imss', 'export-sua', 'export-noi', 'export-cuentas', 'export-Empleados'];

            foreach ($acciones as $accion) {
                Permission::updateOrCreate([
                    'name' => $base . '.' . $accion,
                    'guard_name' => 'web',
                ], [
                    'name' => $base . '.' . $accion,
                    'guard_name' => 'web',
                ]);
            }
        }

        return redirect()->route('/views');
    }

    /**
     * Display the specified resource.
     */
    public function show(Views $views)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Views $view)
    {
        return Inertia::render('Views/Edit', [
            'view' => $view,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Views $view)
    {
        $validated = $request->validate([
            'name' => 'required',
            'url' => 'required|unique:system_views,url,' . $view->id,
        ]);

        $view->update([
            'name' => $validated['name'],
            'url' => $validated['url'],
        ]);

        if ($request->assignPermissions) {
            $path = parse_url($view->url, PHP_URL_PATH) ?? $view->url;
            $last = basename(trim($path, '/'));
            $base = Str::slug($last, '-');

            $acciones = ['index', 'create', 'edit', 'delete', 'export', 'import', 'approve', 'reject', 'validate','multiple-approve', 'multiple-reject', 'multiple-delete', 'export-imss', 'export-sua', 'export-noi', 'export-cuentas', 'export-Empleados'];

            foreach ($acciones as $accion) {
                Permission::firstOrCreate([
                    'name' => "{$base}.{$accion}",
                    'guard_name' => 'web',
                ]);
            }
        }

        return redirect()->route('/views');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Views $view)
    {
        $view->delete();

        return redirect()->route('/views');
    }

    public function destroyMultiple(Request $request)
    {
        //dd($request->all());
        $request->validate([
            'ids' => 'required|array',
        ]);

        Views::whereIn('id', $request->ids)->delete();

        $u = $request->user();
        $u->notify(new RegistroEliminado('Vistas', 3));

        return redirect()->route('/views');
    }
}
