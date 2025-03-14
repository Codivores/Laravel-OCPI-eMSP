<?php

namespace Ocpi\Support\Client;

use Exception;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Ocpi\Models\Party;
use Ocpi\Modules\Cdrs\Client\Resource as CdrsResource;
use Ocpi\Modules\Commands\Client\Resource as CommandsResource;
use Ocpi\Modules\Credentials\Client\Resource as CredentialsResource;
use Ocpi\Modules\Locations\Client\Resource as LocationsResource;
use Ocpi\Modules\Sessions\Client\Resource as SessionsResource;
use Ocpi\Modules\Versions\Client\Resource as VersionsResource;
use Ocpi\Support\Client\Middlewares\LogRequest;
use Ocpi\Support\Client\Middlewares\LogResponse;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AcceptsJson;

class Client extends Connector
{
    use AcceptsJson;

    public function __construct(
        protected readonly Party $party,
        protected string $module,
    ) {
        Context::add('trace_id', Str::uuid()->toString());
        Context::add('party_code', $party?->code);

        $this->middleware()->onRequest(new LogRequest);
        $this->middleware()->onResponse(new LogResponse);
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator($this->party?->encoded_server_token, 'Token');
    }

    protected function defaultConfig(): array
    {
        return [];
    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function resolveBaseUrl(): string
    {
        return match ($this->module) {
            'versions.information' => $this->party?->url,
            'versions.details' => $this->party?->version_url,
            default => $this->party?->endpoints[$this->module] ?? '',
        };
    }

    public function hasRequestFailed(Response $response): ?bool
    {
        if (in_array('application/json', [$response->header('Content-Type'), $response->header('content-type')])) {
            try {
                $responseObject = $response->object();

                return ($responseObject?->status_code ?? 2000) >= 2000;
            } catch (Exception $e) {
                return true;
            }
        }

        return true;
    }

    /***
     * Methods.
     ***/

    public function module(string $module): void
    {
        $this->module = $module;
    }

    /***
     * Resources.
     ***/

    public function cdrs(): CdrsResource
    {
        return new CdrsResource($this);
    }

    public function commands(): CommandsResource
    {
        return new CommandsResource($this);
    }

    public function credentials(): CredentialsResource
    {
        return new CredentialsResource($this);
    }

    public function locations(): LocationsResource
    {
        return new LocationsResource($this);
    }

    public function sessions(): SessionsResource
    {
        return new SessionsResource($this);
    }

    public function versions(): VersionsResource
    {
        return new VersionsResource($this);
    }
}
