<?php

namespace App\Controller;

use App\Repository\SucursalRepository;
use App\Service\Contacto\ContactoLinks;
use App\Service\Seo\NegocioJsonLd;
use App\Service\Seo\SucursalDireccionHorario;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Páginas institucionales (no dependen del catálogo): Nosotros y Contacto.
 */
final class PaginasController extends AbstractController
{
    #[Route('/nosotros', name: 'nosotros', methods: ['GET'])]
    public function nosotros(): Response
    {
        return $this->render('paginas/nosotros.html.twig');
    }

    #[Route('/contacto', name: 'contacto', methods: ['GET'])]
    public function contacto(SucursalRepository $sucursalRepository, ContactoLinks $contactoLinks, NegocioJsonLd $negocioJsonLd, SucursalDireccionHorario $direccionHorario): Response
    {
        // Horarios + CP en la tarjeta de sucursales (pedido explícito del
        // usuario, sep 2026): "agregale esa info de horarios y direccion
        // que se tiene a la card de sucursales, recuerda solo cp y
        // horarios no todo por tema de seguridad" — por eso aquí NO se
        // manda colonia ni ningún dato de calle, aunque SucursalDireccionHorario
        // sí los tenga (esos solo se usan para el JSON-LD, ver NegocioJsonLd).
        // Si una sucursal no tiene datos capturados todavía, codigoPostal
        // queda null y horarios vacío — la plantilla simplemente no
        // muestra esa sección para esa sucursal, igual que ya hace
        // NegocioJsonLd al omitirla del JSON-LD.
        $sucursales = array_map(
            static function ($sucursal) use ($contactoLinks, $direccionHorario) {
                $datos = $direccionHorario->paraSucursal($sucursal->getClave());

                return [
                    'nombre' => $sucursal->getNombre(),
                    'mapsUrl' => $contactoLinks->mapsUrlPara($sucursal->getNombre()),
                    'codigoPostal' => $datos['codigoPostal'] ?? null,
                    'horarios' => $direccionHorario->horariosLegibles($sucursal->getClave()),
                ];
            },
            $sucursalRepository->findAll(),
        );

        return $this->render('paginas/contacto.html.twig', [
            'sucursales' => $sucursales,
            'facebookUrl' => $contactoLinks->facebookUrl(),
            'rappiUrl' => $contactoLinks->rappiUrl(),
            'whatsappUrl' => $contactoLinks->whatsappUrl(),
            'whatsappTelefono' => $contactoLinks->whatsappTelefono(),
            'mapsUrl' => $contactoLinks->mapsUrlPara(),
            'jsonLdNegocio' => $negocioJsonLd->comoJson(),
        ]);
    }

    /**
     * Cross-promoción de los otros negocios de Luis (Pandora Pets, Sigma
     * Tecnologies, Sigma Recargas) y de los de un familiar (ContaConfiable,
     * IntegraCont) — pedido explícito del usuario, sep 2026, con la
     * condición de NO competirle atención al Catálogo de accesorios (por
     * eso es su propia página aparte, no una sección del Home) y de NO
     * decir explícitamente que dos de las tarjetas son de un familiar (así
     * lo pidió el usuario — no confundir con deshonestidad: son negocios
     * reales, solo no se etiqueta el parentesco).
     *
     * Sigma Tecnologies y Sigma Recargas comparten el mismo WhatsApp que
     * Sigma Accesorios (mismo dueño, mismo teléfono) — se reusa
     * ContactoLinks::whatsappUrl(), ya configurado. ContaConfiable e
     * IntegraCont ya tienen su WhatsApp propio confirmado por el usuario
     * (sep 2026) — hardcodeado directo en el template (mismo patrón que el
     * Facebook de Sigma Thecnologies en el footer), no vía ContactoLinks
     * porque no son números de Luis.
     */
    #[Route('/negocios', name: 'negocios', methods: ['GET'])]
    public function negocios(ContactoLinks $contactoLinks): Response
    {
        return $this->render('paginas/negocios.html.twig', [
            'whatsappUrl' => $contactoLinks->whatsappUrl(),
        ]);
    }
}
