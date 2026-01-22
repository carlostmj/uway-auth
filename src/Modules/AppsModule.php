<?php

declare(strict_types=1);

namespace UwayAuth\Sdk\Modules;

use UwayAuth\Sdk\Http\HttpClient;
use UwayAuth\Sdk\UwayAuthClient;

final class AppsModule
{
    public function __construct(
        private readonly UwayAuthClient $client,
        private readonly HttpClient $http
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchAppInfo(string $accessToken): array
    {
        return $this->http->getJson(
            $this->client->getAppsMeEndpoint(),
            $this->client->buildUserInfoHeaders($accessToken)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchAppScopes(string $accessToken): array
    {
        return $this->http->getJson(
            $this->client->getAppsScopesEndpoint(),
            $this->client->buildUserInfoHeaders($accessToken)
        );
    }
}
