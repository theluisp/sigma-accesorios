<?php

namespace App\Entity;

use App\Repository\ProductoSucursalRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Stock/precio/disponibilidad de UN producto en UNA sucursal. Un producto que
 * se vende en ambas sucursales tiene dos filas aquí (una por sucursal).
 */
#[ORM\Entity(repositoryClass: ProductoSucursalRepository::class)]
#[ORM\Table(name: 'producto_sucursal')]
#[ORM\UniqueConstraint(name: 'uniq_producto_sucursal', columns: ['producto_id', 'sucursal_id'])]
class ProductoSucursal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Producto::class, inversedBy: 'existencias')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Producto $producto;

    #[ORM\ManyToOne(targetEntity: Sucursal::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Sucursal $sucursal;

    #[ORM\Column]
    private int $stock = 0;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $precio = '0.00';

    #[ORM\Column]
    private int $descuentoPorcentaje = 0;

    #[ORM\Column]
    private bool $disponible = false;

    #[ORM\Column]
    private \DateTimeImmutable $actualizadoEn;

    public function __construct(Producto $producto, Sucursal $sucursal)
    {
        $this->producto = $producto;
        $this->sucursal = $sucursal;
        $this->actualizadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProducto(): Producto
    {
        return $this->producto;
    }

    public function getSucursal(): Sucursal
    {
        return $this->sucursal;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function getPrecio(): float
    {
        return (float) $this->precio;
    }

    public function getDescuentoPorcentaje(): int
    {
        return $this->descuentoPorcentaje;
    }

    public function isDisponible(): bool
    {
        return $this->disponible;
    }

    public function getActualizadoEn(): \DateTimeImmutable
    {
        return $this->actualizadoEn;
    }

    public function actualizar(int $stock, float $precio, int $descuentoPorcentaje, bool $disponible): void
    {
        $this->stock = $stock;
        $this->precio = number_format($precio, 2, '.', '');
        $this->descuentoPorcentaje = $descuentoPorcentaje;
        $this->disponible = $disponible;
        $this->actualizadoEn = new \DateTimeImmutable();
    }
}
