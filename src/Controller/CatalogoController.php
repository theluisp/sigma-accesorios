<?php

namespace App\Controller;

use App\Entity\Producto;
use App\Repository\ProductoRepository;
use App\Service\Catalog\ProductCategorizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CatalogoController extends AbstractController
{
    private const POR_PAGINA = 12;

    #[Route('/catalogo', name: 'catalogo', methods: ['GET'])]
    public function index(Request $request, ProductoRepository $productoRepository, ProductCategorizer $categorizer): Response
    {
        $texto = trim((string) $request->query->get('q', ''));
        $categoriaSeleccionada = (string) $request->query->get('categoria', '');
        $paginaSolicitada = max(1, (int) $request->query->get('pagina', 1));

        // Búsqueda de texto a nivel SQL (aprovecha el collation de MySQL);
        // la disponibilidad ya viene filtrada por el repositorio.
        $resultadosBusqueda = $productoRepository->buscarDisponibles($texto !== '' ? $texto : null);

        // Conteo por categoría sobre los resultados de la búsqueda de texto
        // (sin aplicar todavía el filtro de categoría), para que las píldoras
        // muestren cuántos hay en cada una dado lo que se está buscando.
        $conteoPorCategoria = [];
        foreach ($resultadosBusqueda as $producto) {
            $cat = $producto->getCategoria();
            $conteoPorCategoria[$cat] = ($conteoPorCategoria[$cat] ?? 0) + 1;
        }

        $productosFiltrados = $categoriaSeleccionada !== ''
            ? array_values(array_filter(
                $resultadosBusqueda,
                static fn (Producto $producto): bool => $producto->getCategoria() === $categoriaSeleccionada,
            ))
            : $resultadosBusqueda;

        $total = count($productosFiltrados);
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $pagina = min($paginaSolicitada, $totalPaginas);

        $productosPagina = array_slice($productosFiltrados, ($pagina - 1) * self::POR_PAGINA, self::POR_PAGINA);

        return $this->render('catalogo/index.html.twig', [
            'productos' => $productosPagina,
            'texto' => $texto,
            'categoriaSeleccionada' => $categoriaSeleccionada,
            'categorias' => $categorizer->todas(),
            'conteoPorCategoria' => $conteoPorCategoria,
            'totalEnBusqueda' => count($resultadosBusqueda),
            'totalResultados' => $total,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'rangoPaginas' => $this->calcularRangoPaginas($pagina, $totalPaginas),
        ]);
    }

    /**
     * Ventana de páginas a mostrar en el paginador, con `null` como marcador
     * de "…" cuando hay huecos. Ej. con 20 páginas y actual=10:
     * [1, null, 9, 10, 11, null, 20].
     *
     * @return array<int, int|null>
     */
    private function calcularRangoPaginas(int $actual, int $total): array
    {
        if ($total <= 7) {
            return range(1, $total);
        }

        $paginas = [1];

        $inicio = max(2, $actual - 1);
        $fin = min($total - 1, $actual + 1);

        if ($inicio > 2) {
            $paginas[] = null;
        }

        for ($i = $inicio; $i <= $fin; ++$i) {
            $paginas[] = $i;
        }

        if ($fin < $total - 1) {
            $paginas[] = null;
        }

        $paginas[] = $total;

        return $paginas;
    }
}
