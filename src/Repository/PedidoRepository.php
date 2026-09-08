<?php

namespace App\Repository;

use App\Entity\Pedido;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pedido>
 */
class PedidoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pedido::class);
    }

    /**
     * @return Pedido[]
     */
    public function findAllOrdenadosPorFecha(): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('i')
            ->leftJoin('p.items', 'i')
            ->orderBy('p.creadoEn', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Pedido[]
     */
    public function findPorEstado(string $estado): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('i')
            ->leftJoin('p.items', 'i')
            ->andWhere('p.estado = :estado')
            ->setParameter('estado', $estado)
            ->orderBy('p.creadoEn', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
