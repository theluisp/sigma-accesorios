<?php

namespace App\Service\GoogleSheets;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Cliente mínimo de la API de Google Sheets (solo lectura de valores).
 */
final class GoogleSheetsClient
{
    public function __construct(
        #[Autowire(env: 'GOOGLE_SHEETS_SPREADSHEET_ID')]
        private readonly string $spreadsheetId,
        private readonly GoogleServiceAccountAuthenticator $authenticator,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @param string $range ej. "'REAL DE GUADALUPE'!A2:L"
     *
     * @return array<int, array<int, string>> filas crudas, cada una es un array de celdas (texto)
     */
    public function getValues(string $range): array
    {
        if ($this->spreadsheetId === '') {
            throw new \RuntimeException(
                'GOOGLE_SHEETS_SPREADSHEET_ID no está configurado en .env.local.'
            );
        }

        $url = sprintf(
            'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s',
            $this->spreadsheetId,
            rawurlencode($range)
        );

        $response = $this->httpClient->request('GET', $url, [
            'auth_bearer' => $this->authenticator->getAccessToken(),
            'query' => ['majorDimension' => 'ROWS'],
        ]);

        try {
            $data = $response->toArray();
        } catch (HttpExceptionInterface $e) {
            $body = $e->getResponse()->getContent(false);
            $reason = $body;

            $decoded = json_decode($body, true);
            if (is_array($decoded) && isset($decoded['error']['message'])) {
                $reason = $decoded['error']['message'];
            }

            throw new \RuntimeException(sprintf(
                'Google rechazó la lectura del rango "%s": %s. '
                .'Revisa que el nombre de la pestaña en GOOGLE_SHEETS_RANGE_* coincida EXACTO con el de tu Sheet.',
                $range,
                $reason
            ), previous: $e);
        }

        return $data['values'] ?? [];
    }
}
