<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\PedidoRepository;

/**
 * Un pedido a domicilio. Nace desde el checkout del sitio (Fase 1 del plan
 * de e-commerce, ver docs/plan-ecommerce-domicilio.md). El pago por
 * transferencia/depósito siempre se confirma a mano: el cliente manda su
 * comprobante por WhatsApp y el negocio valida el dinero entrante antes de
 * pasar el pedido a ESTADO_CONFIRMADO. El pago automático (PayPal) es la
 * Fase 2 y todavía no está implementado — METODO_PAGO_PAYPAL ya existe como
 * constante para no tener que tocar el esquema otra vez cuando se agregue.
 *
 * El envío tiene tres caminos (acordados con el cliente, ver el plan):
 * - TIPO_ENTREGA_RAPPI: el pedido se hace por Rappi, fuera de este sistema.
 * - TIPO_ENTREGA_GRATIS: subtotal mayor a $299, reparto propio sin costo.
 * - TIPO_ENTREGA_DIDI: el cliente cotiza su envío en la herramienta pública
 *   de DiDi Entrega Business y nos reporta el monto (ver
 *   montoEnvioReportado). Ese monto es SOLO una referencia para armar el
 *   total que se manda a WhatsApp — no es una integración con DiDi ni un
 *   cobro automático. El negocio es quien agenda y paga el viaje real con
 *   DiDi una vez que ya validó el pago del cliente, así que un monto
 *   reportado incorrecto no representa un riesgo real: nada se agenda ni se
 *   entrega antes de esa validación humana.
 */
#[ORM\Entity(repositoryClass: PedidoRepository::class)]
#[ORM\Table(name: 'pedidos')]
class Pedido
{
    public const TIPO_ENTREGA_RAPPI = 'rappi';
    public const TIPO_ENTREGA_GRATIS = 'gratis_reparto_propio';
    public const TIPO_ENTREGA_DIDI = 'envio_didi';

    public const METODO_PAGO_TRANSFERENCIA = 'transferencia';
    public const METODO_PAGO_DEPOSITO = 'deposito';
    public const METODO_PAGO_PAYPAL = 'paypal';

    public const ESTADO_PENDIENTE_PAGO = 'pendiente_pago';
    public const ESTADO_PAGO_REPORTADO = 'pago_reportado';
    public const ESTADO_CONFIRMADO = 'confirmado';
    public const ESTADO_EN_PREPARACION = 'en_preparacion';
    public const ESTADO_EN_CAMINO = 'en_camino';
    public const ESTADO_ENTREGADO = 'entregado';
    public const ESTADO_CANCELADO = 'cancelado';

    /** % de descuento sobre el subtotal de productos cuando el envío es TIPO_ENTREGA_DIDI (ver plan, sección 2). */
    public const DESCUENTO_ENVIO_DIDI_PORCENTAJE = 15;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $clienteNombre;

    #[ORM\Column(length: 20)]
    private string $clienteTelefono;

    #[ORM\Column(length: 190, nullable: true)]
    private ?string $clienteEmail = null;

    #[ORM\Column(type: 'text')]
    private string $direccionEntrega;

    #[ORM\ManyToOne(targetEntity: Sucursal::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Sucursal $sucursal;

    #[ORM\Column(length: 30)]
    private string $tipoEntrega;

    /**
     * Monto que el cliente reportó haber cotizado en el sitio público de
     * DiDi Entrega Business (solo relevante cuando tipoEntrega =
     * TIPO_ENTREGA_DIDI, 0 en los demás casos). Ver el doc-comment de la
     * clase: es una referencia para armar el total, no un cobro validado.
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $montoEnvioReportado = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $subtotal = '0.00';

    #[ORM\Column]
    private int $descuentoPorcentaje = 0;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $descuentoMonto = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $total = '0.00';

    #[ORM\Column(length: 20)]
    private string $metodoPago;

    #[ORM\Column(length: 30)]
    private string $estado = self::ESTADO_PENDIENTE_PAGO;

    /** Notas internas del negocio (ej. detalles de la validación del pago o del viaje de DiDi), no visibles al cliente. */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notas = null;

    #[ORM\Column]
    private \DateTimeImmutable $creadoEn;

    #[ORM\Column]
    private \DateTimeImmutable $actualizadoEn;

    /** @var Collection<int, PedidoItem> */
    #[ORM\OneToMany(mappedBy: 'pedido', targetEntity: PedidoItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct(
        string $clienteNombre,
        string $clienteTelefono,
        string $direccionEntrega,
        Sucursal $sucursal,
        string $tipoEntrega,
        string $metodoPago,
    ) {
        $this->clienteNombre = $clienteNombre;
        $this->clienteTelefono = $clienteTelefono;
        $this->direccionEntrega = $direccionEntrega;
        $this->sucursal = $sucursal;
        $this->tipoEntrega = $tipoEntrega;
        $this->metodoPago = $metodoPago;
        $this->descuentoPorcentaje = $tipoEntrega === self::TIPO_ENTREGA_DIDI ? self::DESCUENTO_ENVIO_DIDI_PORCENTAJE : 0;
        $this->creadoEn = new \DateTimeImmutable();
        $this->actualizadoEn = new \DateTimeImmutable();
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClienteNombre(): string
    {
        return $this->clienteNombre;
    }

    public function getClienteTelefono(): string
    {
        return $this->clienteTelefono;
    }

    public function getClienteEmail(): ?string
    {
        return $this->clienteEmail;
    }

    public function setClienteEmail(?string $clienteEmail): void
    {
        $this->clienteEmail = $clienteEmail;
    }

    public function getDireccionEntrega(): string
    {
        return $this->direccionEntrega;
    }

    public function getSucursal(): Sucursal
    {
        return $this->sucursal;
    }

    public function getTipoEntrega(): string
    {
        return $this->tipoEntrega;
    }

    /** Etiqueta legible para el admin (ver /admin/pedidos) — no se guarda, se calcula. */
    public function getTipoEntregaLabel(): string
    {
        return match ($this->tipoEntrega) {
            self::TIPO_ENTREGA_RAPPI => 'Rappi',
            self::TIPO_ENTREGA_GRATIS => 'Reparto propio (gratis)',
            self::TIPO_ENTREGA_DIDI => 'Envío por DiDi (cotizado)',
            default => $this->tipoEntrega,
        };
    }

    public function getMontoEnvioReportado(): float
    {
        return (float) $this->montoEnvioReportado;
    }

    /**
     * Solo tiene sentido cuando tipoEntrega = TIPO_ENTREGA_DIDI. Recalcula
     * el total automáticamente (ver recalcularTotales()).
     */
    public function setMontoEnvioReportado(float $monto): void
    {
        $this->montoEnvioReportado = number_format($monto, 2, '.', '');
        $this->recalcularTotales();
    }

    public function getSubtotal(): float
    {
        return (float) $this->subtotal;
    }

    public function getDescuentoPorcentaje(): int
    {
        return $this->descuentoPorcentaje;
    }

    public function getDescuentoMonto(): float
    {
        return (float) $this->descuentoMonto;
    }

    public function getTotal(): float
    {
        return (float) $this->total;
    }

    public function getMetodoPago(): string
    {
        return $this->metodoPago;
    }

    /** Etiqueta legible para el admin (ver /admin/pedidos) — no se guarda, se calcula. */
    public function getMetodoPagoLabel(): string
    {
        return match ($this->metodoPago) {
            self::METODO_PAGO_TRANSFERENCIA => 'Transferencia',
            self::METODO_PAGO_DEPOSITO => 'Depósito',
            self::METODO_PAGO_PAYPAL => 'PayPal',
            default => $this->metodoPago,
        };
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): void
    {
        $this->estado = $estado;
        $this->actualizadoEn = new \DateTimeImmutable();
    }

    /** Etiqueta legible para el admin (ver /admin/pedidos) — no se guarda, se calcula. */
    public function getEstadoLabel(): string
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE_PAGO => 'Pendiente de pago',
            self::ESTADO_PAGO_REPORTADO => 'Pago reportado',
            self::ESTADO_CONFIRMADO => 'Confirmado',
            self::ESTADO_EN_PREPARACION => 'En preparación',
            self::ESTADO_EN_CAMINO => 'En camino',
            self::ESTADO_ENTREGADO => 'Entregado',
            self::ESTADO_CANCELADO => 'Cancelado',
            default => $this->estado,
        };
    }

    /**
     * Clase CSS (sufijo de .admin-badge--estado-*, ver app.css) para
     * agrupar visualmente los estados en /admin/pedidos: los que necesitan
     * atención (pendientes de revisar), los que ya van en curso, y los que
     * ya cerraron (entregado o cancelado).
     */
    public function getEstadoClaseCss(): string
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE_PAGO, self::ESTADO_PAGO_REPORTADO => 'pendiente',
            self::ESTADO_CONFIRMADO, self::ESTADO_EN_PREPARACION, self::ESTADO_EN_CAMINO => 'proceso',
            self::ESTADO_ENTREGADO => 'completado',
            self::ESTADO_CANCELADO => 'cancelado',
            default => 'pendiente',
        };
    }

    public function getNotas(): ?string
    {
        return $this->notas;
    }

    public function setNotas(?string $notas): void
    {
        $this->notas = $notas;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }

    public function getActualizadoEn(): \DateTimeImmutable
    {
        return $this->actualizadoEn;
    }

    /**
     * @return Collection<int, PedidoItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function agregarItem(PedidoItem $item): void
    {
        if ($this->items->contains($item)) {
            return;
        }

        $this->items->add($item);
        $item->setPedido($this);
        $this->recalcularTotales();
    }

    public function quitarItem(PedidoItem $item): void
    {
        if ($this->items->removeElement($item)) {
            $this->recalcularTotales();
        }
    }

    /**
     * Recalcula subtotal, descuento y total a partir de los items actuales
     * y de montoEnvioReportado. Se llama automáticamente al agregar/quitar
     * items o al actualizar el monto de envío. Es pública para que, si algo
     * externo cambia la cantidad de un PedidoItem directamente (ver
     * PedidoItem::setCantidad()), pueda pedirle a su Pedido que se
     * recalcule sin tener que reconstruir la lista de items.
     */
    public function recalcularTotales(): void
    {
        $subtotal = 0.0;
        foreach ($this->items as $item) {
            $subtotal += $item->getSubtotal();
        }

        $descuentoMonto = round($subtotal * $this->descuentoPorcentaje / 100, 2);
        $costoEnvio = $this->tipoEntrega === self::TIPO_ENTREGA_DIDI ? $this->getMontoEnvioReportado() : 0.0;

        $this->subtotal = number_format($subtotal, 2, '.', '');
        $this->descuentoMonto = number_format($descuentoMonto, 2, '.', '');
        $this->total = number_format($subtotal - $descuentoMonto + $costoEnvio, 2, '.', '');
        $this->actualizadoEn = new \DateTimeImmutable();
    }
}
