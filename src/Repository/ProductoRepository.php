<?php

namespace App\Repository;

use App\Entity\Producto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Producto>
 */
class ProductoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Producto::class);
    }

    public function findOneBySlug(string $slug): ?Producto
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * Trae todos los productos con sus existencias por sucursal y su imagen
     * en una sola consulta (evita N+1 al listarlos, ej. en /admin/imagenes).
     *
     * @return Producto[]
     */
    public function findAllConRelaciones(): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('e', 's', 'i')
            ->leftJoin('p.existencias', 'e')
            ->leftJoin('e.sucursal', 's')
            ->leftJoin('p.imagen', 'i')
            ->orderBy('p.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, Producto> indexado por slug
     */
    public function findAllIndexadosPorSlug(): array
    {
        $indexado = [];
        foreach ($this->findAll() as $producto) {
            $indexado[$producto->getSlug()] = $producto;
        }

        return $indexado;
    }
}
