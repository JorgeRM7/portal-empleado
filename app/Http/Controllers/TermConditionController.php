<?php

namespace App\Http\Controllers;
use Inertia\Inertia;

class TermConditionController
{
    public function index()
    {
        $data = [];
        return Inertia::render('TermConditions/Index', [
            'data' => $data
        ]);
    }
}