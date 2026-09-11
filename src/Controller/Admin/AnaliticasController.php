<?php

namespace App\Controller\Admin;

use App\Repository\VisitaDiariaRepository;
use App\Repository\VisitaPaginaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Panel interno (protegido por HTTP Basic, ver config/packages/security.yaml
 * — mismo usuario/password que /admin/imagenes) con el conteo básico de
 * visitas diarias y las páginas más visitadas. Los datos los alimenta
 * App\EventSubscriber\VisitaTrackerSubscriber en cada visita real al
 * sitio (fuera de /admin, y excluyendo bots/crawlers — ver esa clase).
 */
#[Route('/admin/analiticas')]
final class AnaliticasController extends AbstractController
{
    private const DIAS_A_MOSTRAR = 30;
    private const PAGINAS_A_MOSTRAR = 10;

    /**
     * Nombres legibles para las rutas más comunes, para no mostrarle al
     * usuario "/catalogo" pelado en la tabla — cualquier ruta que no esté
     * aquí (ej. una del futuro que no se haya agregado) se muestra tal
     * cual, no se rompe nada por dejarla fuera de esta lista.
     */
    private const NOMBRES_RUTA = [
        '/' => 'Inicio',
        '/catalogo' => 'Catálogo',
        '/contacto' => 'Contacto y pide a domicilio',
        '/nosotros' => 'Nosotros',
        '/negocios' => 'Esto también te puede interesar',
    ];

    public function __construct(
        private readonly VisitaDiariaRepository $visitas,
        private readonly VisitaPaginaRepository $visitasPorPagina,
    ) {
    }

    #[Route('', name: 'admin_analiticas_index', methods: ['GET'])]
    public function index(): Response
    {
        $ultimosDias = $this->visitas->ultimosDias(self::DIAS_A_MOSTRAR);
        $hoy = end($ultimosDias);

        $totalPeriodo = array_sum(array_column($ultimosDias, 'contador'));
        $promedioPeriodo = $totalPeriodo > 0
            ? round($totalPeriodo / self::DIAS_A_MOSTRAR, 1)
            : 0.0;
        $maxContador = max(array_column($ultimosDias, 'contador')) ?: 1;

        $paginasMasVisitadas = $this->visitasPorPagina->masVisitadas(self::PAGINAS_A_MOSTRAR);
        $maxVistasPagina = $paginasMasVisitadas !== []
            ? max(array_map(static fn ($p) => $p->getContador(), $paginasMasVisitadas))
            : 1;

        return $this->render('admin/analiticas/index.html.twig', [
            'ultimosDias' => $ultimosDias,
            'hoy' => $hoy,
            'totalPeriodo' => $totalPeriodo,
            'promedioPeriodo' => $promedioPeriodo,
            'maxContador' => $maxContador,
            'totalHistorico' => $this->visitas->totalHistorico(),
            'diasAMostrar' => self::DIAS_A_MOSTRAR,
            'paginasMasVisitadas' => $paginasMasVisitadas,
            'maxVistasPagina' => $maxVistasPagina ?: 1,
            'nombresRuta' => self::NOMBRES_RUTA,
        ]);
    }
}
