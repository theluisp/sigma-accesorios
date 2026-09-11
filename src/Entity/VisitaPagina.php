<?php

namespace App\Entity;

use App\Repository\VisitaPaginaRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Contador de vistas por página (ruta) — pedido explícito del usuario, sep
 * 2026: "si se puede medir en donde se quedan los usuarios o que es lo que
 * mas visitan". Mismo criterio de privacidad que VisitaDiaria: NO guarda
 * IP, user-agent ni ningún dato personal, solo "esta ruta se visitó N
 * veces". Se incrementa desde App\EventSubscriber\VisitaTrackerSubscriber.
 *
 * OJO — esto es un contador de VISTAS (pageviews), no de visitantes
 * únicos: a diferencia de VisitaDiaria (que solo suma 1 por navegador por
 * día, sitewide), aquí SÍ se suma en cada carga de página, aunque sea el
 * mismo navegador varias veces el mismo día — si no, con el candado de "1
 * por navegador por día" de VisitaDiaria, solo la PRIMERA página que abre
 * cada visitante contaría (casi siempre Inicio, por ser la más común como
 * entrada), y el resto del sitio se vería visitado artificialmente en
 * cero pase lo que pase. Con este criterio sí se puede comparar de verdad
 * qué páginas se visitan más.
 */
#[ORM\Entity(repositoryClass: VisitaPaginaRepository::class)]
#[ORM\Table(name: 'visitas_paginas')]
#[ORM\UniqueConstraint(name: 'uniq_visita_pagina_ruta', columns: ['ruta'])]
class VisitaPagina
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Ruta sin query string (ej. "/catalogo", "/", "/contacto") — ver getPathInfo(). */
    #[ORM\Column(length: 190)]
    private string $ruta;

    #[ORM\Column]
    private int $contador = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $actualizadoEn;

    public function __construct(string $ruta, int $contador = 0)
    {
        $this->ruta = $ruta;
        $this->contador = $contador;
        $this->actualizadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRuta(): string
    {
        return $this->ruta;
    }

    public function getContador(): int
    {
        return $this->contador;
    }

    public function getActualizadoEn(): \DateTimeImmutable
    {
        return $this->actualizadoEn;
    }
}
