<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">

  <style>
    @page { margin: 36px 42px; }

    :root{
      --text: #111827;       /* gris casi negro */
      --muted: #6B7280;      /* gris medio */
      --line: #E5E7EB;       /* borde suave */
      --soft: #F9FAFB;       /* fondo claro */
      --accent: #DC2626;     /* rojo elegante */
    }

    body{
      font-family: DejaVu Sans, sans-serif;
      color: var(--text);
      font-size: 12px;
      line-height: 1.45;
    }

    .header{
      width: 100%;
      margin-bottom: 18px;
    }

    .header-left{ float:left; width: 55%; }
    .header-right{ float:right; width: 45%; text-align:right; }

    .brand{
      display: inline-block;
      vertical-align: middle;
    }

    .logo{
      width: 44px;
      height: 44px;
      vertical-align: middle;
    }

    .brand-text{
      display:inline-block;
      vertical-align: middle;
      margin-left: 10px;
    }

    .doc-title{
      font-size: 14px;
      font-weight: 700;
      letter-spacing: .5px;
      margin: 0;
    }

    .doc-subtitle{
      margin: 2px 0 0;
      color: var(--muted);
      font-size: 11px;
    }

    .folio{
      font-size: 11px;
      color: var(--muted);
      margin-bottom: 6px;
    }

    .folio strong{
      color: var(--text);
      font-weight: 700;
    }

    .badge{
      display: inline-block;
      padding: 6px 10px;
      border: 1px solid var(--line);
      border-radius: 10px;
      font-size: 11px;
      color: var(--muted);
      background: #fff;
    }

    .clear{ clear: both; }

    .divider{
      height: 1px;
      background: var(--line);
      margin: 14px 0 18px;
    }

    .card{
      border: 1px solid var(--line);
      border-radius: 10px;
      padding: 14px 14px;
      background: #fff;
    }

    .grid{
      width: 100%;
      border-collapse: collapse;
      margin: 0;
    }
    .grid td{
      padding: 6px 6px;
      vertical-align: top;
    }

    .label{
      color: var(--muted);
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: .6px;
      margin-bottom: 2px;
    }

    .value{
      font-weight: 700;
      font-size: 12px;
    }

    .value.accent{
      color: var(--accent);
    }

    .request-box{
      margin-top: 14px;
      border: 1px solid var(--line);
      border-radius: 10px;
      padding: 12px 14px;
      background: var(--soft);
    }

    .request-title{
      font-weight: 700;
      margin: 0 0 6px;
      font-size: 12px;
    }

    .request-text{
      margin: 0;
      color: #111827;
    }

    .pill{
      display: inline-block;
      padding: 2px 8px;
      border-radius: 999px;
      background: #fff;
      border: 1px solid var(--line);
      font-weight: 700;
      color: var(--text);
      font-size: 11px;
    }

    .summary{
      margin-top: 12px;
      width: 100%;
      border-collapse: collapse;
    }
    .summary td{
      padding: 10px 12px;
      border: 1px solid var(--line);
      border-radius: 10px;
      background: #fff;
      vertical-align: middle;
    }

    .footnote{
      margin-top: 10px;
      color: var(--muted);
      font-size: 10px;
    }

    .signatures{
      margin-top: 22px;
      width: 100%;
      border-collapse: collapse;
    }
    .signatures td{
      width: 33.33%;
      padding: 10px 10px 0;
      text-align: center;
      vertical-align: bottom;
    }
    .sign-line{
      margin: 36px 12px 6px;
      border-bottom: 1px solid #9CA3AF;
      height: 1px;
    }
    .sign-label{
      font-size: 10px;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .6px;
    }
  </style>
</head>

<body>
  <div class="header">
    <div class="header-left">
      <span class="brand">
        <span class="brand-text">
          <p class="doc-title">Solicitud de Tiempo por Tiempo</p>
          <p class="doc-subtitle">Formato interno de autorización</p>
        </span>
      </span>
    </div>

    <div class="header-right">
      <div class="folio">Folio: <strong>TXT-{{ $folio }}</strong></div>
      <span class="badge">Fecha solicitud: <strong style="color:#111827;">{{ $fecha_solicitud }}</strong></span>
    </div>

    <div class="clear"></div>
  </div>

  <div class="divider"></div>

  {{-- Datos del colaborador --}}
  <div class="card">
    <table class="grid">
      <tr>
        <td style="width:50%;">
          <div class="label">Empleado</div>
          <div class="value">{{ $empleado }}</div>
        </td>
        <td style="width:50%;">
          <div class="label">Puesto</div>
          <div class="value">{{ $puesto }}</div>
        </td>
      </tr>
      <tr>
        <td>
          <div class="label">Empresa</div>
          <div class="value">{{ $empresa }}</div>
        </td>
        <td>
          <div class="label">Departamento</div>
          <div class="value">{{ $departamento }}</div>
        </td>
      </tr>
      <tr>
        <td>
          <div class="label">Fecha de ingreso</div>
          <div class="value">{{ $fecha_ingreso }}</div>
        </td>
      </tr>
    </table>
  </div>

  {{-- Detalle de solicitud --}}
  <div class="request-box">
    <p class="request-title">Detalle de la solicitud</p>
    <p class="request-text">
      Por medio de la presente solicito a usted <span class="pill">{{ $hours_txt }} hora(s)</span> de TIEMPO POR TIEMPO a partir del
      <span class="pill">{{ $desde }}</span> debiendo presentarme al término de las horas descansadas o al día inmediato posterior en que comience mi
      turno correspondiente.
    </p>
    <div class="footnote">
      MOTIVO: Descanso a cuenta de tiempo por tiempo.
    </div>
  </div>

  {{-- Firmas --}}
  <table class="signatures">
    <tr>
      <td>
        <div class="sign-line"></div>
        <div class="sign-label">Firma solicitante</div>
      </td>
      <td>
        <div class="sign-line"></div>
        <div class="sign-label">Jefe inmediato</div>
      </td>
      <td>
        <div class="sign-line"></div>
        <div class="sign-label">Recursos Humanos</div>
      </td>
    </tr>
  </table>
</body>
</html>
