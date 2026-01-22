<?php

declare(strict_types=1);

namespace UwayAuth\Sdk\Http;

use RuntimeException;

final class HttpClient
{
    private int $timeoutSeconds;

    public function __construct(int $timeoutSeconds = 15)
    {
        if (! function_exists('curl_init')) {
            throw new RuntimeException('Extensao cURL nao esta disponivel.');
        }

        $this->timeoutSeconds = $timeoutSeconds;
    }

    /**
     * @param array<string, string> $data
     * @return array<string, mixed>
     */
    public function postForm(string $url, array $data): array
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
    public function getJson(string $url, array $headers = []): array
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
