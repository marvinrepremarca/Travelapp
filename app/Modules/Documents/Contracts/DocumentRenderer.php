<?php

declare(strict_types=1);

namespace App\Modules\Documents\Contracts;

use Spatie\LaravelPdf\PdfBuilder;

/**
 * Genera documentos PDF con el membrete de la agencia. Cada módulo pone su plantilla (que extiende `documents::layout`)
 * y autoriza la descarga; este módulo solo aporta marca, estilos y motor.
 */
interface DocumentRenderer
{
    /**
     * @param  view-string  $view
     * @param  array<string, mixed>  $data
     */
    public function pdf(string $view, array $data, string $filename): PdfBuilder;

    /**
     * HTML final del documento (para pruebas y vista previa).
     *
     * @param  view-string  $view
     * @param  array<string, mixed>  $data
     */
    public function html(string $view, array $data): string;
}
