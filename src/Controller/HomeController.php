<?php

namespace App\Controller;

use App\Repository\ProductoRepository;
use App\Service\Banner\BannerImageResolver;
use App\Service\Catalog\MarcaCatalog;
use App\Service\Contacto\ContactoLinks;
use App\Service\Seo\NegocioJsonLd;
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
        MarcaCatalog $marcaCatalog,
        NegocioJsonLd $negocioJsonLd,
    ): Response {
        return $this->render('home/index.html.twig', [
            'productos' => $productoRepository->findEnOferta(12),
            'banners' => $this->construirBanners($bannerResolver, $contactoLinks),
            'marcas' => $this->marcasParaMostrar($productoRepository, $marcaCatalog),
            'jsonLdNegocio' => $negocioJsonLd->comoJson(),
        ]);
    }

    /**
     * Marcas de la sección "Marcas" de Home: solo las que tienen producto
     * disponible ahora mismo con alguna de sus palabras clave en el nombre
     * (nunca una tarjeta que lleve a un catálogo vacío), en el orden fijo
     * de MarcaCatalog. La regla de coincidencia vive en MarcaCatalog para
     * que sea exactamente la misma que usa CatalogoController al resolver
     * el pseudo-filtro "marca-<slug>" cuando se da clic en una tarjeta.
     *
     * @return array<int, array{slug: string, label: string}>
     */
    private function marcasParaMostrar(ProductoRepository $productoRepository, MarcaCatalog $marcaCatalog): array
    {
        $conStock = array_flip($productoRepository->marcasConStock($marcaCatalog->keywordsPorSlug()));

        $resultado = [];
        foreach ($marcaCatalog->todas() as $slug => $label) {
            if (isset($conStock[$slug])) {
                $resultado[] = ['slug' => $slug, 'label' => $label];
            }
        }

        return $resultado;
    }

    /**
     * 3 slides del carrusel de Home. Si todavía no se subió la foto real de
     * un banner (public/images/banners/banner-N.*), BannerImageResolver
     * regresa null y la plantilla pinta un fondo con degradado de marca en
     * su lugar — el carrusel se ve bien desde el día uno.
     *
     * El ORDEN en que aparecen los slides es el orden de este array, no el
     * valor de 'numero' (ese solo sirve para que BannerImageResolver
     * encuentre el archivo banner-{numero}.* correcto y para la clase CSS
     * de degradado de respaldo). Pedido explícito del usuario (sep 2026):
     * intercambiar las posiciones 1 y 3 del carrusel — banner-3.png (Promo
     * de Septiembre, ya trae su propio texto en la imagen, por eso sin
     * overlay) ahora se ve primero, y banner-1.png (foto de producto lisa,
     * con overlay "Encuentra el accesorio perfecto" + botón) al final. Cada
     * entrada se mueve completa (imagen + su texto/CTA correspondiente) en
     * vez de solo intercambiar los archivos, para que el overlay de texto
     * no quede encimado sobre la Promo de Septiembre (que ya es
     * autoexplicativa).
     *
     * @return array<int, array{numero: int, imagen: ?string, titulo: string, subtitulo: string, ctaTexto: string, ctaUrl: ?string, ctaExterna: bool}>
     */
    private function construirBanners(BannerImageResolver $bannerResolver, ContactoLinks $contactoLinks): array
    {
        return [
            [
                'numero' => 3,
                'imagen' => $bannerResolver->resolve(3),
                'titulo' => '',
                'subtitulo' => '',
                'ctaTexto' => '',
                // Sin botón a propósito (pedido explícito del usuario) — solo
                // la imagen del banner, sin CTA. ctaUrl en null es lo que
                // evita que la plantilla pinte el botón (ver home/index.html.twig,
                // el bloque del botón está condicionado a `banner.ctaUrl`).
                'ctaUrl' => null,
                'ctaExterna' => true,
            ],
            [
                'numero' => 2,
                'imagen' => $bannerResolver->resolve(2),
                'titulo' => 'Pide por Rappi',
                'subtitulo' => 'Recíbelo el mismo día, directo en tu puerta.',
                'ctaTexto' => 'Pide a domicilio',
                'ctaUrl' => $contactoLinks->rappiUrl(),
                'ctaExterna' => true,
            ],
            [
                'numero' => 1,
                'imagen' => $bannerResolver->resolve(1),
                'titulo' => 'Encuentra el accesorio perfecto',
                'subtitulo' => 'Fundas, cargadores, audio y más — inventario actualizado todos los días.',
                'ctaTexto' => 'Ver catálogo',
                'ctaUrl' => $this->generateUrl('catalogo'),
                'ctaExterna' => false,
            ],
        ];
    }
}
