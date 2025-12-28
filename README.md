# UWAY Auth SDK PHP

SDK PHP para integrar aplicativos com UWAY Auth via OAuth 2.0 e OpenID Connect.

## Instalacao

```
composer require carlostmj/uway-auth
```

## Uso basico

```php
<?php

use UwayAuth\Sdk\UwayAuthClient;

$client = new UwayAuthClient($baseUrl, $clientId, $clientSecret);

$pkce = UwayAuthClient::createPkcePair();
$authUrl = $client->buildAuthorizationUrl($redirectUri, $scopes, $state, $pkce['challenge'], $pkce['method']);

$tokenPayload = $client->buildTokenRequestForAuthorizationCode($code, $redirectUri, $pkce['verifier']);
$refreshPayload = $client->buildTokenRequestForRefreshToken($refreshToken);

$headers = $client->buildUserInfoHeaders($accessToken);
```

## Endpoints padrao
- Authorize: `/oauth/authorize`
- Token: `/oauth/token`
- UserInfo: `/api/user`

## Politica de dados
- O SDK nao expande dados sensiveis por padrao.
- O retorno do `/api/user` respeita o escopo consentido.
- Dados sensiveis como documento, telefone, role e avatar nao sao expostos.

