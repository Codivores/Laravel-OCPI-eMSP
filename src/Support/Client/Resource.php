<?php

namespace Ocpi\Support\Client;

use ArrayObject;
use Ocpi\Support\Client\Requests\GetRequest;
use Ocpi\Support\Client\Requests\PostRequest;
use Saloon\Http\BaseResource;
use Saloon\Http\Response;

class Resource extends BaseResource
{
    public function requestGetSend(?string $endpoint = null, ?array $query = null): ?array
    {
        $response = $this->connector->send(
            (new GetRequest)
                ->withEndpoint($endpoint)
                ->withQuery($query)
        );

        $response->throw();

        return $this->responseGetProcess($response);
    }

    public function requestPostSend(array|ArrayObject|null $payload, ?string $endpoint = null): array|string|null
    {
        $response = $this->connector->send(
            (new PostRequest)
                ->withEndpoint($endpoint)
                ->withPayload($payload)
        );

        $response->throw();

        return $this->responsePostProcess($response);
    }

    public function responseGetProcess(Response $response): ?array
    {
        if (! $response->successful()) {
            return null;
        }

        $responseObject = $response->object();

        return ($responseObject?->data ?? null) ? (array) $responseObject->data : null;
    }

    public function responsePostProcess(Response $response): array|string|null
    {
        if (! $response->successful()) {
            return null;
        }

        $responseObject = $response->object();

        return ($responseObject?->data ?? null)
            ? (is_string($responseObject->data) ? $responseObject->data : (array) $responseObject->data)
            : null;
    }
}
