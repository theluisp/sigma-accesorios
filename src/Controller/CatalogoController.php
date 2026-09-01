<?php

namespace App\Controller;

use App\Entity\Producto;
use App\Repository\ProductoRepository;
use App\Service\Catalog\MarcaCatalog;
use App\Service\Catalog\ProductCategorizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CatalogoController extends AbstractController
{
    private const POR_PAGINA = 12;

    /**
     * Slug reservado para el filtro de "Ofertas" — no es una categoría real
     * de ProductCategorizer, es un pseudo-filtro que se resuelve aparte
     * (ver index() abajo). No puede chocar con un slug real porque
     * ProductCategorizer no usa "ofertas".
     */
    private const OFERTAS_SLUG = 'ofertas';

    /**
     * Slug reservado para el filtro de "Novedades" — igual que Ofertas, NO
     * es una categoría real: es "productos que aparecieron por primera vez
     * en un app:catalog:sync en los últimos N días" (ver
     * Producto::getCreadoEn(), que se fija una sola vez al crear el
     * producto y nunca se actualiza en syncs posteriores).
     */
    private const NOVEDADES_SLUG = 'novedades';
    private const NOVEDADES_DIAS = 15;

    /**
     * Prefijo reservado para el pseudo-filtro de Marcas (ver MarcaCatalog):
     * "marca-apple", "marca-xiaomi", etc. — mismo mecanismo que
     * OFERTAS_SLUG/NOVEDADES_SLUG, así las tarjetas de Marcas de Home
     * enlazan directo a un filtro consistente con la regla de coincidencia
     * real (MarcaCatalog::coincide()), en vez de depender de la búsqueda
     * de texto genérica (?q=), que tiene semántica distinta (AND de
     * palabras sobre el texto literal, no OR de palabras clave de marca).
     */
    private const MARCA_PREFIX = 'marca-';

    #[Route('/catalogo', name: 'catalogo', methods: ['GET'])]
    public function index(Request $request, ProductoRepository $productoRepository, ProductCategorizer $categorizer, MarcaCatalog $marcaCatalog): Response
    {
        $texto = trim((string) $request->query->get('q', ''));
        $categoriaSeleccionada = (string) $request->query->get('categoria', '');
        $paginaSolicitada = max(1, (int) $request->query->get('pagina', 1));

        $marcaSlug = str_starts_with($categoriaSeleccionada, self::MARCA_PREFIX)
            ? substr($categoriaSeleccionada, \strlen(self::MARCA_PREFIX))
            : null;

        // Búsqueda de texto a nivel SQL (aprovecha el collation de MySQL);
        // la disponibilidad ya viene filtrada por el repositorio.
        $resultadosBusqueda = $productoRepository->buscarDisponibles($texto !== '' ? $texto : null);

        $umbralNovedades = (new \DateTimeImmutable())->modify(sprintf('-%d days', self::NOVEDADES_DIAS));

        // Conteo por categoría, por ofertas y por novedades sobre los
        // resultados de la búsqueda de texto (sin aplicar todavía el filtro
        // seleccionado), para que las píldoras muestren cuántos hay en cada
        // una dado lo que se está buscando.
        $conteoPorCategoria = [];
        $conteoOfertas = 0;
        $conteoNovedades = 0;
        foreach ($resultadosBusqueda as $producto) {
            $cat = $producto->getCategoria();
            $conteoPorCategoria[$cat] = ($conteoPorCategoria[$cat] ?? 0) + 1;
            if ($producto->getDescuentoPorcentaje() > 0) {
                ++$conteoOfertas;
            }
            if ($producto->getCreadoEn() >= $umbralNovedades) {
                ++$conteoNovedades;
            }
        }

        $productosFiltrados = match (true) {
            $categoriaSeleccionada === self::OFERTAS_SLUG => array_values(array_filter(
                $resultadosBusqueda,
                static fn (Producto $producto): bool => $producto->getDescuentoPorcentaje() > 0,
            )),
            $categoriaSeleccionada === self::NOVEDADES_SLUG => array_values(array_filter(
                $resultadosBusqueda,
                static fn (Producto $producto): bool => $producto->getCreadoEn() >= $umbralNovedades,
            )),
            $marcaSlug !== null => array_values(array_filter(
                $resultadosBusqueda,
                static fn (Producto $producto): bool => $marcaCatalog->coincide($producto->getNombre(), $marcaSlug),
            )),
            $categoriaSeleccionada !== '' => array_values(array_filter(
                $resultadosBusqueda,
                static fn (Producto $producto): bool => $producto->getCategoria() === $categoriaSeleccionada,
            )),
            default => $resultadosBusqueda,
        };

        // Label legible del filtro activo para el texto "N resultados en
        // X" — resuelto aquí (no en la plantilla) para que la marca use la
        // misma fuente de verdad (MarcaCatalog) que el resto de la lógica.
        $categoriaLabel = match (true) {
            $categoriaSeleccionada === self::OFERTAS_SLUG => 'Ofertas',
            $categoriaSeleccionada === self::NOVEDADES_SLUG => 'Novedades',
            $marcaSlug !== null => $marcaCatalog->label($marcaSlug),
            $categoriaSeleccionada !== '' => $categorizer->label($categoriaSeleccionada),
            default => '',
        };

        $total = count($productosFiltrados);
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $pagina = min($paginaSolicitada, $totalPaginas);

        $productosPagina = array_slice($productosFiltrados, ($pagina - 1) * self::POR_PAGINA, self::POR_PAGINA);

        // Título/descripción para SEO (pedido explícito del usuario, ago
        // 2026): cada filtro real (categoría, marca, ofertas, novedades)
        // tiene su propio <title>/meta description en vez de que TODO el
        // catálogo comparta el mismo — así Google puede indexar
        // "Fundas — Catálogo Sigma Accesorios" y "Ofertas — Catálogo Sigma
        // Accesorios" como páginas distintas. La búsqueda de texto libre
        // (?q=) no cambia el título — son demasiadas combinaciones
        // posibles como para que valga la pena, y no son búsquedas que
        // Google deba indexar por separado.
        $metaTitulo = $categoriaLabel !== ''
            ? $categoriaLabel.' — Catálogo Sigma Accesorios'
            : 'Catálogo completo — Sigma Accesorios para Celular en Puebla';
        $metaDescripcion = $categoriaLabel !== ''
            ? $categoriaLabel.' — catálogo de Sigma Accesorios para Celular en Puebla. Recoge en sucursal o pide a domicilio por WhatsApp, Rappi o Didi Food.'
            : 'Explora todo el catálogo: fundas, cargadores, audífonos, soportes y más accesorios para celular en Puebla. Recoge en sucursal o pide a domicilio.';

        return $this->render('catalogo/index.html.twig', [
            'productos' => $productosPagina,
            'texto' => $texto,
            'categoriaSeleccionada' => $categoriaSeleccionada,
            'categoriaLabel' => $categoriaLabel,
            'metaTitulo' => $metaTitulo,
            'metaDescripcion' => $metaDescripcion,
            'categorias' => $categorizer->todas(),
            'conteoPorCategoria' => $conteoPorCategoria,
            'conteoOfertas' => $conteoOfertas,
            'ofertasSlug' => self::OFERTAS_SLUG,
            'conteoNovedades' => $conteoNovedades,
            'novedadesSlug' => self::NOVEDADES_SLUG,
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
