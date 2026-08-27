<?php

namespace App\Controller;

use App\Repository\ProductoRepository;
use App\Service\Banner\BannerImageResolver;
use App\Service\Catalog\ProductCategorizer;
use App\Service\Contacto\ContactoLinks;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    /**
     * Marcas para la sección "Marcas" de Home (pedido explícito del
     * usuario, ago 2026) — NO es una categoría de ProductCategorizer, es
     * un agrupador aparte que busca la palabra clave directo en el NOMBRE
     * del producto (ver ProductoRepository::marcasConStock()). Orden fijo
     * pedido por el usuario: Apple, Xiaomi, Motorola, Oppo, Samsung.
     *
     * @var array<string, string> slug => palabra clave a buscar en el nombre
     */
    private const MARCAS = [
        'apple' => 'Apple',
        'xiaomi' => 'Xiaomi',
        'motorola' => 'Motorola',
        'oppo' => 'Oppo',
        'samsung' => 'Samsung',
    ];

    #[Route('/', name: 'home')]
    public function index(
        ProductoRepository $productoRepository,
        BannerImageResolver $bannerResolver,
        ContactoLinks $contactoLinks,
        ProductCategorizer $categorizer,
    ): Response {
        return $this->render('home/index.html.twig', [
            'productos' => $productoRepository->findEnOferta(12),
            'banners' => $this->construirBanners($bannerResolver, $contactoLinks),
            'categorias' => $this->categoriasParaMostrar($productoRepository, $categorizer),
            'marcas' => $this->marcasParaMostrar($productoRepository),
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
     * disponible ahora mismo con esa palabra en el nombre (mismo criterio
     * de "nunca una tarjeta vacía" que categoriasParaMostrar()), en el
     * orden fijo de self::MARCAS.
     *
     * @return array<int, array{slug: string, label: string}>
     */
    private function marcasParaMostrar(ProductoRepository $productoRepository): array
    {
        $conStock = array_flip($productoRepository->marcasConStock(self::MARCAS));

        $resultado = [];
        foreach (self::MARCAS as $slug => $label) {
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
