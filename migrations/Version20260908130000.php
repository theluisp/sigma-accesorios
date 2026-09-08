<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crea las tablas de pedidos (Fase 1 del plan de e-commerce, ver
 * docs/plan-ecommerce-domicilio.md): App\Entity\Pedido y
 * App\Entity\PedidoItem. Todavía no hay checkout ni admin que las use —
 * esta migración solo deja la base de datos lista para ir guardando
 * pedidos en cuanto se construya el formulario.
 */
final class Version20260908130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea pedidos y pedido_items para el sistema de pedidos a domicilio';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE pedidos (id INT AUTO_INCREMENT NOT NULL, cliente_nombre VARCHAR(150) NOT NULL, cliente_telefono VARCHAR(20) NOT NULL, cliente_email VARCHAR(190) DEFAULT NULL, direccion_entrega LONGTEXT NOT NULL, tipo_entrega VARCHAR(30) NOT NULL, monto_envio_reportado NUMERIC(10, 2) NOT NULL, subtotal NUMERIC(10, 2) NOT NULL, descuento_porcentaje INT NOT NULL, descuento_monto NUMERIC(10, 2) NOT NULL, total NUMERIC(10, 2) NOT NULL, metodo_pago VARCHAR(20) NOT NULL, estado VARCHAR(30) NOT NULL, notas LONGTEXT DEFAULT NULL, creado_en DATETIME NOT NULL, actualizado_en DATETIME NOT NULL, sucursal_id INT NOT NULL, INDEX IDX_D0CB9AA2279A5D5E (sucursal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE pedido_items (id INT AUTO_INCREMENT NOT NULL, producto_nombre VARCHAR(255) NOT NULL, precio_unitario NUMERIC(10, 2) NOT NULL, cantidad INT NOT NULL, pedido_id INT NOT NULL, producto_id INT DEFAULT NULL, INDEX IDX_2C596A08F2D4CB1B (pedido_id), INDEX IDX_2C596A087645698E (producto_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE pedidos ADD CONSTRAINT FK_D0CB9AA2279A5D5E FOREIGN KEY (sucursal_id) REFERENCES sucursales (id)');
        $this->addSql('ALTER TABLE pedido_items ADD CONSTRAINT FK_2C596A08F2D4CB1B FOREIGN KEY (pedido_id) REFERENCES pedidos (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE pedido_items ADD CONSTRAINT FK_2C596A087645698E FOREIGN KEY (producto_id) REFERENCES productos (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pedidos DROP FOREIGN KEY FK_D0CB9AA2279A5D5E');
        $this->addSql('ALTER TABLE pedido_items DROP FOREIGN KEY FK_2C596A08F2D4CB1B');
        $this->addSql('ALTER TABLE pedido_items DROP FOREIGN KEY FK_2C596A087645698E');
        $this->addSql('DROP TABLE pedido_items');
        $this->addSql('DROP TABLE pedidos');
    }
}
