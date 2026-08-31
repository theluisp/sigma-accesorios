<?php

namespace App\Command;

use App\Entity\Producto;
use App\Repository\ProductoRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Diagnóstico de UN producto: explica exactamente por qué sí o no aparece
 * en el catálogo público, sucursal por sucursal. Pensado para casos como
 * "está disponible en Real de Guadalupe pero no en Capu y no aparece en el
 * sitio" (pedido explícito del usuario, ago 2026) — la regla YA es "se
 * muestra si tiene stock disponible en AL MENOS UNA sucursal" (ver
 * Producto::isDisponible()), así que si un producto no aparece, casi
 * siempre es por otra de las 2 condiciones que también se revisan aquí:
 * sin imagen cargada, o sin stock disponible en NINGUNA sucursal (aunque
 * el Sheet diga "SI" en alguna, si el stock ahí es 0 no cuenta).
 */
#[AsCommand(name: 'app:catalog:diagnostico', description: 'Explica por qué un producto sí o no aparece en el catálogo público')]
final class CatalogDiagnosticoCommand extends Command
{
    public function __construct(private readonly ProductoRepository $productoRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('busqueda', InputArgument::REQUIRED, 'Nombre (o parte del nombre) o slug del producto a revisar')
            ->setHelp('Ejemplo: php bin/console app:catalog:diagnostico "audifono bluetooth"');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $busqueda = mb_strtolower(trim((string) $input->getArgument('busqueda')));

        if ($busqueda === '') {
            $io->error('Escribe al menos parte del nombre o el slug del producto a buscar.');

            return Command::FAILURE;
        }

        $coincidencias = array_values(array_filter(
            $this->productoRepository->findAllConRelaciones(),
            static fn (Producto $producto): bool => str_contains(mb_strtolower($producto->getNombre()), $busqueda)
                || str_contains(mb_strtolower($producto->getSlug()), $busqueda),
        ));

        if ($coincidencias === []) {
            $io->warning(sprintf('No se encontró ningún producto que coincida con "%s".', $busqueda));

            return Command::SUCCESS;
        }

        if (count($coincidencias) > 15) {
            $io->warning(sprintf(
                '%d productos coinciden con "%s" — muestra solo los primeros 15. Sé más específico si buscas uno en particular.',
                count($coincidencias),
                $busqueda,
            ));
            $coincidencias = array_slice($coincidencias, 0, 15);
        }

        foreach ($coincidencias as $producto) {
            $this->mostrarProducto($io, $producto);
        }

        return Command::SUCCESS;
    }

    private function mostrarProducto(SymfonyStyle $io, Producto $producto): void
    {
        $io->section($producto->getNombre());
        $io->text([
            'Slug: '.$producto->getSlug(),
            'Categoría: '.$producto->getCategoria().($producto->isCategoriaManual() ? ' (elegida a mano en /admin/categorias)' : ' (automática por palabras clave)'),
            'Tiene imagen cargada: '.($producto->getImagen() !== null ? 'Sí' : 'NO'),
        ]);

        $filas = [];
        foreach ($producto->getExistencias() as $existencia) {
            $cuentaComoDisponible = $existencia->isDisponible() && $existencia->getStock() > 0;
            $filas[] = [
                $existencia->getSucursal()->getNombre(),
                $existencia->isDisponible() ? 'SI' : 'NO',
                (string) $existencia->getStock(),
                number_format($existencia->getPrecio(), 2),
                $cuentaComoDisponible ? '✔ cuenta como disponible' : '✘ no cuenta',
            ];
        }

        if ($filas === []) {
            $io->text('Sin existencias registradas en ninguna sucursal (no aparece en ninguna de las 2 hojas del Sheet con este slug).');
        } else {
            $io->table(['Sucursal', 'Disponible (Sheet)', 'Stock', 'Precio', '¿Cuenta?'], $filas);
        }

        // Misma regla que ProductoRepository::buscarDisponibles() / Producto::isDisponible():
        // se muestra si tiene imagen Y está disponible con stock > 0 en AL MENOS una sucursal.
        $tieneImagen = $producto->getImagen() !== null;
        $tieneStockEnAlgunaSucursal = $producto->isDisponible();

        if ($tieneImagen && $tieneStockEnAlgunaSucursal) {
            $io->success('SÍ aparece en el catálogo público — tiene imagen y está disponible con stock en al menos una sucursal (no necesita estarlo en las 2).');

            return;
        }

        $motivos = [];
        if (!$tieneImagen) {
            $motivos[] = 'no tiene imagen cargada — se sube en /admin/imagenes';
        }
        if (!$tieneStockEnAlgunaSucursal) {
            $motivos[] = 'no tiene stock disponible (SI + stock > 0) en NINGUNA sucursal — revisa la columna "¿Cuenta?" de la tabla de arriba, ninguna debe estar en 0 con "SI" para que cuente';
        }
        $io->error('NO aparece en el catálogo público porque: '.implode('; y '.PHP_EOL.'  ', $motivos).'.');
    }
}
