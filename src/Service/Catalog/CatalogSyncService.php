<?php

namespace App\Service\Catalog;

use App\Dto\ProductDto;
use App\Service\GoogleSheets\GoogleSheetsClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Junta el catálogo de las dos sucursales (Real De Guadalupe y Capu) en una
 * sola lista de productos, agrupados por su slug interno (estable entre sucursales).
 */
final class CatalogSyncService
{
    /**
     * @var array<string, string> clave interna => etiqueta legible de la sucursal
     */
    private const SUCURSALES = [
        'real_de_guadalupe' => 'Real De Guadalupe',
        'capu' => 'Capu',
    ];

    public function __construct(
        private readonly GoogleSheetsClient $sheetsClient,
        private readonly CatalogRowMapper $mapper,
        private readonly CacheInterface $cache,
        #[Autowire('%env(int:CATALOG_CACHE_TTL)%')]
        private readonly int $cacheTtl,
        #[Autowire('%env(GOOGLE_SHEETS_RANGE_REAL_DE_GUADALUPE)%')]
        private readonly string $rangeRealDeGuadalupe,
        #[Autowire('%env(GOOGLE_SHEETS_RANGE_CAPU)%')]
        private readonly string $rangeCapu,
    ) {
    }

    /**
     * @return ProductDto[] indexados por slug
     */
    public function getCatalog(): array
    {
        return $this->cache->get('catalog_productos', function (ItemInterface $item) {
            $item->expiresAfter($this->cacheTtl);

            return $this->buildCatalog();
        });
    }

    public function findBySlug(string $slug): ?ProductDto
    {
        return $this->getCatalog()[$slug] ?? null;
    }

    /**
     * @return ProductDto[] indexados por slug
     */
    private function buildCatalog(): array
    {
        $ranges = [
            'real_de_guadalupe' => $this->rangeRealDeGuadalupe,
            'capu' => $this->rangeCapu,
        ];

        /** @var array<string, ProductDto> $productos */
        $productos = [];

        foreach (self::SUCURSALES as $sucursalKey => $label) {
            $rows = $this->sheetsClient->getValues($ranges[$sucursalKey]);

            foreach ($rows as $row) {
                $mapped = $this->mapper->map($row, $sucursalKey, $label);

                if ($mapped === null) {
                    continue;
                }

                if (!isset($productos[$mapped['slug']])) {
                    $productos[$mapped['slug']] = new ProductDto(
                        slug: $mapped['slug'],
                        nombre: $mapped['nombre'],
                        descripcion: $mapped['descripcion'],
                    );
                }

                $productos[$mapped['slug']]->addBranch($mapped['branch']);
            }
        }

        return $productos;
    }
}
