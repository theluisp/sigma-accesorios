<?php

namespace App\Service\GoogleSheets;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Obtiene un access token OAuth2 para la cuenta de servicio de Google, firmando
 * un JWT con la llave privada del archivo de credenciales (sin el SDK oficial
 * de Google, que arrastra muchas dependencias que no necesitamos solo para leer Sheets).
 *
 * Referencia: https://developers.google.com/identity/protocols/oauth2/service-account
 */
final class GoogleServiceAccountAuthenticator
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const SCOPE = 'https://www.googleapis.com/auth/spreadsheets.readonly';

    public function __construct(
        #[Autowire('%kernel.project_dir%/%env(GOOGLE_SHEETS_CREDENTIALS_PATH)%')]
        private readonly string $credentialsPath,
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
    ) {
    }

    public function getAccessToken(): string
    {
        return $this->cache->get('google_sheets_access_token', function (ItemInterface $item) {
            $credentials = $this->readCredentials();
            $jwt = $this->buildSignedJwt($credentials['client_email'], $credentials['private_key']);
            $response = $this->httpClient->request('POST', self::TOKEN_URL, [
                'body' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ],
            ]);

            $data = $response->toArray();

            if (!isset($data['access_token'], $data['expires_in'])) {
                throw new \RuntimeException('Google no devolvió un access_token válido al autenticar la cuenta de servicio.');
            }

            // Expira un poco antes del tiempo real para no usar un token a punto de caducar.
            $item->expiresAfter(max(60, (int) $data['expires_in'] - 60));

            return $data['access_token'];
        });
    }

    /**
     * @return array{client_email: string, private_key: string}
     */
    private function readCredentials(): array
    {
        if (!is_file($this->credentialsPath)) {
            throw new \RuntimeException(sprintf(
                'No se encontró el archivo de credenciales de Google en "%s". '
                .'Revisa docs/google-sheets-setup.md para crear la cuenta de servicio.',
                $this->credentialsPath
            ));
        }

        $json = json_decode((string) file_get_contents($this->credentialsPath), true);

        if (!is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
            throw new \RuntimeException(sprintf(
                'El archivo de credenciales "%s" no tiene el formato esperado (falta client_email o private_key).',
                $this->credentialsPath
            ));
        }

        return [
            'client_email' => $json['client_email'],
            'private_key' => $json['private_key'],
        ];
    }

    private function buildSignedJwt(string $clientEmail, string $privateKey): string
    {
        $now = time();

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss' => $clientEmail,
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $signingInput = $this->base64UrlEncode((string) json_encode($header))
            .'.'.$this->base64UrlEncode((string) json_encode($claims));

        $signature = '';
        $signed = openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (!$signed) {
            throw new \RuntimeException('No se pudo firmar el JWT con la llave privada de la cuenta de servicio.');
        }

        return $signingInput.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
