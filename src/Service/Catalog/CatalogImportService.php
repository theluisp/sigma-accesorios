<?php

namespace App\Service\Catalog;

use App\Entity\Producto;
use App\Entity\ProductoSucursal;
use App\Entity\Sucursal;
use App\Repository\ProductoRepository;
use App\Repository\SucursalRepository;
use App\Service\GoogleSheets\GoogleSheetsClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Importa el catálogo desde Google Sheets hacia la base de datos local
 * (clon del inventario). Pensado para correr por cron un par de veces al día
 * (ver docs/google-sheets-setup.md), no en cada visita al sitio.
 */
final class CatalogImportService
{
    private const SUCURSALES = [
        'real_de_guadalupe' => 'Real De Guadalupe',
        'capu' => 'Capu',
    ];

    public function __construct(
        private readonly GoogleSheetsClient $sheetsClient,
        private readonly CatalogRowMapper $mapper,
        private readonly ProductCategorizer $categorizer,
        private readonly EntityManagerInterface $em,
        private readonly ProductoRepository $productoRepository,
        private readonly SucursalRepository $sucursalRepository,
        #[Autowire('%env(GOOGLE_SHEETS_RANGE_REAL_DE_GUADALUPE)%')]
        private readonly string $rangeRealDeGuadalupe,
        #[Autowire('%env(GOOGLE_SHEETS_RANGE_CAPU)%')]
        private readonly string $rangeCapu,
    ) {
    }

    /**
     * @return array{procesados: int, productos_nuevos: int, sucursales: int}
     */
    public function importar(): array
    {
        $sucursalesPorClave = $this->obtenerOCrearSucursales();
        $productosPorSlug = $this->productoRepository->findAllIndexadosPorSlug();

        $ranges = [
            'real_de_guadalupe' => $this->rangeRealDeGuadalupe,
            'capu' => $this->rangeCapu,
        ];

        $procesados = 0;
        $productosNuevos = 0;

        foreach (self::SUCURSALES as $clave => $label) {
            $sucursal = $sucursalesPorClave[$clave];
            $rows = $this->sheetsClient->getValues($ranges[$clave]);

            foreach ($rows as $row) {
                $mapped = $this->mapper->map($row, $clave, $label);
                if ($mapped === null) {
                    continue;
                }

                $categoria = $this->categorizer->classify($mapped['nombre'], $mapped['descripcion']);

                $producto = $productosPorSlug[$mapped['slug']] ?? null;
                if ($producto === null) {
                    $producto = new Producto($mapped['slug'], $mapped['nombre'], $mapped['descripcion']);
                    $producto->setCategoria($categoria);
                    $this->em->persist($producto);
                    $productosPorSlug[$mapped['slug']] = $producto;
                    ++$productosNuevos;
                } else {
                    $producto->setNombre($mapped['nombre']);
                    $producto->setDescripcion($mapped['descripcion']);
                    // Recalculamos por si el producto cambió de nombre entre syncs.
                    $producto->setCategoria($categoria);
                    $producto->marcarActualizado();
                }

                $branch = $mapped['branch'];
                $existencia = $this->buscarOCrearExistencia($producto, $sucursal);
                $existencia->actualizar($branch->stock, $branch->precio, $branch->descuentoPorcentaje, $branch->disponible);

                ++$procesados;
            }
        }

        $this->em->flush();

        return [
            'procesados' => $procesados,
            'productos_nuevos' => $productosNuevos,
            'sucursales' => count($sucursalesPorClave),
        ];
    }

    /**
     * @return array<string, Sucursal> indexado por clave
     */
    private function obtenerOCrearSucursales(): array
    {
        $sucursales = [];

        foreach (self::SUCURSALES as $clave => $label) {
            $sucursal = $this->sucursalRepository->findOneByClave($clave);
            if ($sucursal === null) {
                $sucursal = new Sucursal($clave, $label);
                $this->em->persist($sucursal);
            }
            $sucursales[$clave] = $sucursal;
        }

        // Flush aquí para que las sucursales ya tengan ID antes de usarlas en relaciones.
        $this->em->flush();

        return $sucursales;
    }

    private function buscarOCrearExistencia(Producto $producto, Sucursal $sucursal): ProductoSucursal
    {
        foreach ($producto->getExistencias() as $existencia) {
            if ($existencia->getSucursal() === $sucursal) {
                return $existencia;
            }
        }

        $existencia = new ProductoSucursal($producto, $sucursal);
        $producto->getExistencias()->add($existencia);
        $this->em->persist($existencia);

        return $existencia;
    }
}
