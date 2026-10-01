<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Enums;

enum RequestOutcome: string
{
    case Success = 'success';
    case Timeout = 'timeout';
    case ServerError = 'server_error';
    case ClientError = 'client_error';
    case InvalidResponse = 'invalid_response';
}
