<?php

namespace App\Service\Seo;

use App\Repository\SucursalRepository;
use App\Service\Contacto\ContactoLinks;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Arma el JSON-LD de datos estructurados (schema.org) que le dice a Google
 * explícitamente que Sigma es un negocio local real, con sus sucursales,
 * horarios y forma de contacto — pedido explícito del usuario, sep 2026
 * ("posicionar en los primeros resultados cuando se busque accesorios
 * para celular", ver docs/seo-notas.md).
 *
 * Usa @type ElectronicsStore (subtipo de LocalBusiness) por cada sucursal,
 * todas dentro de un @graph — no hay página propia por sucursal, así que
 * en vez de intentar forzar una URL distinta para cada una (que no
 * existe), se listan varias LocalBusiness completas compartiendo la misma
 * URL del sitio. Es el patrón que recomienda Google para negocios de
 * varias ubicaciones sin página individual por sucursal.
 *
 * NO incluye streetAddress (ver SucursalDireccionHorario) ni priceRange —
 * no tenemos esos datos confirmados, y un valor inventado puede
 * perjudicar más que no incluir el campo.
 *
 * También agrega un nodo @type WebSite con potentialAction: SearchAction
 * (SEO, sep 2026 — "otro poco de seo"), apuntando al buscador global de la
 * navbar (ver templates/base.html.twig, #site-search-panel). Es el
 * schema.org estándar para que Google pueda mostrar una cajita de
 * búsqueda propia del sitio directo en los resultados de búsqueda
 * ("sitelinks search box") — no garantiza que aparezca, pero sin este
 * dato es imposible que Google siquiera lo considere.
 */
final class NegocioJsonLd
{
    private const NOMBRE_NEGOCIO = 'Sigma Accesorios para Celular';

    public function __construct(
        private readonly SucursalRepository $sucursalRepository,
        private readonly SucursalDireccionHorario $direccionHorario,
        private readonly ContactoLinks $contactoLinks,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * JSON ya codificado (string), listo para meter directo en un
     * <script type="application/ld+json">...</script> — se codifica aquí
     * (no en Twig) para controlar las opciones de json_encode sin pelearse
     * con la sintaxis de filtros de Twig.
     */
    public function comoJson(): ?string
    {
        $graph = $this->construirGraph();
        if ($graph === []) {
            return null;
        }

        $documento = [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];

        return json_encode($documento, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function construirGraph(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return [];
        }

        $baseUrl = $request->getSchemeAndHttpHost();
        $telefono = $this->contactoLinks->whatsappTelefono();

        $sameAs = array_values(array_filter([
            $this->contactoLinks->facebookUrl(),
            $this->contactoLinks->rappiUrl(),
        ]));

        $graph = [
            [
                '@type' => 'WebSite',
                '@id' => $baseUrl.'/#website',
                'url' => $baseUrl.'/',
                'name' => self::NOMBRE_NEGOCIO,
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => [
                        '@type' => 'EntryPoint',
                        'urlTemplate' => $baseUrl.'/catalogo?q={search_term_string}',
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];

        foreach ($this->sucursalRepository->findAll() as $sucursal) {
            $datos = $this->direccionHorario->paraSucursal($sucursal->getClave());
            if ($datos === null) {
                // Sucursal nueva sin datos de dirección/horario capturados
                // todavía en SucursalDireccionHorario — se omite en vez de
                // publicar un LocalBusiness incompleto/incorrecto.
                continue;
            }

            $entrada = [
                '@type' => 'ElectronicsStore',
                '@id' => $baseUrl.'/#sucursal-'.$sucursal->getClave(),
                'name' => self::NOMBRE_NEGOCIO.' — '.$sucursal->getNombre(),
                'description' => 'Sucursal en la colonia '.$datos['colonia'].', Puebla, Pue.',
                'image' => $baseUrl.'/images/logo.png',
                'url' => $baseUrl.'/',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => 'Puebla',
                    'addressRegion' => 'Puebla',
                    'postalCode' => $datos['codigoPostal'],
                    'addressCountry' => 'MX',
                ],
                'openingHoursSpecification' => array_map(
                    static fn (array $horario): array => [
                        '@type' => 'OpeningHoursSpecification',
                        'dayOfWeek' => $horario[0],
                        'opens' => $horario[1],
                        'closes' => $horario[2],
                    ],
                    $datos['horarios'],
                ),
            ];

            if ($telefono !== null) {
                // WHATSAPP_PHONE_NUMBER ya viene "con lada país, solo
                // dígitos" (ver ContactoLinks) — nada más falta el "+" para
                // el formato E.164 que espera schema.org.
                $entrada['telephone'] = '+'.preg_replace('/\D+/', '', $telefono);
            }

            if ($sameAs !== []) {
                $entrada['sameAs'] = $sameAs;
            }

            $graph[] = $entrada;
        }

        return $graph;
    }
}
