<?php

namespace App\Service\Catalog;

/**
 * Marcas de la sección "Marcas" de Home (pedido explícito del usuario, ago
 * 2026) — NO son categorías de ProductCategorizer, es un agrupador aparte
 * que busca palabras clave directo en el NOMBRE del producto (a diferencia
 * de ProductCategorizer::classify(), que también revisa la descripción).
 *
 * Mismo patrón que ProductCategorizer (slug => {label, keywords}), para que
 * tanto HomeController (arma las tarjetas de Marcas) como CatalogoController
 * (resuelve el pseudo-filtro "marca-<slug>" al dar clic en una tarjeta,
 * mismo mecanismo que OFERTAS_SLUG/NOVEDADES_SLUG) usen exactamente la
 * misma regla de coincidencia — así una marca nunca muestra en Home
 * productos distintos a los que aparecen al entrar al Catálogo.
 *
 * Apple usa varias palabras clave porque muchos títulos de accesorios
 * compatibles dicen "Cable Lightning" o "Funda iPhone" sin mencionar
 * "Apple" — cualquiera de las tres debe contar (pedido explícito del
 * usuario, ago 2026).
 */
final class MarcaCatalog
{
    /**
     * Orden fijo pedido por el usuario: Apple, Xiaomi, Motorola, Oppo,
     * Samsung, Honor/Huawei (agregada sep 2026).
     *
     * @var array<string, array{label: string, keywords: string[]}>
     */
    private const MARCAS = [
        'apple' => [
            'label' => 'Apple',
            'keywords' => ['apple', 'iphone', 'lightning'],
        ],
        'xiaomi' => [
            'label' => 'Xiaomi',
            'keywords' => ['xiaomi'],
        ],
        'motorola' => [
            'label' => 'Motorola',
            'keywords' => ['motorola'],
        ],
        'oppo' => [
            'label' => 'Oppo',
            'keywords' => ['oppo'],
        ],
        'samsung' => [
            'label' => 'Samsung',
            'keywords' => ['samsung'],
        ],
        // Marca combinada (pedido explícito del usuario, sep 2026): un solo
        // grupo "Honor/Huawei" que junta productos de ambas marcas bajo una
        // sola tarjeta/filtro, igual que Apple agrupa varias palabras clave.
        'honor-huawei' => [
            'label' => 'Honor/Huawei',
            'keywords' => ['honor', 'huawei'],
        ],
    ];

    /**
     * @return array<string, string> slug => label, en el orden fijo de arriba
     */
    public function todas(): array
    {
        $lista = [];
        foreach (self::MARCAS as $slug => $config) {
            $lista[$slug] = $config['label'];
        }

        return $lista;
    }

    public function label(string $slug): string
    {
        return self::MARCAS[$slug]['label'] ?? '';
    }

    /**
     * @return array<string, string[]> slug => palabras clave a buscar en el nombre
     */
    public function keywordsPorSlug(): array
    {
        $lista = [];
        foreach (self::MARCAS as $slug => $config) {
            $lista[$slug] = $config['keywords'];
        }

        return $lista;
    }

    /**
     * Regla de coincidencia única: la palabra clave puede aparecer en
     * CUALQUIER lugar del nombre del producto (mb_stripos, no exige inicio
     * de palabra). Usada tanto por ProductoRepository::marcasConStock()
     * (para saber qué tarjetas de Marcas mostrar en Home) como por
     * CatalogoController (para filtrar el catálogo cuando se da clic en
     * una tarjeta) — la misma regla en ambos lados evita inconsistencias.
     */
    public function coincide(string $nombreProducto, string $slug): bool
    {
        foreach (self::MARCAS[$slug]['keywords'] ?? [] as $palabraClave) {
            if (mb_stripos($nombreProducto, $palabraClave) !== false) {
                return true;
            }
        }

        return false;
    }
}
