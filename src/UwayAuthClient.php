<?php

declare(strict_types=1);

namespace UwayAuth\Sdk;

final class UwayAuthClient
{
    private string $baseUrl;
    private string $clientId;
    private ?string $clientSecret;

    /**
     * Cliente utilitario para montar URLs e payloads OAuth.
     */
    public function __construct(string $baseUrl, string $clientId, ?string $clientSecret = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function getAuthorizeEndpoint(): string
    {
        return $this->baseUrl.'/oauth/authorize';
    }

    public function getTokenEndpoint(): string
    {
        return $this->baseUrl.'/oauth/token';
    }

    public function getUserInfoEndpoint(): string
    {
        return $this->baseUrl.'/api/user';
    }

    /**
     * Gera a URL de autorizacao para o fluxo Authorization Code.
     *
     * @param array<int, string> $scopes
     */
    public function buildAuthorizationUrl(
        string $redirectUri,
        array $scopes,
        string $state,
        ?string $codeChallenge = null,
        string $codeChallengeMethod = 'S256'
    ): string {
        $query = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'scope' => implode(' ', $scopes),
            'state' => $state,
        ];

        if ($codeChallenge !== null) {
            $query['code_challenge'] = $codeChallenge;
            $query['code_challenge_method'] = $codeChallengeMethod;
        }

        return $this->getAuthorizeEndpoint().'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Monta o payload para trocar code por tokens.
     *
     * @return array<string, string>
     */
    public function buildTokenRequestForAuthorizationCode(
        string $code,
        string $redirectUri,
        ?string $codeVerifier = null
    ): array {
        $data = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ];

        if ($this->clientSecret !== null) {
            $data['client_secret'] = $this->clientSecret;
        }

        if ($codeVerifier !== null) {
            $data['code_verifier'] = $codeVerifier;
        }

        return $data;
    }

    /**
     * Monta o payload para renovar tokens via refresh_token.
     *
     * @param array<int, string>|null $scopes
     * @return array<string, string>
     */
    public function buildTokenRequestForRefreshToken(string $refreshToken, ?array $scopes = null): array
    {
        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
        ];

        if ($this->clientSecret !== null) {
            $data['client_secret'] = $this->clientSecret;
        }

        if ($scopes !== null && $scopes !== []) {
            $data['scope'] = implode(' ', $scopes);
        }

        return $data;
    }

    /**
     * Retorna os headers para consultar o endpoint /api/user.
     *
     * @return array<string, string>
     */
    public function buildUserInfoHeaders(string $accessToken): array
    {
        return [
            'Authorization' => 'Bearer '.$accessToken,
        ];
    }

    /**
     * Gera um par PKCE (verifier + challenge) usando S256.
     *
     * @return array{verifier: string, challenge: string, method: string}
     */
    public static function createPkcePair(): array
    {
        $verifier = self::base64UrlEncode(random_bytes(32));
        $challenge = self::base64UrlEncode(hash('sha256', $verifier, true));

        return [
            'verifier' => $verifier,
            'challenge' => $challenge,
            'method' => 'S256',
        ];
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
