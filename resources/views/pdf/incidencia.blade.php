<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 36px 42px; }
    .page-break { page-break-after: always; }
  </style>
</head>
<body>

  {{-- Página 1 --}}
  @include('pdf.partials.vacaciones_formato', ['copia' => 'Copia Recursos Humanos'])

  <div class="page-break"></div>

  {{-- Página 2 (misma info) --}}
  @include('pdf.partials.vacaciones_formato', ['copia' => 'Copia Empleado'])

</body>
</html>
