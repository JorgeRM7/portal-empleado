<?php

namespace App\Http\Controllers;


use App\Models\Incidence;
use Inertia\Inertia;
use Illuminate\Http\Request;

class LibraryController
{


    public function index( Request $request ){
        $incidences = Incidence::whereNotNull('url_video')
        ->where('url_video', '!=', '')
        ->get();
        return Inertia::render('Library/Index', [
            'Incidences' => $incidences
        ]);
    }
}
