<?php

namespace App\Repository;

use App\Entity\VisitaDiaria;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VisitaDiaria>
 */
class VisitaDiariaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VisitaDiaria::class);
    }

    /**
     * Suma 1 al contador del día (crea la fila si es la primera visita de
     * ese día). Usa un upsert atómico en SQL directo (INSERT ... ON
     * DUPLICATE KEY UPDATE) en vez de find()+persist()+flush() por el ORM,
     * a propósito: dos visitantes casi simultáneos podrían pisarse el
     * incremento si se hiciera vía entidad (leer contador=5, ambos suman a
     * mano, ambos guardan 6 en vez de terminar en 7). El upsert lo resuelve
     * la propia base de datos, sin condición de carrera.
     */
    public function registrarVisita(\DateTimeImmutable $fecha): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->executeStatement(
            'INSERT INTO visitas_diarias (fecha, contador) VALUES (:fecha, 1)
             ON DUPLICATE KEY UPDATE contador = contador + 1',
            ['fecha' => $fecha->format('Y-m-d')]
        );
    }

    /**
     * Los últimos $dias días, en orden ascendente (el más viejo primero, hoy
     * al final), rellenando con contador=0 los días sin ninguna visita
     * registrada (para que la gráfica/tabla no tenga huecos).
     *
     * @return array<int, array{fecha: \DateTimeImmutable, contador: int}>
     */
    public function ultimosDias(int $dias): array
    {
        $desde = (new \DateTimeImmutable('today'))->modify(sprintf('-%d days', $dias - 1));

        $registros = $this->createQueryBuilder('v')
            ->andWhere('v.fecha >= :desde')
            ->setParameter('desde', $desde)
            ->orderBy('v.fecha', 'ASC')
            ->getQuery()
            ->getResult();

        $porFecha = [];
        foreach ($registros as $registro) {
            $porFecha[$registro->getFecha()->format('Y-m-d')] = $registro->getContador();
        }

        $resultado = [];
        for ($i = 0; $i < $dias; ++$i) {
            $fecha = $desde->modify(sprintf('+%d days', $i));
            $resultado[] = [
                'fecha' => $fecha,
                'contador' => $porFecha[$fecha->format('Y-m-d')] ?? 0,
            ];
        }

        return $resultado;
    }

    public function totalHistorico(): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COALESCE(SUM(v.contador), 0)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
