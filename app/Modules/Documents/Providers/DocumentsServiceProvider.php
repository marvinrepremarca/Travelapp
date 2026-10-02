<?php

declare(strict_types=1);

namespace App\Modules\Documents\Providers;

use App\Modules\Documents\Contracts\DocumentRenderer;
use App\Modules\Documents\Services\PdfDocumentRenderer;
use Illuminate\Support\ServiceProvider;

final class DocumentsServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        DocumentRenderer::class => PdfDocumentRenderer::class,
    ];

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'documents');
    }
}
