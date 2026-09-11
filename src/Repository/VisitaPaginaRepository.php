<?php

namespace App\Repository;

use App\Entity\VisitaPagina;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VisitaPagina>
 */
class VisitaPaginaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VisitaPagina::class);
    }

    /**
     * Suma 1 al contador de esta ruta (crea la fila si es la primera vez
     * que se ve). Mismo upsert atómico en SQL directo que VisitaDiariaRepository::registrarVisita()
     * y por la misma razón: evitar que dos visitas casi simultáneas se
     * pisen el incremento.
     */
    public function registrarVisita(string $ruta): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->executeStatement(
            'INSERT INTO visitas_paginas (ruta, contador, actualizado_en) VALUES (:ruta, 1, :ahora)
             ON DUPLICATE KEY UPDATE contador = contador + 1, actualizado_en = :ahora',
            ['ruta' => $ruta, 'ahora' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')]
        );
    }

    /**
     * Las $limite rutas con más vistas, de mayor a menor.
     *
     * @return array<int, VisitaPagina>
     */
    public function masVisitadas(int $limite): array
    {
        return $this->createQueryBuilder('v')
            ->orderBy('v.contador', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }
}
