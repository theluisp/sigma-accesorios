<?php

namespace App\Controller;

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

    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
    public function sitemap(): Response
    {
        $response = $this->render('seo/sitemap.xml.twig');
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }
}
