<?php

declare(strict_types=1);

namespace App\Modules\Documents\Services;

use App\Modules\Documents\Contracts\DocumentRenderer;
use App\Modules\Organization\Contracts\AgencyLetterhead;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

final readonly class PdfDocumentRenderer implements DocumentRenderer
{
    private const STYLESHEET = 'css/pdf.css';

    public function __construct(private AgencyLetterhead $letterhead) {}

    public function pdf(string $view, array $data, string $filename): PdfBuilder
    {
        return Pdf::view($view, $this->withBranding($data))->format(Format::A4)->name($filename);
    }

    public function html(string $view, array $data): string
    {
        return view($view, $this->withBranding($data))->render();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withBranding(array $data): array
    {
        return [
            ...$data,
            'letterhead' => $this->letterhead->letterhead(),
            // Hoja propia del repositorio (no contenido de usuario): se incrusta porque DomPDF no carga recursos remotos.
            'pdfStyles' => (string) file_get_contents(resource_path(self::STYLESHEET)),
            'timezone' => config()->string('travel.agency.timezone'),
        ];
    }
}
