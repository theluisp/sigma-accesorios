<?php

namespace App\Controller\Admin;

use App\Repository\ProductoRepository;
use App\Service\Catalog\ProductCategorizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Panel interno (protegido por HTTP Basic, ver config/packages/security.yaml)
 * para reclasificar a mano la categoría de cada producto — el clasificador
 * automático (App\Service\Catalog\ProductCategorizer) va por palabras clave
 * en el nombre y a veces se equivoca (ej. "moto" vs "bocinas y audífonos"),
 * así que aquí el usuario ve TODOS los productos en una sola pantalla, con
 * un selector de categoría cada uno, y los guarda todos juntos de una sola
 * vez con un solo botón — no uno por uno como /admin/imagenes.
 *
 * Distinto del pseudo-filtro de Marcas (MarcaCatalog): esto es la
 * categoría "de verdad" de ProductCategorizer (fundas, cargadores,
 * bocinas-audifonos, soportes-auto, soportes-moto, etc.), no marca de
 * fabricante.
 */
#[Route('/admin/categorias')]
final class CategoriaController extends AbstractController
{
    public function __construct(
        private readonly ProductoRepository $productoRepository,
        private readonly ProductCategorizer $categorizer,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'admin_categorias_index', methods: ['GET'])]
    public function index(): Response
    {
        $productos = $this->productoRepository->findAllConRelaciones();

        return $this->render('admin/categorias/index.html.twig', [
            'productos' => $productos,
            'categorias' => $this->categorizer->todas(),
        ]);
    }

    /**
     * Guarda TODAS las categorías del formulario de una sola vez (un solo
     * POST con un <select name="categoria[slug]"> por producto), en vez de
     * un botón por fila — pedido explícito del usuario. Cada producto
     * guardado queda marcado como "categoría manual" (ver
     * Producto::setCategoriaManual()), así que ya no se pisa en el
     * próximo app:catalog:sync.
     */
    #[Route('/guardar', name: 'admin_categorias_guardar', methods: ['POST'])]
    public function guardar(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('guardar-categorias', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido, intenta de nuevo.');

            return $this->redirectToRoute('admin_categorias_index');
        }

        $categoriasValidas = array_keys($this->categorizer->todas());

        /** @var array<string, string> $seleccion slug de producto => slug de categoría elegida */
        $seleccion = $request->request->all('categoria');

        // Trae todos los productos en una sola consulta (evita ~200 SELECT
        // sueltos, uno por producto — el catálogo es chico pero no hay
        // razón para hacerlo N+1 en un guardado masivo).
        $productosPorSlug = $this->productoRepository->findAllIndexadosPorSlug();

        $actualizados = 0;
        foreach ($seleccion as $slugProducto => $categoriaElegida) {
            if (!\in_array($categoriaElegida, $categoriasValidas, true)) {
                continue;
            }

            $producto = $productosPorSlug[$slugProducto] ?? null;
            if ($producto === null) {
                continue;
            }

            if (!$producto->isCategoriaManual() || $producto->getCategoria() !== $categoriaElegida) {
                $producto->setCategoriaManual($categoriaElegida);
                ++$actualizados;
            }
        }

        $this->em->flush();

        $this->addFlash('success', sprintf(
            '%d producto(s) actualizado(s). Esas categorías ya no se sobreescriben con la sincronización automática.',
            $actualizados,
        ));

        return $this->redirectToRoute('admin_categorias_index');
    }
}
