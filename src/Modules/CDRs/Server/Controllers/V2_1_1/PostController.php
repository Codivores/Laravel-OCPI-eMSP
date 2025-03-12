<?php

namespace Ocpi\Modules\CDRs\Server\Controllers\V2_1_1;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ocpi\Models\Party;
use Ocpi\Modules\CDRs\Traits\HandlesCdr;
use Ocpi\Support\Enums\OcpiClientErrorCode;
use Ocpi\Support\Enums\OcpiServerErrorCode;
use Ocpi\Support\Server\Controllers\Controller;

class PostController extends Controller
{
    use HandlesCdr;

    public function __invoke(
        Request $request,
    ): JsonResponse {
        try {
            $partyCode = Context::get('party_code');

            $party = Party::with(['roles'])->where('code', $partyCode)->first();
            if ($party === null || $party->roles->count() === 0) {
                return $this->ocpiServerErrorResponse(
                    statusCode: OcpiServerErrorCode::PartyApiUnusable,
                    statusMessage: 'Client not found.',
                    httpCode: 405,
                );
            }

            $partyRoleId = $party->roles->first()->id;

            $payload = $request->json()->all();

            // Verify CDR not already exists.
            $cdr = $this->cdrSearch(
                cdr_emsp_id: $payload['id'] ?? null,
                party_role_id: $partyRoleId,
            );

            if ($cdr) {
                return $this->ocpiClientErrorResponse(
                    statusCode: OcpiClientErrorCode::InvalidParameters,
                    statusMessage: 'CDR already exists.',
                );
            }

            DB::connection(config('ocpi.database.connection'))->beginTransaction();

            if (! ($cdr = $this->cdrCreate(
                payload: $payload,
                party_role_id: $partyRoleId,
            ))) {
                DB::connection(config('ocpi.database.connection'))->rollback();

                return $this->ocpiClientErrorResponse(
                    statusCode: OcpiClientErrorCode::NotEnoughInformation,
                );
            }

            DB::connection(config('ocpi.database.connection'))->commit();

            // Add Location header with CDR GET URL.
            return $this->ocpiSuccessResponse()
                ->header('Location', $this->cdrRoute($cdr));
        } catch (Exception $e) {
            DB::connection(config('ocpi.database.connection'))->rollback();

            Log::channel('ocpi')->error($e->getMessage());

            return $this->ocpiServerErrorResponse();
        }
    }
}
