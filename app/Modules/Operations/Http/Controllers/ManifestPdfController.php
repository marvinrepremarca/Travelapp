<?php

declare(strict_types=1);

namespace App\Modules\Operations\Http\Controllers;

use App\Modules\Bookings\Contracts\DepartureManifests;
use App\Modules\Catalog\Contracts\DepartureSchedule;
use App\Modules\Documents\Contracts\DocumentRenderer;
use App\Modules\Identity\Models\User;
use App\Modules\Operations\Models\DepartureAssignment;
use App\Modules\Shared\Enums\Permission;
use Illuminate\Http\Request;
use Spatie\LaravelPdf\PdfBuilder;

/** Manifiesto de la salida en PDF para el guía (documentos enmascarados). */
final class ManifestPdfController
{
    private const VIEW = 'operations::pdf.manifest';

    public function __invoke(Request $request, string $departure, DepartureSchedule $schedule, DepartureManifests $manifests, DocumentRenderer $renderer): PdfBuilder
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can(Permission::OperationsManage->value), 403);
        $slot = $schedule->find($departure) ?? abort(404);

        return $renderer->pdf(self::VIEW, [
            'documentTitle' => __('operations.manifest.title', ['product' => $slot->productName]),
            'departure' => $slot,
            'passengers' => $manifests->passengersOf($slot->ulid),
            'assignment' => DepartureAssignment::query()->where('departure_ulid', $slot->ulid)->with(['guide', 'vehicle'])->first(),
        ], __('operations.manifest.filename', ['date' => $slot->serviceDate->toDateString(), 'time' => str_replace(':', '', $slot->startsAt)]))->download();
    }
}
