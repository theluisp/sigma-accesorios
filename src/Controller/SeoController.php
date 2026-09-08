<?php

namespace App\Controller;

use App\Repository\ProductoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * robots.txt y sitemap.xml (SEO básico, pedido explícito del usuario, ago
 * 2026) — generados por controlador en vez de archivos estáticos en
 * public/ para que las URLs del sitemap usen SIEMPRE el dominio real de
 * producción (via url(), que arma la URL absoluta a partir de la petición
 * actual), sin tener que hardcodear el dominio en ningún lado ni
 * mantenerlo sincronizado a mano.
 */
final class SeoController extends AbstractController
{
    #[Route('/robots.txt', name: 'robots', methods: ['GET'])]
    public function robots(): Response
    {
        $response = $this->render('seo/robots.txt.twig');
        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');

        return $response;
    }

    /**
     * Sitemap de imágenes (SEO sep 2026): además de las páginas de
     * siempre, la entrada del Catálogo ahora incluye una <image:image> por
     * cada producto disponible con foto propia, para que Google Imágenes
     * pueda indexarlas — aunque el Catálogo sea una sola URL (los
     * productos se ven en un modal, no tienen página propia todavía), el
     * sitemap de imágenes SÍ permite listar varias imágenes bajo una misma
     * <url>, así que no hace falta esperar a que cada producto tenga su
     * propia URL para sacarle provecho a esto.
     */
    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
    public function sitemap(ProductoRepository $productoRepository): Response
    {
        $productos = $productoRepository->findParaSitemapImagenes();

        // <lastmod> del Catálogo: la fecha del producto actualizado más
        // recientemente (vía app:catalog:sync), no "ahora" en cada visita
        // — eso sería una señal de frescura falsa. Si no hay productos con
        // imagen todavía, se omite el <lastmod> en vez de inventar una
        // fecha.
        $ultimaActualizacion = null;
        foreach ($productos as $producto) {
            if ($ultimaActualizacion === null || $producto->getActualizadoEn() > $ultimaActualizacion) {
                $ultimaActualizacion = $producto->getActualizadoEn();
            }
        }

        $response = $this->render('seo/sitemap.xml.twig', [
            'productos' => $productos,
            'ultimaActualizacion' => $ultimaActualizacion,
        ]);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }
}
