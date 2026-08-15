<?php

namespace App\Twig;

use App\Service\Contacto\ContactoLinks;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Expone los links de contacto (App\Service\Contacto\ContactoLinks) como
 * funciones globales de Twig, para poder usarlos en plantillas compartidas
 * (base.html.twig, la tarjeta de producto) sin tener que pasarlos manualmente
 * desde cada controlador.
 */
final class ContactoExtension extends AbstractExtension
{
    public function __construct(private readonly ContactoLinks $contactoLinks)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('whatsapp_url', $this->contactoLinks->whatsappUrl(...)),
            new TwigFunction('rappi_url', $this->contactoLinks->rappiUrl(...)),
            new TwigFunction('facebook_url', $this->contactoLinks->facebookUrl(...)),
        ];
    }
}
