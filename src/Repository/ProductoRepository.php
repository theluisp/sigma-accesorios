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

    /**
     * Productos para mostrar en el sitio público: deben tener imagen propia
     * cargada (innerJoin descarta los que no) y al menos una existencia
     * disponible con stock. La disponibilidad se resuelve en PHP reusando
     * Producto::isDisponible() — el catálogo es chico (~200 productos), así
     * que no vale la pena una subconsulta.
     *
     * @return Producto[]
     */
    public function findDestacados(int $limite = 12): array
    {
        $productos = $this->createQueryBuilder('p')
            ->addSelect('e', 's', 'i')
            ->leftJoin('p.existencias', 'e')
            ->leftJoin('e.sucursal', 's')
            ->innerJoin('p.imagen', 'i')
            ->orderBy('p.actualizadoEn', 'DESC')
            ->getQuery()
            ->getResult();

        $disponibles = array_values(array_filter(
            $productos,
            static fn (Producto $producto): bool => $producto->isDisponible(),
        ));

        return array_slice($disponibles, 0, $limite);
    }
}
