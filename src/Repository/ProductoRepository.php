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

    /**
     * Slugs de categoría que tienen al menos un producto disponible ahora
     * mismo — para que el carrusel de categorías de Home muestre solo lo
     * que realmente se vende, nunca una tarjeta muerta hacia un catálogo
     * vacío. El catálogo es chico (~200 productos), así que traer todo y
     * filtrar en PHP (reusando Producto::isDisponible()) es suficiente.
     *
     * @return string[]
     */
    public function categoriasConStock(): array
    {
        $productos = $this->createQueryBuilder('p')
            ->addSelect('e', 's')
            ->leftJoin('p.existencias', 'e')
            ->leftJoin('e.sucursal', 's')
            ->getQuery()
            ->getResult();

        $categorias = [];
        foreach ($productos as $producto) {
            if ($producto->isDisponible()) {
                $categorias[$producto->getCategoria()] = true;
            }
        }

        return array_keys($categorias);
    }

    /**
     * Productos disponibles para el Catálogo público, con búsqueda de texto
     * opcional. El filtro de categoría y la paginación se resuelven en el
     * controlador (CatalogoController) sobre este resultado — el catálogo es
     * chico (~200 productos), así que no vale la pena complicar la consulta.
     *
     * La búsqueda parte el texto en palabras y exige que TODAS aparezcan en
     * el nombre o la descripción (AND), aprovechando que el collation de
     * MySQL (utf8mb4_unicode_ci) ya ignora mayúsculas/acentos por defecto.
     *
     * @return Producto[]
     */
    public function buscarDisponibles(?string $texto): array
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('e', 's', 'i')
            ->leftJoin('p.existencias', 'e')
            ->leftJoin('e.sucursal', 's')
            ->leftJoin('p.imagen', 'i')
            ->orderBy('p.nombre', 'ASC');

        $texto = $texto !== null ? trim($texto) : '';
        if ($texto !== '') {
            foreach (preg_split('/\s+/', $texto) as $indice => $termino) {
                $parametro = 'termino'.$indice;
                $qb->andWhere("(p.nombre LIKE :{$parametro} OR p.descripcion LIKE :{$parametro})")
                    ->setParameter($parametro, '%'.$termino.'%');
            }
        }

        $productos = $qb->getQuery()->getResult();

        return array_values(array_filter(
            $productos,
            static fn (Producto $producto): bool => $producto->isDisponible(),
        ));
    }
}
