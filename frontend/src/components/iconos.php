<?php
declare(strict_types=1);

/** Iconos de interfaz locales. El nombre siempre se toma de esta lista. */
function mostrarIcono(string $nombre): string
{
    $trazos = [
        'clinica' => '<path d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6z"/>',
        'panel' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'pacientes' => '<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m2 3a5 5 0 0 1 3 4v3"/>',
        'usuarios' => '<path d="M8 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-3"/><rect x="8" y="2" width="8" height="4" rx="1"/><circle cx="12" cy="11" r="2"/><path d="M8 18a4 4 0 0 1 8 0"/>',
        'medicos' => '<path d="M5 3v2H3v5a5 5 0 0 0 10 0V5h-2V3M8 15v2a4 4 0 0 0 8 0v-3"/><circle cx="17" cy="11" r="3"/>',
        'especialidades' => '<path d="M20 13 12 21 4 13C-2 7 6-1 12 6c6-7 14 1 8 7Z"/><path d="M4 11h4l2-3 3 7 2-4h5"/>',
        'perfil' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
        'flecha' => '<path d="M5 12h14m-5-5 5 5-5 5"/>',
        'volver' => '<path d="M19 12H5m5-5-5 5 5 5"/>',
        'mas' => '<path d="M12 5v14M5 12h14"/>',
        'buscar' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'salir' => '<path d="M9 4H5v16h4m5-12 4 4-4 4m-5-4h12"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'cerrar' => '<path d="m6 6 12 12M6 18 18 6"/>',
        'calendario' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 11h18m-13 4h2m4 0h2"/>',
        'candado' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/>',
        'informacion' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10v.01"/>',
        'correcto' => '<path d="m5 12 4 4L19 6"/>',
        'correo' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>',
    ];
    $contenido = $trazos[$nombre] ?? $trazos['informacion'];
    return '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $contenido . '</svg>';
}
