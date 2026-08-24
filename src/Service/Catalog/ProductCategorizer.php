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
     *      Orden importa: se evalúan en este orden y gana la primera que matchee
     *      (ej. "Soporte para auto" debe evaluarse ANTES que una categoría
     *      genérica de cargadores/adaptadores para no perder el matiz de "auto").
     *      Lista ampliada a pedido del usuario (ago 2026) — antes solo había
     *      Fundas/Micas/Audio/Cargadores/Soportes genérico; Fundas y Micas se
     *      mantuvieron explícitamente, "Soportes" genérico se reemplazó por
     *      las dos variantes específicas (auto/moto) que pidió, y se agregaron
     *      Adaptadores, Hogar, Videojuegos, Computación, Chips e iPhone.
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
        'chips' => [
            'label' => 'Chips',
            'keywords' => ['chip telcel', 'chip movistar', 'chip at&t', 'chip unefon', 'simcard', 'sim card', 'mini sim', 'micro sim', 'nano sim', 'chip'],
        ],
        'soportes-auto' => [
            'label' => 'Soportes y accesorios para auto',
            'keywords' => ['soporte para auto', 'soporte auto', 'soporte para carro', 'soporte coche', 'base para auto', 'soporte parabrisas', 'soporte tablero', 'soporte rejilla', 'car mount'],
        ],
        'soportes-moto' => [
            'label' => 'Soportes y accesorios para motos',
            'keywords' => ['soporte para moto', 'soporte moto', 'soporte motocicleta', 'soporte manubrio', 'moto mount'],
        ],
        'adaptadores' => [
            'label' => 'Adaptadores',
            'keywords' => ['adaptador', 'otg', 'hub usb', 'lightning a ', 'tipo c a '],
        ],
        'cargadores' => [
            'label' => 'Cargadores y cables',
            'keywords' => ['cargador', 'cable', 'charger', 'power bank', 'powerbank', 'bateria portatil', 'pila'],
        ],
        'bocinas-audifonos' => [
            'label' => 'Bocinas y audífonos',
            'keywords' => ['audifono', 'auricular', 'bocina', 'speaker', 'diadema', 'manos libres', 'earbuds', 'earpods'],
        ],
        'videojuegos' => [
            'label' => 'Videojuegos',
            'keywords' => ['videojuego', 'gamer', 'control inalambrico', 'joystick', 'gamepad', 'consola', 'ps4', 'ps5', 'playstation', 'xbox', 'nintendo', 'switch'],
        ],
        'computacion' => [
            'label' => 'Computación',
            'keywords' => ['laptop', 'computadora', 'teclado', 'mouse', 'monitor', 'disco duro', 'memoria usb', 'webcam', 'impresora'],
        ],
        'hogar' => [
            'label' => 'Hogar',
            'keywords' => ['hogar', 'lampara led', 'foco inteligente', 'extension electrica', 'regleta', 'ventilador'],
        ],
        'iphone' => [
            'label' => 'iPhone',
            'keywords' => ['iphone'],
        ],
    ];

    /**
     * Clasifica por nombre Y descripción (pedido explícito del usuario: si
     * la descripción menciona "iPhone" —o cualquier otra palabra clave—
     * aunque el nombre no lo diga, el producto debe caer en esa categoría
     * igual). Se concatenan ambos textos normalizados antes de buscar, así
     * que aplica parejo a todas las categorías, no solo a iPhone.
     */
    public function classify(string $nombre, string $descripcion = ''): string
    {
        $normalizado = $this->normalizar($nombre.' '.$descripcion);

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
