<?php

use Illuminate\Support\Facades\Schedule;

// COMANDO H2H
Schedule::command('process:invoices-h2h')
    ->everyTenMinutes()
    ->withoutOverlapping();