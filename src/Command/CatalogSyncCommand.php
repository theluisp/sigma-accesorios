<?php

namespace App\Command;

use App\Service\Catalog\CatalogImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Trae el catálogo de Google Sheets y actualiza la base de datos local.
 * Pensado para correr por cron un par de veces al día — ver
 * docs/google-sheets-setup.md para la línea de crontab.
 */
#[AsCommand(name: 'app:catalog:sync', description: 'Importa/actualiza el catálogo desde Google Sheets hacia la base de datos')]
final class CatalogSyncCommand extends Command
{
    public function __construct(private readonly CatalogImportService $importService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $resultado = $this->importService->importar();
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf(
            '%d filas procesadas (%d productos nuevos) en %d sucursales.',
            $resultado['procesados'],
            $resultado['productos_nuevos'],
            $resultado['sucursales']
        ));

        return Command::SUCCESS;
    }
}
