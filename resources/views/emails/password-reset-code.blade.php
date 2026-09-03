<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <style>
        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
        }

        .container {
            max-width: 520px;
            margin: auto;
            background-color: #ffffff;
            border-radius: 12px;
            padding: 32px;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .code {
            margin: 30px 0;
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 10px;
            text-align: center;
        }

        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #6b7280;
        }
    </style>

</head>

<body>

    <div class="container">

        <div class="title">
            Recuperación de contraseña
        </div>

        <p>
            Hola
            <strong>
                {{ $employee->full_name }}
            </strong>.
        </p>

        <p>
            Se solicitó restablecer la contraseña de su cuenta de Mi Portal RH.
        </p>

        <p>
            Ingrese el siguiente código:
        </p>

        <div class="code">
            {{ $code }}
        </div>

        <p>
            El código tiene una vigencia de
            <strong>15 minutos</strong>.
        </p>

        <p>
            Si usted no solicitó este cambio, ignore este correo.
        </p>

        <div class="footer">
            Mi Portal RH
        </div>

    </div>

</body>

</html>