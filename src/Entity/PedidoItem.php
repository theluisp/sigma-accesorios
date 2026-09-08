<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\PedidoItemRepository;

/**
 * Una línea de producto dentro de un Pedido. Guarda una "foto" del nombre y
 * precio del producto al momento del pedido — no referencia el precio
 * actual del catálogo — para que si el precio cambia después en un
 * app:catalog:sync, los pedidos ya hechos no cambien de total.
 */
#[ORM\Entity(repositoryClass: PedidoItemRepository::class)]
#[ORM\Table(name: 'pedido_items')]
class PedidoItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Pedido::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Pedido $pedido;

    /**
     * Referencia al producto del catálogo, solo por conveniencia (ej. para
     * linkear de vuelta desde el admin). Puede quedar en null si el
     * producto se borra del catálogo más adelante — el pedido conserva
     * productoNombre/precioUnitario de cualquier forma.
     */
    #[ORM\ManyToOne(targetEntity: Producto::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Producto $producto = null;

    #[ORM\Column(length: 255)]
    private string $productoNombre;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $precioUnitario;

    #[ORM\Column]
    private int $cantidad;

    public function __construct(?Producto $producto, string $productoNombre, float $precioUnitario, int $cantidad)
    {
        $this->producto = $producto;
        $this->productoNombre = $productoNombre;
        $this->precioUnitario = number_format($precioUnitario, 2, '.', '');
        $this->cantidad = $cantidad;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPedido(): Pedido
    {
        return $this->pedido;
    }

    public function setPedido(Pedido $pedido): void
    {
        $this->pedido = $pedido;
    }

    public function getProducto(): ?Producto
    {
        return $this->producto;
    }

    public function getProductoNombre(): string
    {
        return $this->productoNombre;
    }

    public function getPrecioUnitario(): float
    {
        return (float) $this->precioUnitario;
    }

    public function getCantidad(): int
    {
        return $this->cantidad;
    }

    /**
     * Cambia la cantidad de esta línea. Ojo: esto NO recalcula los totales
     * del Pedido por sí solo — si se usa fuera de Pedido::agregarItem(),
     * hay que llamar $item->getPedido()->recalcularTotales() después.
     */
    public function setCantidad(int $cantidad): void
    {
        $this->cantidad = $cantidad;
    }

    public function getSubtotal(): float
    {
        return round($this->getPrecioUnitario() * $this->cantidad, 2);
    }
}
