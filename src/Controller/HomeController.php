<?php

namespace App\Controller;

use App\Repository\ProductoRepository;
use App\Service\Banner\BannerImageResolver;
use App\Service\Contacto\ContactoLinks;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(
        ProductoRepository $productoRepository,
        BannerImageResolver $bannerResolver,
        ContactoLinks $contactoLinks,
    ): Response {
        return $this->render('home/index.html.twig', [
            'productos' => $productoRepository->findDestacados(12),
            'banners' => $this->construirBanners($bannerResolver, $contactoLinks),
        ]);
    }

    /**
     * 3 slides del carrusel de Home. Si todavía no se subió la foto real de
     * un banner (public/images/banners/banner-N.*), BannerImageResolver
     * regresa null y la plantilla pinta un fondo con degradado de marca en
     * su lugar — el carrusel se ve bien desde el día uno.
     *
     * @return array<int, array{numero: int, imagen: ?string, titulo: string, subtitulo: string, ctaTexto: string, ctaUrl: ?string, ctaExterna: bool}>
     */
    private function construirBanners(BannerImageResolver $bannerResolver, ContactoLinks $contactoLinks): array
    {
        return [
            [
                'numero' => 1,
                'imagen' => $bannerResolver->resolve(1),
                'titulo' => 'Encuentra el accesorio perfecto',
                'subtitulo' => 'Fundas, cargadores, audio y más — inventario actualizado todos los días.',
                'ctaTexto' => 'Ver catálogo',
                'ctaUrl' => $this->generateUrl('catalogo'),
                'ctaExterna' => false,
            ],
            [
                'numero' => 2,
                'imagen' => $bannerResolver->resolve(2),
                'titulo' => 'Pide por Rappi',
                'subtitulo' => 'Recíbelo el mismo día, directo en tu puerta.',
                'ctaTexto' => 'Pedir en Rappi',
                'ctaUrl' => $contactoLinks->rappiUrl(),
                'ctaExterna' => true,
            ],
            [
                'numero' => 3,
                'imagen' => $bannerResolver->resolve(3),
                'titulo' => '¿Tienes dudas?',
                'subtitulo' => 'Escríbenos por WhatsApp y te ayudamos a elegir el accesorio correcto.',
                'ctaTexto' => 'Escribir por WhatsApp',
                'ctaUrl' => $contactoLinks->whatsappUrl(),
                'ctaExterna' => true,
            ],
        ];
    }
}
