<?php

declare(strict_types=1);

namespace UwayAuth\Sdk;

use RuntimeException;

final class UwayAuthService
{
    private UwayAuthClient $client;
    private int $timeoutSeconds;

    /**
     * Service de integracao para login com UWAY Auth.
     */
    public function __construct(UwayAuthClient $client, int $timeoutSeconds = 15)
    {
        if (! function_exists('curl_init')) {
            throw new RuntimeException('Extensao cURL nao esta disponivel.');
        }

        $this->client = $client;
        $this->timeoutSeconds = $timeoutSeconds;
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
        return $this->client->buildAuthorizationUrl(
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
        $payload = $this->client->buildTokenRequestForAuthorizationCode($code, $redirectUri, $codeVerifier);

        return $this->postForm($this->client->getTokenEndpoint(), $payload);
    }

    /**
     * Renova tokens via refresh_token.
     *
     * @param array<int, string>|null $scopes
     * @return array<string, mixed>
     */
    public function refreshTokens(string $refreshToken, ?array $scopes = null): array
    {
        $payload = $this->client->buildTokenRequestForRefreshToken($refreshToken, $scopes);

        return $this->postForm($this->client->getTokenEndpoint(), $payload);
    }

    /**
     * Troca client_credentials por access_token (apps server-to-server).
     *
     * @param array<int, string>|null $scopes
     * @return array<string, mixed>
     */
    public function exchangeClientCredentials(?array $scopes = null): array
    {
        $payload = $this->client->buildTokenRequestForClientCredentials($scopes);

        return $this->postForm($this->client->getTokenEndpoint(), $payload);
    }

    /**
     * Consulta os dados basicos do usuario via /oauth/userinfo.
     *
     * @return array<string, mixed>
     */
    public function fetchUserInfo(string $accessToken): array
    {
        return $this->getJson(
            $this->client->getUserInfoEndpoint(),
            $this->client->buildUserInfoHeaders($accessToken)
        );
    }

    /**
     * Busca o OpenID Connect discovery.
     *
     * @return array<string, mixed>
     */
    public function fetchOpenIdConfiguration(): array
    {
        return $this->getJson($this->client->getOpenIdConfigurationEndpoint());
    }

    /**
     * Busca o JWKS publico para validar JWTs.
     *
     * @return array<string, mixed>
     */
    public function fetchJwks(): array
    {
        return $this->getJson($this->client->getJwksEndpoint());
    }

    /**
     * Retorna dados do app autenticado via client_credentials.
     *
     * @return array<string, mixed>
     */
    public function fetchAppInfo(string $accessToken): array
    {
        return $this->getJson(
            $this->client->getAppsMeEndpoint(),
            $this->client->buildUserInfoHeaders($accessToken)
        );
    }

    /**
     * Retorna escopos do app autenticado via client_credentials.
     *
     * @return array<string, mixed>
     */
    public function fetchAppScopes(string $accessToken): array
    {
        return $this->getJson(
            $this->client->getAppsScopesEndpoint(),
            $this->client->buildUserInfoHeaders($accessToken)
        );
    }

    /**
     * @param array<string, string> $data
     * @return array<string, mixed>
     */
    private function postForm(string $url, array $data): array
    {
        $body = http_build_query($data, '', '&', PHP_QUERY_RFC3986);

        return $this->requestJson('POST', $url, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ], $body);
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    private function getJson(string $url, array $headers = []): array
    {
        $headers['Accept'] = $headers['Accept'] ?? 'application/json';

        return $this->requestJson('GET', $url, $headers, null);
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    private function requestJson(string $method, string $url, array $headers, ?string $body): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Falha ao iniciar cURL.');
        }

        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name.': '.$value;
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Falha na requisicao HTTP: '.$error);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        if (! is_array($data)) {
            throw new RuntimeException('Resposta JSON invalida do UWAY Auth.');
        }

        if ($status >= 400) {
            $message = $data['message'] ?? $data['error_description'] ?? 'Erro desconhecido.';
            throw new RuntimeException('UWAY Auth retornou HTTP '.$status.': '.$message);
        }

        return $data;
    }
}
