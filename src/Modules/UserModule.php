<?php

declare(strict_types=1);

namespace UwayAuth\Sdk\Modules;

use UwayAuth\Sdk\Http\HttpClient;
use UwayAuth\Sdk\UwayAuthClient;

final class UserModule
{
    public function __construct(
        private readonly UwayAuthClient $client,
        private readonly HttpClient $http
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchProfileWithAccessToken(string $accessToken): array
    {
        return $this->http->getJson(
            $this->client->getUserApiEndpoint(),
            $this->client->buildUserInfoHeaders($accessToken)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchProfileWithApiKey(string $apiKey): array
    {
        return $this->http->getJson(
            $this->client->getUserApiEndpoint(),
            $this->client->buildApiKeyHeaders($apiKey)
        );
    }
}
