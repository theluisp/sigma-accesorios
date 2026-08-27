<?php

namespace App\Controller;

use App\Repository\ProductoRepository;
use App\Service\Banner\BannerImageResolver;
use App\Service\Catalog\MarcaCatalog;
use App\Service\Catalog\ProductCategorizer;
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
        ProductCategorizer $categorizer,
        MarcaCatalog $marcaCatalog,
    ): Response {
        return $this->render('home/index.html.twig', [
            'productos' => $productoRepository->findEnOferta(12),
            'banners' => $this->construirBanners($bannerResolver, $contactoLinks),
            'categorias' => $this->categoriasParaMostrar($productoRepository, $categorizer),
            'marcas' => $this->marcasParaMostrar($productoRepository, $marcaCatalog),
        ]);
    }

    /**
     * Categorías del carrusel de Home: solo las que tienen producto
     * disponible ahora mismo (nunca una categoría vacía que decepcione al
     * usuario), en el orden definido por ProductCategorizer.
     *
     * @return array<int, array{slug: string, label: string}>
     */
    private function categoriasParaMostrar(ProductoRepository $productoRepository, ProductCategorizer $categorizer): array
    {
        $conStock = array_flip($productoRepository->categoriasConStock());

        $resultado = [];
        foreach ($categorizer->todas() as $slug => $label) {
            if (isset($conStock[$slug])) {
                $resultado[] = ['slug' => $slug, 'label' => $label];
            }
        }

        return $resultado;
    }

    /**
     * Marcas de la sección "Marcas" de Home: solo las que tienen producto
     * disponible ahora mismo con alguna de sus palabras clave en el nombre
     * (mismo criterio de "nunca una tarjeta vacía" que
     * categoriasParaMostrar()), en el orden fijo de MarcaCatalog. La regla
     * de coincidencia vive en MarcaCatalog para que sea exactamente la
     * misma que usa CatalogoController al resolver el pseudo-filtro
     * "marca-<slug>" cuando se da clic en una tarjeta.
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
                'ctaTexto' => 'Pide a domicilio',
                'ctaUrl' => $contactoLinks->rappiUrl(),
                'ctaExterna' => true,
            ],
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
        ];
    }
}
