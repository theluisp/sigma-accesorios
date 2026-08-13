<?php

namespace App\Controller\Admin;

use App\Service\Catalog\CatalogSyncService;
use App\Service\Catalog\ProductImageResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Panel interno (protegido por HTTP Basic, ver config/packages/security.yaml)
 * para subir manualmente la foto de cada producto del catálogo.
 */
#[Route('/admin/imagenes')]
final class ImageUploadController extends AbstractController
{
    public function __construct(
        private readonly CatalogSyncService $catalogSyncService,
        private readonly ProductImageResolver $imageResolver,
    ) {
    }

    #[Route('', name: 'admin_images_index', methods: ['GET'])]
    public function index(): Response
    {
        $productos = $this->catalogSyncService->getCatalog();

        // Los que no tienen imagen primero, para priorizar el trabajo pendiente.
        uasort($productos, function ($a, $b) {
            return $this->imageResolver->hasImage($a->slug) <=> $this->imageResolver->hasImage($b->slug);
        });

        return $this->render('admin/images/index.html.twig', [
            'productos' => $productos,
            'imageResolver' => $this->imageResolver,
        ]);
    }

    #[Route('/subir/{slug}', name: 'admin_images_upload', methods: ['POST'])]
    public function upload(string $slug, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('upload-image-'.$slug, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido, intenta de nuevo.');

            return $this->redirectToRoute('admin_images_index');
        }

        $file = $request->files->get('imagen');

        if (!$file) {
            $this->addFlash('error', 'No se recibió ninguna imagen.');

            return $this->redirectToRoute('admin_images_index');
        }

        try {
            $this->imageResolver->store($slug, $file);
            $this->addFlash('success', sprintf('Imagen actualizada para "%s".', $slug));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_images_index');
    }
}
