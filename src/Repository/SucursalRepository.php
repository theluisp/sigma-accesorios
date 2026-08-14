<?php

namespace App\Repository;

use App\Entity\Sucursal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sucursal>
 */
class SucursalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sucursal::class);
    }

    public function findOneByClave(string $clave): ?Sucursal
    {
        return $this->findOneBy(['clave' => $clave]);
    }
}
