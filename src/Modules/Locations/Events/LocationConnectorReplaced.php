<?php

namespace Ocpi\Modules\Locations\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class LocationConnectorReplaced implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public int $party_role_id,
        public string $evse_uid,
        public string $id,
        public mixed $payload,
    ) {}
}
