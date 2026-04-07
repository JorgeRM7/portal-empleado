<?php

namespace App\Imports;

use App\Models\EmployeeCompensation;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class EmployeeCompensationImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    //use SkipsFailures;

    protected $branchOfficeId;

    public function __construct($branchOfficeId)
    {
        $this->branchOfficeId = $branchOfficeId;
    }

    public function headingRowFormatter(): callable
    {
        return function ($value) {
            $value = (string) $value;
            $value = Str::lower($value);
            $value = Str::of($value)->ascii();
            $value = preg_replace('/\s+/', '_', $value);
            $value = preg_replace('/[^a-z0-9_]/', '', $value);
            return $value;
        };
    }

    public function model(array $row)
    {

        $employeeId = (int) ($row['numero_de_empleado'] ?? 0);

        return new EmployeeCompensation([
            'employee_id' => $employeeId,
            'branch_office_id' => $this->branchOfficeId,
            'percent' => $this->toNumber($row['eficiencia'] ?? null),
            'compensation' => $this->toMoney($row['compensacion'] ?? 0),
            'piece_work' => $this->toMoney($row['destajo'] ?? 0),
            'extra_compensation' => $this->toMoney($row['compensacion_extra'] ?? 0),
            'transport' => $this->toMoney($row['apoyo_de_transporte'] ?? 0),
            'comment' => $row['observaciones'] ?? null,
            'week_number' => (int) ($row['semana'] ?? 0),
            'week_year' => (int) ($row['anio'] ?? 0),
            'total' => $this->toMoney($row['compensacion'] ?? 0)
                    + $this->toMoney($row['destajo'] ?? 0)
                    + $this->toMoney($row['compensacion_extra'] ?? 0)
                    + $this->toMoney($row['apoyo_de_transporte'] ?? 0),
        ]);
    }

    public function rules(): array
    {
        return [
            '*.numero_de_empleado' => ['required', 'integer'],
            '*.eficiencia' => ['nullable'],
            '*.compensacion' => ['nullable'],
            '*.destajo' => ['nullable'],
            '*.compensacion_extra' => ['nullable'],
            '*.apoyo_de_transporte' => ['nullable'],
            '*.observaciones' => ['nullable', 'string'],
            '*.semana' => ['required', 'integer', 'between:1,53'],
            '*.anio' => ['required', 'integer', 'min:2000'],
        ];
    }

    public function batchSize(): int { return 500; }
    public function chunkSize(): int { return 500; }

    private function toMoney($value): float
    {
        if ($value === null || $value === '') return 0.0;
        $v = (string) $value;
        $v = str_replace(['$', ' '], '', $v);

        if (str_contains($v, ',') && !str_contains($v, '.')) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        } else {
            $v = str_replace(',', '', $v);
        }

        return (float) $v;
    }

    private function toNumber($value): ?float
    {
        if ($value === null || $value === '') return null;

        $v = (string) $value;
        $v = str_replace(['%', ' '], '', $v);
        if ($v === '') return null;
        if (str_contains($v, ',') && !str_contains($v, '.')) {
            $v = str_replace(',', '.', $v);
        }

        return (float) $v;
    }
}
