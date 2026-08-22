<?php

namespace App\Entity;

use App\Repository\VisitaDiariaRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Contador de visitas agregado por día — NO guarda IP, user-agent ni ningún
 * dato personal, solo "el día X hubo N visitas". Se incrementa desde
 * App\EventSubscriber\VisitaTrackerSubscriber (una fila por día, upsert
 * atómico vía SQL directo para evitar condiciones de carrera con varios
 * visitantes al mismo tiempo). Se lee desde /admin/analiticas.
 */
#[ORM\Entity(repositoryClass: VisitaDiariaRepository::class)]
#[ORM\Table(name: 'visitas_diarias')]
#[ORM\UniqueConstraint(name: 'uniq_visita_fecha', columns: ['fecha'])]
class VisitaDiaria
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fecha;

    #[ORM\Column]
    private int $contador = 0;

    public function __construct(\DateTimeImmutable $fecha, int $contador = 0)
    {
        $this->fecha = $fecha;
        $this->contador = $contador;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFecha(): \DateTimeImmutable
    {
        return $this->fecha;
    }

    public function getContador(): int
    {
        return $this->contador;
    }
}
