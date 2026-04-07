<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class PayrollImport implements ToCollection
{

    public function collection(Collection $rows)
    {

        $data = $rows->toArray();
        return $data;

    }

}