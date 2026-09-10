<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AquaTech — Nuestro equipo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold">AquaTech</h1>
            <p class="text-muted">
                Prototipo de sistema de gestión para una oficina de agua potable —
                Universidad Mariano Gálvez de Guatemala, Sprint 2026.
            </p>
        </div>

        <div class="card shadow-sm mx-auto" style="max-width: 600px;">
            <div class="card-body">
                <h5 class="card-title mb-3">Integrantes del equipo</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Marvin Alexander Cámbara Alonzo</li>
                     <li class="list-group-item">Pablo Mauricio López Carrillo</li>
                    <li class="list-group-item">Manuel Alex Monzón</li>
                    <li class="list-group-item">Nayeli Melissa Urrutia Orellana</li>
                    <li class="list-group-item">Gustavo Adolfo</li>
                </ul>
            </div>
        </div>

        <div class="text-center mt-4">
           <a href="{{ route('admin.demo') }}" class="btn btn-outline-primary btn-sm">Volver al sistema</a>
        </div>
    </div>

</body>
</html>