<?php

namespace Ocpi\Modules\Sessions\Server\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ocpi\Modules\Sessions\Traits\HandlesSession;
use Ocpi\Support\Enums\OcpiClientErrorCode;
use Ocpi\Support\Server\Controllers\Controller;

class PatchController extends Controller
{
    use HandlesSession;

    public function __invoke(
        Request $request,
        string $country_code,
        string $party_id,
        string $session_id,
    ): JsonResponse {
        try {
            DB::connection(config('ocpi.database.connection'))->beginTransaction();

            $payload = $request->json()->all();

            $session = $this->sessionSearch(
                session_id: $session_id,
                party_role_id: Context::get('party_role_id'),
            );

            if ($session === null) {
                return $this->ocpiClientErrorResponse(
                    statusCode: OcpiClientErrorCode::InvalidParameters,
                    statusMessage: 'Unknown Session.',
                );
            }

            // Updated Session.
            if (! $this->sessionObjectUpdate(
                payload: $payload,
                session: $session,
            )) {
                DB::connection(config('ocpi.database.connection'))->rollback();

                return $this->ocpiClientErrorResponse(
                    statusCode: OcpiClientErrorCode::NotEnoughInformation,
                );
            }

            DB::connection(config('ocpi.database.connection'))->commit();

            return $this->ocpiSuccessResponse();
        } catch (Exception $e) {
            DB::connection(config('ocpi.database.connection'))->rollback();

            Log::channel('ocpi')->error($e->getMessage());

            return $this->ocpiServerErrorResponse();
        }
    }
}
