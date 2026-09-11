<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crea visitas_paginas (App\Entity\VisitaPagina): contador de vistas por
 * ruta, para saber qué páginas se visitan más — pedido explícito del
 * usuario, sep 2026: "si se puede medir... que es lo que mas visitan".
 * Tabla nueva, separada de visitas_diarias (que sigue igual, sitewide).
 */
final class Version20260911200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea visitas_paginas: contador de vistas por página';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE visitas_paginas (id INT AUTO_INCREMENT NOT NULL, ruta VARCHAR(190) NOT NULL, contador INT NOT NULL, actualizado_en DATETIME NOT NULL, UNIQUE INDEX uniq_visita_pagina_ruta (ruta), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE visitas_paginas');
    }
}
