<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .logo {
            width: 120px;
            margin-bottom: 20px;
        }
        .fecha {
            text-align: right;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 16px;
            margin-bottom: 10px;
        }
        p {
            font-size: 14px;
            line-height: 1.5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th, td {
            padding: 4px;
            text-align: center;
            font-size: 13px;
        }
        th {
            background-color: #ccc;
        }
        td.diferencias {
            color: red;
        }
    </style>
</head>
<body>

{{-- LOGO --}}
@if(!empty($logo))
    <img src="{{ $logo }}" class="logo">
@endif

{{-- FECHA --}}
<div class="fecha">
    {{ $textoFecha ?? '' }}
</div>

<h2>{{ $datos->planta ?? '' }}</h2>
<h2>{{ $datos->tipo_ajuste ?? '' }}</h2>

<p>
    Por medio de la presente, se informa que el trabajador 
    <b>{{ $datos->employee_name ?? '' }}</b> 
    con clave de nómina 
    <b>#{{ $datos->employee_id ?? '' }}</b>, 
    que desempeña el puesto de 
    <b>{{ $datos->actual_position_name ?? '' }}</b> 
    en el área de 
    <b>{{ $datos->actual_department_name ?? '' }}</b> 
    cambia de puesto como 
    <b>{{ $datos->new_position_name ?? '' }}</b> 
    del área de 
    <b>{{ $datos->new_department_name ?? '' }}</b> 
    a partir del día 
    <b>{{ $datos->start_training ?? '' }}</b>.
</p>

<table>
    <thead>
        <tr>
            <th style="width: 40%;"></th>
            <th style="width: 15%;">Salario</th>
            <th style="width: 15%;">Sueldo</th>
            <th style="width: 15%;">Compensación</th>
            <th style="width: 15%;">Neto</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="background-color: #ccc;">
                {{ $datos->actual_position_name ?? '' }}
            </td>
            <td>$ {{ number_format($s_diario ?? 0, 2) }}</td>
            <td>$ {{ number_format($s_diario_neto ?? 0, 2) }}</td>
            <td>$ {{ number_format($comp_actual ?? 0, 2) }}</td>
            <td>$ {{ number_format($neto_sem_actual ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td style="background-color: #ccc;">
                {{ $datos->new_position_name ?? '' }}
            </td>
            <td>$ {{ number_format($sueldo_nuevo ?? 0, 2) }}</td>
            <td>$ {{ number_format($neto_nuevo ?? 0, 2) }}</td>
            <td>$ {{ number_format($comp_nuevo ?? 0, 2) }}</td>
            <td>$ {{ number_format($semanal_nuevo ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td style="background-color: #ccc;">Diferencias</td>
            <td class="diferencias">$ {{ number_format($sueldo_dif ?? 0, 2) }}</td>
            <td class="diferencias">$ {{ number_format($neto_dif ?? 0, 2) }}</td>
            <td class="diferencias">$ {{ number_format($comp_dif ?? 0, 2) }}</td>
            <td class="diferencias">$ {{ number_format($semanal_dif ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td style="background-color: #ccc;">Comentarios:</td>
            <td colspan="4">
                {{ $datos->salary_approved_comment ?? '' }}
            </td>
        </tr>
    </tbody>
</table>

<p>
    Sin más por el momento les envío un cordial saludo y quedo a sus órdenes.
</p>

<table style="border: 0; width: 100%; text-align: center; font-weight: bold; margin-top: 85px;">
    <tr>
        <td style="border: 0; height: 85px; width: 33%;"></td>
        <td style="border: 0; border-top: 4px double black; height: 85px; width: 33%; vertical-align: top; padding-top: 5px;">
            Nombre y firma de colaborador
        </td>
        <td style="border: 0;"></td>
    </tr>
    <tr>
        <td style="border: 0; border-top: 4px double black; height: 85px; vertical-align: top; padding-top: 5px;">
            Jefe de (Nuevo depto)
        </td>
        <td style="border: 0;"></td>
        <td style="border: 0; border-top: 4px double black; height: 85px; vertical-align: top; padding-top: 5px;">
            Jefe de planta
        </td>
    </tr>
    <tr>
        <td style="border: 0; border-top: 4px double black; height: 85px; vertical-align: top; padding-top: 5px;">
            Desarrollador de talento
        </td>
        <td style="border: 0;"></td>
        <td style="border: 0; border-top: 4px double black; height: 85px; vertical-align: top; padding-top: 5px;">
            Gerencia
        </td>
    </tr>
</table>

</body>
</html>