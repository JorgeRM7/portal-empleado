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
    padding: 2px;
    text-align: center;
    font-size: 13px;
}

th {
    background-color: #ccc;
}

/* TABLA EVALUACION */
.tabla-evaluacion {
    border-collapse: collapse;
    width: 100%;
    margin-top: 15px;
}

.tabla-evaluacion th, 
.tabla-evaluacion td {
    border: 1px solid #000;
    padding: 4px 6px;
    font-size: 11px;
    text-align: left;
    vertical-align: top;
}

.tabla-evaluacion th {
    background-color: #f0f0f0;
    font-weight: bold;
    text-align: center;
}

.preponderancia-col { width: 8%; text-align: center; }
.checkbox-col { width: 3%; text-align: center; }
.titulo-area { background-color: #e0e0e0; font-weight: bold; }

.marcado {
    font-size: 16px;
    line-height: 1;
}

/* RESULTADOS */
.resumen-table th, 
.resumen-table td {
    padding: 4px;
    text-align: left;
}

.resumen-table th { width: 70%; }

</style>
</head>

<body>

<img src="{{ public_path('images/logo.png') }}" class="logo">

<h2 style="text-align: center;">{{ $datos->planta }}</h2>

<div class="fecha">{{ $textoFecha }}</div>

<h2>EVALUACIÓN DE DESEMPEÑO.</h2>

<table>
<tr>
    <td style="border:0;"><b>Número de empleado:</b> {{ $datos->employee_id }}</td>
    <td style="border:0;"><b>Departamento:</b> {{ $datos->departamento }}</td>
</tr>
<tr>
    <td style="border:0;"><b>Nombre completo:</b> {{ $datos->empleado }}</td>
    <td style="border:0;"><b>Fecha de Ingreso:</b> {{ $datos->fecha_ingreso }}</td>
</tr>
</table>

<p style="font-size: 11px; margin-top: 15px; text-align: justify;">
    Lea cuidadosamente cada pregunta y piense en situaciones reales que haya observado del colaborador durante su periodo de prueba.
    <strong>Responda con objetividad, imparcialidad y evidencias</strong>, no con base en opiniones personales.
    Responda usando escala Likert donde 1 es muy deficiente, 2 deficiente, 3 aceptable, 4 bueno y 5 excelente.
</p>

<h2 style="font-size:14px;">EVALUACIÓN</h2>

<table class="tabla-evaluacion">
<thead>
<tr>
    <th colspan="2" style="width:60%; text-align:left;"></th>
    <th colspan="5">ESCALA</th>
    <th class="preponderancia-col">Preponderancia</th>
</tr>
<tr>
    <th colspan="2" style="text-align:left;">Criterios de Evaluación</th>
    <th class="checkbox-col">1</th>
    <th class="checkbox-col">2</th>
    <th class="checkbox-col">3</th>
    <th class="checkbox-col">4</th>
    <th class="checkbox-col">5</th>
    <th></th>
</tr>
</thead>

<tbody>

@foreach($areas as $key => $area)
<tr>
    <td colspan="7" class="titulo-area">{{ $area['title'] }}</td>
    <td class="preponderancia-col">{{ $area['weight'] * 100 }}%</td>
</tr>

@php $num = 1; @endphp

@foreach($area['questions'] as $q)
<tr>
    <td style="width:2%; text-align:right;">{{ $num }}</td>
    <td style="width:58%; text-align:left;">
        {{ $preguntas[$q] }}
    </td>

    @for($i=1; $i<=5; $i++)
        <td class="checkbox-col marcado">
            {{ (($resultados[$q] ?? 0) == $i) ? '✓' : '' }}
        </td>
    @endfor

    <td></td>
</tr>

@php $num++; @endphp
@endforeach

@endforeach

</tbody>
</table>

{{-- RESULTADOS --}}
<h2 style="font-size: 14px; margin-top: 15px;">RESULTADOS</h2>

<table class="tabla-evaluacion resumen-table">
    <thead>
        <tr>
            <th style="width: 70%; text-align: left;"></th>
            <th style="width: 15%;">Porcentaje</th>
            <th style="width: 15%;">Puntos</th>
        </tr>
    </thead>
    <tbody>

    @php $porcentaje_final = 0; @endphp

    @foreach($areas as $key => $area)
        @php
            $puntos = $puntos_obtenidos[$key]['puntos'];
            $porcentaje = $puntos_obtenidos[$key]['porcentaje'];
            $porcentaje_final += $porcentaje;
        @endphp

        <tr>
            <th style="text-align: left;">{{ $area['title'] }}</th>
            <td style="text-align: center;">
                {{ number_format($porcentaje, 2) }}%
            </td>
            <td style="text-align: center;">
                {{ number_format($puntos) }}
            </td>
        </tr>
    @endforeach

    <tr style="font-weight: bold;">
        <th style="text-align: right;">TOTAL:</th>
        <td style="text-align: center;">
            {{ number_format($porcentaje_final, 2) }}%
        </td>
        <td style="text-align: center;">
            {{ number_format($total_puntos) }}
        </td>
    </tr>

    </tbody>
</table>

<p style="font-weight:bold;">
Resultado:
<span style="color: {{ $resultado['color'] }}">
    {{ $resultado['texto'] }}. {{ $resultado['detalle'] }}
</span>
</p>

{{-- FIRMAS --}}
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