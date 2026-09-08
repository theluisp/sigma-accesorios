<?php

namespace App\Controller\Admin;

use App\Repository\PedidoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Panel interno (protegido por HTTP Basic, ver config/packages/security.yaml)
 * para VER los pedidos que van entrando (Fase 1 del plan de e-commerce, ver
 * docs/plan-ecommerce-domicilio.md). Es solo el primer paso: todavía no
 * permite confirmar pagos ni cambiar el estado desde aquí — eso queda para
 * el siguiente paso, una vez que el checkout empiece a generar pedidos
 * reales (por ahora la tabla está vacía).
 */
#[Route('/admin/pedidos')]
final class PedidoController extends AbstractController
{
    public function __construct(
        private readonly PedidoRepository $pedidoRepository,
    ) {
    }

    #[Route('', name: 'admin_pedidos_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/pedidos/index.html.twig', [
            'pedidos' => $this->pedidoRepository->findAllOrdenadosPorFecha(),
        ]);
    }

    #[Route('/{id}', name: 'admin_pedidos_ver', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function ver(int $id): Response
    {
        $pedido = $this->pedidoRepository->find($id);
        if ($pedido === null) {
            throw $this->createNotFoundException('Pedido no encontrado.');
        }

        return $this->render('admin/pedidos/ver.html.twig', [
            'pedido' => $pedido,
        ]);
    }
}
