<?php

declare(strict_types=1);

namespace UwayAuth\Sdk\Modules;

use UwayAuth\Sdk\Http\HttpClient;
use UwayAuth\Sdk\UwayAuthClient;

final class OidcModule
{
    public function __construct(
        private readonly UwayAuthClient $client,
        private readonly HttpClient $http
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchOpenIdConfiguration(): array
    {
        return $this->http->getJson($this->client->getOpenIdConfigurationEndpoint());
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchJwks(): array
    {
        return $this->http->getJson($this->client->getJwksEndpoint());
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchUserInfo(string $accessToken): array
    {
        return $this->http->getJson(
            $this->client->getUserInfoEndpoint(),
            $this->client->buildUserInfoHeaders($accessToken)
        );
    }
}
