<?php

namespace App\Service\Catalog;

/**
 * El Sheet (export de Rappi) no trae una columna de categoría, así que la
 * inferimos del nombre del producto por palabras clave. Se usa al importar
 * (CatalogImportService, guarda el resultado en Producto::$categoria) y para
 * armar los filtros del Catálogo (CatalogoController).
 */
final class ProductCategorizer
{
    private const OTROS_SLUG = 'otros';
    private const OTROS_LABEL = 'Otros accesorios';

    /**
     * @var array<string, array{label: string, keywords: string[]}>
     *      Orden importa: se evalúan en este orden y gana la primera que matchee.
     */
    private const CATEGORIAS = [
        'fundas' => [
            'label' => 'Fundas',
            'keywords' => ['funda', 'case', 'estuche', 'cover'],
        ],
        'mica-vidrio' => [
            'label' => 'Micas y vidrios',
            'keywords' => ['mica', 'vidrio', 'templado', 'protector de pantalla', 'screen protector'],
        ],
        'audio' => [
            'label' => 'Audio',
            'keywords' => ['audifono', 'auricular', 'audio', 'bocina', 'speaker', 'diadema', 'manos libres', 'earbuds', 'earpods', 'bluetooth'],
        ],
        'cargadores' => [
            'label' => 'Cargadores y cables',
            'keywords' => ['cargador', 'cable', 'charger', 'adaptador', 'power bank', 'powerbank', 'bateria', 'pila'],
        ],
        'soportes' => [
            'label' => 'Soportes y otros accesorios',
            'keywords' => ['soporte', 'tripie', 'tripode', 'holder', 'stand', 'selfie', 'popsocket', 'anillo'],
        ],
    ];

    public function classify(string $nombre): string
    {
        $normalizado = $this->normalizar($nombre);

        foreach (self::CATEGORIAS as $slug => $config) {
            foreach ($config['keywords'] as $keyword) {
                if (str_contains($normalizado, $keyword)) {
                    return $slug;
                }
            }
        }

        return self::OTROS_SLUG;
    }

    public function label(string $slug): string
    {
        return self::CATEGORIAS[$slug]['label'] ?? self::OTROS_LABEL;
    }

    /**
     * @return array<string, string> slug => label, en orden de despliegue (incluye 'otros' al final)
     */
    public function todas(): array
    {
        $lista = [];
        foreach (self::CATEGORIAS as $slug => $config) {
            $lista[$slug] = $config['label'];
        }
        $lista[self::OTROS_SLUG] = self::OTROS_LABEL;

        return $lista;
    }

    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');

        return strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ñ' => 'n', 'ü' => 'u',
        ]);
    }
}
