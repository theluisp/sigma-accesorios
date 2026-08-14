<?php

namespace App\Entity;

use App\Repository\ProductoImagenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductoImagenRepository::class)]
#[ORM\Table(name: 'producto_imagenes')]
class ProductoImagen
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Producto::class, inversedBy: 'imagen')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Producto $producto;

    /** Ruta pública, ej. "/images/products/slug.webp" */
    #[ORM\Column(length: 255)]
    private string $path;

    #[ORM\Column(length: 50)]
    private string $mimeType;

    #[ORM\Column]
    private int $tamanioBytes;

    #[ORM\Column]
    private \DateTimeImmutable $actualizadoEn;

    public function __construct(Producto $producto, string $path, string $mimeType, int $tamanioBytes)
    {
        $this->producto = $producto;
        $this->path = $path;
        $this->mimeType = $mimeType;
        $this->tamanioBytes = $tamanioBytes;
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

    public function getPath(): string
    {
        return $this->path;
    }

    public function getActualizadoEn(): \DateTimeImmutable
    {
        return $this->actualizadoEn;
    }

    public function actualizar(string $path, string $mimeType, int $tamanioBytes): void
    {
        $this->path = $path;
        $this->mimeType = $mimeType;
        $this->tamanioBytes = $tamanioBytes;
        $this->actualizadoEn = new \DateTimeImmutable();
    }
}
