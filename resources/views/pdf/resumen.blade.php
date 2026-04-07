<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estimación de Horas Extras</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px; /* Tamaño pequeño para que quepan varias columnas */
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 14px;
            font-weight: normal;
            margin: 5px 0;
        }
        .header p {
            font-size: 12px;
            margin: 0;
        }
        
        /* Estilos de la tabla central */
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px 4px;
            word-wrap: break-word;
        }
        th {
            font-weight: bold;
            text-transform: uppercase;
            background-color: #f9f9f9; /* Un fondo sutil opcional */
        }

        /* Estilos de las firmas en el pie de página */
        .signatures {
            width: 100%;
            margin-top: 60px;
            border: none;
        }
        .signatures td {
            border: none;
            padding: 0;
            width: 50%;
            text-align: center;
        }
        .line {
            border-top: 1px solid #000;
            width: 250px;
            margin: 0 auto 10px auto;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>{{ $name_branch_office }}</h1>
        <h2>RESUMEN DE DIFERENCIAS</h2>
        <p>EMITIDO AL {{ now()->format('Y-m-d') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <div class="line"></div>
                Gerente de planta
            </td>
            <td>
                <div class="line"></div>
                Dirección de operaciones
            </td>
        </tr>
    </table>

</body>
</html>