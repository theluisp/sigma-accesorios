<?php

namespace App\Controller;

use App\Repository\SucursalRepository;
use App\Service\Contacto\ContactoLinks;
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
    public function contacto(SucursalRepository $sucursalRepository, ContactoLinks $contactoLinks): Response
    {
        $sucursales = array_map(
            static fn ($sucursal) => [
                'nombre' => $sucursal->getNombre(),
                'mapsUrl' => $contactoLinks->mapsUrlPara($sucursal->getNombre()),
            ],
            $sucursalRepository->findAll(),
        );

        return $this->render('paginas/contacto.html.twig', [
            'sucursales' => $sucursales,
            'facebookUrl' => $contactoLinks->facebookUrl(),
            'rappiUrl' => $contactoLinks->rappiUrl(),
            'whatsappUrl' => $contactoLinks->whatsappUrl(),
            'whatsappTelefono' => $contactoLinks->whatsappTelefono(),
            'mapsUrl' => $contactoLinks->mapsUrlPara(),
        ]);
    }
}
