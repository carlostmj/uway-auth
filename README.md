# Integracao UWAY Auth (OAuth 2.0 + OpenID Connect)

## Base URL
- Producao: https://auth.uway.com.br
- Local: http://127.0.0.1:8000

## Cadastro e registro de app (publico)
1) Crie sua conta em `/register`.
2) Acesse o painel em `/dashboard`.
3) Cadastre um cliente OAuth:
   - Nome do aplicativo.
   - Tipo do cliente: `public` (PKCE) ou `confidential` (com segredo).
   - Redirect URIs (HTTPS obrigatorio para dominios publicos; HTTP apenas para localhost).
   - Escopos permitidos.
4) Guarde o `client_id` e, se for confidential, o `client_secret` (exibido apenas uma vez).

## Escopos e dados compartilhados
`sub` sempre e retornado como identificador unico do usuario.

- `basic`: `sub`, `name`, `email`
- `profile`: `sub`, `name`
- `email`: `sub`, `email`

Dados que nunca sao expostos por escopo: `phone`, `document` (CPF/CNPJ), `role`, `avatar`, sessoes, tokens e metadados internos.

Se o parametro `scope` nao for enviado, o escopo padrao e `basic`.

## Fluxo de autorizacao (Authorization Code + PKCE)

### 1) Redirecionar para `/oauth/authorize`
Parametros obrigatorios:
- `response_type=code`
- `client_id`
- `redirect_uri` (um dos cadastrados)
- `scope` (escopos separados por espaco)
- `state` (recomendado para proteger contra CSRF)
- `code_challenge` e `code_challenge_method=S256` (obrigatorio para clientes `public`)

### 2) Consentimento do usuario
O usuario visualiza o nome do app e os escopos solicitados. Ele pode autorizar ou negar.

### 3) Trocar o `code` por tokens em `/oauth/token`
Requisicao `POST` com `application/x-www-form-urlencoded`.

Campos obrigatorios:
- `grant_type=authorization_code`
- `client_id`
- `redirect_uri`
- `code`
- `code_verifier` (PKCE)

Campos adicionais para clientes `confidential`:
- `client_secret`

Resposta:
- `access_token` (JWT)
- `refresh_token`
- `expires_in`
- `token_type=Bearer`
- `id_token` (retornado quando o escopo inclui `basic`)

### 4) Refresh Token
Use `/oauth/token` com:
- `grant_type=refresh_token`
- `refresh_token`
- `client_id`
- `client_secret` (apenas `confidential`)
- `scope` (opcional, deve ser subconjunto do escopo original)

Refresh tokens sao rotacionados. O token anterior e revogado apos o uso.

## Obter dados do usuario
Endpoint: `GET /api/user` com `Authorization: Bearer` usando o `access_token`.

Resposta baseada em escopos:
- Sempre: `sub`
- `basic` ou `profile`: `name`
- `basic` ou `email`: `email`

## Privacidade e compartilhamento de dados
- O UWAY Auth so entrega dados consentidos e estritamente vinculados ao escopo.
- O app cliente deve solicitar o menor escopo possivel.
- Informacoes sensiveis internas (documento, telefone, role, avatar) nao sao expostas em nenhum endpoint.
- O consentimento e obrigatorio sempre que novos escopos forem solicitados.

## Boas praticas de seguranca
- Use HTTPS em producao.
- Valide o parametro `state`.
- Armazene `client_secret` apenas no backend.
- Para mobile e SPA, use cliente `public` com PKCE.
- Guarde tokens com seguranca e respeite o tempo de expiracao.
- Valide a assinatura do `id_token` com a chave publica do UWAY Auth.

## Erros comuns
- `401 Unauthorized`: token ausente, expirado ou invalido.
- `403 Forbidden`: escopo insuficiente ou acesso negado.
- `invalid_redirect_uri`: URI nao registrada.
- `invalid_scope`: escopo nao permitido.

## SDK PHP (Service)
O SDK PHP inclui um service pronto para o fluxo de login:
- Classe: `UwayAuth\\Sdk\\UwayAuthService`
- Metodos: `buildAuthorizationUrl`, `exchangeAuthorizationCode`, `refreshTokens`, `fetchUserInfo`
- Requer `ext-curl`
