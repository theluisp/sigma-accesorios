<?php

namespace App\Command;

use App\Service\Catalog\CatalogSyncService;
use App\Service\Catalog\ProductImageResolver;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Utilidad de verificación: corre "php bin/console app:catalog:test" para
 * confirmar que la conexión a Google Sheets y el mapeo de productos funcionan,
 * sin tener que abrir el navegador.
 */
#[AsCommand(name: 'app:catalog:test', description: 'Prueba la sincronización del catálogo desde Google Sheets')]
final class CatalogTestCommand extends Command
{
    public function __construct(
        private readonly CatalogSyncService $catalogSyncService,
        private readonly ProductImageResolver $imageResolver,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $productos = $this->catalogSyncService->getCatalog();
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $total = count($productos);
        $disponibles = 0;
        $sinImagen = 0;
        $porSucursal = [];

        foreach ($productos as $producto) {
            if ($producto->isDisponible()) {
                ++$disponibles;
            }
            if (!$this->imageResolver->hasImage($producto->slug)) {
                ++$sinImagen;
            }
            foreach ($producto->getBranches() as $branch) {
                $porSucursal[$branch->sucursalLabel] = ($porSucursal[$branch->sucursalLabel] ?? 0) + 1;
            }
        }

        $io->title('Catálogo Sigma Accesorios');
        $io->table(['Métrica', 'Valor'], [
            ['Productos únicos (por slug)', (string) $total],
            ['Disponibles (stock > 0 en alguna sucursal)', (string) $disponibles],
            ['Sin imagen todavía', (string) $sinImagen],
            ...array_map(
                static fn ($label, $count) => ["Filas en \"$label\"", (string) $count],
                array_keys($porSucursal),
                array_values($porSucursal)
            ),
        ]);

        if ($total > 0) {
            $ejemplo = array_values($productos)[0];
            $io->section('Ejemplo de producto');
            $io->writeln(sprintf('%s (%s)', $ejemplo->nombre, $ejemplo->slug));
            $io->writeln(sprintf('Precio desde: $%.2f', $ejemplo->getPrecioDesde() ?? 0));
            $io->writeln(sprintf('Stock total: %d', $ejemplo->getStockTotal()));
        }

        $io->success('Sincronización OK.');

        return Command::SUCCESS;
    }
}
