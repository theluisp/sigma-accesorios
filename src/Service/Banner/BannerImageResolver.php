<?php

namespace App\Service\Banner;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Resuelve la imagen de cada banner del carrusel de Home por convención de
 * nombre de archivo (banner-1, banner-2, banner-3), igual que
 * App\Service\Catalog\ProductImageResolver hace con las fotos de producto.
 * Si todavía no se subió la imagen real, resolve() regresa null y la
 * plantilla pinta un fondo con degradado de marca en su lugar — así el
 * carrusel se ve bien desde el día uno, y basta con dejar caer
 * banner-1.jpg / banner-2.jpg / banner-3.jpg en public/images/banners/
 * para reemplazarlo por fotos reales, sin tocar código.
 */
final class BannerImageResolver
{
    /** @var string[] */
    private const EXTENSIONS = ['webp', 'jpg', 'jpeg', 'png'];

    public function __construct(
        #[Autowire('%kernel.project_dir%/public/images/banners')]
        private readonly string $bannersDir,
        #[Autowire('/images/banners')]
        private readonly string $publicPath,
    ) {
    }

    public function resolve(int $numero): ?string
    {
        foreach (self::EXTENSIONS as $extension) {
            $filename = "banner-{$numero}.{$extension}";
            $fullPath = $this->bannersDir.'/'.$filename;
            if (is_file($fullPath)) {
                // Cache-busting por fecha de modificación del archivo (igual
                // que app.css/app.js?v=N en base.html.twig, pero automático):
                // el nombre del banner nunca cambia al reemplazarlo, así que
                // sin esto el navegador (y la caché de Hostinger) sigue
                // sirviendo la imagen vieja después de subir una nueva.
                $mtime = filemtime($fullPath) ?: 0;

                return $this->publicPath.'/'.$filename.'?v='.$mtime;
            }
        }

        return null;
    }
}
