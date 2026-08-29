<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Agrega productos.categoria_manual: bandera que marca cuándo una
 * categoría fue elegida a mano en /admin/categorias, para que
 * CatalogImportService deje de recalcularla en cada sync automático (ver
 * App\Entity\Producto::setCategoriaManual()).
 */
final class Version20260829210350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega productos.categoria_manual para clasificación manual de categoría';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE productos ADD categoria_manual TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE productos DROP categoria_manual');
    }
}
