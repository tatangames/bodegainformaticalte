<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redireccionamiento</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .portal-wrapper {
            width: 100%;
            max-width: 960px;
        }

        .portal-header {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }

        .portal-eyebrow {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #2563eb;
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .portal-title {
            font-size: 22px;
            font-weight: 500;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .portal-subtitle {
            font-size: 13px;
            color: #6b7280;
        }

        .systems-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .systems-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 480px) {
            .systems-grid { grid-template-columns: 1fr; }
        }

        .sys-card {
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.25rem 1rem;
            text-decoration: none;
            transition: border-color 0.15s ease, background 0.15s ease;
        }

        .sys-card:hover {
            border-color: #60a5fa;
            background: #f9fafb;
            text-decoration: none;
        }

        .sys-card:hover .sys-name {
            color: #2563eb;
        }

        .sys-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .sys-name {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
            line-height: 1.35;
            transition: color 0.15s;
        }

        .sys-desc {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.55;
            flex: 1;
        }

        .sys-link {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            color: #2563eb;
            margin-top: 4px;
        }

        .sys-link i { font-size: 13px; }

        .ic-blue   { background: #eff6ff; color: #1d4ed8; }
        .ic-teal   { background: #f0fdfa; color: #0f766e; }
        .ic-amber  { background: #fffbeb; color: #b45309; }
        .ic-red    { background: #fef2f2; color: #b91c1c; }
        .ic-purple { background: #f5f3ff; color: #6d28d9; }
        .ic-green  { background: #f0fdf4; color: #15803d; }

        .portal-footer {
            border-top: 1px solid #f3f4f6;
            padding-top: 1rem;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #9ca3af;
        }

        .portal-footer i { font-size: 14px; }
    </style>
</head>
<body>

<div class="portal-wrapper">

    {{-- Encabezado --}}
    <div class="portal-header">
        <p class="portal-eyebrow">
            <i class="ti ti-building"></i> Alcaldía Municipal de Santa Ana Norte
        </p>
        <h1 class="portal-title">Portal de sistemas institucionales</h1>
        <p class="portal-subtitle">Acceso centralizado a las plataformas y servicios</p>
    </div>

    {{-- Grid de sistemas --}}
    <div class="systems-grid">

        {{-- Sistema 1 --}}
        <a href="http://190.86.196.105/clinica.com" target="_blank" rel="noopener noreferrer" class="sys-card">
            <div class="sys-icon ic-blue">
                <i class="ti ti-stethoscope" aria-hidden="true"></i>
            </div>
            <p class="sys-name">Clinica Municipal Cristobal Peraza</p>
            <span class="sys-link">
                <i class="ti ti-external-link"></i> Ingresar
            </span>
        </a>

        {{-- Sistema 2 --}}
        <a href="http://190.86.196.105/electricos.com/" target="_blank" rel="noopener noreferrer" class="sys-card">
            <div class="sys-icon ic-teal">
                <i class="ti ti-bolt" aria-hidden="true"></i>
            </div>
            <p class="sys-name">Sistema Eléctricos</p>
            <span class="sys-link">
                <i class="ti ti-external-link"></i> Ingresar
            </span>
        </a>

        {{-- Sistema 3 --}}
        <a href="http://190.86.196.105/ingenieria.com/" target="_blank" rel="noopener noreferrer" class="sys-card">
            <div class="sys-icon ic-amber">
                <i class="ti ti-settings" aria-hidden="true"></i>
            </div>
            <p class="sys-name">Sistema Ingenieria</p>
            <span class="sys-link">
                <i class="ti ti-external-link"></i> Ingresar
            </span>
        </a>

        {{-- Sistema 4 --}}
        <a href="http://190.86.196.105/agro.com/" target="_blank" rel="noopener noreferrer" class="sys-card">
            <div class="sys-icon ic-red">
                <i class="ti ti-plant-2" aria-hidden="true"></i>
            </div>
            <p class="sys-name">Sistema Agropecuaria</p>
            <span class="sys-link">
                <i class="ti ti-external-link"></i> Ingresar
            </span>
        </a>

        {{-- Sistema 5 --}}
        <a href="http://190.86.196.105/plantel.com/" target="_blank" rel="noopener noreferrer" class="sys-card">
            <div class="sys-icon ic-amber">
                <i class="ti ti-settings" aria-hidden="true"></i>
            </div>
            <p class="sys-name">Plantel Municipal</p>
            <span class="sys-link">
                <i class="ti ti-external-link"></i> Ingresar
            </span>
        </a>

        {{-- Sistema 6 --}}
        <a href="http://190.86.196.105/bienesmuni.com/" target="_blank" rel="noopener noreferrer" class="sys-card">
            <div class="sys-icon ic-green">
                <i class="ti ti-settings" aria-hidden="true"></i>
            </div>
            <p class="sys-name">Bienes Municipales</p>
            <span class="sys-link">
                <i class="ti ti-external-link"></i> Ingresar
            </span>
        </a>

        {{-- Sistema 7 --}}
        <a href="http://190.86.196.105/obradebanco.com/" target="_blank" rel="noopener noreferrer" class="sys-card">
            <div class="sys-icon ic-green">
                <i class="ti ti-settings" aria-hidden="true"></i>
            </div>
            <p class="sys-name">Obra de Banco</p>
            <span class="sys-link">
                <i class="ti ti-external-link"></i> Ingresar
            </span>
        </a>


        {{-- Sistema 8 --}}
        <a href="http://190.86.196.105/proveeduria.com/" target="_blank" rel="noopener noreferrer" class="sys-card">
            <div class="sys-icon ic-green">
                <i class="ti ti-building-warehouse" aria-hidden="true"></i>
            </div>
            <p class="sys-name">Proveeduria y bodega</p>
            <span class="sys-link">
                <i class="ti ti-external-link"></i> Ingresar
            </span>
        </a>

        {{-- Sistema 9 --}}
        <a href="http://190.86.196.105/usso.com/" target="_blank" rel="noopener noreferrer" class="sys-card">
            <div class="sys-icon ic-green">
                <i class="ti ti-helmet" aria-hidden="true"></i>
            </div>
            <p class="sys-name">Seguridad ocupacional</p>
            <span class="sys-link">
                <i class="ti ti-external-link"></i> Ingresar
            </span>
        </a>


        {{-- Sistema 10 --}}
        <a href="http://190.86.196.105/informatica.com/" target="_blank" rel="noopener noreferrer" class="sys-card">
            <div class="sys-icon ic-green">
                <i class="ti ti-device-desktop" aria-hidden="true"></i>
            </div>
            <p class="sys-name">Tecnología de la información</p>
            <span class="sys-link">
                <i class="ti ti-external-link"></i> Ingresar
            </span>
        </a>


        {{-- Sistema 11 --}}
        <a href="http://190.86.196.105/petreos.com/" target="_blank" rel="noopener noreferrer" class="sys-card">
            <div class="sys-icon ic-green">
                <i class="ti ti-settings" aria-hidden="true"></i>
            </div>
            <p class="sys-name">Planta Procesador de Petreos</p>
            <span class="sys-link">
                <i class="ti ti-external-link"></i> Ingresar
            </span>
        </a>


    </div>



</div>

</body>
</html>
