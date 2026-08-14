<?php

namespace App\Entity;

use App\Repository\SucursalRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SucursalRepository::class)]
#[ORM\Table(name: 'sucursales')]
#[ORM\UniqueConstraint(name: 'uniq_sucursal_clave', columns: ['clave'])]
class Sucursal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Clave interna estable, ej. "real_de_guadalupe" (no cambia aunque cambie el nombre visible). */
    #[ORM\Column(length: 50)]
    private string $clave;

    #[ORM\Column(length: 100)]
    private string $nombre;

    public function __construct(string $clave, string $nombre)
    {
        $this->clave = $clave;
        $this->nombre = $nombre;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClave(): string
    {
        return $this->clave;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }
}
