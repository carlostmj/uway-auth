# UWAY Auth SDK PHP

SDK PHP para integrar aplicativos com UWAY Auth via OAuth 2.0 e OpenID Connect.

## Instalacao

```
composer require carlostmj/uway-auth
```

## Uso basico (Service)

```php
<?php

use UwayAuth\Sdk\UwayAuthClient;
use UwayAuth\Sdk\UwayAuthService;

$client = new UwayAuthClient($baseUrl, $clientId, $clientSecret);
$service = new UwayAuthService($client);

$pkce = UwayAuthClient::createPkcePair();
$authUrl = $service->buildAuthorizationUrl($redirectUri, $scopes, $state, $pkce['challenge'], $pkce['method']);

$tokens = $service->exchangeAuthorizationCode($code, $redirectUri, $pkce['verifier']);
$tokens = $service->refreshTokens($refreshToken);
$userInfo = $service->fetchUserInfo($accessToken);
$discovery = $service->fetchOpenIdConfiguration();
$jwks = $service->fetchJwks();
$profile = $service->fetchUserProfileWithAccessToken($accessToken);
```

## Uso por modulos

```php
<?php

use UwayAuth\Sdk\UwayAuthClient;
use UwayAuth\Sdk\UwayAuthService;

$client = new UwayAuthClient($baseUrl, $clientId, $clientSecret);
$service = new UwayAuthService($client);

$auth = $service->auth();
$oidc = $service->oidc();
$user = $service->user();
$apps = $service->apps();

$authUrl = $auth->buildAuthorizationUrl($redirectUri, $scopes, $state);
$tokens = $auth->exchangeAuthorizationCode($code, $redirectUri, $verifier);
$discovery = $oidc->fetchOpenIdConfiguration();
$userinfo = $oidc->fetchUserInfo($tokens['access_token']);
$profile = $user->fetchProfileWithAccessToken($tokens['access_token']);
```

## Requisitos
- PHP 8.3+
- Extensao cURL habilitada

## Endpoints padrao
- Authorize: `/oauth/authorize`
- Token: `/oauth/token`
- UserInfo: `/oauth/userinfo`
- User API: `/api/v1/user`
- Discovery: `/.well-known/openid-configuration`
- JWKS: `/.well-known/jwks.json`

## Politica de dados
- O SDK nao expande dados sensiveis por padrao.
- O retorno do `/oauth/userinfo` respeita o escopo consentido.
- Dados sensiveis como documento, role e metadados internos nao sao expostos.

## User API (API key)
```php
<?php

$profile = $service->fetchUserProfileWithApiKey('uway_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
```

## Client credentials (apps server-to-server)
```php
<?php

$tokens = $service->exchangeClientCredentials(['basic']);
$appInfo = $service->fetchAppInfo($tokens['access_token']);
$appScopes = $service->fetchAppScopes($tokens['access_token']);
```

## Escopos suportados
- `openid`, `basic`, `profile`, `email`, `phone`
