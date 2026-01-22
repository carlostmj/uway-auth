<?php

declare(strict_types=1);

namespace UwayAuth\Sdk\Modules;

use UwayAuth\Sdk\Http\HttpClient;
use UwayAuth\Sdk\UwayAuthClient;

final class AuthModule
{
    public function __construct(
        private readonly UwayAuthClient $client,
        private readonly HttpClient $http
    ) {
    }

    /**
     * @param array<int, string> $scopes
     */
    public function buildAuthorizationUrl(
        string $redirectUri,
        array $scopes,
        string $state,
        ?string $codeChallenge = null,
        string $codeChallengeMethod = 'S256'
    ): string {
        return $this->client->buildAuthorizationUrl(
            $redirectUri,
            $scopes,
            $state,
            $codeChallenge,
            $codeChallengeMethod
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function exchangeAuthorizationCode(
        string $code,
        string $redirectUri,
        ?string $codeVerifier = null
    ): array {
        $payload = $this->client->buildTokenRequestForAuthorizationCode($code, $redirectUri, $codeVerifier);

        return $this->http->postForm($this->client->getTokenEndpoint(), $payload);
    }

    /**
     * @param array<int, string>|null $scopes
     * @return array<string, mixed>
     */
    public function refreshTokens(string $refreshToken, ?array $scopes = null): array
    {
        $payload = $this->client->buildTokenRequestForRefreshToken($refreshToken, $scopes);

        return $this->http->postForm($this->client->getTokenEndpoint(), $payload);
    }

    /**
     * @param array<int, string>|null $scopes
     * @return array<string, mixed>
     */
    public function exchangeClientCredentials(?array $scopes = null): array
    {
        $payload = $this->client->buildTokenRequestForClientCredentials($scopes);

        return $this->http->postForm($this->client->getTokenEndpoint(), $payload);
    }
}
