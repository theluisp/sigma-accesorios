<?php

namespace App\Command;

use App\Repository\ProductoRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Muestra el estado actual del catálogo tal como está guardado en la base de
 * datos (no llama a Google Sheets) — útil para confirmar que la última
 * sincronización (app:catalog:sync) funcionó.
 */
#[AsCommand(name: 'app:catalog:status', description: 'Muestra el estado del catálogo guardado en la base de datos')]
final class CatalogStatusCommand extends Command
{
    public function __construct(private readonly ProductoRepository $productoRepository)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $productos = $this->productoRepository->findAllConRelaciones();
        $total = count($productos);

        if ($total === 0) {
            $io->warning('No hay productos en la base de datos todavía. Corre "php bin/console app:catalog:sync" primero.');

            return Command::SUCCESS;
        }

        $disponibles = 0;
        $sinImagen = 0;
        $porSucursal = [];
        $ultimaActualizacion = null;

        foreach ($productos as $producto) {
            if ($producto->isDisponible()) {
                ++$disponibles;
            }
            if ($producto->getImagen() === null) {
                ++$sinImagen;
            }
            foreach ($producto->getExistencias() as $existencia) {
                $label = $existencia->getSucursal()->getNombre();
                $porSucursal[$label] = ($porSucursal[$label] ?? 0) + 1;
            }
            if ($ultimaActualizacion === null || $producto->getActualizadoEn() > $ultimaActualizacion) {
                $ultimaActualizacion = $producto->getActualizadoEn();
            }
        }

        $io->title('Catálogo Sigma Accesorios (desde la base de datos)');
        $io->table(['Métrica', 'Valor'], [
            ['Productos en BD', (string) $total],
            ['Disponibles (stock > 0 en alguna sucursal)', (string) $disponibles],
            ['Sin imagen todavía', (string) $sinImagen],
            ...array_map(
                static fn ($label, $count) => ["Existencias en \"$label\"", (string) $count],
                array_keys($porSucursal),
                array_values($porSucursal)
            ),
            ['Última sincronización', $ultimaActualizacion?->format('Y-m-d H:i:s') ?? 'N/A'],
        ]);

        $io->success('OK.');

        return Command::SUCCESS;
    }
}
