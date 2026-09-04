<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            background-color: #eef4fb;
            color: #1f2937;
        }

        .wrapper {
            width: 100%;
            padding: 40px 15px;
            box-sizing: border-box;
        }

        .container {
            max-width: 560px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.08);
        }

        .header {
            background-color: #0f4c81;
            padding: 32px 30px;
            text-align: center;
            color: #ffffff;
        }

        .header-title {
            font-size: 26px;
            font-weight: bold;
            margin: 0;
        }

        .header-subtitle {
            margin-top: 8px;
            font-size: 14px;
            color: #dbeafe;
        }

        .content {
            padding: 35px 35px 30px 35px;
        }

        .welcome {
            font-size: 17px;
            margin-bottom: 18px;
        }

        .text {
            font-size: 15px;
            line-height: 1.7;
            color: #4b5563;
            margin-bottom: 18px;
        }

        .code-container {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 25px 15px;
            margin: 28px 0;
            text-align: center;
        }

        .code-label {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .code {
            font-size: 38px;
            font-weight: bold;
            letter-spacing: 10px;
            color: #0f4c81;
            margin: 0;
        }

        .expiration {
            text-align: center;
            margin-top: 12px;
            font-size: 13px;
            color: #64748b;
        }

        .security-box {
            background-color: #f8fafc;
            border-left: 4px solid #2563eb;
            padding: 15px 18px;
            margin-top: 25px;
            border-radius: 6px;
        }

        .security-title {
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .security-text {
            margin: 0;
            font-size: 13px;
            line-height: 1.6;
            color: #64748b;
        }

        .footer {
            border-top: 1px solid #e5e7eb;
            padding: 22px 30px;
            text-align: center;
            background-color: #f8fafc;
        }

        .footer-title {
            font-size: 14px;
            font-weight: bold;
            color: #0f4c81;
        }

        .footer-text {
            margin-top: 6px;
            font-size: 12px;
            color: #94a3b8;
        }

        @media only screen and (max-width: 600px) {
            .wrapper {
                padding: 20px 10px;
            }

            .content {
                padding: 28px 22px;
            }

            .header {
                padding: 28px 20px;
            }

            .code {
                font-size: 32px;
                letter-spacing: 7px;
            }
        }
    </style>

</head>

<body>

    <div class="wrapper">

        <div class="container">

            <!-- HEADER -->
            <div class="header">

                <div class="header-title">
                    Mi Portal RH
                </div>

                <div class="header-subtitle">
                    Recuperación de contraseña
                </div>

            </div>


            <!-- CONTENIDO -->
            <div class="content">

                <div class="welcome">
                    Hola,
                    <strong>{{ $employee->full_name }}</strong>
                </div>


                <div class="text">
                    Recibimos una solicitud para restablecer la contraseña de su cuenta de
                    <strong>Mi Portal RH</strong>.
                </div>


                <div class="text">
                    Para continuar con el proceso, ingrese el siguiente código de verificación:
                </div>


                <!-- CODIGO -->
                <div class="code-container">

                    <div class="code-label">
                        Código de verificación
                    </div>

                    <div class="code">
                        {{ $code }}
                    </div>

                    <div class="expiration">
                        Este código es válido durante <strong>15 minutos</strong>.
                    </div>

                </div>


                <div class="text">
                    Una vez validado el código, podrá establecer una nueva contraseña para ingresar nuevamente a su cuenta.
                </div>


                <!-- SEGURIDAD -->
                <div class="security-box">

                    <div class="security-title">
                        Aviso de seguridad
                    </div>

                    <p class="security-text">
                        Si usted no solicitó este cambio de contraseña, puede ignorar este correo.
                        Su contraseña actual permanecerá sin modificaciones.
                    </p>

                </div>

            </div>


            <!-- FOOTER -->
            <div class="footer">

                <div class="footer-title">
                    Mi Portal RH
                </div>

                <div class="footer-text">
                    Este es un correo automático. Por favor, no responda a este mensaje.
                </div>

            </div>

        </div>

    </div>

</body>

</html>