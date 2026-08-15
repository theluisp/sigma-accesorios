<?php

namespace App\Entity;

use App\Repository\ProductoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductoRepository::class)]
#[ORM\Table(name: 'productos')]
#[ORM\UniqueConstraint(name: 'uniq_producto_slug', columns: ['slug'])]
class Producto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Slug interno de Rappi (columna F del Sheet) — estable entre sucursales, es nuestra llave real. */
    #[ORM\Column(length: 190)]
    private string $slug;

    #[ORM\Column(length: 255)]
    private string $nombre;

    #[ORM\Column(type: 'text')]
    private string $descripcion = '';

    /** Inferida del nombre por App\Service\Catalog\ProductCategorizer (el Sheet no trae categoría). */
    #[ORM\Column(length: 40)]
    private string $categoria = 'otros';

    #[ORM\Column]
    private \DateTimeImmutable $creadoEn;

    #[ORM\Column]
    private \DateTimeImmutable $actualizadoEn;

    /** @var Collection<int, ProductoSucursal> */
    #[ORM\OneToMany(mappedBy: 'producto', targetEntity: ProductoSucursal::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $existencias;

    #[ORM\OneToOne(mappedBy: 'producto', targetEntity: ProductoImagen::class, cascade: ['persist', 'remove'])]
    private ?ProductoImagen $imagen = null;

    public function __construct(string $slug, string $nombre, string $descripcion)
    {
        $this->slug = $slug;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->creadoEn = new \DateTimeImmutable();
        $this->actualizadoEn = new \DateTimeImmutable();
        $this->existencias = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    public function getCategoria(): string
    {
        return $this->categoria;
    }

    public function setCategoria(string $categoria): void
    {
        $this->categoria = $categoria;
    }

    public function getActualizadoEn(): \DateTimeImmutable
    {
        return $this->actualizadoEn;
    }

    public function marcarActualizado(): void
    {
        $this->actualizadoEn = new \DateTimeImmutable();
    }

    /** @return Collection<int, ProductoSucursal> */
    public function getExistencias(): Collection
    {
        return $this->existencias;
    }

    public function getImagen(): ?ProductoImagen
    {
        return $this->imagen;
    }

    public function setImagen(?ProductoImagen $imagen): void
    {
        $this->imagen = $imagen;
    }

    public function isDisponible(): bool
    {
        foreach ($this->existencias as $existencia) {
            if ($existencia->isDisponible() && $existencia->getStock() > 0) {
                return true;
            }
        }

        return false;
    }

    public function getStockTotal(): int
    {
        $total = 0;
        foreach ($this->existencias as $existencia) {
            $total += $existencia->getStock();
        }

        return $total;
    }

    public function getPrecioDesde(): ?float
    {
        $precios = [];
        foreach ($this->existencias as $existencia) {
            if ($existencia->isDisponible()) {
                $precios[] = $existencia->getPrecio();
            }
        }

        return $precios === [] ? null : min($precios);
    }
}
