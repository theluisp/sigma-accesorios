<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260814223159 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE producto_imagenes (id INT AUTO_INCREMENT NOT NULL, path VARCHAR(255) NOT NULL, mime_type VARCHAR(50) NOT NULL, tamanio_bytes INT NOT NULL, actualizado_en DATETIME NOT NULL, producto_id INT NOT NULL, UNIQUE INDEX UNIQ_1FF911D47645698E (producto_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE producto_sucursal (id INT AUTO_INCREMENT NOT NULL, stock INT NOT NULL, precio NUMERIC(10, 2) NOT NULL, descuento_porcentaje INT NOT NULL, disponible TINYINT NOT NULL, actualizado_en DATETIME NOT NULL, producto_id INT NOT NULL, sucursal_id INT NOT NULL, INDEX IDX_C10F1C837645698E (producto_id), INDEX IDX_C10F1C83279A5D5E (sucursal_id), UNIQUE INDEX uniq_producto_sucursal (producto_id, sucursal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE productos (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(190) NOT NULL, nombre VARCHAR(255) NOT NULL, descripcion LONGTEXT NOT NULL, creado_en DATETIME NOT NULL, actualizado_en DATETIME NOT NULL, UNIQUE INDEX uniq_producto_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE sucursales (id INT AUTO_INCREMENT NOT NULL, clave VARCHAR(50) NOT NULL, nombre VARCHAR(100) NOT NULL, UNIQUE INDEX uniq_sucursal_clave (clave), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE producto_imagenes ADD CONSTRAINT FK_1FF911D47645698E FOREIGN KEY (producto_id) REFERENCES productos (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE producto_sucursal ADD CONSTRAINT FK_C10F1C837645698E FOREIGN KEY (producto_id) REFERENCES productos (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE producto_sucursal ADD CONSTRAINT FK_C10F1C83279A5D5E FOREIGN KEY (sucursal_id) REFERENCES sucursales (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE producto_imagenes DROP FOREIGN KEY FK_1FF911D47645698E');
        $this->addSql('ALTER TABLE producto_sucursal DROP FOREIGN KEY FK_C10F1C837645698E');
        $this->addSql('ALTER TABLE producto_sucursal DROP FOREIGN KEY FK_C10F1C83279A5D5E');
        $this->addSql('DROP TABLE producto_imagenes');
        $this->addSql('DROP TABLE producto_sucursal');
        $this->addSql('DROP TABLE productos');
        $this->addSql('DROP TABLE sucursales');
    }
}
