<?php

declare(strict_types=1);

namespace UwayAuth\Sdk;

use UwayAuth\Sdk\Http\HttpClient;
use UwayAuth\Sdk\Modules\AppsModule;
use UwayAuth\Sdk\Modules\AuthModule;
use UwayAuth\Sdk\Modules\OidcModule;
use UwayAuth\Sdk\Modules\UserModule;

final class UwayAuthService
{
    private UwayAuthClient $client;
    private HttpClient $http;
    private AuthModule $auth;
    private OidcModule $oidc;
    private UserModule $user;
    private AppsModule $apps;

    /**
     * Service de integracao para login com UWAY Auth.
     */
    public function __construct(UwayAuthClient $client, int $timeoutSeconds = 15)
    {
        $this->client = $client;
        $this->http = new HttpClient($timeoutSeconds);
        $this->auth = new AuthModule($this->client, $this->http);
        $this->oidc = new OidcModule($this->client, $this->http);
        $this->user = new UserModule($this->client, $this->http);
        $this->apps = new AppsModule($this->client, $this->http);
    }

    public function auth(): AuthModule
    {
        return $this->auth;
    }

    public function oidc(): OidcModule
    {
        return $this->oidc;
    }

    public function user(): UserModule
    {
        return $this->user;
    }

    public function apps(): AppsModule
    {
        return $this->apps;
    }

    /**
     * Monta a URL de autorizacao para redirecionamento do usuario.
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
        return $this->auth->buildAuthorizationUrl(
            $redirectUri,
            $scopes,
            $state,
            $codeChallenge,
            $codeChallengeMethod
        );
    }

    /**
     * Troca o code pelos tokens.
     *
     * @return array<string, mixed>
     */
    public function exchangeAuthorizationCode(
        string $code,
        string $redirectUri,
        ?string $codeVerifier = null
    ): array {
        return $this->auth->exchangeAuthorizationCode($code, $redirectUri, $codeVerifier);
    }

    /**
     * Renova tokens via refresh_token.
     *
     * @param array<int, string>|null $scopes
     * @return array<string, mixed>
     */
    public function refreshTokens(string $refreshToken, ?array $scopes = null): array
    {
        return $this->auth->refreshTokens($refreshToken, $scopes);
    }

    /**
     * Troca client_credentials por access_token (apps server-to-server).
     *
     * @param array<int, string>|null $scopes
     * @return array<string, mixed>
     */
    public function exchangeClientCredentials(?array $scopes = null): array
    {
        return $this->auth->exchangeClientCredentials($scopes);
    }

    /**
     * Consulta os dados basicos do usuario via /oauth/userinfo.
     *
     * @return array<string, mixed>
     */
    public function fetchUserInfo(string $accessToken): array
    {
        return $this->oidc->fetchUserInfo($accessToken);
    }

    /**
     * Consulta o perfil do usuario via /api/v1/user usando access_token.
     *
     * @return array<string, mixed>
     */
    public function fetchUserProfileWithAccessToken(string $accessToken): array
    {
        return $this->user->fetchProfileWithAccessToken($accessToken);
    }

    /**
     * Consulta o perfil do usuario via /api/v1/user usando API key.
     *
     * @return array<string, mixed>
     */
    public function fetchUserProfileWithApiKey(string $apiKey): array
    {
        return $this->user->fetchProfileWithApiKey($apiKey);
    }

    /**
     * Busca o OpenID Connect discovery.
     *
     * @return array<string, mixed>
     */
    public function fetchOpenIdConfiguration(): array
    {
        return $this->oidc->fetchOpenIdConfiguration();
    }

    /**
     * Busca o JWKS publico para validar JWTs.
     *
     * @return array<string, mixed>
     */
    public function fetchJwks(): array
    {
        return $this->oidc->fetchJwks();
    }

    /**
     * Retorna dados do app autenticado via client_credentials.
     *
     * @return array<string, mixed>
     */
    public function fetchAppInfo(string $accessToken): array
    {
        return $this->apps->fetchAppInfo($accessToken);
    }

    /**
     * Retorna escopos do app autenticado via client_credentials.
     *
     * @return array<string, mixed>
     */
    public function fetchAppScopes(string $accessToken): array
    {
        return $this->apps->fetchAppScopes($accessToken);
    }
}
