<?php

namespace App\Controller\Admin;

use App\Repository\VisitaDiariaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Panel interno (protegido por HTTP Basic, ver config/packages/security.yaml
 * — mismo usuario/password que /admin/imagenes) con el conteo básico de
 * visitas diarias. Los datos los alimenta App\EventSubscriber\VisitaTrackerSubscriber
 * en cada visita real al sitio (fuera de /admin).
 */
#[Route('/admin/analiticas')]
final class AnaliticasController extends AbstractController
{
    private const DIAS_A_MOSTRAR = 30;

    public function __construct(
        private readonly VisitaDiariaRepository $visitas,
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

        return $this->render('admin/analiticas/index.html.twig', [
            'ultimosDias' => $ultimosDias,
            'hoy' => $hoy,
            'totalPeriodo' => $totalPeriodo,
            'promedioPeriodo' => $promedioPeriodo,
            'maxContador' => $maxContador,
            'totalHistorico' => $this->visitas->totalHistorico(),
            'diasAMostrar' => self::DIAS_A_MOSTRAR,
        ]);
    }
}
