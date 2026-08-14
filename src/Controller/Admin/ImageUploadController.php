<?php

namespace App\Controller\Admin;

use App\Entity\ProductoImagen;
use App\Repository\ProductoRepository;
use App\Service\Catalog\ProductImageResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Panel interno (protegido por HTTP Basic, ver config/packages/security.yaml)
 * para subir manualmente la foto de cada producto del catálogo. La lista de
 * productos viene de la base de datos (ver App\Service\Catalog\CatalogImportService),
 * no de Google Sheets directamente.
 */
#[Route('/admin/imagenes')]
final class ImageUploadController extends AbstractController
{
    public function __construct(
        private readonly ProductoRepository $productoRepository,
        private readonly ProductImageResolver $imageResolver,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'admin_images_index', methods: ['GET'])]
    public function index(): Response
    {
        $productos = $this->productoRepository->findAllConRelaciones();

        // Los que no tienen imagen primero, para priorizar el trabajo pendiente.
        usort($productos, static fn ($a, $b) => ($a->getImagen() !== null) <=> ($b->getImagen() !== null));

        return $this->render('admin/images/index.html.twig', [
            'productos' => $productos,
        ]);
    }

    #[Route('/subir/{slug}', name: 'admin_images_upload', methods: ['POST'])]
    public function upload(string $slug, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('upload-image-'.$slug, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido, intenta de nuevo.');

            return $this->redirectToRoute('admin_images_index');
        }

        $producto = $this->productoRepository->findOneBySlug($slug);
        if ($producto === null) {
            $this->addFlash('error', 'Producto no encontrado (¿ya corriste app:catalog:sync?).');

            return $this->redirectToRoute('admin_images_index');
        }

        $file = $request->files->get('imagen');
        if (!$file) {
            $this->addFlash('error', 'No se recibió ninguna imagen.');

            return $this->redirectToRoute('admin_images_index');
        }

        try {
            $path = $this->imageResolver->store($slug, $file);
            $mimeType = (string) $file->getMimeType();
            $tamanio = (int) $file->getSize();

            $imagen = $producto->getImagen();
            if ($imagen === null) {
                $imagen = new ProductoImagen($producto, $path, $mimeType, $tamanio);
                $this->em->persist($imagen);
                $producto->setImagen($imagen);
            } else {
                $imagen->actualizar($path, $mimeType, $tamanio);
            }

            $this->em->flush();

            $this->addFlash('success', sprintf('Imagen actualizada para "%s".', $producto->getNombre()));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_images_index');
    }
}
