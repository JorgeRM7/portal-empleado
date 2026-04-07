<?php

namespace App\Http\Controllers;

use App\Services\HikVisionService;
use Illuminate\Http\Request;

class HikcentralController
{
    public function __construct(
        protected HikVisionService $hikVisionService
    ) {}

    public function getPersonByCode(Request $request)
{
    $request->validate([
        'personCode' => 'required',
    ]);

    $result = $this->hikVisionService->getPersonByCode((string) $request->personCode);

    return response()->json([
        'config_host' => config('services.hikcentral.host'),
        'app_key' => config('services.hikcentral.app_key'),
        'status' => $result['response']->status(),
        'json' => $result['json'],
        'raw' => $result['response']->body(),
    ]);
}
}