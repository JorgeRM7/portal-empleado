<?php

use App\Models\InvoiceSat;

if (! function_exists('map_xml_costs')) {

    function map_xml_costs(array $data, InvoiceSat $invoiceSat): array
    {
        $location     = 509;
        $category     = 100;
        $department   = 103;
        $invoiceClass = 7;

        return collect($data)
            ->map(function ($item) use ($category, $location, $department, $invoiceClass) {
                $cost         = $item['Importe'];
                $has_discount = isset($item['Descuento']);

                $tax_data = data_get($item, 'Impuestos.Traslados.Traslado') ?? [];

                $has_ieps = collect($tax_data)->contains('Impuesto', '003');
                $base     = collect($tax_data)->where('Impuesto', '002')->first()['Base'] ?? 0;

                $data_ieps = [
                    'Importe'    => '34.12',
                    'Impuesto'   => '003',
                    'TasaOCuota' => '0.030000',
                    'TipoFactor' => 'Tasa',
                    'Base'       => '1137.39',
                ];

                if ($has_ieps || $has_discount) {
                    $cost = $base;
                }

                //                if (floatval($base) < floatval($cost)) {
                //                    $cost = $base;
                //                }

                return [
                    'categoria'    => $category,
                    'costo'        => $cost,
                    'ubicacion'    => $location,
                    'departamento' => $department,
                    'clase'        => $invoiceClass,
                    'concepto'     => $item['Descripcion']   ?? '',
                    'claveprodser' => $item['ClaveProdServ'] ?? '',
                    'Impuestos'    => $item['Impuestos']     ?? [],
                ];
            })->all();
    }
    
}

if (! function_exists('map_xml_articles')) {

    function map_xml_articles(array $data, InvoiceSat $invoiceSat): array
    {
        $article    = $invoiceSat->invoiceArticle?->code;
        $accounting = $invoiceSat->invoiceAccountingList?->code;

        return collect($data)
            ->map(function ($item) use ($article, $accounting) {
                return [
                    'landedcost' => $accounting,
                    'articulo'   => $article,
                    'cantidad'   => data_get($item, 'Cantidad')      ?? '0',
                    'costo'      => data_get($item, 'ValorUnitario') ?? '0',
                ];
            })->all();
    }
}