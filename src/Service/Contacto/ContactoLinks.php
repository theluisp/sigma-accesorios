<?php

namespace App\Service\Contacto;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Centraliza los links de contacto/redes (Facebook, Rappi, WhatsApp, Maps)
 * a partir de variables de entorno — así se configuran una sola vez en
 * .env.local con los datos reales del negocio y los usan tanto el carrusel
 * de Home como la página de Contacto. Si una variable está vacía (todavía
 * no configurada), el método correspondiente regresa null y la plantilla
 * simplemente no pinta ese botón, en vez de mostrar un link roto.
 */
final class ContactoLinks
{
    public function __construct(
        #[Autowire('%env(FACEBOOK_URL)%')]
        private readonly string $facebookUrl,
        #[Autowire('%env(RAPPI_URL)%')]
        private readonly string $rappiUrl,
        #[Autowire('%env(WHATSAPP_PHONE_NUMBER)%')]
        private readonly string $whatsappPhone,
        #[Autowire('%env(MAPS_QUERY)%')]
        private readonly string $mapsQuery,
    ) {
    }

    public function facebookUrl(): ?string
    {
        return $this->facebookUrl !== '' ? $this->facebookUrl : null;
    }

    public function rappiUrl(): ?string
    {
        return $this->rappiUrl !== '' ? $this->rappiUrl : null;
    }

    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', $this->whatsappPhone) ?? '';

        return $digits !== '' ? 'https://wa.me/'.$digits : null;
    }

    public function whatsappTelefono(): ?string
    {
        return $this->whatsappPhone !== '' ? $this->whatsappPhone : null;
    }

    /**
     * Link de búsqueda en Google Maps (no un pin exacto — no tenemos
     * coordenadas/dirección confirmadas). `$lugar` puede afinar la búsqueda,
     * ej. con el nombre de una sucursal.
     */
    public function mapsUrlPara(?string $lugar = null): string
    {
        $base = $this->mapsQuery !== '' ? $this->mapsQuery : 'Sigma Accesorios';
        $query = $lugar !== null && $lugar !== '' ? $lugar.' '.$base : $base;

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($query);
    }
}
